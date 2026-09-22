@extends('layouts.app')
@section('title', 'Create account')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:24px">
            <span class="dash-logo" style="background:#0B0F19;color:#fff">F</span>
            <h1 style="font-size:20px;font-weight:800;margin-top:14px;letter-spacing:-0.02em">Create your account</h1>
        </div>
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('register') }}" style="display:grid;gap:14px">
            @csrf
            <div class="field">
                <label>Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Confirm password</label>
                <input type="password" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Create account</button>
        </form>
        <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:18px">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </p>
    </div>
</div>
@endsection
