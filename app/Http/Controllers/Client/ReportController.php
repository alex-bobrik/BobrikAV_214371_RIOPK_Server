<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Claim;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display reports dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Статистика по договорам
        $contractStats = [
            'total' => Contract::where('insurer_id', $companyId)->count(),
            'active' => Contract::where('insurer_id', $companyId)
                ->where('status', 'active')
                ->count(),
            'pending' => Contract::where('insurer_id', $companyId)
                ->where('status', 'pending')
                ->count(),
        ];

        // Статистика по убыткам
        $claimStats = [
            'total' => Claim::whereHas('contract', fn($q) => $q->where('insurer_id', $companyId))
                ->count(),
            'pending' => Claim::whereHas('contract', fn($q) => $q->where('insurer_id', $companyId))
                ->where('status', 'pending')
                ->count(),
            'paid' => Claim::whereHas('contract', fn($q) => $q->where('insurer_id', $companyId))
                ->where('status', 'paid')
                ->count(),
        ];

        // График договоров по месяцам
        $contractsByMonth = Contract::where('insurer_id', $companyId)
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::create($item->year, $item->month, 1)->format('M Y'),
                    'count' => $item->count,
                ];
            });

        return view('client.reports.index', compact(
            'contractStats',
            'claimStats',
            'contractsByMonth'
        ));
    }
}