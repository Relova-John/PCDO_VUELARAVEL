<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $selectedProgram }} Monthly Report - {{ $date }}</title>
    <style>
        @page {
            margin: 170px 40px 80px 40px;
        }

        .header {
            position: fixed;
            top: -150px;
            left: 0;
            right: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 10px;
        }

        h2 {
            margin-top: 25px;
            font-size: 16px;
            border-bottom: 1px solid #aaa;
            padding-bottom: 5px;
        }

        h3 {
            margin-top: 15px;
            font-size: 14px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 20px;
            page-break-inside: auto;
        }

        th,
        td {
            border: 1.5px solid #000;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #ffffff;
            /* remove gray */
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .partial {
            color: orange;
        }

        .unpaid {
            color: red;
        }

        .overdue {
            color: darkred;
            font-weight: bold;
        }

        .paid {
            color: #2b6;
            font-weight: bold;
        }

        .resolved {
            color: #555;
        }

        .page-break {
            page-break-after: always;
        }

        small {
            color: #555;
        }

        .section {
            margin-bottom: 30px;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 30px;
            font-family: Arial, sans-serif;
            font-size: 8px;
            color: #666;
            text-align: right;
            line-height: 1.2;
        }

        .footer span {
            display: block;
        }

        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
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
    <h1>{{ $selectedProgram }} Monthly Report</h1>
    <p style="text-align:center;"><strong>{{ $date }}</strong></p>

    {{-- 1 Registered Cooperatives --}}
    <div class="section">
        <h2>Registered Cooperatives ({{ $registeredCoops->count() }})</h2>
        @if($registeredCoops->count())
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cooperative Name</th>
                        <th>Registered Date</th>
                        <th>Registered Program</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registeredCoops as $index => $coop)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $coop->cooperative_name }}</td>
                            <td>{{ $coop->registered_at->format('F d, Y') }}</td>
                            <td>{{ $coop->program_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p><i>No cooperatives registered this month.</i></p>
        @endif
    </div>

    {{-- 2 Finished Cooperatives --}}
    <div class="section">
        <h2>Finished Cooperatives ({{ $finishedCoops->count() }})</h2>
        @if($finishedCoops->count())
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cooperative Name</th>
                        <th>Status</th>
                        <th>Finished Date</th>
                        <th>Finished Program</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($finishedCoops as $index => $coop)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $coop->cooperative_name }}</td>
                            <td>{{ $coop->status }}</td>
                            <td>{{ $coop->finished_at->format('F d, Y') }}</td>
                            <td>{{ $coop->program_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p><i>No cooperatives finished this month.</i></p>
        @endif
    </div>
    <div class="page-break"></div>
    {{-- 3 Programs --}}
    @foreach($programs as $program)
        <div class="section">
            <h1>{{ $selectedProgram }} Monthly Report</h1>
            <p style="text-align:center;"><strong>{{ $date }}</strong></p>
            <h2>Program: {{ $program['program_name'] }}</h2>

            {{-- 🔸 A. With Amortization Schedule --}}
            <h3>A. With Amortization Schedule ({{ count($program['has_amortization']) }})</h3>
            @if(count($program['has_amortization']))
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cooperative Name</th>
                            <th>Status</th>
                            <th class="text-right">Total Loan Amount</th>
                            <th class="text-right">Total Amount Paid</th>
                            <th class="text-right">Total Remaining Balance + Penalty</th>
                            <th class="text-right">Last Date Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($program['has_amortization'] as $index => $coop)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $coop['cooperative_name'] }}</td>
                                <td
                                    class="
                                                                                                                                                                @if($coop['payment_status'] === 'Paid') paid
                                                                                                                                                                @elseif($coop['payment_status'] === 'Overdue') overdue
                                                                                                                                                                @elseif($coop['payment_status'] === 'Unpaid') unpaid
                                                                                                                                                                @else partial
                                                                                                                                                                @endif
                                                                                                                                                            ">
                                    {{ $coop['payment_status'] }}
                                </td>
                                <td class="text-right">₱{{ $coop['loan_amount'] }}</td>
                                <td class="text-right">₱{{ $coop['amount_paid'] }}</td>
                                <td class="text-right">₱{{ $coop['remaining_balance'] }} + (₱{{ $coop['penalty'] }})</td>
                                <td class="text-right">{{ $coop['last_paid'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p><i>No cooperatives with amortization schedules.</i></p>
            @endif

            {{-- 🔹 B. Checklist-only cooperatives --}}
            <h3>B. Checklist-only ({{ count($program['checklist_only']) }})</h3>
            @if(count($program['checklist_only']))
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cooperative Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($program['checklist_only'] as $index => $coop)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $coop['cooperative_name'] }}</td>
                                <td>{{ $coop['program_status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p><i>No checklist-only cooperatives.</i></p>
            @endif
        </div>

        {{-- 📄 Add page break after each program --}}
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

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