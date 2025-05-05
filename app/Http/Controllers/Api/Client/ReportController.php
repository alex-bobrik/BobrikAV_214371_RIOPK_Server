<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Claim;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function contracts(Request $request)
    {
        $query = Contract::where('insurer_id', Auth::user()->company_id);

        if ($request->has('period')) {
            switch ($request->period) {
                case 'month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
                case 'quarter':
                    $query->where('created_at', '>=', now()->subMonths(3));
                    break;
                case 'year':
                    $query->where('created_at', '>=', now()->subYear());
                    break;
            }
        }

        return response()->json([
            'by_type' => $query->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_status' => $query->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_month' => $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, count(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(fn($item) => [
                    'month' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                    'count' => $item->count,
                ]),
        ]);
    }

    public function claims(Request $request)
    {
        $query = Claim::whereHas('contract', function($q) {
            $q->where('insurer_id', Auth::user()->company_id);
        });

        if ($request->has('period')) {
            switch ($request->period) {
                case 'month':
                    $query->where('filed_at', '>=', now()->subMonth());
                    break;
                case 'quarter':
                    $query->where('filed_at', '>=', now()->subMonths(3));
                    break;
                case 'year':
                    $query->where('filed_at', '>=', now()->subYear());
                    break;
            }
        }

        return response()->json([
            'by_status' => $query->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_month' => $query->selectRaw('YEAR(filed_at) as year, MONTH(filed_at) as month, count(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(fn($item) => [
                    'month' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                    'count' => $item->count,
                ]),
            'total_amount' => $query->sum('amount'),
        ]);
    }
}