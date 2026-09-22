@extends('layouts.app')
@section('title', 'Forgot password')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:24px">
            <span class="dash-logo" style="background:#0B0F19;color:#fff">F</span>
            <h1 style="font-size:20px;font-weight:800;margin-top:14px;letter-spacing:-0.02em">Reset your password</h1>
            <p style="font-size:13px;color:var(--muted);margin-top:8px">We'll email you a link to choose a new one.</p>
        </div>
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('password.email') }}" style="display:grid;gap:14px">
            @csrf
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Send reset link</button>
        </form>
        <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:18px">
            <a href="{{ route('login') }}">Back to sign in</a>
        </p>
    </div>
</div>
@endsection
