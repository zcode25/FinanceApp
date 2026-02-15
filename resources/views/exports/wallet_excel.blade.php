<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 14px;">{{ $wallet['name'] }}
                ({{ $wallet['currency'] }})</th>
        </tr>
        <tr>
            <th colspan="6"></th>
        </tr>
        <!-- Summary Section -->
        <tr>
            <td colspan="2" style="font-weight: bold;">Opening Balance</td>
            <td style="text-align: right;">{{ $summary['opening_balance'] }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Total Income</td>
            <td style="text-align: right;">+{{ $summary['income'] }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Total Expense</td>
            <td style="text-align: right;">-{{ $summary['expense'] }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Closing Balance</td>
            <td style="text-align: right;">{{ $summary['closing_balance'] }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <th colspan="6"></th>
        </tr>
        <!-- Table Headers -->
        <tr>
            <th style="font-weight: bold; border: 1px solid #000000;">Date</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Description</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Category</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Type</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Amount</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Balance</th>
        </tr>
    </thead>
    <tbody>
        <!-- Opening Balance Row -->
        <tr>
            <td style="font-weight: bold; border: 1px solid #000000;">OPENING</td>
            <td colspan="4" style="font-style: italic; border: 1px solid #000000;">Brought forward from previous period
            </td>
            <td style="text-align: right; font-weight: bold; border: 1px solid #000000;">
                {{ $summary['opening_balance'] }}
            </td>
        </tr>

        @foreach($transactions as $tx)
            <tr>
                <td style="border: 1px solid #000000;">{{ \Carbon\Carbon::parse($tx['date'])->format('d M Y') }}</td>
                <td style="border: 1px solid #000000;">{{ $tx['description'] }}</td>
                <td style="border: 1px solid #000000;">{{ $tx['category']['name'] }}</td>
                <td style="border: 1px solid #000000;">{{ strtoupper($tx['type']) }}</td>
                <td style="text-align: right; border: 1px solid #000000;">
                    {{ $tx['type'] === 'income' ? '+' : '-' }}{{ $tx['amount'] }}
                </td>
                <td style="text-align: right; font-weight: bold; border: 1px solid #000000;">{{ $tx['running_balance'] }}
                </td>
            </tr>
        @endforeach

        <!-- Closing Balance Row -->
        <tr>
            <td style="font-weight: bold; border: 1px solid #000000;">CLOSING</td>
            <td colspan="4" style="font-style: italic; border: 1px solid #000000;">Ending balance for this period</td>
            <td style="text-align: right; font-weight: bold; border: 1px solid #000000;">
                {{ $summary['closing_balance'] }}
            </td>
        </tr>
    </tbody>
</table>