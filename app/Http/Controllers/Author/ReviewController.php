<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))->with('product', 'customer');
        if ($request->string('tab')->value() === 'needs_reply') {
            $query->whereNull('reply_body');
        }
        $reviews = $query->latest()->paginate(10)->withQueryString();

        return view('author.reviews', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('reviews'),
            'reviews' => $reviews,
            'avgRating' => number_format(Review::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))->avg('rating') ?? 0, 1),
            'unanswered' => Review::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))->whereNull('reply_body')->count(),
        ]);
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('reply', $review);
        $data = $request->validate(['reply_body' => ['required', 'string', 'max:2000']]);
        $review->update(['reply_body' => $data['reply_body'], 'replied_at' => now()]);

        return back()->with('status', 'Reply posted.');
    }
}
