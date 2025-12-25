<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashController extends Controller
{
    public function index()
    {
        $currentRegister = CashRegister::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        $salesToday = Order::whereDate('created_at', Carbon::today())
            ->where('status', 'pagado')
            ->sum('total');

        return view('cash.index', compact('currentRegister', 'salesToday'));
    }

    public function open(Request $request)
    {
        $request->validate(['opening_balance' => 'required|numeric|min:0']);

        CashRegister::create([
            'user_id' => auth()->id(),
            'opening_balance' => $request->opening_balance,
            'opened_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Caja abierta correctamente.');
    }

    public function close(Request $request)
    {
        $register = CashRegister::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->firstOrFail();

        $register->update([
            'closing_balance' => $request->closing_balance,
            'closed_at' => now(),
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Caja cerrada correctamente.');
    }
}
