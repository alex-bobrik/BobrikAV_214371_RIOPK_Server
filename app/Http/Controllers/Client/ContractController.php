<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with('reinsurer')
            ->where('insurer_id', Auth::user()->company_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('client.contracts.index', compact('contracts'));
    }

    public function create()
    {
        $reinsurers = Company::where('type', 'reinsurer')->get();
        return view('client.contracts.create', compact('reinsurers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['quota', 'excess', 'facultative'])],
            'reinsurer_id' => 'required|exists:companies,id',
            'premium' => 'required|numeric|min:0',
            'coverage' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $contract = new Contract($validated);
        $contract->insurer_id = Auth::user()->company_id;
        $contract->created_by = Auth::id();
        $contract->status = 'pending';
        $contract->save();

        return redirect()->route('client.contracts.index')
            ->with('success', 'Договор успешно создан и отправлен на рассмотрение');
    }

    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);

        return view('client.contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $this->authorize('update', $contract);

        if ($contract->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно редактировать только договоры со статусом "На рассмотрении"');
        }

        $reinsurers = Company::where('type', 'reinsurer')->get();
        $types = [
            'quota' => 'Квотный',
            'excess' => 'Эксцедент',
            'facultative' => 'Факультативный'
        ];

        return view('client.contracts.edit', compact('contract', 'reinsurers', 'types'));
    }

    public function update(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        if ($contract->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно редактировать только договоры со статусом "На рассмотрении"');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['quota', 'excess', 'facultative'])],
            'reinsurer_id' => 'required|exists:companies,id',
            'premium' => 'required|numeric|min:0',
            'coverage' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $contract->update($validated);

        return redirect()->route('client.contracts.index')
            ->with('success', 'Договор успешно обновлен');
    }

    public function destroy(Contract $contract)
    {
        $this->authorize('delete', $contract);

        if ($contract->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно удалять только договоры со статусом "На рассмотрении"');
        }

        $contract->delete();

        return redirect()->route('client.contracts.index')
            ->with('success', 'Договор успешно удален');
    }
}