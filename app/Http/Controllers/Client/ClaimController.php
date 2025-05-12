<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimController extends Controller
{
    public function index()
    {
        $claims = Claim::with(['contract.reinsurer'])
            ->whereHas('contract', function($query) {
                $query->where('insurer_id', Auth::user()->company_id);
            })
            ->orderBy('filed_at', 'desc')
            ->paginate(10);

        return view('client.claims.index', compact('claims'));
    }

    public function create()
    {
        $contracts = Contract::where('insurer_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->get();

        return view('client.claims.create', compact('contracts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => [
                'required',
                'exists:contracts,id',
                function ($attribute, $value, $fail) {
                    $contract = Contract::find($value);
                    if ($contract && $contract->insurer_id !== Auth::user()->company_id) {
                        $fail('Вы можете подавать убытки только по своим договорам.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:2000',
        ]);

        $claim = new Claim($validated);
        $claim->filed_at = now();
        $claim->status = 'pending';
        $claim->save();

        return redirect()->route('client.claims.index')
            ->with('success', 'Убыток успешно зарегистрирован');
    }

    public function show($id)
    {
        $claim = Claim::with(['contract.insurer', 'contract.reinsurer'])->findOrFail($id);
        return view('client.claims.show', compact('claim'));
    }

    public function edit(Claim $claim)
    {
        $this->authorize('update', $claim);

        if ($claim->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно редактировать только убытки со статусом "На рассмотрении"');
        }

        $contracts = Contract::where('insurer_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->get();

        return view('client.claims.edit', compact('claim', 'contracts'));
    }

    public function update(Request $request, Claim $claim)
    {
        $this->authorize('update', $claim);

        if ($claim->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно редактировать только убытки со статусом "На рассмотрении"');
        }

        $validated = $request->validate([
            'contract_id' => [
                'required',
                'exists:contracts,id',
                function ($attribute, $value, $fail) {
                    $contract = Contract::find($value);
                    if ($contract && $contract->insurer_id !== Auth::user()->company_id) {
                        $fail('Вы можете подавать убытки только по своим договорам.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:2000',
        ]);

        $claim->update($validated);

        return redirect()->route('client.claims.index')
            ->with('success', 'Убыток успешно обновлен');
    }

    public function destroy(Claim $claim)
    {
        $this->authorize('delete', $claim);

        if ($claim->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Можно удалять только убытки со статусом "На рассмотрении"');
        }

        $claim->delete();

        return redirect()->route('client.claims.index')
            ->with('success', 'Убыток успешно удален');
    }
}