<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function dashboard()
    {
        // ── Tab Cocina: solo órdenes NO impresas aún ─────────────────────────
        $pedidosCocina = Order::where('status', 'pendiente')
            ->whereNull('kitchen_printed_at')
            ->with('items.product')
            ->orderBy('created_at', 'asc')
            ->get();

        // ── Tab Cobros: solo órdenes YA impresas (listas para cobrar) ────────
        $ordenesCobro = Order::with(['user', 'items', 'childOrders.items'])
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'pendiente')
            ->whereNotNull('kitchen_printed_at')   // ← solo las impresas
            ->orderBy('created_at', 'asc')
            ->get();

        $ordersByTable = $this->agruparPorMesa($ordenesCobro);

        // ── Tab Historial: pagos y cancelaciones ──────────────────────────────
        $historial = Order::whereIn('status', ['pagado', 'cancelado'])
            ->whereDate('updated_at', Carbon::today())
            ->with('items')                          // ← cargar items
            ->orderByDesc('updated_at')
            ->get();

        $countPagado    = $historial->where('status', 'pagado')->count();
        // Total calculado desde ítems reales, no desde el campo total (que puede estar inflado)
        $totalPagado    = $historial->where('status', 'pagado')
            ->sum(fn ($o) => $o->items->sum(fn ($it) => $it->price * $it->quantity));
        $countCancelado = $historial->where('status', 'cancelado')->count();

        // ── Tab Caja ──────────────────────────────────────────────────────────
        $cajaAbierta = CashRegister::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        // Calculado desde ítems reales para evitar totales inflados
        $ventasHoy = $historial->where('status', 'pagado')
            ->sum(fn ($o) => $o->items->sum(fn ($it) => $it->price * $it->quantity));

        $hashCocina = md5(
            $pedidosCocina->pluck('id')->sort()->values()->join(',')
        );

        return view('caja.dashboard', compact(
            'pedidosCocina',
            'ordersByTable',
            'historial',
            'countPagado',
            'totalPagado',
            'countCancelado',
            'cajaAbierta',
            'ventasHoy',
            'hashCocina'
        ));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function agruparPorMesa($ordenes)
    {
        $grouped = collect();

        foreach ($ordenes as $order) {
            $tables = $order->table_numbers ?? [];
            $key    = ! empty($tables)
                ? implode(',', $tables)
                : ($order->type === 'llevar' ? 'llevar' : '');

            if (! $grouped->has($key)) {
                $grouped->put($key, collect());
            }
            $grouped->get($key)->push($order);
        }

        $result = collect();

        foreach ($grouped as $tableKey => $tableOrders) {
            $sorted         = $tableOrders->sortBy('created_at');
            $sessions       = collect();
            $currentSession = collect();

            foreach ($sorted as $order) {
                $isParent      = is_null($order->origin_order_id);
                $currentClosed = $currentSession->count() > 0
                    && $currentSession->where('status', 'pendiente')->count() === 0;

                if ($isParent && $currentClosed) {
                    $sessions->push([
                        'tableKey'  => $tableKey,
                        'orders'    => $currentSession,
                        'isCurrent' => false,
                    ]);
                    $currentSession = collect();
                }

                $currentSession->push($order);
            }

            if ($currentSession->count() > 0) {
                $sessions->push([
                    'tableKey'  => $tableKey,
                    'orders'    => $currentSession,
                    'isCurrent' => true,
                ]);
            }

            foreach ($sessions as $s) {
                $result->push($s);
            }
        }

        return $result->sortByDesc('isCurrent');
    }
}