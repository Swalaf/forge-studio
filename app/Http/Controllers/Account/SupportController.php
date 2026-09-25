<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Notifications\TicketReplied;
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
        $query = Auth::user()->openedTickets()->with('product');
        match ($tab) {
            'waiting' => $query->where('status', 'waiting'),
            'solved' => $query->where('status', 'solved'),
            default => $query->whereIn('status', ['open', 'breaching']),
        };
        $tickets = $query->latest()->paginate(10)->withQueryString();

        $rows = $tickets->getCollection()->map(fn ($t) => [
            'title' => $t->subject, 'meta' => $t->product?->title ?? 'General',
            'b' => $t->product?->title ?? '—', 'c' => $t->updated_at->diffForHumans(),
            'status' => str($t->status)->headline(),
            'tone' => match ($t->status) {
                'solved' => 'ok', 'breaching' => 'bad', 'waiting' => 'info', default => 'wait'
            },
            'primary' => ['label' => 'Open', 'url' => route('account.support.show', $t)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('support'),
            'title' => 'Support', 'subtitle' => 'Tickets with the studio and with product authors.',
            'stats' => [
                ['k' => 'Open tickets', 'v' => (string) Auth::user()->openedTickets()->whereIn('status', ['open', 'breaching'])->count(), 'tone' => 'wait'],
                ['k' => 'Solved', 'v' => (string) Auth::user()->openedTickets()->where('status', 'solved')->count(), 'tone' => 'ok'],
            ],
            'tabs' => collect(['open' => 'Open', 'waiting' => 'Waiting on you', 'solved' => 'Solved'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Ticket', 'colB' => 'Product', 'colC' => 'Updated',
            'rows' => $rows, 'pagination' => $tickets->links(),
            'actionsHtml' => '<a class="btn btn-dark" href="'.route('account.support.create').'">Open a ticket</a>',
        ]);
    }

    public function create(): View
    {
        return view('account.support-create', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('support'),
            'products' => Auth::user()->licenses()->with('product')->get()->pluck('product')->unique('id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'product_id' => ['nullable', 'exists:products,id'],
            'body' => ['required', 'string'],
        ]);

        $ticket = Ticket::create([
            'opener_id' => $request->user()->id,
            'product_id' => $data['product_id'] ?? null,
            'subject' => $data['subject'],
        ]);
        $ticket->messages()->create(['author_id' => $request->user()->id, 'body' => $data['body']]);

        return redirect()->route('account.support.show', $ticket)->with('status', 'Ticket opened.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        return view('account.support-show', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('support'),
            'ticket' => $ticket->load('messages.author', 'product'),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);
        $data = $request->validate(['body' => ['required', 'string']]);
        $message = $ticket->messages()->create(['author_id' => $request->user()->id, 'body' => $data['body']]);
        $ticket->update(['status' => 'open']);

        $recipient = $ticket->assignee ?? $ticket->product?->author;
        $recipient?->notify(new TicketReplied($message));

        return back()->with('status', 'Message sent.');
    }
}
