<?php

namespace App\Http\Controllers\Underwriter;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::where('reinsurer_id', Auth::user()->company_id)->latest()->paginate(10);
        return view('underwriter.contracts.index', compact('contracts'));
    }

    public function show(Contract $contract)
    {
        return view('underwriter.contracts.show', compact('contract'));
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
