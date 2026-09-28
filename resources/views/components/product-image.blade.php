@props(['product', 'loading' => 'lazy'])

@if ($product->banner)
    <img src="{{ $product->banner->url() }}" alt="Preview of {{ $product->title }}" loading="{{ $loading }}" {{ $attributes->merge(['class' => 'product-image']) }}>
@endif
