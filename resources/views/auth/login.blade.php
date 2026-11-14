@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="card" style="max-width: 420px; margin: 2rem auto;">
        <h1 style="margin-top: 0;">Log in</h1>
        <p class="muted">Session login for the dashboard. API still uses Sanctum tokens.</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">Email</label>
            <input id="email" class="field" type="email" name="email" value="{{ old('email') }}" required autofocus>

            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror

            <label for="password">Password</label>
            <input id="password" class="field" type="password" name="password" required>

            <label style="display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem;">
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>

            <button type="submit" class="btn">Log in</button>
        </form>
    </div>
@endsection
