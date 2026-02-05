<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Your PCDO Password</title>
</head>
<body>
    <p>Dear user,</p>

    <p>This email contains your PCDO account password. Please change it after logging in for security purposes.</p>

    <p><strong>Password:</strong> {{ $x }}</p>

    <p>Login here: <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>

    <p>Regards,<br/>PCDO Team</p>
</body>
</html>
