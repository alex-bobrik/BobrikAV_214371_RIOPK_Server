<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with('reinsurer')
            ->where('insurer_id', Auth::user()->company_id)
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->paginate(10),
            'reinsurers' => Company::where('type', 'reinsurer')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:quota,excess,facultative'],
            'reinsurer_id' => ['required', 'exists:companies,id'],
            'premium' => ['required', 'numeric', 'min:0'],
            'coverage' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $contract = new Contract($validated);
        $contract->insurer_id = Auth::user()->company_id;
        $contract->created_by = Auth::id();
        $contract->status = 'pending';
        $contract->save();

        return response()->json([
            'message' => 'Договор успешно создан',
            'data' => $contract->load('reinsurer'),
        ], 201);
    }

    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);
        
        return response()->json([
            'data' => $contract->load(['reinsurer', 'payments', 'claims']),
        ]);
    }

    public function update(Request $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        if ($contract->status !== 'pending') {
            return response()->json([
                'message' => 'Можно редактировать только договоры со статусом "На рассмотрении"'
            ], 403);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'in:quota,excess,facultative'],
            'reinsurer_id' => ['sometimes', 'exists:companies,id'],
            'premium' => ['sometimes', 'numeric', 'min:0'],
            'coverage' => ['sometimes', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
        ]);

        $contract->update($validated);

        return response()->json([
            'message' => 'Договор обновлен',
            'data' => $contract->load('reinsurer'),
        ]);
    }

    public function destroy(Contract $contract)
    {
        $this->authorize('delete', $contract);

        if ($contract->status !== 'pending') {
            return response()->json([
                'message' => 'Можно удалять только договоры со статусом "На рассмотрении"'
            ], 403);
        }

        $contract->delete();

        return response()->json([
            'message' => 'Договор удален',
        ]);
    }

    public function stats()
    {
        $companyId = Auth::user()->company_id;

        return response()->json([
            'active' => Contract::where('insurer_id', $companyId)
                ->where('status', 'active')
                ->count(),
            'by_type' => Contract::where('insurer_id', $companyId)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'coverage' => Contract::where('insurer_id', $companyId)
                ->where('created_at', '>=', now()->subMonths(3))
                ->sum('coverage'),
        ]);
    }
}