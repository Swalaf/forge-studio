<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Forge Market') — by Forge Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

<header class="site-header">
    <div class="site-header-row">
        <a href="{{ route('home') }}" class="brand">
            <span class="dash-logo">F</span>
            <span>
                <span class="brand-name" style="display:block">Forge Market</span>
                <span class="brand-sub">by Forge Studio</span>
            </span>
        </a>
        <input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox">
        <label for="nav-toggle" class="nav-toggle-btn" aria-label="Menu">
            <span></span><span></span><span></span>
        </label>
        <nav class="site-nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
            <a href="{{ route('market.browse') }}" class="{{ request()->routeIs('market.browse') ? 'is-active' : '' }}">Marketplace</a>
            <a href="{{ route('acquisitions.index') }}" class="{{ request()->routeIs('acquisitions.*') ? 'is-active' : '' }}">Projects for sale</a>
            <details class="nav-dropdown">
                <summary>Product</summary>
                <div class="nav-dropdown-menu">
                    @foreach ($navCategories as $category)
                        <a href="{{ route('market.browse', ['category' => $category->slug]) }}">
                            {{ $category->name }} <span>{{ $category->products_count }}</span>
                        </a>
                    @endforeach
                </div>
            </details>
            <a href="{{ route('hire-us') }}" class="{{ request()->routeIs('hire-us*') ? 'is-active' : '' }}">Custom development</a>
            <a href="{{ auth()->check() && auth()->user()->role === 'author' ? route('author.products.create') : route('register', ['role' => 'author']) }}" class="{{ request()->routeIs('author.products.create') ? 'is-active' : '' }}">Submit</a>
            <a href="{{ auth()->check() && auth()->user()->role === 'author' ? route('author.overview') : route('login', ['role' => 'author']) }}" class="{{ request()->routeIs('author.*') ? 'is-active' : '' }}">Developer</a>
            <div class="site-nav-auth">
                @auth
                    <a href="{{ match(auth()->user()->role) { 'admin' => route('admin.overview'), 'author' => route('author.overview'), default => route('account.overview') } }}" class="btn btn-outline btn-sm">My account</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Sign in</a>
                    <a href="{{ route('register') }}" class="btn btn-outline btn-sm">Create account</a>
                @endauth
            </div>
        </nav>
        <div class="site-header-auth">
            @auth
                <a href="{{ match(auth()->user()->role) { 'admin' => route('admin.overview'), 'author' => route('author.overview'), default => route('account.overview') } }}" class="btn btn-outline btn-sm">My account</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Sign in</a>
                <a href="{{ route('register') }}" class="btn btn-outline btn-sm">Create account</a>
            @endauth
        </div>
    </div>
</header>

<main>
    @if (session('status'))
        <div class="container" style="padding-top:20px"><div class="alert alert-success">{{ session('status') }}</div></div>
    @endif
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container" style="padding:0">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="brand">
                    <span class="dash-logo">F</span>
                    <span>
                        <span class="brand-name" style="display:block">Forge Market</span>
                        <span class="brand-sub">by Forge Studio</span>
                    </span>
                </a>
                <p>Production software, vetted and shipped — plus the engineering team behind it, one click away.</p>
            </div>
            <div class="footer-col">
                <div class="footer-col-title">Marketplace</div>
                <a href="{{ route('market.browse') }}">Browse all products</a>
                <a href="{{ route('acquisitions.index') }}">Buy one-time projects</a>
                <a href="{{ route('market.browse') }}">Studio originals</a>
                <a href="{{ route('hire-us') }}">Hire the studio</a>
            </div>
            <div class="footer-col">
                <div class="footer-col-title">Account</div>
                @auth
                    <a href="{{ match(auth()->user()->role) { 'admin' => route('admin.overview'), 'author' => route('author.overview'), default => route('account.overview') } }}">My account</a>
                @else
                    <a href="{{ route('login') }}">Sign in</a>
                    <a href="{{ route('register') }}">Create account</a>
                    <a href="{{ route('password.request') }}">Forgot password</a>
                @endauth
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ now()->year }} Forge Studio. All rights reserved.</span>
            <span>Built with Laravel.</span>
        </div>
    </div>
</footer>

</body>
</html>
