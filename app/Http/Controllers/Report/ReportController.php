<?php

namespace App\Http\Controllers\Report;

use App\Domain\Report\Services\ReportService;
use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('reports.view');

        $service = $this->serviceFromRequest($request);

        return view('report.index', [
            'service' => $service,
            'branches' => Branch::orderBy('name')->get(),
            'perBranch' => $service->perBranch(),
            'salesPerCategory' => $service->salesPerCategory(),
            'topProducts' => $service->topProducts(),
            'topCustomers' => $service->topCustomers(),
            'campaigns' => $service->campaigns(),
        ]);
    }

    /**
     * Export CSV (bisa dibuka di Excel). PDF/Excel native butuh package
     * tambahan — masih open question (lihat progress tracker).
     */
    public function export(Request $request): Response
    {
        $this->authorize('reports.export');

        $service = $this->serviceFromRequest($request);

        $filename = 'laporan-'.$service->dateFrom->format('Ymd').'-'.$service->dateUntil->format('Ymd').'.csv';

        return ResponseFacade::streamDownload(function () use ($service) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Laporan Tulamben Scuba']);
            fputcsv($out, ['Rentang', $service->dateFrom->format('Y-m-d'), 's/d', $service->dateUntil->format('Y-m-d')]);
            fputcsv($out, []);
            fputcsv($out, ['Ringkasan', 'Nilai (Rp)']);
            fputcsv($out, ['Omzet', $service->revenue()]);
            fputcsv($out, ['Biaya', $service->expenseTotal()]);
            fputcsv($out, ['Laba', $service->profitAndLoss()['profit']]);
            fputcsv($out, []);

            fputcsv($out, ['Per Cabang', 'Transaksi', 'Omzet', 'Biaya', 'Laba']);
            foreach ($service->perBranch() as $row) {
                fputcsv($out, [$row['branch']->name, $row['transactions'], $row['revenue'], $row['expense'], $row['profit']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Penjualan per Kategori', 'Qty', 'Total']);
            foreach ($service->salesPerCategory() as $row) {
                fputcsv($out, [$row['category'], $row['qty'], $row['total']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Top Produk', 'Qty', 'Total']);
            foreach ($service->topProducts(10) as $row) {
                fputcsv($out, [$row['product'], $row['qty'], $row['total']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Top Pelanggan', 'Order', 'Total']);
            foreach ($service->topCustomers(10) as $row) {
                fputcsv($out, [$row['customer'], $row['orders'], $row['total']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function excel(Request $request): BinaryFileResponse
    {
        $this->authorize('reports.export');

        $service = $this->serviceFromRequest($request);

        $filename = 'laporan-'.$service->dateFrom->format('Ymd').'-'.$service->dateUntil->format('Ymd').'.xlsx';

        return Excel::download(new ReportExport($service), $filename);
    }

    /**
      * PDF rekap laporan (ringkasan + per cabang + kategori + top list).
      */
    public function pdf(Request $request): Response
    {
        $this->authorize('reports.view');

        $service = $this->serviceFromRequest($request);

        $pdf = Pdf::loadView('pdf.report', [
            'service' => $service,
            'perBranch' => $service->perBranch(),
            'salesPerCategory' => $service->salesPerCategory(),
            'topProducts' => $service->topProducts(10),
            'topCustomers' => $service->topCustomers(10),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-'.$service->dateFrom->format('Ymd').'-'.$service->dateUntil->format('Ymd').'.pdf');
    }

    private function serviceFromRequest(Request $request): ReportService
    {
        $branchIds = null;

        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id')->all();
        } elseif ($branchId = $request->input('branch_id')) {
            $branchIds = [(int) $branchId];
        }

        return ReportService::make(
            $branchIds,
            $request->input('date_from'),
            $request->input('date_until'),
        );
    }
}
