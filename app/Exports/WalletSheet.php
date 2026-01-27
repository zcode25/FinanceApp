<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WalletSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    protected $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function view(): View
    {
        return view('exports.wallet_excel', [
            'wallet' => $this->report['wallet'],
            'summary' => $this->report['summary'],
            'transactions' => $this->report['transactions']
        ]);
    }

    public function title(): string
    {
        // Excel sheet names have length limits and invalid chars
        return substr(str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $this->report['wallet']['name']), 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}
