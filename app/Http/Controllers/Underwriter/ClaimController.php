<?php

namespace App\Http\Controllers\Underwriter;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ClaimController extends Controller
{
    public function index()
    {
        $claims = Claim::whereHas('contract', function ($query) {
            $query->where('reinsurer_id', Auth::user()->company_id)
                  ->where('status', 'active');
        })->latest()->paginate(10);

        return view('underwriter.claims.index', compact('claims'));
    }

    public function show(Claim $claim)
    {
        return view('underwriter.claims.show', compact('claim'));
    }

    public function approve(Claim $claim)
    {
        if ($claim->status !== 'pending') {
            return back()->with('error', 'Этот убыток уже обработан.');
        }

        Payment::create([
            'contract_id' => $claim->contract_id,
            'amount' => $claim->amount,
            'type' => 'claim',
            'status' => 'pending',
            'payment_date' => Carbon::now(),
        ]);

        $claim->update(['status' => 'approved']);


        return redirect()->route('underwriter.claims.index')->with('success', 'Убыток принят, платёж создан.');
    }


    public function reject(Claim $claim)
    {
        if ($claim->status !== 'pending') {
            return back()->with('error', 'Этот убыток уже обработан.');
        }

        $claim->update(['status' => 'rejected']);
        return redirect()->route('underwriter.claims.index')->with('success', 'Убыток отклонен.');
    }
}
