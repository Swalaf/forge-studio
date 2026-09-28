<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
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
            'role' => in_array($request->query('role'), ['customer', 'author', 'admin'], true) ? $request->query('role') : 'customer',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'intended_role' => ['required', 'in:customer,author,admin'],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['intended_role'] === 'author' ? 'author' : 'customer',
        ]);

        $status = null;

        if ($data['intended_role'] === 'admin') {
            $ticket = Ticket::create([
                'opener_id' => $user->id,
                'subject' => 'Studio console access requested',
                'priority' => 'normal',
            ]);
            $ticket->messages()->create([
                'author_id' => $user->id,
                'body' => 'New account requesting Forge Admin console access.'.($user->company ? ' Team: '.$user->company.'.' : ''),
            ]);
            $status = 'Account created — your console access request has been sent to the studio.';
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended($user->isAuthor() ? route('author.overview') : route('account.overview'))
            ->with('status', $status);
    }
}
