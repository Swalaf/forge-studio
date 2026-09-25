<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.index', [
            'mode' => 'signin',
            'role' => in_array($request->query('role'), ['customer', 'author', 'admin'], true) ? $request->query('role') : 'customer',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials don\'t match an account.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeFor($request->user()->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function homeFor(string $role): string
    {
        return match ($role) {
            'admin' => route('admin.overview'),
            'author' => route('author.overview'),
            default => route('account.overview'),
        };
    }
}
