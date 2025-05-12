<?php

namespace App\Http\Controllers\Underwriter;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $contractCount = Contract::where('reinsurer_id', $user->company_id)->count();

        $paymentCount = Payment::whereHas('contract', function ($query) use ($user) {
            $query->where('reinsurer_id', $user->company_id);
        })->count();

        $contractStatuses = Contract::where('reinsurer_id', $user->company_id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $contractStatuses = array_merge([
            'active' => 0,
            'pending' => 0,
            'canceled' => 0,
        ], $contractStatuses);

        $contractStatuses = [
            'labels' => ['Активен', 'На рассмотрении', 'Отменен'],
            'data' => [
                $contractStatuses['active'],
                $contractStatuses['pending'],
                $contractStatuses['canceled'],
            ]
        ];

        $paymentStatuses = Payment::whereHas('contract', function ($query) use ($user) {
            $query->where('reinsurer_id', $user->company_id);
        })
        ->selectRaw('status, count(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();

        $paymentStatuses = array_merge([
            'pending' => 0,
            'paid' => 0,
            'failed' => 0,
        ], $paymentStatuses);

        $paymentStatuses = [
            'labels' => ['Ожидает', 'Завершено', 'Отказано'],
            'data' => [
                $paymentStatuses['pending'],
                $paymentStatuses['paid'],
                $paymentStatuses['failed'],
            ]
        ];

        return view('underwriter.dashboard', compact('contractCount', 'paymentCount', 'contractStatuses', 'paymentStatuses'));
    }
}
