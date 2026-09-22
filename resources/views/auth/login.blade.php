@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:24px">
            <span class="dash-logo" style="background:#0B0F19;color:#fff">F</span>
            <h1 style="font-size:20px;font-weight:800;margin-top:14px;letter-spacing:-0.02em">Sign in to Forge Market</h1>
        </div>
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login') }}" style="display:grid;gap:14px">
            @csrf
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Sign in</button>
        </form>
        <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:14px">
            <a href="{{ route('password.request') }}">Forgot your password?</a>
        </p>
        <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:6px">
            No account? <a href="{{ route('register') }}">Create one</a>
        </p>
    </div>
</div>
@endsection
