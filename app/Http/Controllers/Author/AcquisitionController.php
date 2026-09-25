<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\AcquisitionOffer;
use App\Models\Category;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AcquisitionController extends Controller
{
    public function index(): View
    {
        $listings = Product::acquisition()
            ->where('author_id', Auth::id())
            ->withCount(['acquisitionOffers', 'acquisitionBids'])
            ->latest()
            ->paginate(10);

        $rows = $listings->getCollection()->map(fn (Product $listing) => [
            'title' => $listing->title,
            'meta' => str($listing->acquisition_sale_type)->headline().' · '.$listing->acquisitionOffers_count.' offers · '.$listing->acquisitionBids_count.' bids',
            'b' => $listing->askingPriceFormatted(),
            'c' => $listing->monthlyRevenueFormatted().' / mo',
            'status' => str($listing->acquisition_status)->headline(),
            'tone' => match ($listing->acquisition_status) {
                'available' => 'ok', 'pending_transfer' => 'wait', 'sold' => 'info', default => 'bad',
            },
            'primary' => ['label' => 'Manage', 'url' => route('author.acquisitions.edit', $listing)],
            'secondary' => ['label' => 'View', 'url' => route('acquisitions.show', $listing)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('acquisitions'),
            'title' => 'Project sales', 'subtitle' => 'One-time project acquisitions, offers, bids and transfer status.',
            'stats' => [
                ['k' => 'Listings', 'v' => (string) Product::acquisition()->where('author_id', Auth::id())->count()],
                ['k' => 'Open offers', 'v' => (string) AcquisitionOffer::whereHas('product', fn ($query) => $query->where('author_id', Auth::id()))->where('status', 'pending')->count(), 'tone' => 'wait'],
                ['k' => 'Pending transfer', 'v' => (string) Product::acquisition()->where('author_id', Auth::id())->where('acquisition_status', 'pending_transfer')->count(), 'tone' => 'wait'],
            ],
            'colA' => 'Listing', 'colB' => 'Asking', 'colC' => 'Revenue',
            'rows' => $rows, 'pagination' => $listings->links(),
            'actionsHtml' => '<a class="btn btn-dark" href="'.route('author.acquisitions.create').'">New project sale</a>',
        ]);
    }

    public function create(): View
    {
        return view('author.acquisition-form', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('acquisitions'),
            'listing' => new Product(['listing_type' => 'acquisition', 'acquisition_sale_type' => 'fixed_or_offer']),
            'categories' => Category::orderBy('name')->get(),
            'offers' => collect(),
            'bids' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = Auth::id();
        $data['listing_type'] = 'acquisition';
        $data['acquisition_status'] = 'available';
        $data['status'] = 'draft';
        $data['slug'] = Str::slug($data['title']).'-'.Str::random(5);
        $data['price_cents'] = $data['asking_price_cents'];

        $listing = Product::create($data);

        return redirect()->route('author.acquisitions.edit', $listing)->with('status', 'Project sale draft created.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        abort_unless($product->isAcquisition(), 404);

        return view('author.acquisition-form', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('acquisitions'),
            'listing' => $product,
            'categories' => Category::orderBy('name')->get(),
            'offers' => $product->acquisitionOffers()->with('buyer')->latest()->get(),
            'bids' => $product->acquisitionBids()->with('bidder')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($product->isAcquisition(), 404);

        $data = $this->validated($request);
        $data['price_cents'] = $data['asking_price_cents'];
        $product->update($data);

        return redirect()->route('author.acquisitions.index')->with('status', 'Project sale updated.');
    }

    public function respondToOffer(Request $request, AcquisitionOffer $offer): RedirectResponse
    {
        abort_unless($offer->product->author_id === Auth::id(), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
            'seller_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $offer->update([
            'status' => $data['decision'],
            'seller_response' => $data['seller_response'] ?? null,
            'responded_at' => now(),
        ]);

        if ($data['decision'] === 'accepted') {
            $offer->product->update([
                'acquisition_status' => 'pending_transfer',
                'sold_to_user_id' => $offer->buyer_id,
            ]);
        }

        return back()->with('status', 'Offer '.$data['decision'].'.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'acquisition_sale_type' => ['required', 'in:fixed,offer,fixed_or_offer,auction'],
            'asking_price_cents' => ['required', 'integer', 'min:0'],
            'reserve_price_cents' => ['nullable', 'integer', 'min:0'],
            'minimum_offer_cents' => ['nullable', 'integer', 'min:0'],
            'monthly_revenue_cents' => ['nullable', 'integer', 'min:0'],
            'monthly_profit_cents' => ['nullable', 'integer', 'min:0'],
            'monthly_visitors' => ['nullable', 'integer', 'min:0'],
            'monthly_pageviews' => ['nullable', 'integer', 'min:0'],
            'demo_url' => ['nullable', 'url', 'max:255'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'seller_disclosures' => ['nullable', 'string'],
            'transfer_notes' => ['nullable', 'string'],
            'due_diligence_notes' => ['nullable', 'string'],
            'auction_starts_at' => ['nullable', 'date'],
            'auction_ends_at' => ['nullable', 'date', 'after:auction_starts_at'],
        ]);
    }
}
