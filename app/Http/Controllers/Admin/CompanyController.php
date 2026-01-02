<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
public function index(Request $request)
    {
        $query = Company::query();
        
        // Поиск по названию
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }
        
        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $companies = $query->paginate(20);
        
        if ($request->ajax()) {
            return view('admin.companies.partials.companies-table', compact('companies'))->render();
        }
        
        return view('admin.companies.index', compact('companies'));
    }
    
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('companies')->ignore($company->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
        
        try {
            $company->update($validated);
            return response()->json(['success' => true, 'message' => 'Компания обновлена']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка при обновлении'], 500);
        }
    }
    
    public function toggleStatus(Company $company)
    {    
        try {
            $newStatus = !$company->is_active;
            
            // Обновляем статус компании
            $company->update(['is_active' => $newStatus]);
            
            // Обновляем статусы всех пользователей компании
            User::where('company_id', $company->id)
                ->update(['is_active' => $newStatus]);
                    
            return response()->json([
                'success' => true,
                'message' => $newStatus 
                    ? 'Компания и все ее пользователи активированы' 
                    : 'Компания и все ее пользователи деактивированы',
                'is_active' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Произошла ошибка при изменении статуса'
            ], 500);
        }
    }


}
