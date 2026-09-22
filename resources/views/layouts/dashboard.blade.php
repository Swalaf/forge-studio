<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $dashTitle ?? 'Forge Market')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

<div class="dash">
    <aside class="dash-sidebar">
        <div class="dash-sidebar-head">
            <span class="dash-logo">F</span>
            <div>
                <div class="dash-sidebar-title">{{ $dashTitle ?? 'Forge Market' }}</div>
                <div class="dash-sidebar-sub">{{ $dashSub ?? '' }}</div>
            </div>
        </div>
        <nav class="dash-nav">
            @foreach ($navGroups as $group)
                <div class="dash-nav-group">
                    @if ($group['group'])
                        <div class="dash-nav-group-label">{{ $group['group'] }}</div>
                    @endif
                    @foreach ($group['items'] as $item)
                        <a href="{{ route($item['route']) }}" class="dash-nav-item {{ $item['active'] ? 'is-active' : '' }}">
                            <span class="mark">{{ $item['mark'] }}</span>
                            <span class="label">{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span class="badge">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="dash-user">
            <span class="dash-user-avatar">{{ auth()->user()->initials() }}</span>
            <div style="min-width:0">
                <div class="dash-user-name">{{ auth()->user()->name }}</div>
                <div class="dash-user-role">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
        </div>
    </aside>

    <main class="dash-main">
        <div class="dash-topbar">
            <div class="dash-search">
                <span>⌕</span>
                <input placeholder="Search…" disabled>
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-left:auto;flex-wrap:wrap">
                <a href="{{ route('home') }}" class="btn btn-outline btn-sm">View storefront ↗</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Sign out</button>
                </form>
            </div>
        </div>

        <div class="dash-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

</body>
</html>
