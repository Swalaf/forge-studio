<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SavedController extends Controller
{
    public function index(): View
    {
        return view('account.saved', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('saved'),
            'saved' => Auth::user()->savedItems()->with('product.category')->latest()->get(),
        ]);
    }

    public function store(Product $product): RedirectResponse
    {
        Auth::user()->savedItems()->firstOrCreate(['product_id' => $product->id]);

        return back()->with('status', 'Saved.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Auth::user()->savedItems()->where('product_id', $product->id)->delete();

        return back()->with('status', 'Removed from saved items.');
    }
}
