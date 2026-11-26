<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CashTransaction;
use App\Services\CommunityReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly CommunityReport $report)
    {
    }

    public function ai()
    {
        return view('reports.ai', ['report' => $this->report->data()]);
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:attendance,cash'],
            'format' => ['required', 'in:pdf,csv'],
        ]);
        $type = $data['type'];
        $records = $type === 'attendance'
            ? Attendance::with(['member', 'schedule'])->orderByDesc('attendance_date')->get()
            : CashTransaction::with('member')->orderByDesc('transaction_date')->get();
        $filename = $type === 'attendance' ? 'laporan-kehadiran' : 'laporan-kas';

        if ($data['format'] === 'pdf') {
            return Pdf::loadView('reports.pdf', compact('records', 'type'))
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf');
        }

        $headers = $type === 'attendance'
            ? ['Tanggal', 'Nama Anggota', 'Jadwal', 'Status']
            : ['Tanggal', 'Jenis', 'Kategori', 'Nominal', 'Anggota', 'Keterangan'];
        $callback = static function () use ($records, $type, $headers) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, ';');
            foreach ($records as $record) {
                fputcsv($output, $type === 'attendance'
                    ? [$record->attendance_date->format('Y-m-d'), $record->member->full_name, $record->schedule->location, $record->status]
                    : [$record->transaction_date->format('Y-m-d'), $record->type, $record->category, $record->amount, $record->member?->full_name ?? '', $record->description ?? ''], ';');
            }
            fclose($output);
        };

        return response()->streamDownload($callback, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
