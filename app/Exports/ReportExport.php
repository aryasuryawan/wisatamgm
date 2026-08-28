<?php

namespace App\Exports;

use App\Domain\Report\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private readonly ReportService $service)
    {
    }

    public function array(): array
    {
        $s = $this->service;
        $pl = $s->profitAndLoss();

        $rows = [];

        $rows[] = ['Laporan Tulamben Scuba & ScubaGo'];
        $rows[] = ['Rentang', $s->dateFrom->format('Y-m-d'), 's/d', $s->dateUntil->format('Y-m-d')];
        $rows[] = [];
        $rows[] = ['Ringkasan', 'Nilai (Rp)'];
        $rows[] = ['Omzet', $s->revenue()];
        $rows[] = ['Biaya', $s->expenseTotal()];
        $rows[] = ['Laba', $pl['profit']];
        $rows[] = ['Transaksi Lunas', $s->transactionCount()];
        $rows[] = [];

        $rows[] = ['Per Cabang', 'Transaksi', 'Omzet', 'Biaya', 'Laba'];
        foreach ($s->perBranch() as $row) {
            $rows[] = [$row['branch']->name, $row['transactions'], $row['revenue'], $row['expense'], $row['profit']];
        }
        $rows[] = [];

        $rows[] = ['Penjualan per Kategori', 'Qty', 'Total'];
        foreach ($s->salesPerCategory() as $row) {
            $rows[] = [$row['category'], $row['qty'], $row['total']];
        }
        $rows[] = [];

        $rows[] = ['Top Produk', 'Qty', 'Total'];
        foreach ($s->topProducts(10) as $row) {
            $rows[] = [$row['product'], $row['qty'], $row['total']];
        }
        $rows[] = [];

        $rows[] = ['Top Pelanggan', 'Order', 'Total'];
        foreach ($s->topCustomers(10) as $row) {
            $rows[] = [$row['customer'], $row['orders'], $row['total']];
        }
        $rows[] = [];

        $rows[] = ['Kampanye', 'Budget', 'Terpakai', 'Persen'];
        foreach ($s->campaigns() as $row) {
            $pct = $row['campaign']->budget > 0 ? round($row['spent'] / $row['campaign']->budget * 100) : 0;
            $rows[] = [$row['campaign']->name, $row['campaign']->budget, $row['spent'], $pct.'%'];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold header rows: 1, 4, 9, etc. Simplify: first row and section headers
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            4 => ['font' => ['bold' => true]],
            9 => ['font' => ['bold' => true]],
            11 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']]],
        ];
    }

    public function title(): string
    {
        return 'Laporan '.$this->service->dateFrom->format('Ymd').'-'.$this->service->dateUntil->format('Ymd');
    }
}
