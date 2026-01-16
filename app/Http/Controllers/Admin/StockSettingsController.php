<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class StockSettingsController extends Controller
{
    public function edit()
    {
        $stockEnabled = (bool) Setting::getValue('stock_enabled', false);
        $allowNegative = (bool) Setting::getValue('stock_allow_negative', false);
        $stockMinimum = Setting::getValue('stock_minimum_threshold', null);

        return view('admin.settings.stock', compact('stockEnabled', 'allowNegative', 'stockMinimum'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'stock_enabled' => 'sometimes|boolean',
            'stock_allow_negative' => 'sometimes|boolean',
            'stock_minimum_threshold' => 'sometimes|nullable|integer|min:0',
        ]);

        Setting::setValue('stock_enabled', $request->boolean('stock_enabled'));
        Setting::setValue('stock_allow_negative', $request->boolean('stock_allow_negative'));
        // optional minimum threshold for low-stock warnings
        if ($request->has('stock_minimum_threshold') && $request->input('stock_minimum_threshold') !== null && $request->input('stock_minimum_threshold') !== '') {
            Setting::setValue('stock_minimum_threshold', (int) $request->input('stock_minimum_threshold'));
        } else {
            Setting::setValue('stock_minimum_threshold', null);
        }

        return redirect()->route('admin.settings.stock.edit')->with('success', 'Configuración de stock actualizada.');
    }
}
