<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReleaseTableController extends Controller
{
    private const CANCEL_REASON = 'Liberado desde administración';

    /**
     * Muestra todas las mesas que tienen órdenes pendientes (ocupadas)
     * y permite al admin liberarlas.
     */
    public function index()
    {
        // Obtener todas las órdenes pendientes agrupadas por mesa
        $ordenesPendientes = Order::where('status', 'pendiente')
            ->with(['user', 'items'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Agrupar por número de mesa
        $mesasOcupadas = collect();

        foreach ($ordenesPendientes as $order) {
            $tables = $order->table_numbers ?? [];

            if (empty($tables) && $order->type === 'llevar') {
                continue; // Ignorar pedidos para llevar
            }

            $key = ! empty($tables) ? implode(',', $tables) : null;

            if (! $key) {
                continue;
            }

            if (! $mesasOcupadas->has($key)) {
                $mesasOcupadas->put($key, collect());
            }
            $mesasOcupadas->get($key)->push($order);
        }

        return view('admin.tables.release', compact('mesasOcupadas'));
    }

    /**
     * Libera una mesa cancelando todas sus órdenes pendientes.
     * El motivo queda registrado como "Liberado desde administración".
     */
    public function release(Request $request)
    {
        $request->validate([
            'table_key' => 'required|string',
        ]);

        $tableKey = $request->input('table_key');
        $tables   = explode(',', $tableKey);

        DB::transaction(function () use ($tables) {
            // Buscar todas las órdenes pendientes de esas mesas
            $orders = Order::where('status', 'pendiente')
                ->where(function ($q) use ($tables) {
                    foreach ($tables as $t) {
                        $q->orWhereJsonContains('table_numbers', (int) $t);
                    }
                })
                ->with('childOrders')
                ->get();

            foreach ($orders as $order) {
                // Cancelar la orden con motivo de liberación
                $order->update([
                    'status'        => 'cancelado',
                    'cancel_reason' => self::CANCEL_REASON,
                ]);

                // Cancelar también sus hijos pendientes
                $order->childOrders()
                    ->where('status', 'pendiente')
                    ->each(function ($child) {
                        $child->update([
                            'status'        => 'cancelado',
                            'cancel_reason' => self::CANCEL_REASON,
                        ]);
                    });
            }
        });

        $label = count(explode(',', $tableKey)) > 1
            ? 'Mesas ' . $tableKey
            : 'Mesa ' . $tableKey;

        return redirect()
            ->route('admin.tables.release')
            ->with('success', "{$label} liberada correctamente. Todas sus órdenes fueron canceladas.");
    }
}