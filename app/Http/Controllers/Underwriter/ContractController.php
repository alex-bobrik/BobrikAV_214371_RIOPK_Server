<?php

namespace App\Http\Controllers\Underwriter;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractMessage;
use App\Models\MessageAttachment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractController extends Controller
{
public function incoming(Request $request)
{
    $query = Contract::where('reinsurer_id', Auth::user()->company_id);

    // Поиск
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('number', 'like', "%{$search}%")
              ->orWhere('terms', 'like', "%{$search}%")
              ->orWhereHas('insurer', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Фильтр по статусу
    if ($request->has('status') && !empty($request->status)) {
        $query->where('status', $request->status);
    }

    // Фильтр по дате создания
    if ($request->has('date_range') && !empty($request->date_range)) {
        $dateFilter = $this->getDateFilter($request->date_range);
        $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
    }

    // Сортировка
    $sort = $request->get('sort', 'newest');
    switch ($sort) {
        case 'oldest':
            $query->orderBy('created_at', 'asc');
            break;
        case 'coverage_asc':
            $query->orderBy('coverage', 'asc');
            break;
        case 'coverage_desc':
            $query->orderBy('coverage', 'desc');
            break;
        case 'number_asc':
            $query->orderBy('number', 'asc');
            break;
        case 'number_desc':
            $query->orderBy('number', 'desc');
            break;
        default: // newest
            $query->latest();
    }

    $contracts = $query->paginate(10)->appends($request->query());

    // Статистика
    $stats = $this->getContractStats('incoming');

    return view('underwriter.contracts.incoming', compact('contracts', 'stats'));
}

public function outgoing(Request $request)
{
    $query = Contract::where('insurer_id', Auth::user()->company_id);

    // Поиск
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('number', 'like', "%{$search}%")
              ->orWhere('terms', 'like', "%{$search}%")
              ->orWhereHas('reinsurer', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Фильтр по статусу
    if ($request->has('status') && !empty($request->status)) {
        $query->where('status', $request->status);
    }

    // Фильтр по дате создания
    if ($request->has('date_range') && !empty($request->date_range)) {
        $dateFilter = $this->getDateFilter($request->date_range);
        $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
    }

    // Сортировка
    $sort = $request->get('sort', 'newest');
    switch ($sort) {
        case 'oldest':
            $query->orderBy('created_at', 'asc');
            break;
        case 'coverage_asc':
            $query->orderBy('coverage', 'asc');
            break;
        case 'coverage_desc':
            $query->orderBy('coverage', 'desc');
            break;
        case 'number_asc':
            $query->orderBy('number', 'asc');
            break;
        case 'number_desc':
            $query->orderBy('number', 'desc');
            break;
        default: // newest
            $query->latest();
    }

    $contracts = $query->paginate(10)->appends($request->query());

    // Статистика
    $stats = $this->getContractStats('outgoing');

    return view('underwriter.contracts.outgoing', compact('contracts', 'stats'));
}

/**
 * Экспорт договоров в CSV
 */
public function export(Request $request)
{
    $type = $request->get('type', 'incoming');
    $companyId = Auth::user()->company_id;

    // Создаем базовый запрос
    $query = Contract::when($type === 'incoming', function($q) use ($companyId) {
        return $q->where('reinsurer_id', $companyId);
    }, function($q) use ($companyId) {
        return $q->where('insurer_id', $companyId);
    });

    // Применяем те же фильтры, что и на странице
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function($q) use ($search, $type) {
            $q->where('number', 'like', "%{$search}%")
              ->orWhere('terms', 'like', "%{$search}%");
            
            if ($type === 'incoming') {
                $q->orWhereHas('insurer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            } else {
                $q->orWhereHas('reinsurer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
        });
    }

    if ($request->has('status') && !empty($request->status)) {
        $query->where('status', $request->status);
    }

    if ($request->has('date_range') && !empty($request->date_range)) {
        $dateFilter = $this->getDateFilter($request->date_range);
        $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
    }

    // Получаем все договоры без пагинации
    $contracts = $query->with(['insurer', 'reinsurer'])->get();

    // Выбранные колонки для экспорта
    $selectedColumns = $request->get('columns', ['id', 'number', 'insurer', 'coverage', 'status', 'created_at']);
    $format = $request->get('format', 'csv');
    $encoding = $request->get('encoding', 'UTF-8');

    // Определяем разделитель в зависимости от формата
    $delimiter = $format === 'csv_excel' ? ',' : ';';
    
    // Создаем CSV
    $fileName = $type . '_contracts_' . date('Y-m-d_H-i-s') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv; charset=' . $encoding,
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    ];

    // Функция для преобразования массива в CSV строку
    $callback = function() use ($contracts, $selectedColumns, $delimiter, $type) {
        $file = fopen('php://output', 'w');
        
        // Добавляем BOM для правильного отображения кириллицы в Excel
        fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Заголовки столбцов
        $headers = [];
        foreach ($selectedColumns as $column) {
            switch ($column) {
                case 'id':
                    $headers[] = 'ID договора';
                    break;
                case 'number':
                    $headers[] = 'Номер договора';
                    break;
                case 'insurer':
                    $headers[] = $type === 'incoming' ? 'Страхователь' : 'Перестраховщик';
                    break;
                case 'coverage':
                    $headers[] = 'Сумма покрытия (BYN)';
                    break;
                case 'status':
                    $headers[] = 'Статус';
                    break;
                case 'created_at':
                    $headers[] = 'Дата создания';
                    break;
                case 'terms':
                    $headers[] = 'Условия договора';
                    break;
            }
        }
        fputcsv($file, $headers, $delimiter);
        
        // Данные
        foreach ($contracts as $contract) {
            $row = [];
            foreach ($selectedColumns as $column) {
                switch ($column) {
                    case 'id':
                        $row[] = $contract->id;
                        break;
                    case 'number':
                        $row[] = $contract->number ?? 'Без номера';
                        break;
                    case 'insurer':
                        if ($type === 'incoming') {
                            $row[] = $contract->insurer->name ?? 'Не указан';
                        } else {
                            $row[] = $contract->reinsurer->name ?? 'Не указан';
                        }
                        break;
                    case 'coverage':
                        $row[] = number_format($contract->coverage, 0, '', ' ');
                        break;
                    case 'status':
                        $statusLabels = [
                            'new' => 'Новый',
                            'pending' => 'На рассмотрении',
                            'active' => 'Активен',
                            'need_details' => 'Нужны детали',
                            'denied' => 'Отклонен',
                            'canceled' => 'Отменен'
                        ];
                        $row[] = $statusLabels[$contract->status] ?? $contract->status;
                        break;
                    case 'created_at':
                        $row[] = $contract->created_at ? $contract->created_at->format('d.m.Y H:i') : '';
                        break;
                    case 'terms':
                        $row[] = $contract->terms ?? '';
                        break;
                    case 'type':
                        $row[] = $contract->type ?? '';
                        break;
                }
            }
            fputcsv($file, $row, $delimiter);
        }
        
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}


private function getContractStats(string $type): array
{
    $userId = Auth::user()->company_id;
    
    $query = Contract::when($type === 'incoming', function($q) use ($userId) {
        return $q->where('reinsurer_id', $userId);
    }, function($q) use ($userId) {
        return $q->where('insurer_id', $userId);
    });

    $stats = [
        'total' => $query->count(),
        'new' => $query->clone()->where('status', 'new')->count(),
        'active' => $query->clone()->where('status', 'active')->count(),
        'pending' => $query->clone()->where('status', 'pending')->count(),
        'draft' => $query->clone()->where('status', 'draft')->count(),
        'need_details' => $query->clone()->where('status', 'need_details')->count(),
        'denied' => $query->clone()->where('status', 'denied')->count(),
        'canceled' => $query->clone()->where('status', 'canceled')->count(),
        'total_coverage' => $query->clone()->where('status', 'active')->sum('coverage'),
    ];

    return $stats;
}

/**
 * Получить диапазон дат для фильтра
 */
private function getDateFilter(string $range): array
{
    $now = Carbon::now();
    
    switch ($range) {
        case 'today':
            return [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ];
        case 'week':
            return [
                'start' => $now->copy()->subWeek()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ];
        case 'month':
            return [
                'start' => $now->copy()->subMonth()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ];
        case 'quarter':
            return [
                'start' => $now->copy()->subQuarter()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ];
        default:
            return [
                'start' => Carbon::create(2000, 1, 1), // Начальная дата
                'end' => $now->copy()->endOfDay(),
            ];
    }
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
            'number' => $request->get('number') ?? 'CNT-' . Str::uuid()->toString(),
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
