<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your PCDO Password</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family: Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:24px;">
                <!-- Main card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#ffffff; border-radius:8px; padding:24px;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="text-align:center; padding-bottom:16px;">
                            <h2 style="margin:0; color:#111827;">PCDO Account Access</h2>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="color:#374151; font-size:14px; line-height:1.6;">
                            <p>Dear User,</p>

                            <p>
                                This email contains your <strong>PCDO account password</strong>.
                                For your security, please change this password immediately after logging in.
                            </p>

                            <p style="margin-top:20px; margin-bottom:8px;">
                                <strong>Your temporary password:</strong>
                            </p>

                            <!-- Password box -->
                            <div style="
                                background:#f9fafb;
                                border:1px dashed #9ca3af;
                                padding:14px;
                                text-align:center;
                                font-size:18px;
                                font-weight:bold;
                                letter-spacing:2px;
                                color:#111827;
                                border-radius:6px;
                            ">
                                {{ $x }}
                            </div>

                            <!-- Login link -->
                            <p style="margin-top:20px;">
                                Login here:
                                <br>
                                <a href="{{ config('app.url') }}" style="color:#2563eb; text-decoration:none;">
                                    {{ config('app.url') }}
                                </a>
                            </p>

                            <!-- Signature -->
                            <p style="margin-top:24px;">
                                Regards,<br>
                                <strong>PCDO Team</strong>
                            </p>
                        </td>
                    </tr>

                </table>

                <!-- Footer note -->
                <p style="font-size:12px; color:#6b7280; margin-top:12px; text-align:center;">
                    Please do not share your password with anyone.
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
