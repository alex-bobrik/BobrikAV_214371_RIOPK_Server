<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     */
    public function index(Request $request)
    {
        // Получаем текущего пользователя
        $user = Auth::user();
        $userId = $user->id;
        $companyId = $user->company_id;
        
        // Получаем запрос с фильтрами
        $query = Payment::with(['claim', 'contract.reinsurer', 'createdBy'])
            ->whereIn('type', ['insurer_payment', 'premium']); // Только выплаты страховщика и премии
        
        // Фильтруем по компании пользователя: находим все выплаты, созданные пользователями той же компании
        $query->whereHas('createdBy', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        });
        
        // Применяем фильтры из запроса
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // Сортировка
        switch ($request->get('sort', 'newest')) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'amount_asc':
                $query->orderBy('amount', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('amount', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        $payments = $query->paginate(15);
        
        // Статистика
        $statsQuery = Payment::whereIn('type', ['insurer_payment', 'premium'])
            ->whereHas('createdBy', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        
        $stats = [
            'total' => $statsQuery->count(),
            'pending' => $statsQuery->clone()->where('status', 'pending')->count(),
            'paid' => $statsQuery->clone()->where('status', 'paid')->count(),
            'cancelled' => $statsQuery->clone()->where('status', 'cancelled')->count(),
            'total_amount' => $statsQuery->clone()->where('status', 'paid')->sum('amount'),
        ];
        
        return view('owner.payments.index', compact('payments', 'stats', 'userId', 'companyId'));
    }
    
    /**
     * Update payment status.
     */
    public function updateStatus(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Проверяем, принадлежит ли выплата компании пользователя
        $user = Auth::user();
        $userId = $user->id;
        $companyId = $user->company_id;
        
        // Проверяем, что создатель выплаты принадлежит той же компании
        $isCompanyPayment = $payment->createdBy && 
                           $payment->createdBy->company_id == $companyId && 
                           in_array($payment->type, ['insurer_payment', 'premium']);
        
        if (!$isCompanyPayment) {
            return redirect()->back()->with('error', 'У вас нет прав для изменения этой выплаты.');
        }
        
        // Обновляем статус
        $oldStatus = $payment->status;
        $payment->status = $request->status;
        
        // Добавляем заметку в описание, если есть
        if ($request->filled('notes')) {
            $payment->description .= "\n\n[Статус изменен: {$oldStatus} → {$request->status}]\n{$request->notes}";
        }
        
        $payment->save();
        
        return redirect()->back()->with('success', 'Статус выплаты успешно обновлен.');
    }
    
    /**
     * AJAX endpoint for updating payment status.
     */
    public function updateStatusAjax(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Проверяем, принадлежит ли выплата компании пользователя
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Проверяем, что создатель выплаты принадлежит той же компании
        $isCompanyPayment = $payment->createdBy && 
                           $payment->createdBy->company_id == $companyId && 
                           in_array($payment->type, ['insurer_payment', 'premium']);
        
        if (!$isCompanyPayment) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав для изменения этой выплаты.'
            ], 403);
        }
        
        // Обновляем статус
        $oldStatus = $payment->status;
        $payment->status = $request->status;
        
        // Добавляем заметку в описание, если есть
        if ($request->filled('notes')) {
            $payment->description .= "\n\n[Статус изменен: {$oldStatus} → {$request->status}]\n{$request->notes}";
        }
        
        $payment->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Статус выплаты успешно обновлен.',
            'payment' => $payment
        ]);
    }

    /**
     * Get payment details for AJAX modal.
     */
    public function getDetails(Payment $payment)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Проверяем права доступа
        $isCompanyPayment = $payment->createdBy && 
                        $payment->createdBy->company_id == $companyId && 
                        in_array($payment->type, ['insurer_payment', 'premium']);
        
        if (!$isCompanyPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }
        
        return view('owner.payments.partials.details', compact('payment'));
    }


}