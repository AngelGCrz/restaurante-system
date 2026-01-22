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

        // Default: exclude canceled orders unless explicitly requesting 'all'
        $statusFilter = $request->input('status');
        if (! $statusFilter || $statusFilter !== 'all') {
            $baseQuery->where('status', '!=', 'cancelado');
        }
        if ($statusFilter && in_array($statusFilter, ['pendiente', 'pagado'])) {
            $baseQuery->where('status', $statusFilter);
        }

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
                DB::raw('SUM(total) as total_sales'),
                DB::raw('AVG(total) as avg_ticket'),
                DB::raw("SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count")
            )
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();
        } else {
            $perDay = $baseQuery->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_sales'), DB::raw('AVG(total) as avg_ticket'))
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get();
        }

        if ($breakdown) {
            $totals = $baseQuery->select(
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('AVG(total) as avg_ticket'),
                DB::raw("SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN status = 'pagado' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_count")
            )->first();
        } else {
            $totals = $baseQuery->select(DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_sales'), DB::raw('AVG(total) as avg_ticket'))->first();
        }

        // Export CSV if requested
        if ($request->boolean('export')) {
            $rows = [];
            $rows[] = ['Fecha', 'Pedidos', 'Total ventas', 'Promedio por ticket'];
            foreach ($perDay as $row) {
                $rows[] = [
                    $row->date,
                    $row->orders_count,
                    number_format($row->total_sales, 2),
                    number_format($row->avg_ticket, 2),
                ];
            }
            $rows[] = ['TOTALES', $totals->orders_count ?? 0, number_format($totals->total_sales ?? 0, 2), number_format($totals->avg_ticket ?? 0, 2)];

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
