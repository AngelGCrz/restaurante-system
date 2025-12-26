<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class TableSettingsController extends Controller
{
    public function edit()
    {
        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);

        return view('admin.tables.settings', compact('tableCount'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'total_tables' => 'required|integer|min:0|max:500',
        ]);

        Setting::setValue('total_tables', (int) $validated['total_tables']);

        return redirect()->route('admin.tables.edit')->with('success', 'Cantidad de mesas actualizada.');
    }
}
