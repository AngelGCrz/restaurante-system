<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
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
        $start = $request->input('start');
        $end = $request->input('end');

        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfDay();

        $baseQuery = Order::query()->whereBetween('created_at', [$startDate, $endDate]);

        // Handle status filter explicitly:
        // - null/empty => default: exclude 'cancelado'
        // - 'all' => include all statuses
        // - specific status (pendiente|pagado|cancelado) => filter by that status
        $statusFilter = $request->input('status');
        if ($statusFilter && $statusFilter !== 'all') {
            if (in_array($statusFilter, ['pendiente', 'pagado', 'cancelado'])) {
                $baseQuery->where('status', $statusFilter);
            }
        }
        // $statusFilter = $request->input('status');
        // if ($statusFilter === null || $statusFilter === '') {
        //     $baseQuery->where('status', '!=', 'cancelado');
        // } elseif ($statusFilter === 'all') {
        //     // no-op, include all statuses
        // } elseif (in_array($statusFilter, ['pendiente', 'pagado', 'cancelado'])) {
        //     $baseQuery->where('status', $statusFilter);
        // }

        // Optional filter by user (mozo)
        $userId = $request->input('user_id');
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }

        // Whether to include breakdown columns per status
        $breakdown = (bool) $request->boolean('breakdown');

        if ($breakdown) {
            $perDay = $baseQuery->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                // total_sales: sum only orders that are pagado
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales"),
                // avg_ticket: average only over pagado orders
                DB::raw("AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket"),
                DB::raw("SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count"),
                DB::raw("CASE WHEN COUNT(*) = 0 THEN 0 ELSE (SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) END as cancelled_pct")
            )
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();
        } else {
            $perDay = $baseQuery->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales"),
                DB::raw("AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket"),
                DB::raw("SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count"),
                DB::raw("CASE WHEN COUNT(*) = 0 THEN 0 ELSE (SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) END as cancelled_pct")
            )
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get();
        }

        if ($breakdown) {
            $totals = $baseQuery->select(
                DB::raw('COUNT(*) as orders_count'),
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales"),
                DB::raw("AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket"),
                DB::raw("SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count"),
                DB::raw("CASE WHEN COUNT(*) = 0 THEN 0 ELSE (SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) END as cancelled_pct")
            )->first();
        } else {
            $totals = $baseQuery->select(
                DB::raw('COUNT(*) as orders_count'),
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales"),
                DB::raw("AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket"),
                DB::raw("SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count"),
                DB::raw("CASE WHEN COUNT(*) = 0 THEN 0 ELSE (SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) END as cancelled_pct")
            )->first();
        }

        // Export CSV if requested
        if ($request->boolean('export')) {
            $rows = [];
            // Header depends on breakdown
            if ($breakdown) {
                $rows[] = ['Fecha', 'Pedidos', 'Total ventas', 'Venta promedio por pedido', 'Pendiente', 'Cobrado', 'Cancelado', 'Cancelación %'];
            } else {
                $rows[] = ['Fecha', 'Pedidos', 'Total ventas', 'Venta promedio por pedido', 'Cancelación %'];
            }

            foreach ($perDay as $row) {
                if ($breakdown) {
                    $rows[] = [
                        $row->date,
                        $row->orders_count,
                        number_format($row->total_sales, 2),
                        number_format($row->avg_ticket, 2),
                        $row->pending_count ?? 0,
                        $row->paid_count ?? 0,
                        $row->cancelled_count ?? 0,
                            number_format($row->cancelled_pct ?? 0, 2) . '%',
                    ];
                } else {
                    $rows[] = [
                        $row->date,
                        $row->orders_count,
                        number_format($row->total_sales, 2),
                        number_format($row->avg_ticket, 2),
                            number_format($row->cancelled_pct ?? 0, 2) . '%',
                    ];
                }
            }

            // Totals row
            if ($breakdown) {
                $rows[] = [
                    'TOTALES',
                    $totals->orders_count ?? 0,
                    number_format($totals->total_sales ?? 0, 2),
                    number_format($totals->avg_ticket ?? 0, 2),
                    $totals->pending_count ?? 0,
                    $totals->paid_count ?? 0,
                    $totals->cancelled_count ?? 0,
                    number_format($totals->cancelled_pct ?? 0, 2) . '%',
                ];
            } else {
                $rows[] = ['TOTALES', $totals->orders_count ?? 0, number_format($totals->total_sales ?? 0, 2), number_format($totals->avg_ticket ?? 0, 2), number_format($totals->cancelled_pct ?? 0, 2) . '%'];
            }

            $filename = 'sales-report-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.csv';
            $csv = '';
            foreach ($rows as $r) {
                $csv .= implode(',', array_map(function ($v) {
                    return '"' . str_replace('"', '""', $v) . '"';
                }, $r)) . "\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return view('admin.reports.sales', compact('perDay', 'totals', 'startDate', 'endDate'));
    }

     public function salesdetallada(Request $request)
    {
        // Implementation for detailed sales report would go here
        $start = $request->input('start');
    $end = $request->input('end');

    $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->startOfMonth();
    $endDate   = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfDay();

    $baseQuery = Order::query()->whereBetween('created_at', [$startDate, $endDate]);

    // Filtro por estado
    $status = $request->input('status');
    if ($status && $status !== 'all') {
        $baseQuery->where('status', $status);
    }

    // Filtro por mozo
    $userId = $request->input('user_id');
    if ($userId) {
        $baseQuery->where('user_id', $userId);
    }

    $breakdown = (bool) $request->boolean('breakdown');

    // 🔹 Totales generales
    $totals = $baseQuery->clone()->selectRaw("
        COUNT(*) as orders_count,
        SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales,
        AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket,
        SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count
    ")->first();

    // 🔹 Resumen por día
    $perDay = $baseQuery->clone()
        ->selectRaw("
            DATE(created_at) as date,
            COUNT(*) as orders_count,
            SUM(CASE WHEN status = 'pagado' THEN total ELSE 0 END) as total_sales,
            AVG(CASE WHEN status = 'pagado' THEN total ELSE NULL END) as avg_ticket,
            SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count,
            (SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as cancelled_pct
        ")
        ->groupByRaw("DATE(created_at)")
        ->orderBy('date', 'desc')
        ->get();

    // 🔥 DETALLE DE PEDIDOS
    $orders = $baseQuery->clone()
        ->with(['user', 'orderItems.product'])
        ->orderBy('created_at', 'desc')
        ->get();

    // 🔥 RESUMEN POR PRODUCTO
    $productsSummary = \App\Models\OrderItem::query()
        ->whereHas('order', function ($q) use ($startDate, $endDate, $status, $userId) {
            $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
            if ($status && $status !== 'all') $q->where('status', $status);
            if ($userId) $q->where('user_id', $userId);
        })
        ->selectRaw("
            product_id,
            SUM(quantity) as total_quantity,
            SUM(quantity * price) as total_sales
        ")
        ->groupBy('product_id')
        ->with('product')
        ->orderByDesc('total_sales')
        ->get();

    // 📤 Exportar CSV
    if ($request->boolean('export')) {
        $rows = [];
        $rows[] = ['Fecha', 'Pedidos', 'Total ventas', 'Venta promedio', 'Pendiente', 'Cobrado', 'Cancelado', '% Cancelado'];

        foreach ($perDay as $row) {
            $rows[] = [
                $row->date,
                $row->orders_count,
                number_format($row->total_sales, 2),
                number_format($row->avg_ticket, 2),
                $row->pending_count ?? 0,
                $row->paid_count ?? 0,
                $row->cancelled_count ?? 0,
                number_format($row->cancelled_pct ?? 0, 2) . '%',
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

        $filename = 'sales-report-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.csv';
        $csv = '';
        foreach ($rows as $r) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $r)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    return view('admin.reports.salesdetallada', compact(
        'perDay',
        'totals',
        'orders',
        'productsSummary',
        'startDate',
        'endDate'
    ));
    }

    public function cash()
    {
        return view('admin.reports.cash');
    }

    public function inventory()
    {
        return view('admin.reports.inventory');
    }

    public function customers()
    {
        return view('admin.reports.customers');
    }

    public function tables()
    {
        return view('admin.reports.tables');
    }

    public function kitchen()
    {
        return view('admin.reports.kitchen');
    }

    public function profit()
    {
        return view('admin.reports.profit');
    }
}
