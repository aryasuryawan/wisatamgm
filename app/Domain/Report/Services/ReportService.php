<?php

namespace App\Domain\Report\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\MarketingCampaign;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Query laporan keuangan & penjualan.
 *
 * Konvensi pendapatan mengikuti System Design §4: hanya transaksi berstatus
 * `paid` yang dihitung sebagai omzet. Biaya = semua baris expenses (termasuk
 * payroll yang otomatis ter-generate).
 */
class ReportService
{
    /**
     * @param  array<int, int>|null  $branchIds  null = semua cabang
     */
    public function __construct(
        private ?array $branchIds,
        public readonly Carbon $dateFrom,
        public readonly Carbon $dateUntil,
    ) {}

    public static function make(?array $branchIds, ?string $from = null, ?string $until = null): self
    {
        return new self(
            $branchIds,
            Carbon::parse($from ?? now()->startOfMonth()),
            Carbon::parse($until ?? now()->endOfMonth()),
        );
    }

    private function paidTransactions(): Builder
    {
        return Transaction::query()
            ->where('transactions.status', 'paid')
            ->whereBetween(DB::raw('date(transactions.transaction_date)'), [$this->dateFrom->toDateString(), $this->dateUntil->toDateString()])
            ->when($this->branchIds !== null, fn ($q) => $q->whereIn('transactions.branch_id', $this->branchIds));
    }

    private function expenses(): Builder
    {
        return Expense::query()
            ->whereBetween('expense_date', [$this->dateFrom->toDateString(), $this->dateUntil->toDateString()])
            ->when($this->branchIds !== null, fn ($q) => $q->whereIn('branch_id', $this->branchIds));
    }

    public function revenue(): float
    {
        return (float) (clone $this->paidTransactions())->sum('grand_total');
    }

    public function transactionCount(): int
    {
        return (clone $this->paidTransactions())->count();
    }

    public function expenseTotal(): float
    {
        return (float) (clone $this->expenses())->sum('amount');
    }

    /**
     * @return array{revenue: float, expense: float, profit: float}
     */
    public function profitAndLoss(): array
    {
        $revenue = $this->revenue();
        $expense = $this->expenseTotal();

        return [
            'revenue' => $revenue,
            'expense' => $expense,
            'profit' => $revenue - $expense,
        ];
    }

    /** P&L per cabang (untuk perbandingan antar cabang). */
    public function perBranch(): array
    {
        $revenueByBranch = (clone $this->paidTransactions())
            ->select('branch_id', DB::raw('SUM(grand_total) as revenue'), DB::raw('COUNT(*) as transactions'))
            ->groupBy('branch_id')
            ->pluck('revenue', 'branch_id');

        $txnCountByBranch = (clone $this->paidTransactions())
            ->select('branch_id', DB::raw('COUNT(*) as transactions'))
            ->groupBy('branch_id')
            ->pluck('transactions', 'branch_id');

        $expenseByBranch = (clone $this->expenses())
            ->select('branch_id', DB::raw('SUM(amount) as expense'))
            ->groupBy('branch_id')
            ->pluck('expense', 'branch_id');

        $branches = Branch::query()
            ->when($this->branchIds !== null, fn ($q) => $q->whereIn('id', $this->branchIds))
            ->orderBy('name')
            ->get();

        return $branches->map(fn (Branch $branch) => [
            'branch' => $branch,
            'revenue' => (float) ($revenueByBranch[$branch->id] ?? 0),
            'expense' => (float) ($expenseByBranch[$branch->id] ?? 0),
            'transactions' => (int) ($txnCountByBranch[$branch->id] ?? 0),
            'profit' => (float) ($revenueByBranch[$branch->id] ?? 0) - (float) ($expenseByBranch[$branch->id] ?? 0),
        ])->all();
    }

