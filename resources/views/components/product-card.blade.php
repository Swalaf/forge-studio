@props(['product'])
<a href="{{ route('market.show', $product) }}" class="product-card">
    <div class="product-shot">
        @if ($product->is_studio_original)
            <span class="badge-corner">Studio original</span>
        @endif
    </div>
    <div class="product-body">
        <div class="product-cat">{{ $product->category?->name ?? 'Uncategorized' }}</div>
        <div class="product-title">{{ $product->title }}</div>
        <div class="product-foot">
            <span><strong style="color:var(--ink)">★ {{ number_format($product->rating_avg, 1) }}</strong> &nbsp; {{ $product->sales_count }} sales</span>
            <span class="product-price">{{ $product->priceFormatted() }}</span>
        </div>
    </div>
</a>
