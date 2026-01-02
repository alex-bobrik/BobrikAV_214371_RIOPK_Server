<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimController extends Controller
{
public function index(Request $request)
{
    $query = Claim::whereHas('contract', function($q) {
        $q->where('insurer_id', Auth::user()->company_id);
    });

    // Поиск
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereHas('contract', function($q) use ($search) {
                  $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('reinsurer', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
              });
        });
    }

    // Фильтр по статусу
    if ($request->has('status') && !empty($request->status)) {
        $query->where('status', $request->status);
    }

    // Фильтр по дате
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
        case 'amount_asc':
            $query->orderBy('amount', 'asc');
            break;
        case 'amount_desc':
            $query->orderBy('amount', 'desc');
            break;
        default: // newest
            $query->latest();
    }

    $claims = $query->with(['contract.reinsurer'])->paginate(10)->appends($request->query());

    // Статистика
    $stats = $this->getClaimsStats();

    return view('specialist.claims.index', compact('claims', 'stats'));
}

    public function details(Claim $claim)
    {
        // Проверяем доступ пользователя к убытку
        if ($claim->contract->insurer_id !== Auth::user()->company_id) {
            abort(403, 'Доступ запрещен');
        }
        
        $payments = $claim->payments()->with('contract')->get();
        
        return view('specialist.claims.partials.details', compact('claim', 'payments'));
    }

    public function export(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Создаем базовый запрос
        $query = Claim::whereHas('contract', function($q) use ($companyId) {
            $q->where('insurer_id', $companyId);
        })->with(['contract.reinsurer']);

        // Применяем те же фильтры, что и на странице
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('contract', function($q) use ($search) {
                      $q->where('number', 'like', "%{$search}%")
                        ->orWhereHas('reinsurer', function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                  });
            });
        }

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
            case 'amount_asc':
                $query->orderBy('amount', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('amount', 'desc');
                break;
            default: // newest
                $query->latest();
        }

        // Получаем все убытки без пагинации
        $claims = $query->get();

        // Выбранные колонки для экспорта
        $selectedColumns = $request->get('columns', ['id', 'contract_id', 'reinsurer', 'amount', 'created_at']);
        $format = $request->get('format', 'csv');
        $encoding = $request->get('encoding', 'UTF-8');

        // Определяем разделитель в зависимости от формата
        $delimiter = $format === 'csv_excel' ? ',' : ';';
        
        // Создаем CSV
        $fileName = 'claims_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=' . $encoding,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        // Функция для преобразования массива в CSV строку
        $callback = function() use ($claims, $selectedColumns, $delimiter) {
            $file = fopen('php://output', 'w');
            
            // Добавляем BOM для правильного отображения кириллицы в Excel
            fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Заголовки столбцов
            $headers = [];
            foreach ($selectedColumns as $column) {
                switch ($column) {
                    case 'id':
                        $headers[] = 'ID убытка';
                        break;
                    case 'contract_id':
                        $headers[] = 'ID договора';
                        break;
                    case 'contract_number':
                        $headers[] = 'Номер договора';
                        break;
                    case 'reinsurer':
                        $headers[] = 'Перестраховщик';
                        break;
                    case 'amount':
                        $headers[] = 'Сумма убытка (BYN)';
                        break;
                    case 'description':
                        $headers[] = 'Описание убытка';
                        break;
                    case 'created_at':
                        $headers[] = 'Дата создания';
                        break;
                    case 'status':
                        $headers[] = 'Статус';
                        break;
                }
            }
            fputcsv($file, $headers, $delimiter);
            
            // Данные
            foreach ($claims as $claim) {
                $row = [];
                foreach ($selectedColumns as $column) {
                    switch ($column) {
                        case 'id':
                            $row[] = $claim->id;
                            break;
                        case 'contract_id':
                            $row[] = $claim->contract_id;
                            break;
                        case 'contract_number':
                            $row[] = $claim->contract->number ?? 'Без номера';
                            break;
                        case 'reinsurer':
                            $row[] = $claim->contract->reinsurer->name ?? 'Не указан';
                            break;
                        case 'amount':
                            $row[] = number_format($claim->amount, 0, '', ' ');
                            break;
                        case 'description':
                            $row[] = $claim->description ?? '';
                            break;
                        case 'created_at':
                            $row[] = $claim->created_at ? $claim->created_at->format('d.m.Y H:i') : '';
                            break;
                        case 'status':
                            $statusLabels = [
                                'pending' => 'На рассмотрении',
                                'approved' => 'Одобрен',
                                'rejected' => 'Отклонен'
                            ];
                            $row[] = $statusLabels[$claim->status] ?? $claim->status;
                            break;
                    }
                }
                fputcsv($file, $row, $delimiter);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

private function getClaimsStats()
{
    $companyId = Auth::user()->company_id;
    
    $query = Claim::whereHas('contract', function($q) use ($companyId) {
        $q->where('insurer_id', $companyId);
    });

    return [
        'total' => $query->count(),
        'pending' => $query->clone()->where('status', 'pending')->count(),
        'approved' => $query->clone()->where('status', 'approved')->count(),
        'rejected' => $query->clone()->where('status', 'rejected')->count(),
        'total_amount' => $query->clone()->sum('amount'),
    ];
}

    public function create()
    {
        $contracts = Contract::where('insurer_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->get();

        return view('specialist.claims.create', compact('contracts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => [
                'required',
                'exists:contracts,id',
                function ($attribute, $value, $fail) {
                    $contract = Contract::find($value);
                    if ($contract && $contract->insurer_id !== Auth::user()->company_id) {
                        $fail('Вы можете подавать убытки только по своим договорам.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:2000',
        ]);

        $claim = new Claim($validated);
        $claim->filed_at = now();
        $claim->status = 'pending';
        $claim->save();

        return redirect()->route('specialist.claims.index')
            ->with('success', 'Убыток успешно зарегистрирован');
    }

    public function show($id)
    {
        $claim = Claim::with(['contract.insurer', 'contract.reinsurer'])->findOrFail($id);
        return view('specialist.claims.show', compact('claim'));
    }

    public function edit(Claim $claim)
    {
        $this->authorize('update', $claim);

        if ($claim->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно редактировать только убытки со статусом "На рассмотрении"');
        }

        $contracts = Contract::where('insurer_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->get();

        return view('specialist.claims.edit', compact('claim', 'contracts'));
    }

    public function update(Request $request, Claim $claim)
    {
        $this->authorize('update', $claim);

        if ($claim->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно редактировать только убытки со статусом "На рассмотрении"');
        }

        $validated = $request->validate([
            'contract_id' => [
                'required',
                'exists:contracts,id',
                function ($attribute, $value, $fail) {
                    $contract = Contract::find($value);
                    if ($contract && $contract->insurer_id !== Auth::user()->company_id) {
                        $fail('Вы можете подавать убытки только по своим договорам.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:2000',
        ]);

        $claim->update($validated);

        return redirect()->route('specialist.claims.index')
            ->with('success', 'Убыток успешно обновлен');
    }

    public function destroy(Claim $claim)
    {
        $this->authorize('delete', $claim);

        if ($claim->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно удалять только убытки со статусом "На рассмотрении"');
        }

        $claim->delete();

        return redirect()->route('specialist.claims.index')
            ->with('success', 'Убыток успешно удален');
    }
}