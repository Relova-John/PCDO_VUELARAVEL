<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Program Enrollment</title>

    <style>
        @page {
            margin: 40px 50px;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .title {
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            margin-top: 10px;
        }

        .content {
            padding: 20px 40px;
        }

        .footer {
            margin-top: 40px;
            text-align: right;
        }
    </style>
</head>

<body>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff;">
    <tr>
        <td align="center">
            <table width="510" cellpadding="0" cellspacing="0" style="background:#fff;">

                <!-- HEADER -->
                <tr>
                    <td>
                        <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                            <tr>
                                <td width="90" align="center">
                                    <img src="{{ public_path('img/province_of_palawan_logo.png') }}"
                                         width="70">
                                </td>

                                <td align="center">
                                    <strong>Republic of the Philippines</strong><br>
                                    Provincial Government of Palawan<br>
                                    PROVINCIAL COOPERATIVE DEVELOPMENT OFFICE<br>
                                    Capitol Bldg., Puerto Princesa City<br>
                                    pcdo.palawan@gmail.com<br>
                                    (048) 434-4173
                                </td>

                                <td width="90" align="center">
                                    <img src="{{ public_path('img/pcdo_logo.png') }}"
                                         width="70">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- TITLE -->
                <tr>
                    <td>
                        <div class="title">PROGRAM ENROLLMENT</div>
                    </td>
                </tr>

                <!-- CONTENT -->
                <tr>
                    <td class="content">

                        <p><strong>Hello {{ $cooperative->name }},</strong></p>

                        <p>
                            We’re pleased to inform you that your cooperative has been successfully enrolled in the following program:
                        </p>

                        <ul>
                            <li><strong>Program:</strong> {{ $program->name }}</li>
                            <li><strong>Enrolled Date:</strong>
                                {{ now()->setTimezone('Asia/Manila')->format('F d, Y') }}
                            </li>
                            <li><strong>Status:</strong> Ongoing</li>
                        </ul>

                        <p>
                            Thank you for your continued partnership with the Provincial Cooperative Development Office.
                        </p>

                        <p>— PCDO Team</p>

                        <!-- FOOTER SIGNATURE (Optional for formal look) -->
                        <div class="footer">
                            <p>
                                Very truly yours,<br><br><br>
                                <strong>GINA S. SOCRATES</strong><br>
                                Prov'l. Cooperatives Dev't. Officer
                            </p>
                        </div>

                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>