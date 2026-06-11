<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->role?->name;
        $today = Carbon::today();

        $data = match ($role) {
            'admin'  => $this->adminData($today),
            'cajero' => $this->cajeroData($today),
            'cocina' => $this->cocinaData(),
            'mozo'   => $this->mozoData($user),
            default  => [],
        };

        return view('dashboard', array_merge($data, ['role' => $role]));
    }

    // ─── Admin ────────────────────────────────────────────────────────────────

    private function adminData(Carbon $today): array
    {
        $ordersToday = Order::whereDate('created_at', $today)->get();

        return [
            'pedidosHoy'         => $ordersToday->whereNotIn('status', ['cancelado'])->count(),
            'ventasHoy'          => $ordersToday->where('status', 'pagado')->sum('total'),
            'pedidosPendientes'  => Order::where('status', 'pendiente')->count(),
            'productosPocoStock' => Product::where('stock', '>', 0)->where('stock', '<=', 5)->count(),
            'productosSinStock'  => Product::where('stock', '<=', 0)->count(),
            'totalProductos'     => Product::count(),
        ];
    }

    // ─── Cajero ───────────────────────────────────────────────────────────────

    private function cajeroData(Carbon $today): array
    {
        $ordersToday = Order::whereDate('created_at', $today)->get();
        $cajaAbierta = CashRegister::whereNull('closed_at')->latest('opened_at')->first();

        return [
            'ventasHoy'          => $ordersToday->where('status', 'pagado')->sum('total'),
            'pedidosCobradosHoy' => $ordersToday->where('status', 'pagado')->count(),
            'pedidosPendientes'  => Order::where('status', 'pendiente')->count(),
            'cajaAbierta'        => $cajaAbierta,
        ];
    }

    // ─── Cocina ───────────────────────────────────────────────────────────────

    private function cocinaData(): array
    {
        return [
            'pedidosPendientes' => Order::where('status', 'pendiente')->count(),
        ];
    }

    // ─── Mozo ─────────────────────────────────────────────────────────────────

    private function mozoData($user): array
    {
        return [
            'misPedidosActivos' => Order::where('user_id', $user->id)
                ->where('status', 'pendiente')
                ->count(),
        ];
    }
}
