<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tables = Table::all();
        return view('admin.tables.index', compact('tables'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.tables.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string|unique:tables,number',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:libre,ocupada,reservada',
        ]);

        Table::create($validated);
        return redirect()->route('admin.tables.index')->with('success', 'Mesa creada exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Table $table)
    {
        return view('admin.tables.edit', compact('table'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'number' => 'required|string|unique:tables,number,' . $table->id,
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:libre,ocupada,reservada',
        ]);

        $table->update($validated);
        return redirect()->route('admin.tables.index')->with('success', 'Mesa actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Table $table)
    {
        $table->delete();
        return redirect()->route('admin.tables.index')->with('success', 'Mesa eliminada.');
    }
}
