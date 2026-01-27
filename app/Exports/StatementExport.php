<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StatementExport implements WithMultipleSheets
{
    protected $reports;

    public function __construct($reports)
    {
        $this->reports = $reports;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->reports as $report) {
            $sheets[] = new WalletSheet($report);
        }

        return $sheets;
    }
}
