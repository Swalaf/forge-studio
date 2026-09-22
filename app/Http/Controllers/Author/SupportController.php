<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'open';
        $query = Ticket::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))->with('opener', 'product');
        match ($tab) {
            'breaching' => $query->where('status', 'breaching'),
            'waiting' => $query->where('status', 'waiting'),
            'solved' => $query->where('status', 'solved'),
            default => $query->where('status', 'open'),
        };
        $tickets = $query->latest()->paginate(10)->withQueryString();

        $rows = $tickets->getCollection()->map(fn ($t) => [
            'title' => $t->subject, 'meta' => $t->product->title,
            'b' => $t->opener->name, 'c' => $t->updated_at->diffForHumans(),
            'status' => str($t->status)->headline(),
            'tone' => match ($t->status) { 'solved' => 'ok', 'breaching' => 'bad', 'waiting' => 'info', default => 'wait' },
            'primary' => ['label' => 'Reply', 'url' => route('author.support.show', $t)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('support'),
            'title' => 'Buyer support', 'subtitle' => 'Tickets routed to you as the author of the product.',
            'stats' => [
                ['k' => 'Open', 'v' => (string) $query->clone()->count(), 'tone' => 'wait'],
            ],
            'tabs' => collect(['open' => 'Open', 'breaching' => 'Breaching', 'waiting' => 'Waiting', 'solved' => 'Solved'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Ticket', 'colB' => 'From', 'colC' => 'Updated',
            'rows' => $rows, 'pagination' => $tickets->links(),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        return view('author.support-show', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('support'),
            'ticket' => $ticket->load('messages.author', 'opener', 'product'),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);
        $data = $request->validate(['body' => ['required', 'string']]);
        $ticket->messages()->create(['author_id' => $request->user()->id, 'body' => $data['body']]);
        $ticket->update(['status' => 'waiting']);
        AuditLog::record('ticket.replied', $ticket);

        return back()->with('status', 'Reply sent.');
    }
}
