<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'signup' ? 'Create account' : 'Sign in' }} — Forge Market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body data-initial-mode="{{ $mode }}">

<div class="auth-split">
    <section class="auth-hero">
        <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:10px;color:#fff">
            <span class="dash-logo" style="background:#fff;color:#0B0F19">F</span>
            <span style="line-height:1.05">
                <span style="display:block;font-weight:800;font-size:16.5px;letter-spacing:-0.02em">Forge Market</span>
                <span class="mono" style="font-size:9.5px;letter-spacing:0.14em;text-transform:uppercase;color:#98A0B3">by Forge Studio</span>
            </span>
        </a>

        <div>
            <h1>One account for buying, selling and shipping software.</h1>
            <p>Licenses and downloads, author payouts, service projects and support — all in the same place, with the studio one click away.</p>
            <div style="display:grid;gap:11px;margin-top:26px;max-width:460px">
                <div class="perk-card">
                    <span>✓</span>
                    <div><div class="title">Licenses that stay put</div><div class="note">Keys, domains and support windows in one list.</div></div>
                </div>
                <div class="perk-card">
                    <span>✦</span>
                    <div><div class="title">Author payouts, monthly</div><div class="note">70–80% share, statements and tax documents.</div></div>
                </div>
                <div class="perk-card">
                    <span>◈</span>
                    <div><div class="title">Reviewed before publication</div><div class="note">Every third-party listing is tested by a studio engineer.</div></div>
                </div>
                <div class="perk-card">
                    <span>◐</span>
                    <div><div class="title">The studio, one click away</div><div class="note">Customization, installation and custom builds.</div></div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:26px;flex-wrap:wrap">
            <div class="auth-stat"><div class="v">{{ number_format(\App\Models\User::where('role', 'customer')->count()) }}</div><div class="k">Accounts</div></div>
            <div class="auth-stat"><div class="v">{{ number_format(\App\Models\User::where('role', 'author')->count()) }}</div><div class="k">Authors</div></div>
            <div class="auth-stat"><div class="v">{{ number_format(\App\Models\Product::avg('rating_avg') ?? 0, 1) }}★</div><div class="k">Avg rating</div></div>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-panel-inner">
            <div class="card card-pad">
                <div class="auth-tabs">
                    <button type="button" id="tab-signin" class="auth-tab">Sign in</button>
                    <button type="button" id="tab-signup" class="auth-tab">Create account</button>
                </div>

                <div style="margin-top:20px">
                    <div class="eyebrow" id="role-label">Sign in as</div>
                    <div class="role-picks">
                        <button type="button" class="role-pick" data-role="customer">
                            <span class="lbl">🛒 Customer</span>
                            <span class="note">Buy &amp; manage licenses</span>
                        </button>
                        <button type="button" class="role-pick" data-role="author">
                            <span class="lbl">⌨ Developer</span>
                            <span class="note">Sell your products</span>
                        </button>
                        <button type="button" class="role-pick" data-role="admin">
                            <span class="lbl">◈ Studio admin</span>
                            <span class="note">Owner console</span>
                        </button>
                    </div>
                </div>

                <h2 id="auth-heading" style="margin:22px 0 0;font-size:21px;letter-spacing:-0.025em;font-weight:780"></h2>
                <p id="auth-subheading" style="margin:7px 0 0;font-size:13.5px;color:var(--muted);line-height:1.55"></p>

                @if (session('status'))
                    <div class="alert alert-success" style="margin-top:16px">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error" style="margin-top:16px">{{ $errors->first() }}</div>
                @endif

                {{-- Sign in --}}
                <form method="POST" action="{{ route('login') }}" id="form-signin" style="display:grid;gap:13px;margin-top:20px">
                    @csrf
                    <div class="field">
                        <label>Work email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="field">
                        <label style="display:flex;justify-content:space-between;align-items:baseline">
                            <span>Password</span>
                            <a href="{{ route('password.request') }}" style="font-size:12px">Forgot?</a>
                        </label>
                        <input type="password" name="password" required>
                    </div>
                    <label style="display:flex;gap:9px;align-items:center;font-size:12.5px;color:#4A5262">
                        <input type="checkbox" name="remember" value="1" checked style="width:15px;height:15px;accent-color:oklch(0.52 0.17 268)">
                        <span>Keep me signed in on this device</span>
                    </label>
                    <button type="submit" class="btn btn-dark" style="height:50px;border-radius:13px">Sign in</button>
                </form>

                {{-- Create account --}}
                <form method="POST" action="{{ route('register') }}" id="form-signup" style="display:grid;gap:13px;margin-top:20px">
                    @csrf
                    <input type="hidden" name="intended_role" value="customer">
                    <div class="field-row">
                        <div class="field">
                            <label>Full name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="field">
                            <label id="org-label">Company</label>
                            <input type="text" name="company" value="{{ old('company') }}" id="org-input">
                        </div>
                    </div>
                    <div class="field">
                        <label>Work email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" required minlength="8">
                    </div>
                    <label style="display:flex;gap:10px;align-items:flex-start;font-size:12.5px;color:#4A5262;line-height:1.5">
                        <input type="checkbox" name="terms" value="1" required style="width:15px;height:15px;margin-top:2px;accent-color:oklch(0.52 0.17 268)">
                        <span id="terms-copy">I accept the terms of service and the refund policy.</span>
                    </label>
                    <button type="submit" class="btn btn-dark" id="signup-submit" style="height:50px;border-radius:13px"></button>
                    <div id="approval-note" style="display:none;gap:10px;background:#F7F8FA;border:1px solid #E6E8EE;border-radius:12px;padding:12px 13px">
                        <p style="margin:0;font-size:12.5px;line-height:1.55;color:#4A5262" id="approval-copy"></p>
                    </div>
                </form>

                <div style="display:flex;align-items:center;gap:12px;margin:20px 0">
                    <div style="flex:1;height:1px;background:#EBEDF2"></div>
                    <span class="mono" style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:#98A0B3">or continue with</span>
                    <div style="flex:1;height:1px;background:#EBEDF2"></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:9px">
                    <button type="button" class="oauth-btn" title="Not available yet" disabled>Google</button>
                    <button type="button" class="oauth-btn" title="Not available yet" disabled>GitHub</button>
                    <button type="button" class="oauth-btn" title="Not available yet" disabled>SSO</button>
                </div>
            </div>

            @if (app()->environment(['local', 'staging']))
                <div class="card card-pad" style="margin-top:14px">
                    <div style="display:flex;align-items:center;gap:9px">
                        <span>🔑</span>
                        <span style="font-size:13.5px;font-weight:700">Demo accounts — one click</span>
                        <span class="pill pill-wait" style="margin-left:auto">{{ strtoupper(app()->environment()) }} only</span>
                    </div>
                    <div style="display:grid;gap:8px;margin-top:12px">
                        @foreach ([
                            ['name' => 'Ana Duarte', 'email' => 'admin@forgemarket.test', 'initials' => 'AD', 'role' => 'Admin', 'bg' => '#0B0F19'],
                            ['name' => 'Mara Okonkwo', 'email' => 'mara@forgemarket.test', 'initials' => 'MO', 'role' => 'Developer', 'bg' => 'linear-gradient(140deg,oklch(0.52 0.17 268),#2A3350)'],
                            ['name' => 'Jordan Reyes', 'email' => 'customer@forgemarket.test', 'initials' => 'JR', 'role' => 'Customer', 'bg' => 'linear-gradient(140deg,#4A5262,#0B0F19)'],
                        ] as $demo)
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <input type="hidden" name="email" value="{{ $demo['email'] }}">
                                <input type="hidden" name="password" value="password">
                                <button type="submit" class="demo-account-btn">
                                    <span style="width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:11.5px;font-weight:700;flex-shrink:0;color:#fff;background:{{ $demo['bg'] }}">{{ $demo['initials'] }}</span>
                                    <span style="flex:1;min-width:0">
                                        <span style="display:block;font-size:13px;font-weight:650;color:#0B0F19">{{ $demo['name'] }}</span>
                                        <span class="mono" style="display:block;font-size:10px;color:#98A0B3;margin-top:2px">{{ $demo['email'] }}</span>
                                    </span>
                                    <span class="pill">{{ $demo['role'] }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="text-align:center;margin-top:14px;font-size:12.5px;color:#7A8296">
                <a href="{{ route('home') }}">← Back to the marketplace</a>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    var mode = document.body.dataset.initialMode === 'signup' ? 'signup' : 'signin';
    var role = 'customer';

    var tabSignin = document.getElementById('tab-signin');
    var tabSignup = document.getElementById('tab-signup');
    var formSignin = document.getElementById('form-signin');
    var formSignup = document.getElementById('form-signup');
    var heading = document.getElementById('auth-heading');
    var subheading = document.getElementById('auth-subheading');
    var roleLabel = document.getElementById('role-label');
    var roleButtons = document.querySelectorAll('.role-pick');
    var submitBtn = document.getElementById('signup-submit');
    var orgLabel = document.getElementById('org-label');
    var orgInput = document.getElementById('org-input');
    var termsCopy = document.getElementById('terms-copy');
    var approvalNote = document.getElementById('approval-note');
    var approvalCopy = document.getElementById('approval-copy');
    var intendedRoleInput = formSignup.querySelector('input[name="intended_role"]');

    var copy = {
        signin: {
            customer: ['Welcome back', 'Your purchases, licenses, downloads and service orders.'],
            author: ['Author sign in', 'Sales, submissions, reviews and payouts for your listings.'],
            admin: ['Studio console', 'Owner access to the review queue, finance and every setting.']
        },
        signup: {
            customer: ['Create your account', 'Buy once, download forever — and hire the studio when you need more.'],
            author: ['Apply as a developer', 'Sell on Forge Market with a hand-reviewed listing.'],
            admin: ['Request console access', 'Studio admin accounts are provisioned by an existing owner.']
        }
    };
    var submitLabels = { customer: 'Create account', author: 'Apply to sell', admin: 'Request access' };
    var orgLabels = { customer: ['Company', 'Stacklane'], author: ['Author alias', 'mara.dev'], admin: ['Team', 'Forge Studio'] };
    var termsCopyByRole = {
        customer: 'I accept the terms of service and the refund policy.',
        author: 'I accept the author agreement and understand every listing is reviewed by Forge Studio before publication.',
        admin: 'I accept the terms of service. Console access still requires manual approval.'
    };
    var approvalCopyByRole = {
        author: 'Developer applications are reviewed by the studio — usually within a few business days.',
        admin: 'Console access is granted by an existing owner. Your request is sent to the studio for approval.'
    };

    function paint() {
        tabSignin.classList.toggle('is-active', mode === 'signin');
        tabSignup.classList.toggle('is-active', mode === 'signup');
        formSignin.style.display = mode === 'signin' ? 'grid' : 'none';
        formSignup.style.display = mode === 'signup' ? 'grid' : 'none';

        var c = copy[mode][role];
        heading.textContent = c[0];
        subheading.textContent = c[1];
        roleLabel.textContent = mode === 'signup' ? 'I want to' : 'Sign in as';

        roleButtons.forEach(function (b) { b.classList.toggle('is-active', b.dataset.role === role); });
        intendedRoleInput.value = role;
        submitBtn.textContent = submitLabels[role];
        orgLabel.textContent = orgLabels[role][0];
        orgInput.placeholder = orgLabels[role][1];
        termsCopy.textContent = termsCopyByRole[role];

        if (mode === 'signup' && approvalCopyByRole[role]) {
            approvalNote.style.display = 'flex';
            approvalCopy.textContent = approvalCopyByRole[role];
        } else {
            approvalNote.style.display = 'none';
        }
    }

    tabSignin.addEventListener('click', function () { mode = 'signin'; paint(); });
    tabSignup.addEventListener('click', function () { mode = 'signup'; paint(); });
    roleButtons.forEach(function (b) {
        b.addEventListener('click', function () { role = b.dataset.role; paint(); });
    });

    paint();
})();
</script>

</body>
</html>
