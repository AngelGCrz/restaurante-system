<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(protected PrinterService $printer) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Creación de pedidos
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Crea un pedido nuevo con sus items.
     *
     * @param  array{user_id: int, customer_name: ?string, comment: ?string,
     *               type: string, table_numbers: array, items: array}  $data
     */
    public function createOrder(array $data): Order
    {
        [$stockEnabled, $stockAllowNegative] = $this->stockSettings();

        if ($stockEnabled) {
            $this->assertStock($data['items'], $stockAllowNegative);
        }

        return DB::transaction(function () use ($data, $stockEnabled, $stockAllowNegative) {
            $order = Order::create([
                'user_id'       => $data['user_id'],
                'customer_name' => $data['customer_name'] ?? null,
                'comment'       => $data['comment'] ?? null,
                'type'          => $data['type'],
                'table_numbers' => $data['type'] === 'mesa' ? $data['table_numbers'] : [],
                'total'         => 0,
                'status'        => 'pendiente',
            ]);

            $total = $this->attachItems($order, $data['items'], $stockEnabled, $stockAllowNegative);
            $order->update(['total' => $total]);

            return $order;
        });
    }

    /**
     * Agrega items a un pedido existente creando una orden hija.
     *
     * @param  array{items: array, takeaway?: bool}  $data
     */
    public function addItemsToOrder(Order $parent, array $data, int $userId): Order
    {
        [$stockEnabled, $stockAllowNegative] = $this->stockSettings();

        if ($stockEnabled) {
            $this->assertStock($data['items'], $stockAllowNegative);
        }

        return DB::transaction(function () use ($parent, $data, $userId, $stockEnabled, $stockAllowNegative) {
            $child = Order::create([
                'user_id'         => $userId,
                'customer_name'   => $parent->customer_name,
                'comment'         => 'Agregado a la orden #'.$parent->id,
                'type'            => ($data['takeaway'] ?? false) ? 'llevar' : $parent->type,
                'table_numbers'   => $parent->table_numbers,
                'total'           => 0,
                'status'          => 'pendiente',
                'origin_order_id' => $parent->id,
            ]);

            $childTotal = $this->attachItems($child, $data['items'], $stockEnabled, $stockAllowNegative);
            $child->update(['total' => $childTotal]);

            // Incrementar total del padre solo si son del mismo tipo
            if ($child->type === $parent->type) {
                $parent->increment('total', $childTotal);
            }

            return $child;
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cobro
    // ──────────────────────────────────────────────────────────────────────────

    public function payOrder(Order $order, string $paymentMethod, string $receiptType): void
    {
        $order->update([
            'payment_method' => $paymentMethod,
            'receipt_type'   => $receiptType,
            'status'         => 'pagado',
        ]);
    }

    /**
     * Cobra todos los pedidos pendientes de una mesa (o de llevar).
     *
     * @return Collection<Order>  Pedidos cobrados.
     */
    public function payTable(string $tableKey, string $paymentMethod, string $receiptType): Collection
    {
        $orders = $this->pendingOrdersByTableKey($tableKey);

        if ($orders->isEmpty()) {
            throw new \RuntimeException('No hay pedidos pendientes en esta mesa.');
        }

        DB::transaction(function () use ($orders, $paymentMethod, $receiptType) {
            foreach ($orders as $order) {
                $order->update([
                    'payment_method' => $paymentMethod,
                    'receipt_type'   => $receiptType,
                    'status'         => 'pagado',
                ]);
            }
        });

        return $orders;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cancelación
    // ──────────────────────────────────────────────────────────────────────────

    public function cancelOrder(Order $order): void
    {
        if ($order->status === 'pagado') {
            throw new \RuntimeException('No se puede cancelar un pedido ya cobrado.');
        }

        $order->update(['status' => 'cancelado']);

        // Si es orden padre, promover hijos pendientes
        if (is_null($order->origin_order_id)) {
            $order->childOrders()->where('status', 'pendiente')->each(function ($child) use ($order) {
                $child->origin_order_id = null;
                $child->user_id         = $order->user_id;
                $child->save();
            });
        }

        // Si es orden hija, descontar del padre
        if ($order->origin_order_id && $order->type !== 'llevar') {
            $order->originOrder?->decrement('total', $order->total);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Agrupación por mesa (vista cajero)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Agrupa pedidos pendientes del día por mesa y sesión para la vista de caja.
     */
    public function groupOrdersByTableForCashier(Collection $orders): Collection
    {
        // 1. Agrupar por clave de mesa
        $grouped = collect();

        foreach ($orders as $order) {
            $tables = $order->table_numbers ?? [];
            $key    = ! empty($tables)
                ? implode(',', $tables)
                : ($order->type === 'llevar' ? 'llevar' : '');

            if (! $grouped->has($key)) {
                $grouped->put($key, collect());
            }
            $grouped->get($key)->push($order);
        }

        // 2. Dentro de cada mesa, separar sesiones
        $result = collect();

        foreach ($grouped as $tableKey => $tableOrders) {
            $sorted = $tableOrders->sortBy('created_at');

            $sessions       = collect();
            $currentSession = collect();

            foreach ($sorted as $order) {
                $isParent       = is_null($order->origin_order_id);
                $currentClosed  = $currentSession->count() > 0
                    && $currentSession->where('status', 'pendiente')->count() === 0;

                if ($isParent && $currentClosed) {
                    $sessions->push(['tableKey' => $tableKey, 'orders' => $currentSession, 'isCurrent' => false]);
                    $currentSession = collect();
                }

                $currentSession->push($order);
            }

            if ($currentSession->count() > 0) {
                $sessions->push(['tableKey' => $tableKey, 'orders' => $currentSession, 'isCurrent' => true]);
            }

            foreach ($sessions as $session) {
                $result->push($session);
            }
        }

        return $result->sortByDesc('isCurrent');
    }

    /**
     * Agrupa pedidos por mesa para la vista del mozo.
     */
    public function groupOrdersByTableForWaiter(Collection $orders): Collection
    {
        return $orders->groupBy(function ($order) {
            $tables = $order->table_numbers ?? [];

            if ($order->type === 'llevar' && empty($tables)) {
                return 'llevar';
            }

            return ! empty($tables) ? implode(',', $tables) : '';
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Validación de mesas
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Retorna las mesas ocupadas por otros mozos (excluyendo $exceptOrderId).
     */
    public function busyTablesByOtherWaiters(int $waiterId, ?int $exceptOrderId = null): array
    {
        return Order::pending()
            ->where('user_id', '!=', $waiterId)
            ->when($exceptOrderId, fn ($q) => $q->where('id', '!=', $exceptOrderId))
            ->pluck('table_numbers')
            ->flatten()
            ->map(fn ($t) => (int) $t)
            ->filter(fn ($t) => $t > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Retorna las mesas ocupadas (sin importar quién) excluyendo una orden.
     */
    public function busyTables(?int $exceptOrderId = null): array
    {
        return Order::pending()
            ->when($exceptOrderId, fn ($q) => $q->where('id', '!=', $exceptOrderId))
            ->pluck('table_numbers')
            ->flatten()
            ->map(fn ($t) => (int) $t)
            ->filter(fn ($t) => $t > 0)
            ->unique()
            ->values()
            ->all();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Impresión en cocina
    // ──────────────────────────────────────────────────────────────────────────

    public function printKitchenSafely(Order $order): void
    {
        try {
            $this->printer->printKitchenOrder($order);
        } catch (\Throwable $e) {
            Log::error('Fallo impresión cocina orden #'.$order->id.': '.$e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────────────────────

    /** Adjunta items a una orden, descuenta stock y retorna el total. */
    private function attachItems(Order $order, array $items, bool $stockEnabled, bool $allowNegative): float
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::lockForUpdate()->find($item['product_id']);
            if (! $product) {
                continue;
            }

            if ($stockEnabled) {
                if ($product->stock <= 0) {
                    throw new \RuntimeException($product->name.' (agotado)');
                }
                if (! $product->hasStockFor((int) $item['quantity'], $allowNegative)) {
                    throw new \RuntimeException($product->name.' (disponible: '.$product->stock.')');
                }
            }

            $price = $this->resolvePrice($item, $product, $order->type);

            $order->items()->create([
                'product_id' => $product->id,
                'quantity'   => (int) $item['quantity'],
                'price'      => $price,
                'comment'    => $item['comment'] ?? null,
            ]);

            if ($stockEnabled) {
                $product->decreaseStock((int) $item['quantity'], $allowNegative);
            }

            $total += $price * $item['quantity'];
        }

        return $total;
    }

    /**
     * Resuelve el precio final de un item.
     * Prioriza el precio enviado desde el frontend (para precios especiales),
     * y aplica el recargo de +1 sol para pedidos para llevar con precio > 9.
     */
    private function resolvePrice(array $item, Product $product, string $orderType): float
    {
        if (isset($item['price']) && is_numeric($item['price'])) {
            return (float) $item['price'];
        }

        $price = (float) $product->price;

        if ($orderType === 'llevar' && $price > 9) {
            $price += 1;
        }

        return $price;
    }

    /** Valida stock antes de abrir la transacción (early-fail). */
    private function assertStock(array $items, bool $allowNegative): void
    {
        $insufficient = [];

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                continue;
            }

            if ($product->stock <= 0) {
                $insufficient[] = $product->name.' (agotado)';
            } elseif (! $product->hasStockFor((int) $item['quantity'], $allowNegative)) {
                $insufficient[] = $product->name.' (disponible: '.$product->stock.')';
            }
        }

        if (! empty($insufficient)) {
            throw new \RuntimeException('Stock insuficiente para: '.implode(', ', $insufficient));
        }
    }

    private function stockSettings(): array
    {
        return [
            (bool) Setting::getValue('stock_enabled', false),
            (bool) Setting::getValue('stock_allow_negative', false),
        ];
    }

    private function pendingOrdersByTableKey(string $tableKey): Collection
    {
        return Order::pending()
            ->when($tableKey !== 'llevar', function ($q) use ($tableKey) {
                $tables = explode(',', $tableKey);
                $q->where(function ($inner) use ($tables) {
                    foreach ($tables as $t) {
                        $inner->orWhereJsonContains('table_numbers', (int) $t);
                    }
                });
            })
            ->when($tableKey === 'llevar', fn ($q) => $q->where('type', 'llevar'))
            ->get();
    }
}
