<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/contracts",
     *     summary="Get a list of contracts",
     *     tags={"Contracts"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by contract status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A list of contracts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Contract")
     *             ),
     *             @OA\Property(
     *                 property="reinsurers",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Company")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $rawUser = $request->input('user');

        $query = Contract::with('reinsurer')
            ->where('insurer_id', $rawUser['company_id'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->paginate(10),
            'reinsurers' => Company::where('type', 'reinsurer')->get(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contracts",
     *     summary="Создать новый договор",
     *     tags={"Contracts"},
     *     @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="string", enum={"quota", "excess", "facultative"})),
     *     @OA\Parameter(name="reinsurer_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="premium", in="query", required=true, @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="coverage", in="query", required=true, @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="start_date", in="query", required=true, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", required=true, @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=201,
     *         description="Договор успешно создан",
     *         @OA\JsonContent(ref="#/components/schemas/Contract")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $rawUser = $request->input('user');

        $validated = $request->validate([
            'type' => ['required', 'in:quota,excess,facultative'],
            'reinsurer_id' => ['required', 'exists:companies,id'],
            'premium' => ['required', 'numeric', 'min:0'],
            'coverage' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);
    
        $contract = new Contract($validated);
        $contract->insurer_id = $rawUser['company_id'];
        $contract->created_by = $rawUser['id'];
        $contract->status = 'pending';
        $contract->save();
    
        return response()->json([
            'message' => 'Договор успешно создан',
            'data' => $contract->load('reinsurer'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/contracts/{contract}",
     *     summary="Get a specific contract",
     *     tags={"Contracts"},
     *     @OA\Parameter(
     *         name="contract",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract details",
     *         @OA\JsonContent(ref="#/components/schemas/Contract")
     *     )
     * )
     */
    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);
        
        return response()->json([
            'data' => $contract->load(['reinsurer', 'payments', 'claims']),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/contracts/{contract}",
     *     summary="Обновить договор",
     *     tags={"Contracts"},
     *     @OA\Parameter(name="contract", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"quota", "excess", "facultative"})),
     *     @OA\Parameter(name="reinsurer_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="premium", in="query", required=false, @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="coverage", in="query", required=false, @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Договор обновлен",
     *         @OA\JsonContent(ref="#/components/schemas/Contract")
     *     )
     * )
     */
    public function update(Request $request, Contract $contract)
    {
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

    /**
     * @OA\Delete(
     *     path="/api/v1/contracts/{contract}",
     *     summary="Delete a contract",
     *     tags={"Contracts"},
     *     @OA\Parameter(
     *         name="contract",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contract deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/contracts/stats",
     *     summary="Get contract statistics",
     *     tags={"Contracts"},
     *     @OA\Response(
     *         response=200,
     *         description="Contract statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="active", type="integer"),
     *             @OA\Property(property="by_type", type="object"),
     *             @OA\Property(property="coverage", type="number", format="float")
     *         )
     *     )
     * )
     */
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