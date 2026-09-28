@props(['product'])

<div class="listing-divider"></div>
<div class="listing-section-head">
    <div>
        <div class="eyebrow eyebrow-accent">02 / Storefront image</div>
        <h2>Show your software</h2>
        <p>Use a clear screenshot or banner so buyers can see the product before opening its page.</p>
    </div>
</div>
<div class="listing-banner-stage">
    <div class="listing-banner-preview">
        <img id="banner-preview-image" alt="Product banner preview" @if ($product->exists && $product->banner) src="{{ $product->banner->url() }}" @else hidden @endif>
        <div id="banner-preview-empty" class="listing-banner-empty" @if ($product->exists && $product->banner) hidden @endif>
            <span class="listing-banner-mark" aria-hidden="true">F</span>
            <span>Your storefront preview will appear here</span>
        </div>
    </div>
    <div class="listing-upload">
        <div class="listing-upload-icon" aria-hidden="true">IMG</div>
        <div class="field">
            <label for="banner-image">Screenshot or banner</label>
            <input id="banner-image" type="file" name="banner_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <span class="field-hint">JPG, PNG or WebP · 5 MB max · at least 400 × 225 px. Landscape images look best.</span>
            @if ($product->exists && $product->banner)
                <span class="field-hint">Choose a new image to replace the current storefront preview.</span>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('banner-image');
    const image = document.getElementById('banner-preview-image');
    const empty = document.getElementById('banner-preview-empty');
    const original = image.getAttribute('src');
    let previewUrl = null;

    input.addEventListener('change', function () {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        const file = input.files[0];
        previewUrl = file ? URL.createObjectURL(file) : null;
        if (previewUrl || original) image.src = previewUrl || original;
        image.hidden = ! previewUrl && ! original;
        empty.hidden = ! image.hidden;
    });
})();
</script>
