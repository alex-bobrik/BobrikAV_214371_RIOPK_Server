<?php

namespace App\Http\Controllers\Underwriter;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractMessage;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    public function incoming()
    {
        $contracts = Contract::where(
            'reinsurer_id', 
            Auth::user()->company_id
            )
            ->latest()->paginate(10);

        return view('underwriter.contracts.incoming', compact('contracts'));
    }

    public function outgoing()
    {
        $contracts = Contract::where(
            'insurer_id',
            Auth::user()->company_id
            )->latest()->paginate(10);

        return view('underwriter.contracts.outgoing', compact('contracts'));
    }

    public function showCreateContract()
    {
        $currentCompanyId = Auth::user()->company_id;
        
        // Получаем все компании КРОМЕ текущей
        $companies = Company::where('id', '!=', $currentCompanyId)
            ->orderBy('name')
            ->get();
        return view('underwriter.contracts.create', compact('companies'));

    }

    public function storeContract(Request $request)
    {
        $contract = Contract::create([
            'number' => $request->get('number') ?? "GENERATE IT",
            'insurer_id' => Auth::user()->company_id,
            'reinsurer_id' => $request->get('reinsurer_company_id'),
            'coverage' => $request->get('coverage'),
            'terms' => $request->get('terms'),
            'status' => 'draft',
        ]);

        if (!empty($request->get('message'))) {
            $contractMessage = ContractMessage::create([
                'contract_id' => $contract->id,
                'created_by' => Auth::user()->id,
                'message' => $request->get('message'),
            ]);
            
            if ($request->hasFile('message_files')) {
                foreach ($request->file('message_files') as $file) {
                    $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
                    
                    $path = $file->storeAs('attachments', $filename, 'public');
                    
                    MessageAttachment::create([
                        'contract_message_id' => $contractMessage->id,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                    ]);
                }
            }
        }

        $contracts = Contract::where(
            'insurer_id',
            Auth::user()->company_id
            )->latest()->paginate(10);

        return redirect()->route('underwriter.contracts.outgoing', compact('contracts'));
    }


    public function addMessage(Request $request, Contract $contract)
    {
        // Проверяем авторизацию
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Требуется авторизация'], 401);
        }
        
        // Валидация
        $request->validate([
            'message' => 'required|string|max:2000',
            'message_files.*' => 'nullable|file|mimes:pdf,csv|max:10240',
        ]);
        
        try {
            // Создаем сообщение
            $contractMessage = ContractMessage::create([
                'contract_id' => $contract->id,
                'message' => $request->input('message'),
                'created_by' => Auth::user()->id,
            ]);
            
            $attachmentsData = [];
            
            // Обрабатываем файлы
            if ($request->hasFile('message_files')) {
                foreach ($request->file('message_files') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    
                    // Генерируем уникальное имя
                    $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) 
                            . '_' . Str::random(10) . '.' . $extension;
                    
                    // Сохраняем файл
                    $path = $file->storeAs(
                        'contracts/' . $contract->id . '/messages/' . $contractMessage->id,
                        $filename,
                        'public'
                    );
                    
                    // Создаем запись в БД
                    $attachment = MessageAttachment::create([
                        'contract_message_id' => $contractMessage->id,
                        'name' => $originalName,
                        'path' => $path,
                    ]);
                    
                    // Данные для ответа
                    $attachmentsData[] = [
                        'name' => $originalName,
                        'url' => Storage::url($path),
                        'size' => $this->formatFileSize($file->getSize()),
                        'type' => $extension,
                    ];
                }
            }
            
            // Данные для ответа
            $messageData = [
                'id' => $contractMessage->id,
                'message' => $contractMessage->message,
                'author_name' => Auth::user()->name,
                'created_at' => $contractMessage->created_at->format('d.m.Y H:i'),
                'attachments' => $attachmentsData,
            ];
            
            return response()->json([
                'success' => true,
                'message' => $messageData,
            ]);
            
        } catch (\Exception $e) {        
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении сообщения',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function updateStatus(Request $request, Contract $contract)
    {                
                
        try {
            // Обновляем статус договора
            $contract->update([
                'status' => $request->get('status'),
                'updated_at' => now(),
            ]);
            
            $statusMessages = [
                'active' => 'Договор подтвержден и активирован.',
                'denied' => 'Договор отклонен.',
                'need_details' => 'Запрошены дополнительные детали по договору.',
            ];
            
            if (isset($statusMessages[$request->get('status')])) {
                ContractMessage::create([
                    'contract_id' => $contract->id,
                    'message' => 'Статус договора изменен: ' . $statusMessages[$request->get('status')],
                    'created_by' => Auth::id(),
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
                        
            $successMessages = [
                'active' => 'Договор успешно подтвержден и активирован.',
                'denied' => 'Договор отклонен.',
                'need_details' => 'Запрос на дополнительные детали отправлен.',
                'pending' => 'Договор возвращен на рассмотрение.',
            ];
            
            return back()->with('success', $successMessages[$request->get('status')] ?? 'Статус договора обновлен.');
            
        } catch (\Exception $e) {
        
            return back()->with('error', 'Ошибка при изменении статуса: ' . $e->getMessage());
        }
    }


    private function formatFileSize($bytes)
    {
        if ($bytes == 0) {
            return "0 Bytes";
        }
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Скачивание вложения
     */
    public function downloadAttachment(MessageAttachment $attachment)
    {
        // Проверяем права доступа
        if (!Auth::check()) {
            abort(401);
        }
        
        // Проверяем, что файл существует
        if (!Storage::disk('public')->exists($attachment->path)) {
            abort(404, 'Файл не найден');
        } 
        
        // Получаем полный путь к файлу
        $filePath = Storage::disk('public')->path($attachment->path);
        
        // Возвращаем файл для скачивания
        return response()->download($filePath, $attachment->name);
    }

    public static function formatSize($bytes)
    {
        if ($bytes == 0) {
            return "0 Bytes";
        }
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    public function index()
    {
        $contracts = Contract::where('reinsurer_id', Auth::user()->company_id)->latest()->paginate(10);
        return view('underwriter.contracts.index', compact('contracts'));
    }

    public function show(Contract $contract)
    {
        $contract->load(['insurer', 'reinsurer']);
        
        // Загружаем сообщения отдельно с алиасом
        $contract_messages = $contract->contractMessages()
            ->with(['createdBy', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        return view('underwriter.contracts.show', compact('contract', 'contract_messages'));
    }

    public function approve(Contract $contract)
    {
        if ($contract->status !== 'pending') {
            return redirect()->back()->with('error', 'Этот договор уже обработан.');
        }

        $contract->update(['status' => 'active']);
        return redirect()->route('underwriter.contracts.index')->with('success', 'Договор принят.');
    }

    public function reject(Contract $contract)
    {
        if ($contract->status !== 'pending') {
            return redirect()->back()->with('error', 'Этот договор уже обработан.');
        }

        $contract->update(['status' => 'canceled']);
        return redirect()->route('underwriter.contracts.index')->with('success', 'Договор отклонен.');
    }


}
