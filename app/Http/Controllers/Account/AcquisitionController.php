<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AcquisitionBid;
use App\Models\AcquisitionOffer;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcquisitionController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $offers = AcquisitionOffer::where('buyer_id', $user->id)->with('product.author')->latest()->get();
        $bids = AcquisitionBid::where('bidder_id', $user->id)->with('product.author')->latest()->get();
        $purchases = Product::acquisition()->where('sold_to_user_id', $user->id)->with('author')->latest('sold_at')->get();

        $rows = collect()
            ->merge($offers->map(fn (AcquisitionOffer $offer) => [
                'title' => $offer->product->title,
                'meta' => 'Offer · '.$offer->created_at->format('d M Y'),
                'b' => $offer->amountFormatted(),
                'c' => $offer->product->author->name,
                'status' => str($offer->status)->headline(),
                'tone' => match ($offer->status) {
                    'accepted' => 'ok', 'pending' => 'wait', 'rejected' => 'bad', default => 'info',
                },
                'primary' => ['label' => 'View', 'url' => route('acquisitions.show', $offer->product)],
            ]))
            ->merge($bids->map(fn (AcquisitionBid $bid) => [
                'title' => $bid->product->title,
                'meta' => 'Bid · '.$bid->created_at->format('d M Y'),
                'b' => $bid->amountFormatted(),
                'c' => $bid->product->author->name,
                'status' => str($bid->status)->headline(),
                'tone' => match ($bid->status) {
                    'winning', 'accepted' => 'ok', 'active' => 'wait', 'outbid' => 'bad', default => 'info',
                },
                'primary' => ['label' => 'View', 'url' => route('acquisitions.show', $bid->product)],
            ]))
            ->merge($purchases->map(fn (Product $product) => [
                'title' => $product->title,
                'meta' => 'Purchased project · '.($product->sold_at?->format('d M Y') ?? 'pending'),
                'b' => $product->askingPriceFormatted(),
                'c' => $product->author->name,
                'status' => str($product->acquisition_status)->headline(),
                'tone' => $product->acquisition_status === 'sold' ? 'ok' : 'wait',
                'primary' => ['label' => 'View', 'url' => route('acquisitions.show', $product)],
            ]))
            ->values();

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('acquisitions'),
            'title' => 'Project deals', 'subtitle' => 'Your offers, bids, auction wins and acquisition transfer status.',
            'stats' => [
                ['k' => 'Offers', 'v' => (string) $offers->count()],
                ['k' => 'Bids', 'v' => (string) $bids->count()],
                ['k' => 'Purchased', 'v' => (string) $purchases->count(), 'tone' => 'ok'],
            ],
            'colA' => 'Project', 'colB' => 'Amount', 'colC' => 'Seller',
            'rows' => $rows,
        ]);
    }
}
