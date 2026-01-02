<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $allContracts = Contract::where('insurer_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $claims = Claim::whereHas('contract', function($query) use ($companyId) {
                $query->where('insurer_id', $companyId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $payments = Payment::where(function($query) use ($companyId) {
                $query->whereHas('contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                })
                ->orWhereHas('claim.contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                });
            })
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $stats = [
            'total_contracts_count' => $allContracts->count(),
            'active_contracts_count' => $allContracts->where('status', 'active')->count(),
            'total_contracts_coverage' => $allContracts->sum('coverage'),
            'total_contracts_premium' => $allContracts->sum('premium'),
            
            'claims_count' => $claims->count(),
            'claims_total_amount' => $claims->sum('amount'),
            
            'payments_count' => $payments->count(),
            'payments_total_amount' => $payments->sum('amount'),
            'payments_in' => $payments->where('type', 'incoming')->sum('amount'),
            'payments_out' => $payments->where('type', 'outgoing')->sum('amount'),
            
            'contracts_active' => $allContracts->where('status', 'active')->count(),
            'contracts_draft' => $allContracts->where('status', 'draft')->count(),
            'contracts_need_details' => $allContracts->where('status', 'need_details')->count(),
            'contracts_denied' => $allContracts->where('status', 'denied')->count(),
            
            'avg_claim_amount' => $claims->count() > 0 ? $claims->avg('amount') : 0,
            'payment_success_rate' => $payments->count() > 0 
                ? ($payments->where('status', 'paid')->count() / $payments->count() * 100) 
                : 0,
        ];
        
        $charts = [
            'contracts_by_status' => [
                'labels' => ['Активные', 'Новые', 'Нужны детали', 'Отклонены'],
                'data' => [
                    $stats['contracts_active'],
                    $stats['contracts_draft'],
                    $stats['contracts_need_details'],
                    $stats['contracts_denied']
                ],
                'colors' => ['#28a745', '#6c757d', '#ffc107', '#dc3545']
            ],
            
            'contracts_monthly' => $this->getMonthlyData($allContracts, $start, $end),
            
            'payments_by_type' => [
                'labels' => ['Входящие', 'Исходящие'],
                'data' => [
                    $payments->where('type', 'incoming')->count(),
                    $payments->where('type', 'outgoing')->count()
                ],
                'colors' => ['#17a2b8', '#fd7e14']
            ],
            
            'payments_monthly' => $this->getMonthlyPaymentData($payments, $start, $end),
        ];
        
        $recentContracts = Contract::where('insurer_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->with('insurer')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        $recentClaims = Claim::whereHas('contract', function($query) use ($companyId) {
                $query->where('insurer_id', $companyId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->with('contract.insurer')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        $recentPayments = Payment::where(function($query) use ($companyId) {
                $query->whereHas('contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                })
                ->orWhereHas('claim.contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                });
            })
            ->whereBetween('created_at', [$start, $end])
            ->with(['contract', 'claim'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        return view('owner.reports.index', compact(
            'stats',
            'charts',
            'recentContracts',
            'recentClaims',
            'recentPayments',
            'startDate',
            'endDate'
        ));
    }
    
    public function exportPdf(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        // 1. Все договоры компании (где компания является перестраховщиком)
        $allContracts = Contract::where('insurer_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $claims = Claim::whereHas('contract', function($query) use ($companyId) {
                $query->where('insurer_id', $companyId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $payments = Payment::where(function($query) use ($companyId) {
                $query->whereHas('contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                })
                ->orWhereHas('claim.contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                });
            })
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $stats = [
            'total_contracts_count' => $allContracts->count(),
            'active_contracts_count' => $allContracts->where('status', 'active')->count(),
            'total_contracts_coverage' => $allContracts->sum('coverage'),
            'total_contracts_premium' => $allContracts->sum('premium'),
            
            'claims_count' => $claims->count(),
            'claims_total_amount' => $claims->sum('amount'),
            
            'payments_count' => $payments->count(),
            'payments_total_amount' => $payments->sum('amount'),
            'payments_in' => $payments->where('type', 'incoming')->sum('amount'),
            'payments_out' => $payments->where('type', 'outgoing')->sum('amount'),
            
            'contracts_active' => $allContracts->where('status', 'active')->count(),
            'contracts_draft' => $allContracts->where('status', 'draft')->count(),
            'contracts_need_details' => $allContracts->where('status', 'need_details')->count(),
            'contracts_denied' => $allContracts->where('status', 'denied')->count(),
            
            'avg_claim_amount' => $claims->count() > 0 ? $claims->avg('amount') : 0,
            'payment_success_rate' => $payments->count() > 0 
                ? ($payments->where('status', 'paid')->count() / $payments->count() * 100) 
                : 0,
        ];
        
        $recentContracts = Contract::where('insurer_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->with('insurer')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        $recentClaims = Claim::whereHas('contract', function($query) use ($companyId) {
                $query->where('insurer_id', $companyId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->with('contract.insurer')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        $recentPayments = Payment::where(function($query) use ($companyId) {
                $query->whereHas('contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                })
                ->orWhereHas('claim.contract', function($q) use ($companyId) {
                    $q->where('insurer_id', $companyId);
                });
            })
            ->whereBetween('created_at', [$start, $end])
            ->with(['contract', 'claim'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        $data = [
            'stats' => $stats,
            'recentContracts' => $recentContracts,
            'recentClaims' => $recentClaims,
            'recentPayments' => $recentPayments,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'companyName' => Auth::user()->company->name ?? 'Компания',
            'generatedDate' => Carbon::now()->format('d.m.Y H:i'),
            'user' => Auth::user(),
        ];
        
        $data['totalContracts'] = $stats['total_contracts_count'];
        $data['activeContracts'] = $stats['contracts_active'];
        $data['draftContracts'] = $stats['contracts_draft'];
        $data['needDetailsContracts'] = $stats['contracts_need_details'];
        $data['deniedContracts'] = $stats['contracts_denied'];
        $data['totalCoverage'] = $stats['total_contracts_coverage'];
        
        $pdf = PDF::loadView('owner.reports.pdf', $data);
        
        $filename = 'report_' . $startDate . '_to_' . $endDate . '.pdf';
        
        return $pdf->download($filename);
    }
    
    private function getMonthlyData($contracts, $start, $end)
    {
        $months = [];
        $current = $start->copy();
        
        while ($current <= $end) {
            $months[$current->format('Y-m')] = 0;
            $current->addMonth();
        }
        
        foreach ($contracts as $contract) {
            $month = $contract->created_at->format('Y-m');
            if (isset($months[$month])) {
                $months[$month]++;
            }
        }
        
        return [
            'labels' => array_keys($months),
            'data' => array_values($months)
        ];
    }
    
    private function getMonthlyPaymentData($payments, $start, $end)
    {
        $months = [];
        $current = $start->copy();
        
        while ($current <= $end) {
            $months[$current->format('Y-m')] = 0;
            $current->addMonth();
        }
        
        foreach ($payments as $payment) {
            $month = $payment->created_at->format('Y-m');
            if (isset($months[$month])) {
                $months[$month] += $payment->amount;
            }
        }
        
        return [
            'labels' => array_keys($months),
            'data' => array_values($months)
        ];
    }

}
