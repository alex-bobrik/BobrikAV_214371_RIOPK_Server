<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Claim;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the client dashboard with statistics
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // 1. Активные договоры
        $activeContractsCount = Contract::where('insurer_id', $companyId)
            ->where('status', 'active')
            ->count();

        // 2. Изменение количества договоров за месяц
        $previousMonthCount = Contract::where('insurer_id', $companyId)
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth()
            ])
            ->count();

        $currentMonthCount = Contract::where('insurer_id', $companyId)
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->count();

        $contractsChangePercent = $previousMonthCount > 0 
            ? round(($currentMonthCount - $previousMonthCount) / $previousMonthCount * 100)
            : ($currentMonthCount > 0 ? 100 : 0);

        // 3. Сумма покрытия за последний квартал
        $totalCoverage = Contract::where('insurer_id', $companyId)
            ->where('status', 'active')
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->sum('coverage');

        // 4. Ожидающие убытки
        $pendingClaimsCount = Claim::whereHas('contract', function($query) use ($companyId) {
                $query->where('insurer_id', $companyId);
            })
            ->where('status', 'pending')
            ->count();

        // 5. Последние платежи
        $recentPayments = Payment::whereHas('contract', function($query) use ($companyId) {
                $query->where('insurer_id', $companyId);
            })
            ->with('contract.reinsurer')
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get();

        // 6. Последние договоры
        $recentContracts = Contract::with('reinsurer')
            ->where('insurer_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 7. Статистика по типам договоров
        $contractsByType = Contract::where('insurer_id', $companyId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return view('client.dashboard', [
            'activeContractsCount' => $activeContractsCount,
            'contractsChangePercent' => $contractsChangePercent,
            'totalCoverage' => $totalCoverage,
            'pendingClaimsCount' => $pendingClaimsCount,
            'recentPayments' => $recentPayments,
            'recentContracts' => $recentContracts,
            'contractsByType' => $contractsByType,
            'company' => $user->company
        ]);
    }
}