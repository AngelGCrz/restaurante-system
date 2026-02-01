<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class KitchenController extends Controller
{
    public function index()
    {
        // En un escenario real, esto se actualizaría vía WebSockets (Reverb) o Polling
        $orders = Order::whereIn('status', ['pendiente', 'en_preparacion'])
            ->with('items.product')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('kitchen.index', compact('orders'));
    }

    public function prepare(Order $order)
    {
        if ($order->status !== 'pendiente') {
            return back()->withErrors(['order' => 'El pedido no puede pasar a preparación desde su estado actual.']);
        }

        $order->update(['status' => 'en_preparacion']);

        return back()->with('success', 'Pedido marcado como En Preparación.');
    }

    public function ready(Order $order)
    {
        if ($order->status !== 'en_preparacion') {
            return back()->withErrors(['order' => 'El pedido debe estar en preparación para marcarlo como listo.']);
        }

        $now = now();
        $seconds = $now->diffInSeconds($order->created_at);

        $order->update([
            'status' => 'listo',
            'prepared_at' => $now,
            'preparation_seconds' => $seconds,
        ]);

        return back()->with('success', 'Pedido marcado como Listo. Tiempo: ' . gmdate('H:i:s', $seconds));
    }

    public function show(Order $order)
    {
        $order->load('items.product');
        return view('kitchen.show', compact('order'));
    }
}
