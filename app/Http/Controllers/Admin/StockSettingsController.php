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

        return view('admin.settings.stock', compact('stockEnabled', 'allowNegative'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'stock_enabled' => 'sometimes|boolean',
            'stock_allow_negative' => 'sometimes|boolean',
        ]);

        Setting::setValue('stock_enabled', $request->boolean('stock_enabled'));
        Setting::setValue('stock_allow_negative', $request->boolean('stock_allow_negative'));

        return redirect()->route('admin.settings.stock.edit')->with('success', 'Configuración de stock actualizada.');
    }
}
