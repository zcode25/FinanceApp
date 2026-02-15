<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ __('monthly_financial_statement') }}</title>
    <style>
        @page {
            margin: 100px 25px 60px 25px;
            /* Top Bottom margins for fixed header/footer */
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            /* Margins handled by @page */
        }

        .header {
            position: fixed;
            top: -70px;
            /* Position in top margin */
            left: 0;
            right: 0;
            height: 60px;
            width: 100%;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            text-align: left;
            vertical-align: bottom;
            padding-bottom: 0px;
            /* Reduced padding since border is gone */
        }

        .header-right {
            text-align: right;
            vertical-align: bottom;
            padding-bottom: 0px;
        }

        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #111;
            font-weight: bold;
            color: #111;
        }

        .meta-text {
            font-size: 9px;
            color: #666;
            margin: 2px 0;
        }

        .footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        /* ... Rest of styles ... */
        .wallet-section {
            margin-bottom: 40px;
            /* page-break-inside: avoid; REMOVED to prevent blank first page */
        }

        .wallet-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .wallet-title {
            font-size: 16px;
            font-weight: bold;
            color: #111;
            margin: 0 0 5px 0;
        }

        .wallet-currency {
            font-size: 11px;
            color: #666;
            font-size: 11px;
            color: #666;
            letter-spacing: 0.5px;
        }

        .summary-table {
            width: 100%;
            margin-top: 10px;
            table-layout: fixed;
        }

        .summary-table td {
            vertical-align: top;
            padding-right: 20px;
        }

        .summary-label {
            display: block;
            font-size: 9px;
            color: #888;
            font-size: 9px;
            color: #888;
            margin-bottom: 2px;
        }

        .summary-value {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #111;
        }

        .text-green {
            color: #10b981;
        }

        .text-red {
            color: #ef4444;
        }

        .text-blue {
            color: #2563eb;
        }

        table.tx-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.tx-table th {
            background-color: #f1f5f9;
            padding: 10px 8px;
            text-align: left;
            font-size: 9px;
            color: #64748b;
            border-bottom: 2px solid #cbd5e1;
        }

        table.tx-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            color: #334155;
        }

        .text-right {
            text-align: right;
        }

        table.tx-table th.text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .italic {
            font-style: italic;
            color: #94a3b8;
        }

        .row-opening {
            background-color: #f8fafc;
        }

        .row-closing {
            background-color: #f1f5f9;
            border-top: 2px solid #e2e8f0;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="brand-title">VibeFinance</div>
                </td>
                <td class="header-right">
                    <div class="meta-text">{{ __('period_label') }} {{ $period }}</div>
                    <div class="meta-text">{{ __('generated_on') }} {{ $generated_at }}</div>
                </td>
            </tr>
        </table>
    </div>

    @foreach($reports as $report)
        <div class="wallet-section" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
            <!-- Wallet Card & Summary -->
            <div class="wallet-card">
                <h3 class="wallet-title">{{ $report['wallet']['name'] }}</h3>
                <div class="wallet-currency">{{ $report['wallet']['currency'] }} {{ __('wallet') }}</div>

                <table class="summary-table">
                    <tr>
                        <td>
                            <span class="summary-label">{{ __('opening_balance') }}</span>
                            <span class="summary-value">{{ number_format($report['summary']['opening_balance'], 0) }}</span>
                        </td>
                        <td>
                            <span class="summary-label">{{ __('income') }}</span>
                            <span
                                class="summary-value text-green">+{{ number_format($report['summary']['income'], 0) }}</span>
                        </td>
                        <td>
                            <span class="summary-label">{{ __('expense') }}</span>
                            <span class="summary-value">-{{ number_format($report['summary']['expense'], 0) }}</span>
                        </td>
                        <td>
                            <span class="summary-label">{{ __('closing_balance') }}</span>
                            <span class="summary-value">{{ number_format($report['summary']['closing_balance'], 0) }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Transaction Table -->
            <table class="tx-table">
                <thead>
                    <tr>
                        <th width="15%">{{ __('date') }}</th>
                        <th width="40%">{{ __('description') }}</th>
                        <th width="15%">{{ __('category') }}</th>
                        <th width="15%" class="text-right">{{ __('amount') }}</th>
                        <th width="15%" class="text-right">{{ __('balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance -->
                    <tr class="row-opening">
                        <td class="font-bold text-blue">{{ __('opening') }}</td>
                        <td colspan="3" class="italic">{{ __('brought_forward') }}</td>
                        <td class="text-right font-bold text-blue">
                            {{ number_format($report['summary']['opening_balance'], 0) }}
                        </td>
                    </tr>

                    @forelse($report['transactions'] as $tx)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tx['date'])->locale(app()->getLocale())->isoFormat('D MMM Y') }}</td>
                            <td>{{ $tx['description'] }}</td>
                            <td>
                                <span
                                    style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #475569; font-size: 9px; font-weight: bold;">
                                    {{ $tx['category']['name'] }}
                                </span>
                            </td>
                            <td class="text-right"
                                style="font-weight: bold; {{ $tx['type'] === 'income' ? 'color: #10b981;' : '' }}">
                                {{ $tx['type'] === 'income' ? '+' : '-' }} {{ number_format($tx['amount'], 0) }}
                            </td>
                            <td class="text-right font-bold" style="color: #334155;">
                                {{ number_format($tx['running_balance'], 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">
                                {{ __('no_transactions_on_period') }}
                            </td>
                        </tr>
                    @endforelse

                    <!-- Closing Balance -->
                    <tr class="row-closing">
                        <td class="font-bold">{{ __('closing') }}</td>
                        <td colspan="3" class="italic">{{ __('ending_balance_period') }}</td>
                        <td class="text-right font-bold">
                            {{ number_format($report['summary']['closing_balance'], 0) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Generated by VibeFinance &bull; {{ __('page') }} <span class="page-number"></span>
    </div>
</body>

</html>