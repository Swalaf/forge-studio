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
    public function create(): View
    {
        return view('auth.index', ['mode' => 'signup']);
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

        // Every public sign-up becomes a 'customer' account — there is no path here that
        // grants 'author' or 'admin' directly. Both of those are requests a human reviews:
        // an author application goes into the same queue Admin\AuthorController already
        // approves from, and an admin-access request lands as a ticket the studio can act on.
        $user = User::create([
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        $status = null;

        if ($data['intended_role'] === 'author') {
            $user->update([
                'author_application_status' => 'pending',
                'author_application_note' => trim('Applied via sign-up.'.($user->company ? ' Alias: '.$user->company.'.' : '')),
            ]);
            $status = 'Account created — your author application is in review. We\'ll email you within a few days.';
        }

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

        return redirect()->intended(route('account.overview'))->with('status', $status);
    }
}
