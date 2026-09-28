<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.index', [
            'mode' => 'signup',
            'role' => in_array($request->query('role'), ['customer', 'author'], true) ? $request->query('role') : 'customer',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'intended_role' => ['required', 'in:customer,author'],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['intended_role'] === 'author' ? 'author' : 'customer',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended($user->isAuthor() ? route('author.overview') : route('account.overview'));
    }
}
