<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Nav;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = User::where('role', 'customer')->withCount('orders')->paginate(10);

        $rows = $customers->getCollection()->map(fn ($c) => [
            'avatarKind' => 'person', 'avatarText' => $c->initials(),
            'title' => $c->company ?: $c->name, 'meta' => 'CUS-'.str_pad($c->id, 4, '0', STR_PAD_LEFT).' · '.($c->company ? 'business' : 'individual'),
            'b' => (string) $c->orders_count,
            'c' => '$'.number_format($c->orders()->where('status', 'paid')->sum('total_cents') / 100, 0),
            'status' => 'Active', 'tone' => 'ok',
            'primary' => ['label' => 'Open', 'url' => route('admin.customers.show', $c)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('customers'),
            'crumb' => 'Marketplace', 'title' => 'Customers', 'subtitle' => 'Accounts, lifetime value and the services they have bought from the studio.',
            'stats' => [
                ['k' => 'Accounts', 'v' => (string) User::where('role', 'customer')->count(), 'tone' => 'ok'],
                ['k' => 'Service clients', 'v' => (string) User::where('role', 'customer')->whereHas('serviceProjects')->count()],
            ],
            'colA' => 'Customer', 'colB' => 'Orders', 'colC' => 'Lifetime value',
            'rows' => $rows, 'pagination' => $customers->links(),
        ]);
    }

    public function show(User $customer): View
    {
        return view('admin.customer-show', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('customers'),
            'customer' => $customer,
            'orders' => $customer->orders()->latest()->take(10)->get(),
            'tickets' => $customer->openedTickets()->latest()->take(10)->get(),
        ]);
    }
}
