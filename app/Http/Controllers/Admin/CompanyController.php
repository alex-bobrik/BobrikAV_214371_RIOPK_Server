<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function edit(Company $company)
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'type' => 'required|in:insurer,reinsurer',
            'description' => 'nullable|string|max:1000',
        ]);
        
        $company->update([
            'name' => $request->name,
            'country' => $request->country,
            'type' => $request->type,
            'description' => $request->description,
        ]);
        
    
        return redirect()->route('admin.companies.index')->with('success', 'Компания успешно обновлена!');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'type' => 'required|in:insurer,reinsurer',
            'description' => 'nullable|string|max:1000',
        ]);
        
        Company::create([
            'name' => $request->name,
            'country' => $request->country,
            'type' => $request->type,
            'description' => $request->description,
        ]);
    
        return redirect()->route('admin.companies.index')->with('success', 'Новая компания успешно добавлена!');
    }
    
    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('admin.companies.index')->with('success', 'Компания удалена');
    }
}
