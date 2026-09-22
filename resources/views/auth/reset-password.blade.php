@extends('layouts.app')
@section('title', 'Choose a new password')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:24px">
            <span class="dash-logo" style="background:#0B0F19;color:#fff">F</span>
            <h1 style="font-size:20px;font-weight:800;margin-top:14px;letter-spacing:-0.02em">Choose a new password</h1>
        </div>
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('password.update') }}" style="display:grid;gap:14px">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus>
            </div>
            <div class="field">
                <label>New password</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Confirm new password</label>
                <input type="password" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Reset password</button>
        </form>
    </div>
</div>
@endsection
