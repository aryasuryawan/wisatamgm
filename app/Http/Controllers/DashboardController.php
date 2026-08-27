<?php

namespace App\Http\Controllers;

use App\Domain\Report\Services\ReportService;
use App\Models\Product;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const PERIODS = ['month', 'last_month', 'year'];

    public function index(Request $request): View
    {
        $branchIds = null;

        if (auth()->user()?->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id')->all();
        }

        $period = in_array($request->input('period'), self::PERIODS, true)
            ? $request->input('period')
            : 'month';

        [$periodFrom, $periodUntil] = match ($period) {
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $todayReport = ReportService::make($branchIds, now()->startOfDay(), now()->endOfDay());
        $yesterdayReport = ReportService::make($branchIds, now()->subDay()->startOfDay(), now()->subDay()->endOfDay());
        $periodReport = ReportService::make($branchIds, $periodFrom, $periodUntil);
        $pl = $periodReport->profitAndLoss();

        // Previous period (same duration, immediately before current period)
        $periodDays = $periodFrom->diffInDays($periodUntil);
        $prevPeriodUntil = $periodFrom->subDay()->endOfDay();
        $prevPeriodFrom = $prevPeriodUntil->copy()->subDays($periodDays)->startOfDay();
        $prevPeriodReport = ReportService::make($branchIds, $prevPeriodFrom, $prevPeriodUntil);

        // Kartu pembanding tetap: tahun ini / bulan ini / bulan lalu.
        $yearReport = ReportService::make($branchIds, now()->startOfYear(), now()->endOfYear());
        $thisMonthReport = ReportService::make($branchIds, now()->startOfMonth(), now()->endOfMonth());
        $lastMonthReport = ReportService::make($branchIds, now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth());

        // Delta helpers
        $deltaToday = $yesterdayReport->revenue() > 0
            ? round(($todayReport->revenue() - $yesterdayReport->revenue()) / $yesterdayReport->revenue() * 100, 1)
            : null;
        $prevPL = $prevPeriodReport->profitAndLoss();
        $deltaRevenue = $prevPL['revenue'] > 0
            ? round(($pl['revenue'] - $prevPL['revenue']) / $prevPL['revenue'] * 100, 1)
            : null;
        $deltaExpense = $prevPL['expense'] > 0
            ? round(($pl['expense'] - $prevPL['expense']) / $prevPL['expense'] * 100, 1)
            : null;
        $deltaProfit = $prevPL['profit'] != 0
            ? round(($pl['profit'] - $prevPL['profit']) / abs($prevPL['profit']) * 100, 1)
            : null;

        return view('dashboard.index', [
            'period' => $period,
            'revenueToday' => $todayReport->revenue(),
            'transactionsToday' => $todayReport->transactionCount(),
            'pl' => $pl,
            'periodTransactions' => $periodReport->transactionCount(),
            'deltaToday' => $deltaToday,
            'deltaRevenue' => $deltaRevenue,
            'deltaExpense' => $deltaExpense,
            'deltaProfit' => $deltaProfit,
            'comparison' => [
                'year' => ['revenue' => $yearReport->revenue(), 'transactions' => $yearReport->transactionCount()],
                'month' => ['revenue' => $thisMonthReport->revenue(), 'transactions' => $thisMonthReport->transactionCount()],
                'last_month' => ['revenue' => $lastMonthReport->revenue(), 'transactions' => $lastMonthReport->transactionCount()],
            ],
            'perBranch' => $branchIds === null ? $periodReport->perBranch() : [],
            'lowStockCount' => Product::where('is_active', true)->whereBetween('stock_quantity', [1, 5])->count(),
            'outOfStockCount' => Product::where('is_active', true)->where('stock_quantity', 0)->count(),
            'schedulesWithoutStaff' => Schedule::whereIn('status', ['draft', 'confirmed'])
                ->upcoming()
                ->whereBetween('date_start', [now(), now()->addDays(7)])
                ->when($branchIds !== null, fn ($q) => $q->forBranches($branchIds))
                ->doesntHave('staff')
                ->with(['product', 'branch'])
                ->orderBy('date_start')
                ->limit(5)
                ->get(),
            'myBranches' => auth()->user()?->branches ?? collect(),
        ]);
    }
}
