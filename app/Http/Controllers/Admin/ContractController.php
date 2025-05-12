<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with(['insurer', 'reinsurer'])->get();
        return view('admin.contracts.index', compact('contracts'));
    }

    public function show($id)
    {
        $contract = Contract::with(['reinsurer'])->findOrFail($id);

        return view('admin.contracts.show', compact('contract'));
    }
}
