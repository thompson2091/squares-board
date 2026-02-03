<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board->name }} - Print</title>
    <style>
        @page {
            size: landscape;
            margin: 0.25in;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        .header {
            text-align: center;
            margin-bottom: 0.1in;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2pt;
        }

        .header p {
            font-size: 10pt;
            color: #333;
        }

        .container {
            display: flex;
            gap: 0.25in;
            align-items: flex-start;
        }

        .grid-section {
            flex: 0 0 auto;
        }

        .payout-section {
            flex: 1;
            max-width: 3in;
            font-size: 7pt;
        }

        /* Grid styles */
        .grid-wrapper {
            display: flex;
            align-items: stretch;
        }

        .row-team-label {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-weight: bold;
            font-size: 9pt;
            padding-right: 4pt;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grid-container {
            display: flex;
            flex-direction: column;
        }

        .col-team-label {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            padding-bottom: 2pt;
        }

        .grid {
            border-collapse: collapse;
        }

        .grid td,
        .grid th {
            width: 0.65in;
            height: 0.65in;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
            padding: 2pt;
            font-size: 6pt;
            overflow: hidden;
        }

        .grid th {
            background: #374151;
            color: #fff;
            font-weight: bold;
            font-size: 10pt;
        }

        .grid th.corner {
            background: transparent;
            border: none;
        }

        .grid td .name {
            font-size: 5.5pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            max-height: 100%;
            line-height: 1.1;
        }

        /* Pot section */
        .pot-box {
            border: 1px solid #e5e7eb;
            border-radius: 6pt;
            padding: 8pt 12pt;
            text-align: center;
            margin-bottom: 10pt;
        }

        .pot-label {
            font-size: 7pt;
            font-weight: 600;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 2pt;
        }

        .pot-amount {
            font-size: 16pt;
            font-weight: bold;
            color: #111;
            margin-bottom: 2pt;
        }

        .pot-per-square {
            font-size: 7pt;
            color: #6b7280;
        }

        /* Payouts section */
        .payouts-header {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 8pt;
            padding-bottom: 4pt;
            border-bottom: 1px solid #e5e7eb;
        }

        .quarters-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6pt;
        }

        .quarter-box {
            border: 1px solid #e5e7eb;
            border-left: 3pt solid #fbbf24;
            border-radius: 4pt;
            padding: 6pt 8pt;
        }

        .quarter-label {
            font-size: 8pt;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 3pt;
        }

        .payout-item {
            display: flex;
            align-items: center;
            gap: 4pt;
            font-size: 8pt;
            margin-bottom: 2pt;
        }

        .payout-dot {
            width: 6pt;
            height: 6pt;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-primary {
            background-color: #22c55e;
        }

        .dot-reverse {
            background-color: #a855f7;
        }

        .dot-touching {
            background-color: #3b82f6;
        }

        .dot-2mw {
            background-color: #f97316;
        }

        .payout-amount {
            font-weight: 500;
        }

        /* Legend */
        .legend {
            margin-top: 10pt;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8pt 12pt;
            font-size: 7pt;
            color: #6b7280;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 3pt;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Screen-only styles for close button */
        @media screen {
            .no-print {
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 1000;
            }

            .close-btn {
                padding: 8px 16px;
                background: #6366f1;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
            }

            .close-btn:hover {
                background: #4f46e5;
            }
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button class="close-btn" onclick="window.close()">Close</button>
    </div>

    <div class="header">
        <h1>{{ $board->name }}</h1>
        <p>{{ $board->team_row }} vs {{ $board->team_col }}@if($board->game_date) &mdash; {{ $board->game_date->format('M j, Y') }}@endif</p>
    </div>

    <div class="container">
        <div class="grid-section">
            <div class="grid-wrapper">
                <div class="row-team-label">{{ $board->team_row }}</div>
                <div class="grid-container">
                    <div class="col-team-label">{{ $board->team_col }}</div>
                    <table class="grid">
                        <tr>
                            <th class="corner"></th>
                            @for($col = 0; $col < 10; $col++)
                                <th>
                                    @if($board->numbers_revealed && $board->col_numbers)
                                        {{ $board->col_numbers[$col] }}
                                    @else
                                        ?
                                    @endif
                                </th>
                            @endfor
                        </tr>
                        @for($row = 0; $row < 10; $row++)
                            <tr>
                                <th>
                                    @if($board->numbers_revealed && $board->row_numbers)
                                        {{ $board->row_numbers[$row] }}
                                    @else
                                        ?
                                    @endif
                                </th>
                                @for($col = 0; $col < 10; $col++)
                                    @php
                                        $square = $grid[$row][$col] ?? null;
                                        $displayName = $square ? ($square->display_name ?? ($square->user?->name ?? '')) : '';
                                    @endphp
                                    <td>
                                        @if($displayName)
                                            <div class="name">{{ $displayName }}</div>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endfor
                    </table>
                </div>
            </div>
        </div>

        <div class="payout-section">
            @php
                $potTotal = $board->price_per_square * 100;
                $payoutsByQuarter = $board->payoutRules->groupBy('quarter');
                $quarters = ['Q1', 'Q2', 'Q3', 'final'];
                $quarterLabels = \App\Models\PayoutRule::QUARTER_LABELS;
            @endphp

            <div class="pot-box">
                <div class="pot-label">Pot</div>
                <div class="pot-amount">${{ number_format($potTotal / 100, 2) }}</div>
                <div class="pot-per-square">${{ number_format($board->price_per_square / 100, 2) }} per square</div>
            </div>

            <div class="payouts-header">Payouts</div>

            @if($board->payoutRules->isNotEmpty())
                <div class="quarters-grid">
                    @foreach($quarters as $quarter)
                        @php
                            $rules = $payoutsByQuarter->get($quarter, collect());
                        @endphp
                        <div class="quarter-box">
                            <div class="quarter-label">{{ $quarterLabels[$quarter] ?? $quarter }}</div>
                            @forelse($rules->sortBy(fn($r) => array_search($r->winner_type, ['primary', 'reverse', 'touching', '2mw'])) as $rule)
                                <div class="payout-item">
                                    <span class="payout-dot dot-{{ $rule->winner_type }}"></span>
                                    <span class="payout-amount">{{ $rule->amount_display }}</span>
                                </div>
                            @empty
                                <div class="payout-item" style="color: #9ca3af; font-style: italic;">
                                    No payout
                                </div>
                            @endforelse
                        </div>
                    @endforeach
                </div>

                <div class="legend">
                    <div class="legend-item">
                        <span class="payout-dot dot-primary"></span>
                        <span>Winner</span>
                    </div>
                    <div class="legend-item">
                        <span class="payout-dot dot-reverse"></span>
                        <span>Reverse</span>
                    </div>
                    <div class="legend-item">
                        <span class="payout-dot dot-touching"></span>
                        <span>Touching</span>
                    </div>
                    <div class="legend-item">
                        <span class="payout-dot dot-2mw"></span>
                        <span>2MW</span>
                    </div>
                </div>
            @else
                <p style="font-size: 8pt; color: #6b7280; text-align: center;">No payout rules configured.</p>
            @endif
        </div>
    </div>
</body>
</html>
