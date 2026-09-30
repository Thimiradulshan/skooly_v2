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
        .alert { background: #fdecea; border: 1px solid #b00020; padding: 0.6rem; }
        .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr)); gap: 1rem; }
        .card { border: 1px solid #ccc; padding: 1rem; }
        .card h3 { margin-top: 0; }
        .nav a { margin-right: 0.25rem; }
    </style>
</head>
<body>
<h1>@yield('heading', 'Skooly')</h1>

@auth
    <p>
        Signed in as {{ auth()->user()->email }}
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </p>

    <p class="nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> |
        <a href="{{ route('families.index') }}">Families</a> |
        <a href="{{ route('fee-categories.index') }}">Fees</a> |
        <a href="{{ route('due-generation.recurring.create') }}">Due Generation</a> |
        <a href="{{ route('dues-dashboard.index') }}">Dues Dashboard</a> |
        <a href="{{ route('events.index') }}">Events</a> |
        <a href="{{ route('promotion-batches.index') }}">Promotion</a> |
        <a href="{{ route('payment-reminders.index') }}">Reminders</a>
    </p>
@else
    <p><a href="{{ route('login') }}">Sign in</a></p>
@endauth

@if (session('status'))
    <p class="status">{{ session('status') }}</p>
@endif

@if (session('error'))
    <p class="alert">{{ session('error') }}</p>
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
