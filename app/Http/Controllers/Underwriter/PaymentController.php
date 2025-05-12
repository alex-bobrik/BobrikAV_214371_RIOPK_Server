<?php

namespace App\Http\Controllers\Underwriter;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::whereHas('contract', function ($query) {
            $query->where('reinsurer_id', Auth::user()->company_id);
        })->latest()->paginate(10);

        return view('underwriter.payments.index', compact('payments'));
    }


    public function show(Payment $payment)
    {
        return view('underwriter.payments.show', compact('payment'));
    }

    public function approve(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Этот платёж уже обработан.');
        }

        $payment->update([
            'status' => 'paid',
            'payment_date' => now(),
        ]);

        return redirect()->route('underwriter.payments.index')->with('success', 'Платёж одобрен.');
    }

    public function reject(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Этот платёж уже обработан.');
        }

        $payment->update(['status' => 'failed']);

        return redirect()->route('underwriter.payments.index')->with('success', 'Платёж отклонён.');
    }
}
