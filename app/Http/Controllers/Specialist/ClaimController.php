<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Claim;
use App\Models\ContractMessage;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    public function create()
    {
        $contracts = Contract::where('insurer_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->with('reinsurer')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('specialist.claims.create', compact('contracts'));
    }
    
    public function store(Request $request)
    {        
        $contract = Contract::findOrFail($request->get('contract_id'));
        
        if ($contract->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя создать убыток по неактивному договору'
            ], 422);
        }
        
        if ($contract->insurer_id !== Auth::user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому договору'
            ], 403);
        }
                
        try {
            $claim = Claim::create([
                'contract_id' => $contract->id,
                'amount' => $request->get('amount'),
                'description' => $request->get('description'),
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);
            
            ContractMessage::create([
                'contract_id' => $contract->id,
                'message' => 'Создан убыток #' . $claim->id . ' на сумму ' . number_format($request->get('amount'), 0, '', ' ') . ' BYN. ' . 
                           ($request->get('description') ? 'Описание: ' . substr($request->get('description'), 0, 100) . '...' : ''),
                'created_by' => Auth::id(),
                'is_system' => true,
            ]);
            
            if ($request->get('reinsurer_payment') > 0) {
                Payment::create([
                    'claim_id' => $claim->id,
                    'contract_id' => $contract->id,
                    'amount' => $request->get('reinsurer_payment'),
                    'type' => 'reinsurer_payment',
                    'status' => 'pending',
                    'description' => 'Выплата перестраховщику по убытку #' . $claim->id,
                    'created_by' => Auth::id(),
                ]);
            }
            
            if ($request->get('our_payment') > 0) {
                Payment::create([
                    'claim_id' => $claim->id,
                    'contract_id' => $contract->id,
                    'amount' => $request->get('our_payment'),
                    'type' => 'insurer_payment',
                    'status' => 'pending',
                    'description' => 'Выплата страховщика по убытку #' . $claim->id,
                    'created_by' => Auth::id(),
                ]);
            }
            
            if ($request->get('calculated_premium') > 0) {
                Payment::create([
                    'claim_id' => $claim->id,
                    'contract_id' => $contract->id,
                    'amount' => $request->get('calculated_premium'),
                    'type' => 'premium',
                    'status' => 'pending',
                    'description' => 'Расчетная премия по убытку #' . $claim->id,
                    'created_by' => Auth::id(),
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'claim_id' => $claim->id,
                'message' => 'Убыток успешно создан',
            ]);
            
        } catch (\Exception $e) {            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании убытка: ' . $e->getMessage(),
            ], 500);
        }
    }
}