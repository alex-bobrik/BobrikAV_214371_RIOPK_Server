<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Company;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
public function index(Request $request)
{
    $userCompanyId = Auth::user()->company_id;
    
    // Начинаем запрос с базовых условий
    $query = Company::where('is_active', true)
        ->where('id', '!=', $userCompanyId);
    
    // Поиск по названию, ИНН, email или телефону
    if ($request->has('search') && $request->search != '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('inn', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%')
              ->orWhere('phone', 'like', '%' . $search . '%');
        });
    }
    
    // Фильтр по типу компании
    if ($request->has('type') && $request->type != '') {
        $query->where('type', $request->type);
    }
    
    // Фильтр по статусу (активные/неактивные)
    if ($request->has('status') && $request->status != '') {
        if ($request->status == 'active') {
            $query->where('is_active', true);
        } elseif ($request->status == 'inactive') {
            $query->where('is_active', false);
        }
    }
    
    // Сортировка
    $sortField = 'created_at';
    $sortDirection = 'desc';
    
    if ($request->has('sort')) {
        switch ($request->sort) {
            case 'newest':
                $sortField = 'created_at';
                $sortDirection = 'desc';
                break;
            case 'oldest':
                $sortField = 'created_at';
                $sortDirection = 'asc';
                break;
            case 'name_asc':
                $sortField = 'name';
                $sortDirection = 'asc';
                break;
            case 'name_desc':
                $sortField = 'name';
                $sortDirection = 'desc';
                break;
            case 'coverage_asc':
                // Если у компаний есть поле coverage
                $sortField = 'coverage';
                $sortDirection = 'asc';
                break;
            case 'coverage_desc':
                $sortField = 'coverage';
                $sortDirection = 'desc';
                break;
        }
    }
    
    $query->orderBy($sortField, $sortDirection);
    
    // Фильтр по дате создания (date_range)
    if ($request->has('date_range') && $request->date_range != '') {
        $now = Carbon::now();
        
        switch ($request->date_range) {
            case 'today':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'week':
                $query->where('created_at', '>=', $now->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', $now->subMonth());
                break;
            case 'quarter':
                $query->where('created_at', '>=', $now->subMonths(3));
                break;
            case 'year':
                $query->where('created_at', '>=', $now->subYear());
                break;
        }
    }
    
    // Пагинация
    $perPage = $request->has('per_page') ? (int)$request->per_page : 10;
    $companies = $query->paginate($perPage)->appends($request->query());
    

    return view('owner.companies.index', compact('companies'));
}

}
