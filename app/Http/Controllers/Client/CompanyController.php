<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Company;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::all();

        return view('client.companies.index', compact('companies'));
    }

    public function show(Company $company)
    {
        return view('client.companies.show', compact('company'));
    }
}
