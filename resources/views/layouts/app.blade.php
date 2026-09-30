<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Skooly')</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; color: #222; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 1.5rem; }
        th, td { border: 1px solid #ccc; padding: 0.4rem 0.6rem; text-align: left; }
        label { display: block; margin-top: 0.75rem; font-weight: 600; }
        input[type=text], input[type=date], input[type=email], input[type=number], select, textarea {
            width: 100%; max-width: 28rem; padding: 0.3rem;
        }
        .error { color: #b00020; }
        .status { background: #e6f4ea; border: 1px solid #34a853; padding: 0.6rem; }
    </style>
</head>
<body>
<h1>@yield('heading', 'Skooly')</h1>

@if (session('status'))
    <p class="status">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <ul class="error">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

@yield('content')
</body>
</html>
