<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\CashRegister;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

   

     public function sales(Request $request)
    {
        $startDate = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        $status  = $request->input('status');
        $userId  = $request->input('user_id');

        $base = Order::query()->whereBetween('created_at', [$startDate, $endDate]);
        if ($status && $status !== 'all') {
            $base->where('status', $status);
        }
        if ($userId) {
            $base->where('user_id', $userId);
        }

        // Totales generales
        $totals = (clone $base)->selectRaw("
            COUNT(*) as orders_count,
            SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales,
            AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket,
            SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count
        ")->first();

        // Resumen por día
        $perDay = (clone $base)->selectRaw("
            DATE(created_at) as date,
            COUNT(*) as orders_count,
            SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales,
            AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket,
            SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count,
            CASE WHEN COUNT(*) = 0 THEN 0
                 ELSE (SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*))
            END as cancelled_pct
        ")->groupByRaw("DATE(created_at)")->orderBy('date', 'desc')->get();

        // Detalle de pedidos
        $orders = (clone $base)->with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')->get();

        // Resumen por producto
        $productsSummary = OrderItem::query()
            ->whereHas('order', function ($q) use ($startDate, $endDate, $status, $userId) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
                if ($status && $status !== 'all') {
                    $q->where('status', $status);
                }
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            })
            ->selectRaw("product_id, SUM(quantity) as total_quantity, SUM(quantity * price) as total_sales")
            ->groupBy('product_id')
            ->with('product')
            ->orderByDesc('total_sales')
            ->get();

        // Exportar CSV
        if ($request->boolean('export')) {
            $rows   = [];
            $rows[] = ['Fecha', 'Pedidos', 'Total ventas', 'Promedio', 'Pendientes', 'Cobrados', 'Cancelados', '% Cancelado'];
            foreach ($perDay as $row) {
                $rows[] = [
                    $row->date,
                    $row->orders_count,
                    number_format($row->total_sales, 2),
                    number_format($row->avg_ticket ?? 0, 2),
                    $row->pending_count ?? 0,
                    $row->paid_count ?? 0,
                    $row->cancelled_count ?? 0,
                    number_format($row->cancelled_pct ?? 0, 1) . '%',
                ];
            }
            $rows[] = [
                'TOTALES',
                $totals->orders_count ?? 0,
                number_format($totals->total_sales ?? 0, 2),
                number_format($totals->avg_ticket ?? 0, 2),
                $totals->pending_count ?? 0,
                $totals->paid_count ?? 0,
                $totals->cancelled_count ?? 0,
                '',
            ];

            $filename = 'ventas-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.csv';
            $csv = '';
            foreach ($rows as $r) {
                $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $r)) . "\n";
            }
            return response($csv, 200, [
                'Content-Type'        => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $mozos = \App\Models\User::whereHas('role', fn($q) => $q->where('name', 'mozo'))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.reports.sales', compact(
            'perDay', 'totals', 'orders', 'productsSummary', 'startDate', 'endDate', 'mozos'
        ));
    }

    public function cash(Request $request)
    {
        $startDate = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        $sessions = CashRegister::with('user')
            ->whereBetween('opened_at', [$startDate, $endDate])
            ->orderBy('opened_at', 'desc')
            ->get()
            ->map(function ($s) {
                $s->duration_minutes = $s->closed_at
                    ? $s->opened_at->diffInMinutes($s->closed_at)
                    : null;
                $s->balance_diff = $s->closing_balance !== null
                    ? $s->closing_balance - $s->opening_balance
                    : null;
                return $s;
            });

        $totalOpened  = $sessions->count();
        $totalClosed  = $sessions->whereNotNull('closed_at')->count();
        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'pagado')
            ->sum('total');

        return view('admin.reports.cash', compact(
            'sessions', 'startDate', 'endDate', 'totalOpened', 'totalClosed', 'totalRevenue'
        ));
    }

    public function inventory()
    {
        $products = Product::orderBy('stock')->get();

        $totalProducts  = $products->count();
        $lowStock       = $products->where('stock', '<=', 5)->where('stock', '>', 0)->count();
        $outOfStock     = $products->where('stock', '<=', 0)->count();
        $totalStockValue = $products->sum(fn($p) => $p->stock * $p->price);

        return view('admin.reports.inventory', compact(
            'products', 'totalProducts', 'lowStock', 'outOfStock', 'totalStockValue'
        ));
    }

    public function customers(Request $request)
    {
        $startDate = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        $customers = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'pagado')
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->selectRaw("
                customer_name,
                COUNT(*) as visits,
                SUM(total) as total_spent,
                AVG(total) as avg_ticket,
                MAX(created_at) as last_visit
            ")
            ->groupBy('customer_name')
            ->orderByDesc('total_spent')
            ->get();

        $totalCustomers = $customers->count();
        $totalSpent     = $customers->sum('total_spent');
        $topCustomer    = $customers->first();

        return view('admin.reports.customers', compact(
            'customers', 'startDate', 'endDate', 'totalCustomers', 'totalSpent', 'topCustomer'
        ));
    }

    public function tables(Request $request)
    {
        $startDate = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Pull paid orders with table_numbers in range
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'pagado')
            ->whereNotNull('table_numbers')
            ->get(['table_numbers', 'total', 'created_at']);

        // Aggregate per table number
        $tableStats = [];
        foreach ($orders as $order) {
            $tables = is_array($order->table_numbers) ? $order->table_numbers : [];
            foreach ($tables as $tNum) {
                if (!isset($tableStats[$tNum])) {
                    $tableStats[$tNum] = ['table' => $tNum, 'orders' => 0, 'revenue' => 0];
                }
                $tableStats[$tNum]['orders']++;
                $tableStats[$tNum]['revenue'] += $order->total;
            }
        }
        usort($tableStats, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        $tableStats = collect($tableStats);

        $totalOrders  = $tableStats->sum('orders');
        $totalRevenue = $tableStats->sum('revenue');
        $busyTable    = $tableStats->first();

        return view('admin.reports.tables', compact(
            'tableStats', 'startDate', 'endDate', 'totalOrders', 'totalRevenue', 'busyTable'
        ));
    }

    public function kitchen(Request $request)
    {
        $startDate = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        $ordersWithTime = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('preparation_seconds')
            ->where('preparation_seconds', '>', 0)
            ->selectRaw("
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                AVG(preparation_seconds) as avg_seconds,
                MIN(preparation_seconds) as min_seconds,
                MAX(preparation_seconds) as max_seconds
            ")
            ->groupByRaw("DATE(created_at)")
            ->orderBy('date', 'desc')
            ->get();

        $globalAvg = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('preparation_seconds')
            ->where('preparation_seconds', '>', 0)
            ->avg('preparation_seconds');

        $slowOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('preparation_seconds')
            ->where('preparation_seconds', '>', 900) // > 15 min
            ->with('user')
            ->orderByDesc('preparation_seconds')
            ->limit(10)
            ->get();

        return view('admin.reports.kitchen', compact(
            'ordersWithTime', 'globalAvg', 'slowOrders', 'startDate', 'endDate'
        ));
    }

    public function profit(Request $request)
    {
        $startDate = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfYear()->startOfDay();
        $endDate = $request->filled('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        $byMonth = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'pagado')
            ->selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as orders_count,
                SUM(total) as revenue,
                AVG(total) as avg_ticket
            ")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        $totalRevenue = $byMonth->sum('revenue');
        $totalOrders  = $byMonth->sum('orders_count');
        $bestMonth    = $byMonth->sortByDesc('revenue')->first();

        // Top selling products (by revenue) in period
        $topProducts = OrderItem::query()
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'pagado'))
            ->selectRaw("product_id, SUM(quantity) as qty, SUM(quantity * price) as revenue")
            ->groupBy('product_id')
            ->with('product')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return view('admin.reports.profit', compact(
            'byMonth', 'totalRevenue', 'totalOrders', 'bestMonth', 'topProducts', 'startDate', 'endDate'
        ));
    }
}
