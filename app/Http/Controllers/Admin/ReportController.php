<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function sales()
    {
        return view('admin.reports.sales');
    }

    public function cash()
    {
        return view('admin.reports.cash');
    }

    public function inventory()
    {
        return view('admin.reports.inventory');
    }

    public function customers()
    {
        return view('admin.reports.customers');
    }

    public function tables()
    {
        return view('admin.reports.tables');
    }

    public function kitchen()
    {
        return view('admin.reports.kitchen');
    }

    public function profit()
    {
        return view('admin.reports.profit');
    }
}