    /** Omzet per kategori produk (transaksi paid). */
    public function salesPerCategory(): array
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->where('transactions.status', 'paid')
            ->whereBetween(DB::raw('date(transactions.transaction_date)'), [$this->dateFrom->toDateString(), $this->dateUntil->toDateString()])
            ->when($this->branchIds !== null, fn ($q) => $q->whereIn('transactions.branch_id', $this->branchIds))
            ->selectRaw('product_categories.name as category, SUM(transaction_items.qty * transaction_items.price) as total, SUM(transaction_items.qty) as qty')
            ->groupBy('product_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'qty' => (int) $row->qty,
                'total' => (float) $row->total,
            ])
            ->all();
    }

    public function topProducts(int $limit = 5): array
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->where('transactions.status', 'paid')
            ->whereBetween(DB::raw('date(transactions.transaction_date)'), [$this->dateFrom->toDateString(), $this->dateUntil->toDateString()])
            ->when($this->branchIds !== null, fn ($q) => $q->whereIn('transactions.branch_id', $this->branchIds))
            ->selectRaw('products.name as product, SUM(transaction_items.qty) as qty, SUM(transaction_items.qty * transaction_items.price) as total')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product' => $row->product,
                'qty' => (int) $row->qty,
                'total' => (float) $row->total,
            ])
            ->all();
    }

    public function topCustomers(int $limit = 5): array
    {
        return (clone $this->paidTransactions())
            ->join('customers', 'customers.id', '=', 'transactions.customer_id')
            ->selectRaw('customers.name as customer, COUNT(*) as orders, SUM(transactions.grand_total) as total')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'customer' => $row->customer,
                'orders' => (int) $row->orders,
                'total' => (float) $row->total,
            ])
            ->all();
    }

    /**
     * Utilisasi budget kampanye. Atribusi omzet per kampanye menunggu link
     * discounts↔campaign (lihat open question di progress tracker), jadi di sini
     * hanya budget vs biaya kampanye.
     */
    public function campaigns(int $limit = 10): array
    {
        return MarketingCampaign::query()
            ->with('branch')
            ->when($this->branchIds !== null, fn ($q) => $q->forBranches($this->branchIds))
            ->withSum(['expenses' => fn ($q) => $q->whereBetween('expense_date', [$this->dateFrom->toDateString(), $this->dateUntil->toDateString()])], 'amount')
            ->orderByDesc('start_date')
            ->limit($limit)
            ->get()
            ->map(fn (MarketingCampaign $campaign) => [
                'campaign' => $campaign,
                'spent' => (float) ($campaign->expenses_sum_amount ?? 0),
            ])
            ->filter(fn ($row) => $row['spent'] > 0 || $row['campaign']->budget > 0)
            ->values()
            ->all();
    }

    /** Stok rendah/habis untuk alert dashboard. */
    public function stockAlerts(): array
    {
        return [
            'low' => Product::where('is_active', true)->whereBetween('stock_quantity', [1, 5])->count(),
            'out' => Product::where('is_active', true)->where('stock_quantity', 0)->count(),
        ];
    }

    // ------------------------------------------------------- branch dashboard

    /**
     * Series harian omzet & biaya dalam periode (untuk line chart & sparkline).
     * Mengembalikan koleksi ['date' => 'Y-m-d', 'revenue' => f, 'expense' => f]
     * lengkap untuk SETIAP hari di rentang (hari kosong = 0).
     */
    public function dailySeries(): array
    {
        $revenue = (clone $this->paidTransactions())
            ->selectRaw('date(transactions.transaction_date) as d, SUM(grand_total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $expense = (clone $this->expenses())
            ->selectRaw('date(expense_date) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $series = [];
        for ($date = $this->dateFrom->copy(); $date->lte($this->dateUntil); $date->addDay()) {
            $key = $date->toDateString();
            $series[] = [
                'date' => $key,
                'revenue' => (float) ($revenue[$key] ?? 0),
                'expense' => (float) ($expense[$key] ?? 0),
            ];
        }

        return $series;
    }

    /** Distribusi omzet kategori (untuk donut chart). */
    public function categoryDistribution(): array
    {
        $rows = $this->salesPerCategory();
        $total = array_sum(array_column($rows, 'total'));

        return array_map(function ($row) use ($total) {
            $row['pct'] = $total > 0 ? round($row['total'] / $total * 100, 1) : 0;

            return $row;
        }, $rows);
    }

    /**
     * Pola kunjungan: transaksi lunas per hari dalam minggu dan per jam.
     */
    public function busiestPattern(): array
    {
        $byDay = (clone $this->paidTransactions())
            ->selectRaw('date(transactions.transaction_date) as d, COUNT(*) as c')
            ->groupBy('d')
            ->get()
            ->mapWithKeys(fn ($row) => [
                // 0=Minggu .. 6=Sabtu (Carbon dayOfWeek)
                Carbon::parse($row->d)->dayOfWeek => (int) $row->c,
            ]);

        $byHour = (clone $this->paidTransactions())
            ->selectRaw('substr(transactions.transaction_date, 12, 2) as h, COUNT(*) as c')
            ->groupBy('h')
            ->orderBy('h')
            ->pluck('c', 'h');

        $dayLabels = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $order = [1, 2, 3, 4, 5, 6, 0];

        return [
            'days' => collect($order)
                ->map(fn ($dow) => ['label' => $dayLabels[$dow], 'count' => $byDay[$dow] ?? 0])
                ->all(),
            'hours' => collect(range(7, 21))
                ->map(fn ($h) => [
                    'label' => sprintf('%02d:00', $h),
                    'count' => (int) ($byHour[sprintf('%02d', $h)] ?? 0),
                ])
                ->all(),
        ];
    }

    /**
     * Top produk + estimasi margin. Basis biaya = rata-rata unit_cost
     * pembelian (stock_movements in); produk tanpa biaya → margin null.
     */
    public function topProductsWithMargin(int $limit = 5): array
    {
        $rows = $this->topProducts($limit);

        $names = array_column($rows, 'product');
        $products = Product::query()
            ->whereIn('name', $names)
            ->when($this->branchIds !== null, fn ($q) => $q->whereIn('branch_id', $this->branchIds))
            ->get()
            ->keyBy('name');

        foreach ($rows as &$row) {
            $product = $products[$row['product']] ?? null;

            if (! $product) {
                $row['margin_pct'] = null;

                continue;
            }

            $avgCost = DB::table('stock_movements')
                ->where('product_id', $product->id)
                ->where('type', 'in')
                ->whereNotNull('unit_cost')
                ->avg('unit_cost');

            if (! $avgCost || $row['total'] <= 0) {
                $row['margin_pct'] = null;

                continue;
            }

            $cost = (float) $avgCost * $row['qty'];
            $row['margin_pct'] = round(($row['total'] - $cost) / $row['total'] * 100, 1);
        }
        unset($row);

        return $rows;
    }

    /**
     * Produk berisiko: stok menipis/habis DAN termasuk terlaris periode ini.
     */
    public function atRiskBestsellers(): array
    {
        $bestsellerNames = array_column($this->topProducts(10), 'product');
        if ($bestsellerNames === []) {
            return [];
        }

        return Product::query()
            ->where('is_active', true)
            ->whereIn('name', $bestsellerNames)
            ->where('stock_quantity', '<=', 5)
            ->orderBy('stock_quantity')
            ->get(['id', 'name', 'stock_quantity'])
            ->map(fn ($p) => ['name' => $p->name, 'stock' => $p->stock_quantity])
            ->all();
    }
}
