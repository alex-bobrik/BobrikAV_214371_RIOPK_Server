<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = Claim::with(['contract.reinsurer'])
            ->whereHas('contract', function($q) {
                $q->where('insurer_id', Auth::user()->company_id);
            })
            ->orderBy('filed_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->paginate(10),
            'contracts' => Contract::where('insurer_id', Auth::user()->company_id)
                ->where('status', 'active')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => [
                'required',
                'exists:contracts,id',
                function ($attr, $value, $fail) {
                    $contract = Contract::find($value);
                    if ($contract && $contract->insurer_id !== Auth::user()->company_id) {
                        $fail('Неверный договор');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $claim = new Claim($validated);
        $claim->filed_at = now();
        $claim->status = 'pending';
        $claim->save();

        return response()->json([
            'message' => 'Убыток зарегистрирован',
            'data' => $claim->load('contract.reinsurer'),
        ], 201);
    }

    public function show(Claim $claim)
    {
        $this->authorize('view', $claim);
        
        return response()->json([
            'data' => $claim->load(['contract.reinsurer', 'payments']),
        ]);
    }

    public function update(Request $request, Claim $claim)
    {
        $this->authorize('update', $claim);

        if ($claim->status !== 'pending') {
            return response()->json([
                'message' => 'Можно редактировать только убытки со статусом "На рассмотрении"'
            ], 403);
        }

        $validated = $request->validate([
            'contract_id' => [
                'sometimes',
                'exists:contracts,id',
                function ($attr, $value, $fail) {
                    $contract = Contract::find($value);
                    if ($contract && $contract->insurer_id !== Auth::user()->company_id) {
                        $fail('Неверный договор');
                    }
                },
            ],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['sometimes', 'string', 'max:2000'],
        ]);

        $claim->update($validated);

        return response()->json([
            'message' => 'Убыток обновлен',
            'data' => $claim->load('contract.reinsurer'),
        ]);
    }

    public function destroy(Claim $claim)
    {
        $this->authorize('delete', $claim);

        if ($claim->status !== 'pending') {
            return response()->json([
                'message' => 'Можно удалять только убытки со статусом "На рассмотрении"'
            ], 403);
        }

        $claim->delete();

        return response()->json([
            'message' => 'Убыток удален',
        ]);
    }
}