<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'open';
        $query = Ticket::with('opener', 'assignee');
        match ($tab) {
            'breaching' => $query->where('status', 'breaching'),
            'waiting' => $query->where('status', 'waiting'),
            'solved' => $query->where('status', 'solved'),
            default => $query->where('status', 'open'),
        };
        $tickets = $query->latest()->paginate(10)->withQueryString();

        $rows = $tickets->getCollection()->map(fn ($t) => [
            'title' => $t->subject, 'meta' => 'Opened by '.$t->opener->name.' · '.$t->created_at->diffForHumans(),
            'b' => $t->product?->title ?? 'General',
            'c' => $t->updated_at->diffForHumans(),
            'status' => str($t->status)->headline(),
            'tone' => match ($t->status) { 'solved' => 'ok', 'open' => 'info', 'waiting' => 'wait', 'breaching' => 'bad', default => 'info' },
            'primary' => ['label' => 'Open', 'url' => route('admin.tickets.show', $t)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('tickets'),
            'crumb' => 'Operations', 'title' => 'Support', 'subtitle' => 'Buyer and author tickets, routed to the studio or the product author.',
            'stats' => [
                ['k' => 'Open tickets', 'v' => (string) Ticket::whereIn('status', ['open', 'breaching'])->count(), 'tone' => 'wait'],
                ['k' => 'Breaching SLA', 'v' => (string) Ticket::where('status', 'breaching')->count(), 'tone' => Ticket::where('status', 'breaching')->count() ? 'bad' : 'ok'],
                ['k' => 'Solved', 'v' => (string) Ticket::where('status', 'solved')->count(), 'tone' => 'ok'],
            ],
            'tabs' => collect(['open' => 'Open', 'breaching' => 'Breaching', 'waiting' => 'Waiting', 'solved' => 'Solved'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Ticket', 'colB' => 'Product', 'colC' => 'Updated',
            'rows' => $rows, 'pagination' => $tickets->links(),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        return view('admin.ticket-show', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('tickets'),
            'ticket' => $ticket->load('messages.author', 'opener', 'assignee', 'product'),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string']]);
        $ticket->messages()->create(['author_id' => $request->user()->id, 'body' => $data['body']]);
        $ticket->update(['status' => 'waiting']);
        AuditLog::record('ticket.replied', $ticket);

        return back()->with('status', 'Reply sent.');
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate(['assigned_to' => ['required', 'exists:users,id']]);
        $ticket->update($data);
        AuditLog::record('ticket.assigned', $ticket, $data);

        return back()->with('status', 'Ticket assigned.');
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['status' => 'solved']);
        AuditLog::record('ticket.closed', $ticket);

        return back()->with('status', 'Ticket closed.');
    }
}
