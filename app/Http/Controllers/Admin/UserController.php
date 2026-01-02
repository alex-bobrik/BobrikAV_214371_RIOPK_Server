<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
public function index(Request $request)
    {
        // Получаем всех пользователей, кроме текущего админа
        $query = User::where('id', '!=', auth()->id())
                     ->with('company');
        
        // Поиск
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('company', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Фильтр по роли
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Сортировка
        switch ($request->get('sort', 'created_at_desc')) {
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'email_asc':
                $query->orderBy('email', 'asc');
                break;
            case 'email_desc':
                $query->orderBy('email', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
        
        $users = $query->paginate(20);
        
        // Статистика
        $activeCount = User::where('id', '!=', auth()->id())
                          ->where('is_active', true)
                          ->count();
        $inactiveCount = User::where('id', '!=', auth()->id())
                            ->where('is_active', false)
                            ->count();
        
        // Для формы создания пользователя
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $roles = ['manager' => 'Менеджер', 'user' => 'Пользователь']; // Исключаем админа
        
        return view('admin.users.index', compact('users', 'activeCount', 'inactiveCount', 'companies', 'roles'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed'],
            'role' => ['required', 'string'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'is_active' => ['boolean'],
        ]);
        
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'company_id' => $validated['company_id'],
                'is_active' => $validated['is_active'] ?? true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно создан',
                'user' => $user->load('company')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании пользователя: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function update(Request $request, User $user)
    {
        // Нельзя редактировать текущего админа
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя редактировать текущего пользователя'
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed'],
            'role' => ['required', 'string'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'is_active' => ['boolean'],
        ]);
        
        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'company_id' => $validated['company_id'],
                'is_active' => $validated['is_active'],
            ];
            
            // Обновляем пароль только если он был указан
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            
            $user->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно обновлен',
                'user' => $user->fresh()->load('company')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении пользователя: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function toggleStatus(User $user)
    {
        // Нельзя заблокировать текущего админа
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя заблокировать текущего пользователя'
            ], 403);
        }
        
        try {
            $user->update(['is_active' => !$user->is_active]);
            
            return response()->json([
                'success' => true,
                'message' => $user->is_active 
                    ? 'Пользователь активирован' 
                    : 'Пользователь заблокирован',
                'is_active' => $user->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при изменении статуса пользователя'
            ], 500);
        }
    }
}
