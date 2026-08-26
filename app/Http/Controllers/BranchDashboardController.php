<?php

namespace App\Http\Controllers;

use App\Domain\Report\Services\ReportService;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BranchDashboardController extends Controller
{
    /**
     * Daftar cabang sebagai pintu masuk dashboard per-cabang.
     */
    public function index(): View
    {
        $this->authorize('dashboard.view');

        $service = ReportService::make(null, now()->startOfMonth(), now()->endOfDay());

        return view('dashboard.branches', [
            'perBranch' => $service->perBranch(),
        ]);
    }

    public function show(Request $request, Branch $branch): View
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            $allowed = auth()->user()->branches()->pluck('branches.id')->contains($branch->id);
            abort_unless($allowed, 403);
        }

        [$from, $until] = $this->resolveRange($request);

        $current = ReportService::make([$branch->id], $from->toDateString(), $until->toDateString());
        $pl = $current->profitAndLoss();

        $compare = $request->boolean('compare');
        $prev = null;
        $prevPl = null;
        if ($compare) {
            // Panjang periode dalam hari utuh, dibandingkan dgn window tepat sebelumnya.
            $length = (int) $from->copy()->startOfDay()->diffInDays($until->copy()->startOfDay()) + 1;
            $prevFrom = $from->copy()->subDays($length)->startOfDay();
            $prevUntil = $from->copy()->subDay()->endOfDay();
            $prev = ReportService::make([$branch->id], $prevFrom->toDateString(), $prevUntil->toDateString());
            $prevPl = $prev->profitAndLoss();
        }

        $series = $current->dailySeries();
        $categories = $current->categoryDistribution();
        $busiest = $current->busiestPattern();
        $topWithMargin = $current->topProductsWithMargin(5);
        $atRisk = $current->atRiskBestsellers();

        return view('dashboard.branch', [
            'branch' => $branch,
            'branches' => Branch::orderBy('name')->get(),
            'from' => $from,
            'until' => $until,
            'compare' => $compare,
            'pl' => $pl,
            'deltas' => [
                'revenue' => $this->pct($pl['revenue'], $prevPl['revenue'] ?? null),
                'expense' => $this->pct($pl['expense'], $prevPl['expense'] ?? null),
                'profit' => $this->pct($pl['profit'], $prevPl['profit'] ?? null),
            ],
            'txnCount' => $current->transactionCount(),
            'sparkRevenue' => array_column($series, 'revenue'),
            'trendChart' => $this->trendChartConfig($series),
            'categoryChart' => $this->categoryChartConfig($categories),
            'busiest' => $busiest,
            'dayChart' => $this->barChartConfig('Hari tersibuk', array_column($busiest['days'], 'label'), array_column($busiest['days'], 'count')),
            'hourChart' => $this->barChartConfig('Jam tersibuk', array_column($busiest['hours'], 'label'), array_column($busiest['hours'], 'count')),
            'categories' => $categories,
            'topProducts' => $current->topProducts(5),
            'lowMargin' => $this->lowestMargin(array_merge($topWithMargin, $current->topProductsWithMargin(20))),
            'insights' => $this->buildInsights($pl, $prevPl, $categories, $atRisk),
            'alerts' => $this->buildAlerts($branch),
            'recentTransactions' => Transaction::with(['customer', 'branch'])
                ->where('branch_id', $branch->id)
                ->orderByDesc('transaction_date')
                ->limit(10)
                ->get(),
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $preset = $request->input('preset', 'month');

        $range = match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfDay()],
            'prev_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->input('date_from', now()->startOfMonth())),
                Carbon::parse($request->input('date_until', now())),
            ],
            default => [now()->startOfMonth(), now()->endOfDay()],
        };

        if ($range[0]->gt($range[1])) {
            [$range[0], $range[1]] = [$range[1], $range[0]];
        }

        return [$range[0], $range[1]];
    }

    private function pct(float $current, ?float $previous): ?float
    {
        if ($previous === null || $previous == 0.0) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    private function lowestMargin(array $rows, int $limit = 5): array
    {
        return collect($rows)
            ->filter(fn ($r) => $r['margin_pct'] !== null)
            ->sortBy('margin_pct')
            ->unique('product')
            ->take($limit)
            ->values()
            ->all();
    }

    private function trendChartConfig(array $series): array
    {
        return [
            'type' => 'line',
            'data' => [
                'labels' => array_column($series, 'date'),
                'datasets' => [
                    [
                        'label' => 'Omzet',
                        'data' => array_column($series, 'revenue'),
                        'borderColor' => '#2fb344',
                        'backgroundColor' => 'rgba(47,179,68,.08)',
                        'fill' => true,
                        'tension' => 0.35,
                        'pointRadius' => 0,
                    ],
                    [
                        'label' => 'Biaya',
                        'data' => array_column($series, 'expense'),
                        'borderColor' => '#d63939',
                        'tension' => 0.35,
                        'pointRadius' => 0,
                    ],
                    [
                        'label' => 'Laba',
                        'data' => array_map(fn ($r) => $r['revenue'] - $r['expense'], $series),
                        'borderColor' => '#206bc4',
                        'tension' => 0.35,
                        'pointRadius' => 0,
                    ],
                ],
            ],
            'options' => [
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'plugins' => ['legend' => ['position' => 'bottom']],
                'scales' => [
                    'y' => ['ticks' => ['callback' => 'function(v){return "Rp"+Number(v).toLocaleString("id-ID")}']],
                ],
            ],
        ];
    }

    private function categoryChartConfig(array $categories): array
    {
        $palette = ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#4299e1', '#f1b963'];

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_column($categories, 'category'),
                'datasets' => [[
                    'data' => array_column($categories, 'total'),
                    'backgroundColor' => $palette,
                ]],
            ],
            'options' => [
                'cutout' => '62%',
                'plugins' => ['legend' => ['position' => 'bottom']],
            ],
        ];
    }

    private function barChartConfig(string $label, array $labels, array $data): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => $label,
                    'data' => $data,
                    'backgroundColor' => '#206bc4cc',
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
            ],
        ];
    }

    /**
     * Insight naratif dari rule sederhana (tanpa AI).
     */
    private function buildInsights(array $pl, ?array $prevPl, array $categories, array $atRisk): array
    {
        $insights = [];

        if ($prevPl) {
            $revDelta = $this->pct($pl['revenue'], $prevPl['revenue']);
            $profitDelta = $this->pct($pl['profit'], $prevPl['profit']);

            if ($revDelta !== null && abs($revDelta) >= 5) {
                $driver = $categories[0]['category'] ?? null;
                $insights[] = [
                    'level' => $revDelta > 0 ? 'success' : 'danger',
                    'text' => 'Omzet '.($revDelta > 0 ? 'naik' : 'turun').' '.abs($revDelta).
                        '% dibanding periode sebelumnya'.($driver ? ", didorong kategori {$driver}." : '.'),
                ];
            }

            if ($revDelta !== null && $revDelta > 5 && $profitDelta !== null && $profitDelta < 0) {
                $insights[] = [
                    'level' => 'warning',
                    'text' => 'Omzet naik tapi laba turun — periksa kenaikan biaya di menu Keuangan.',
                ];
            }
        }

        if ($atRisk !== []) {
            $names = implode(', ', array_column(array_slice($atRisk, 0, 3), 'name'));
            $insights[] = [
                'level' => 'danger',
                'text' => count($atRisk).' produk terlaris stoknya menipis/habis: '.$names.'. Segera restock.',
            ];
        }

        return $insights;
    }

    private function buildAlerts(Branch $branch): array
    {
        $lowStock = Product::where('is_active', true)
            ->whereBetween('stock_quantity', [1, 5])
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branch->id))
            ->orderBy('stock_quantity')
            ->get(['id', 'name', 'stock_quantity']);

        $outStock = Product::where('is_active', true)
            ->where('stock_quantity', 0)
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branch->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $schedulesWithoutStaff = Schedule::whereIn('status', ['draft', 'confirmed'])
            ->upcoming()
            ->whereBetween('date_start', [now(), now()->addDays(7)])
            ->where('branch_id', $branch->id)
            ->doesntHave('staff')
            ->with('product')
            ->orderBy('date_start')
            ->get(['id', 'product_id', 'date_start']);

        return [
            ['level' => 'danger', 'key' => 'out', 'title' => __('ui.out_of_stock_products'), 'items' => $outStock],
            ['level' => 'warning', 'key' => 'low', 'title' => __('ui.low_stock_products'), 'items' => $lowStock],
            ['level' => 'danger', 'key' => 'nostaff', 'title' => __('ui.schedules_without_guide'), 'items' => $schedulesWithoutStaff],
        ];
    }
}
