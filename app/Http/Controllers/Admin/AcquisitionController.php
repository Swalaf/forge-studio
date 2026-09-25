<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcquisitionController extends Controller
{
    public function index(): View
    {
        $listings = Product::acquisition()->with('author', 'soldTo')->withCount(['acquisitionOffers', 'acquisitionBids'])->latest()->paginate(10);

        $rows = $listings->getCollection()->map(fn (Product $listing) => [
            'title' => $listing->title,
            'meta' => $listing->author->name.' · '.str($listing->acquisition_sale_type)->headline().' · '.$listing->acquisitionOffers_count.' offers · '.$listing->acquisitionBids_count.' bids',
            'b' => $listing->askingPriceFormatted(),
            'c' => $listing->soldTo?->name ?? '—',
            'status' => str($listing->acquisition_status)->headline().' / '.str($listing->status)->headline(),
            'tone' => match ($listing->acquisition_status) {
                'available' => 'ok', 'pending_transfer' => 'wait', 'sold' => 'info', default => 'bad',
            },
            'primary' => ['label' => 'Manage', 'url' => route('admin.acquisitions.show', $listing)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('acquisitions'),
            'crumb' => 'Marketplace', 'title' => 'Project sales', 'subtitle' => 'Moderate one-time acquisitions, offers, auctions and transfers.',
            'stats' => [
                ['k' => 'Live', 'v' => (string) Product::availableAcquisition()->count(), 'tone' => 'ok'],
                ['k' => 'Pending transfer', 'v' => (string) Product::acquisition()->where('acquisition_status', 'pending_transfer')->count(), 'tone' => 'wait'],
                ['k' => 'Sold', 'v' => (string) Product::acquisition()->where('acquisition_status', 'sold')->count()],
            ],
            'colA' => 'Listing', 'colB' => 'Asking', 'colC' => 'Buyer',
            'rows' => $rows, 'pagination' => $listings->links(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->isAcquisition(), 404);

        return view('admin.acquisition-show', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('acquisitions'),
            'listing' => $product->load(['author', 'soldTo', 'acquisitionOffers.buyer', 'acquisitionBids.bidder']),
        ]);
    }

    public function approve(Product $product): RedirectResponse
    {
        abort_unless($product->isAcquisition(), 404);

        $product->update(['status' => 'live', 'published_at' => $product->published_at ?? now()]);

        return back()->with('status', 'Acquisition listing approved and published.');
    }

    public function hide(Product $product): RedirectResponse
    {
        abort_unless($product->isAcquisition(), 404);

        $product->update(['status' => 'hidden']);

        return back()->with('status', 'Acquisition listing hidden.');
    }

    public function closeAuction(Product $product): RedirectResponse
    {
        abort_unless($product->isAcquisition() && $product->acquisition_sale_type === 'auction', 404);

        $winningBid = $product->acquisitionBids()->whereIn('status', ['winning', 'active'])->orderByDesc('amount_cents')->first();

        if (! $winningBid) {
            return back()->withErrors(['auction' => 'This auction has no bids to close.']);
        }

        $product->update([
            'acquisition_status' => 'pending_transfer',
            'sold_to_user_id' => $winningBid->bidder_id,
        ]);
        $winningBid->update(['status' => 'accepted']);

        return back()->with('status', 'Auction closed and highest bidder marked as buyer.');
    }

    public function updateTransfer(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->isAcquisition(), 404);

        $data = $request->validate(['acquisition_status' => ['required', 'in:available,pending_transfer,sold,withdrawn']]);
        $product->update([
            'acquisition_status' => $data['acquisition_status'],
            'sold_at' => $data['acquisition_status'] === 'sold' ? now() : $product->sold_at,
        ]);

        return back()->with('status', 'Transfer status updated.');
    }
}
