<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Summary Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #111827;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 6px 0;
        }

        .meta {
            margin-bottom: 12px;
            font-size: 10px;
            color: #4b5563;
            line-height: 1.5;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 14px 0 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-weight: bold;
        }

        .group-row td {
            background: #e5e7eb;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .empty {
            text-align: center;
            padding: 16px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        <table width="100%" cellpadding="0" cellspacing="0" style="border:0;">
            <tr>
                <td width="80" align="left" style="border:0;">
                    <img src="{{ public_path('img/province_of_palawan_logo.png') }}" width="70">
                </td>

                <td align="center" style="border:0;">
                    <div style="text-align:center;">
                        <strong>Republic of the Philippines</strong><br>
                        Provincial Government of Palawan<br>
                        <strong>PROVINCIAL COOPERATIVE DEVELOPMENT OFFICE</strong><br>
                        Capitol Bldg., Puerto Princesa City<br>
                        pcdo.palawan@gmail.com<br>
                        (048) 434-4173
                    </div>
                </td>

                <td width="80" align="right" style="border:0;">
                    <img src="{{ public_path('img/pcdo_logo.png') }}" width="70">
                </td>
            </tr>
        </table>

        <hr style="border:1px solid #000; margin-top:10px;">
    </div>
    @php
        $reportingPeriod = $reportingDate
            ? str_pad($reportingDate->reporting_month, 2, '0', STR_PAD_LEFT) . '/' . $reportingDate->reporting_year
            : 'N/A';

        $filterParts = collect([
            !empty($filters['category']) ? 'Category: ' . $filters['category'] : 'Category: All',
            !empty($filters['search_name']) ? 'Name: ' . $filters['search_name'] : null,
            'Status: ' . ucfirst($filters['status'] ?? 'all'),
            'View: ' . ucfirst($viewType),
        ])->implode(' | ');

        $money = fn($value) => number_format((float) $value, 2);
    @endphp

    <h1>Summary Report</h1>

    <div class="meta">
        <div><strong>Reporting Period:</strong> {{ $reportingPeriod }}</div>
        <div><strong>Filters:</strong> {{ $filterParts }}</div>
    </div>

    @if ($viewType === 'cooperative')
        <div class="section-title">Cooperative View</div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Coop Location</th>
                    <th>Item Location</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cooperativeGroupedRows as $group)
                    <tr class="group-row">
                        <td colspan="7">{{ $group['coop_name'] }}</td>
                    </tr>

                    @foreach ($group['item_rows'] as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['item_name'] }}</td>
                            <td>{{ $row['coop_location'] ?: '-' }}</td>
                            <td>{{ $row['item_location'] ?: '-' }}</td>
                            <td class="text-right">{{ $money($row['value']) }}</td>
                            <td class="text-right">{{ number_format($row['display_quantity']) }}</td>
                            <td class="text-right">{{ $money($row['total']) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="empty">No summary records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <div class="section-title">Inventory View</div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cooperative</th>
                    <th class="text-right">Quantity</th>
                    <th>Coop Location</th>
                    <th>Item Location</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventoryGroupedRows as $group)
                    <tr class="group-row">
                        <td colspan="6">{{ $group['category'] }} - {{ $group['itemName'] }}</td>
                    </tr>

                    @foreach ($group['rows'] as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['coop_name'] }}</td>
                            <td class="text-right">{{ number_format($row['display_quantity']) }}</td>
                            <td>{{ $row['coop_location'] ?: '-' }}</td>
                            <td>{{ $row['item_location'] ?: '-' }}</td>
                            <td class="text-right">
                                {{ $money($row['value']) }} x {{ number_format($row['display_quantity']) }}
                                = {{ $money($row['total']) }}
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" class="empty">No summary records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
    <div class="footer">
        <hr style="border: 0.3px solid #ccc; margin-bottom: 3px;">
        <span>
            Provincial Cooperative Development Office
        </span>
        <span>
            Generated on {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }} | Printed by: {{ auth()->user()->name }}
        </span>
    </div>
</body>

</html>