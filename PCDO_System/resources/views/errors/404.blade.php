{{-- resources/views/errors/404.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: "Nunito", sans-serif;
            background-color: #f8fafc;
            color: #1f2937;
        }
        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
        }
        h1 {
            font-size: 10rem;
            margin: 0;
        }
        h2 {
            font-size: 2rem;
            margin: 0.5rem 0 1rem;
        }
        p {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
        a {
            text-decoration: none;
            color: #3b82f6;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>{{ $exception->getMessage() ?: 'Sorry, the page you are looking for could not be found.' }}</p>
    </div>
</body>
</html>