<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Login Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .code {
            font-size: 4rem;       /* BIG letters */
            font-weight: bold;
            letter-spacing: 0.5rem;
            color: #1a73e8;
            margin: 20px 0;
        }

        p {
            font-size: 1.2rem;
            margin: 10px 0;
        }

        .note {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <p>Your login code is:</p>
        <div class="code">{{ $code }}</div>
        <p class="note">This code will expire in 5 minutes.</p>
    </div>
</body>
</html>