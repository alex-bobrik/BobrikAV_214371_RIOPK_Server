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
        $rawUser = $request->input('user');

        $validated = $request->validate([
            'user' => ['required', 'array'],
            'user.id' => ['required', 'integer', 'exists:users,id'],
            'user.company_id' => ['required', 'integer'],
            'contract_id' => [
                'required',
                'exists:contracts,id',
                function ($attr, $value, $fail) use ($rawUser) {
                    $contract = Contract::find($value);
                    if ($contract && (!isset($rawUser['company_id']) || $contract->insurer_id !== $rawUser['company_id'])) {
                        $fail('Неверный договор');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $claim = new Claim([
            'contract_id' => $validated['contract_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'user_id' => $validated['user']['id'],
            'filed_at' => now(),
            'status' => 'pending',
        ]);

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
        $validated = $request->validate([
            'user' => ['required', 'array'],
            'user.id' => ['required', 'integer', 'exists:users,id'],
            'user.company_id' => ['required', 'integer'],
            'contract_id' => [
                'required',
                'exists:contracts,id',
                function ($attr, $value, $fail) use ($request) {
                    $contract = Contract::find($value);
                    $userCompanyId = $request->input('user.company_id');
                    if ($contract && $contract->insurer_id !== $userCompanyId) {
                        $fail('Неверный договор');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $claim->update([
            'contract_id' => $validated['contract_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'user_id' => $validated['user']['id'],
        ]);

        return response()->json([
            'message' => 'Убыток обновлен',
            'data' => $claim->load('contract.reinsurer'),
        ], 200);
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