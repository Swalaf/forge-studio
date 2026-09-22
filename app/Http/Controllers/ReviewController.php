<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        Review::create([
            'product_id' => $product->id,
            'customer_id' => $request->user()->id,
            'rating' => $data['rating'],
            'body' => $data['body'],
        ]);

        $product->update([
            'rating_count' => $product->reviews()->count(),
            'rating_avg' => round($product->reviews()->avg('rating'), 2),
        ]);

        return back()->with('status', 'Thanks for the review.');
    }
}
