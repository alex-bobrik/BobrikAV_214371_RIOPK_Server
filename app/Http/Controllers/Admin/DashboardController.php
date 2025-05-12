<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userCount = User::with('company')
            ->where('role', '!=', value: 'admin')
            ->where('role', '!=', value: 'underwriter')
            ->count();   

        $companyCount = \App\Models\Company::count();
        $contractCount = Contract::count();

        $statusTranslations = [
            'pending' => 'В ожидании',
            'active' => 'Активный',
            'canceled' => 'Отменён',
        ];

        $contractStatusRaw = Contract::selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

        $contractStatusData = [
            'labels' => $contractStatusRaw->keys()->map(function ($status) use ($statusTranslations) {
                return $statusTranslations[$status] ?? $status;
            })->values()->all(),
            'data' => $contractStatusRaw->values()->all()
        ];
        
        $claims = Claim::selectRaw('contract_id, sum(amount) as total_claims')
            ->groupBy('contract_id')
            ->get();

        $claimsData = [
            'labels' => $claims->pluck('contract_id')->toArray(),
            'data' => $claims->pluck('total_claims')->toArray(),
        ];

        $payments = Payment::selectRaw('contract_id, sum(amount) as total_payments')
            ->groupBy('contract_id')
            ->get();

        $paymentsData = [
            'labels' => $payments->pluck('contract_id')->toArray(),
            'data' => $payments->pluck('total_payments')->toArray(),
        ];

        return view('admin.dashboard', compact(
            'userCount', 'companyCount', 'contractCount', 
            'contractStatusData', 'claimsData', 'paymentsData'
        ));
    }
}
