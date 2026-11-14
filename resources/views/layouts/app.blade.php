<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'URL Shortener') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root { color-scheme: light; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; background: #f6f7f9; color: #1b1b18; }
        a { color: #1d4ed8; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { max-width: 960px; margin: 0 auto; padding: 1.5rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; }
        .btn { display: inline-block; background: #1b1b18; color: #fff; border: 0; border-radius: 6px; padding: .55rem 1rem; cursor: pointer; }
        .btn:hover { opacity: .9; text-decoration: none; color: #fff; }
        .btn-secondary { background: #fff; color: #1b1b18; border: 1px solid #d1d5db; }
        .field { display: block; width: 100%; margin-top: .35rem; margin-bottom: 1rem; padding: .55rem .7rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
        .error { color: #b91c1c; font-size: .9rem; margin-top: -.5rem; margin-bottom: 1rem; }
        .status { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .65rem .4rem; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { font-size: .85rem; color: #6b7280; font-weight: 600; }
        .tag { display: inline-block; background: #eef2ff; color: #3730a3; border-radius: 999px; padding: .15rem .55rem; margin: 0 .25rem .25rem 0; font-size: .8rem; }
        .muted { color: #6b7280; }
        .pagination { margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="shell">
        <header class="nav">
            <div>
                <strong>{{ config('app.name', 'URL Shortener') }}</strong>
                @auth
                    <span class="muted"> — {{ auth()->user()->name }}</span>
                @endauth
            </div>
            <div>
                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline; margin-left: 1rem;">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Log in</a>
                @endauth
            </div>
        </header>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
