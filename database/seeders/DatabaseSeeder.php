<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\ProjectMilestone;
use App\Models\Review;
use App\Models\ServiceProject;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->call(ProductionSeeder::class);

            return;
        }

        $this->seedSettings();
        $categories = $this->seedCategories();
        [$admin, $authors] = $this->seedPeople();
        $products = $this->seedProducts($categories, $authors);
        $this->seedVersions($products, $admin);
        $customer = $this->seedCustomerActivity($products);
        $this->seedPayouts($authors);
        $this->seedServiceWork($customer, $admin);
        $this->seedTickets($products, $customer);
    }

    private function seedSettings(): void
    {
        Setting::put('site_name', 'Forge Market', 'general');
        Setting::put('support_email', 'support@forgemarket.test', 'general');
        Setting::put('default_commission_pct', '70', 'general');
        Setting::put('payout_threshold_cents', '5000', 'general');

        Setting::put('home_headline', 'Production software you can ship this week.', 'content');
        Setting::put('home_subhead', 'Scripts, SaaS starters, apps and plugins — built in-house, plus hand-approved work from independent developers.', 'content');
        Setting::put('studio_bio', 'A product studio shipping commercial software since 2016. We build every original listing here, review every third-party submission by hand, and take on custom work for the teams who buy from us.', 'content');
        Setting::put('hire_us_headline', 'Need customization, development or installation? Hire us.', 'content');
    }

    private function seedCategories(): \Illuminate\Support\Collection
    {
        $defs = [
            ['SaaS starters', '◈'], ['Scripts', '▤'], ['Mobile apps', '▥'], ['Templates', '▦'],
            ['Plugins', '⌸'], ['APIs', '⇄'], ['CRM', '◫'], ['E-commerce', '✦'],
        ];

        return collect($defs)->map(fn ($d) => Category::create([
            'name' => $d[0], 'slug' => Str::slug($d[0]), 'mark' => $d[1],
        ]));
    }

    private function seedPeople(): array
    {
        $admin = User::create([
            'name' => 'Ana Duarte', 'email' => 'admin@forgemarket.test', 'password' => Hash::make('password'),
            'role' => 'admin', 'standing' => 'good',
        ]);

        $authors = [
            'studio' => User::create([
                'name' => 'Forge Studio', 'email' => 'studio@forgemarket.test', 'password' => Hash::make('password'),
                'role' => 'author', 'author_tier' => 'studio_original', 'commission_pct' => 100, 'standing' => 'good', 'country' => 'Remote',
            ]),
            'mara' => User::create([
                'name' => 'Mara Okonkwo', 'email' => 'mara@forgemarket.test', 'password' => Hash::make('password'),
                'role' => 'author', 'author_tier' => 'standard', 'commission_pct' => 70, 'standing' => 'good', 'country' => 'Lagos, Nigeria',
                'payout_method' => 'SEPA ···8841',
            ]),
            'kin' => User::create([
                'name' => 'Collective KIN', 'email' => 'kin@forgemarket.test', 'password' => Hash::make('password'),
                'role' => 'author', 'author_tier' => 'exclusive', 'commission_pct' => 80, 'standing' => 'good', 'country' => 'Berlin, Germany',
            ]),
            'ivan' => User::create([
                'name' => 'Ivan Petrov', 'email' => 'ivan@forgemarket.test', 'password' => Hash::make('password'),
                'role' => 'author', 'author_tier' => 'probation', 'commission_pct' => 60, 'standing' => 'probation', 'country' => 'Sofia, Bulgaria',
            ]),
            'dmitri' => User::create([
                'name' => 'Dmitri Aas', 'email' => 'dmitri@forgemarket.test', 'password' => Hash::make('password'),
                'role' => 'author', 'author_tier' => 'standard', 'commission_pct' => 70, 'standing' => 'suspended', 'country' => 'Tallinn, Estonia',
            ]),
            'lena' => User::create([
                'name' => 'Lena Voss', 'email' => 'lena@forgemarket.test', 'password' => Hash::make('password'),
                'role' => 'customer', 'author_application_status' => 'pending',
                'author_application_note' => 'React developer, 5 years — wants to list Harbor CRM Template.', 'country' => 'Vienna, Austria',
            ]),
        ];

        return [$admin, $authors];
    }

    private function seedProducts($categories, array $authors): \Illuminate\Support\Collection
    {
        $byName = $categories->keyBy('name');
        $defs = [
            ['Nimbus SaaS Starter Kit', 'saas', 'studio', 8900, 14900, true, true, 'Multi-tenant SaaS foundation with Stripe billing, RBAC and audit logs.'],
            ['Atlas Admin Dashboard', 'saas', 'studio', 3900, null, true, true, 'A production-grade admin dashboard shell with charts, tables and auth.'],
            ['Beacon Helpdesk SaaS', 'saas', 'studio', 14900, null, true, true, 'Multi-channel helpdesk with SLAs, macros and a customer portal.'],
            ['Parcelly Delivery App', 'mobile-apps', 'studio', 12900, null, true, false, 'A courier dispatch and delivery-tracking mobile app.'],
            ['Signal Analytics API', 'apis', 'studio', 19900, null, true, false, 'Event analytics API written in Go with a queryable dashboard.'],
            ['Tide Push Gateway', 'apis', 'studio', 9900, null, true, false, 'A push-notification gateway supporting APNs, FCM and web push.'],
            ['Ledgerly Invoicing', 'saas', 'mara', 7900, null, true, false, 'Multi-currency invoicing with tax rules and recurring billing.'],
            ['Slate Form Builder', 'plugins', 'mara', 4500, null, false, false, 'A drag-and-drop form builder with conditional logic.'],
            ['Tally Recurring Billing', 'saas', 'mara', 6500, null, false, false, 'Subscription billing with proration and dunning emails.'],
            ['Rota Staff Scheduler', 'saas', 'mara', 5500, null, false, false, 'Shift scheduling and swaps for restaurants and retail teams.'],
            ['Quill CMS Plugin Pack', 'plugins', 'kin', 2900, null, false, false, 'A bundle of CMS plugins for SEO, forms and media.'],
            ['Vault Licensing Script', 'scripts', 'kin', 5900, null, false, false, 'A self-hosted license key generator and validator.'],
            ['Harbor CRM Template', 'crm', 'kin', 5900, null, false, false, 'A React CRM template with pipelines and contact timelines.'],
            ['Pulse Status Page', 'templates', 'ivan', 4900, null, false, false, 'A status-page template with incident history and subscriptions.'],
            ['Kiln Image CDN', 'scripts', 'dmitri', 3500, null, false, false, 'A self-hosted image resizing and CDN script.'],
        ];

        $products = collect();
        foreach ($defs as [$title, $catSlug, $authorKey, $price, $compare, $featured, $studio, $tagline]) {
            $category = $categories->firstWhere('slug', $catSlug);
            $products->push(Product::create([
                'author_id' => $authors[$authorKey]->id, 'category_id' => $category?->id,
                'title' => $title, 'slug' => Str::slug($title), 'tagline' => $tagline,
                'description' => $tagline.' Built for teams who need production-ready software without the six-month build.',
                'price_cents' => $price, 'compare_at_price_cents' => $compare,
                'is_studio_original' => $studio, 'is_featured' => $featured,
                'status' => 'live', 'published_at' => now()->subDays(random_int(10, 400)),
                'tech_stack' => ['PHP', 'Laravel', 'MySQL', 'Alpine.js'],
                'requirements' => ['PHP' => '8.1+', 'Database' => 'MySQL 8 / SQLite', 'Server' => 'Any LAMP host'],
                'current_version' => '1.0.0',
                'rating_avg' => round(4 + (random_int(0, 9) / 10), 2), 'rating_count' => random_int(20, 420),
                'sales_count' => random_int(30, 2400), 'view_count' => random_int(800, 18000),
            ]));
        }

        // In-flight submissions from the mockups: one in review, one needing changes, one draft.
        $products->push(Product::create([
            'author_id' => $authors['mara']->id, 'category_id' => $byName['SaaS starters']->id,
            'title' => 'Orbit Booking Engine', 'slug' => 'orbit-booking-engine', 'tagline' => 'Appointment booking with staff calendars and deposits.',
            'description' => 'Appointment booking with staff calendars and deposits.',
            'price_cents' => 7900, 'status' => 'in_review', 'current_version' => '1.0.0',
        ]));

        $rota = $products->firstWhere('title', 'Rota Staff Scheduler');
        $rota->update(['status' => 'changes_requested']);

        $products->push(Product::create([
            'author_id' => $authors['mara']->id, 'category_id' => $byName['APIs']->id,
            'title' => 'Dispatch SMS Bridge', 'slug' => 'dispatch-sms-bridge', 'tagline' => 'A carrier-agnostic SMS bridge with delivery webhooks.',
            'price_cents' => 3900, 'status' => 'draft', 'current_version' => '1.0.0',
        ]));

        return $products;
    }

    private function seedVersions($products, User $admin): void
    {
        foreach ($products as $product) {
            if ($product->status === 'live') {
                $product->versions()->create([
                    'version' => '1.0.0', 'type' => 'new', 'status' => 'approved',
                    'reviewed_by' => $admin->id, 'submitted_at' => $product->published_at, 'reviewed_at' => $product->published_at,
                ]);
            }
        }

        $orbit = $products->firstWhere('title', 'Orbit Booking Engine');
        $orbit->versions()->create([
            'version' => '1.0.0', 'type' => 'new', 'status' => 'pending', 'submitted_at' => now()->subDays(2),
            'changelog' => 'Initial submission: booking calendar, deposits via Stripe, staff availability rules.',
        ]);

        $rota = $products->firstWhere('title', 'Rota Staff Scheduler');
        $rota->versions()->create([
            'version' => '3.1.1', 'type' => 'patch', 'status' => 'changes_requested',
            'reviewed_by' => $admin->id, 'submitted_at' => now()->subDays(4), 'reviewed_at' => now()->subDays(3),
            'reviewer_notes' => 'Install works on a clean PHP 8.2 box. Two items before approval: the seed data script fails on MySQL 8.4, and the README skips the webhook secret step.',
        ]);

    }

    private function seedCustomerActivity($products): User
    {
        $customer = User::create([
            'name' => 'Jordan Reyes', 'email' => 'customer@forgemarket.test', 'password' => Hash::make('password'),
            'role' => 'customer', 'company' => 'Stacklane',
        ]);

        User::factory()->count(12)->create(['role' => 'customer']);

        $purchases = [
            ['Nimbus SaaS Starter Kit', 'regular'],
            ['Atlas Admin Dashboard', 'extended'],
            ['Vault Licensing Script', 'regular'],
            ['Quill CMS Plugin Pack', 'regular'],
            ['Ledgerly Invoicing', 'regular'],
        ];

        foreach ($purchases as [$title, $licenseType]) {
            $product = $products->firstWhere('title', $title);
            $price = $licenseType === 'extended' ? ($product->extended_price_cents ?? $product->price_cents * 2) : $product->price_cents;

            $order = Order::create([
                'customer_id' => $customer->id, 'type' => 'product', 'status' => 'paid',
                'payment_method' => 'card', 'subtotal_cents' => $price, 'tax_cents' => 0, 'total_cents' => $price,
                'created_at' => now()->subDays(random_int(5, 130)),
            ]);
            $item = $order->items()->create([
                'product_id' => $product->id, 'description' => $product->title, 'license_type' => $licenseType,
                'unit_price_cents' => $price, 'commission_cents' => (int) round($price * 0.3), 'author_share_cents' => (int) round($price * 0.7),
            ]);
            License::create([
                'order_item_id' => $item->id, 'product_id' => $product->id, 'customer_id' => $customer->id,
                'license_key' => 'LIC-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)),
                'license_type' => $licenseType, 'domain' => Str::slug($product->title).'.stacklane.io',
                'support_until' => now()->addMonths(random_int(-1, 12)),
                'status' => random_int(0, 4) === 0 ? 'expiring' : 'active',
            ]);
            $product->increment('sales_count');
        }

        // A few real reviews on products the customer owns.
        $nimbus = $products->firstWhere('title', 'Nimbus SaaS Starter Kit');
        $review = Review::create([
            'product_id' => $nimbus->id, 'customer_id' => $customer->id, 'rating' => 5,
            'body' => 'Multi-currency handling is the cleanest I have seen in this price range. Tax rules took ten minutes to configure.',
        ]);
        $nimbus->update(['rating_count' => $nimbus->reviews()->count(), 'rating_avg' => round($nimbus->reviews()->avg('rating'), 2)]);

        $slate = $products->firstWhere('title', 'Slate Form Builder');
        Review::create([
            'product_id' => $slate->id, 'customer_id' => $customer->id, 'rating' => 4,
            'body' => 'Great builder, but the conditional logic docs assume you already know the schema. A worked example would help a lot.',
        ]);
        $slate->update(['rating_count' => $slate->reviews()->count(), 'rating_avg' => round($slate->reviews()->avg('rating'), 2)]);

        // Saved items.
        $customer->savedItems()->createMany(
            $products->whereNotIn('title', array_column($purchases, 0))->take(4)->pluck('id')
                ->map(fn ($id) => ['product_id' => $id])->all()
        );

        return $customer;
    }

    private function seedPayouts(array $authors): void
    {
        foreach (['mara', 'kin', 'ivan'] as $key) {
            $author = $authors[$key];
            foreach (range(5, 0, -1) as $monthsAgo) {
                $periodEnd = now()->subMonths($monthsAgo)->endOfMonth();
                Payout::create([
                    'author_id' => $author->id,
                    'period_start' => $periodEnd->copy()->startOfMonth(),
                    'period_end' => $periodEnd,
                    'amount_cents' => random_int(80000, 620000),
                    'method' => 'SEPA',
                    'status' => $monthsAgo === 0 ? 'scheduled' : 'paid',
                    'paid_at' => $monthsAgo === 0 ? null : $periodEnd->copy()->addDays(3),
                ]);
            }
        }

        Payout::create([
            'author_id' => $authors['dmitri']->id, 'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(), 'amount_cents' => 74000, 'method' => 'PayPal', 'status' => 'failed',
        ]);
    }

    private function seedServiceWork(User $customer, User $admin): void
    {
        $request = ServiceRequest::create([
            'customer_id' => $customer->id, 'contact_name' => $customer->name, 'contact_email' => $customer->email,
            'project_type' => 'custom_build', 'budget_range' => '5k_20k',
            'message' => 'We need Nimbus white-labeled with SSO for our internal rollout.', 'status' => 'accepted',
        ]);

        $sso = ServiceProject::create([
            'service_request_id' => $request->id, 'customer_id' => $customer->id, 'owner_id' => $admin->id,
            'title' => 'Nimbus white-label + SSO', 'type' => 'custom_build', 'status' => 'active',
            'value_cents' => 1400000, 'billing_note' => 'Milestone billing · 2 of 5 paid',
        ]);
        $this->milestones($sso, [
            ['Discovery', 'done', 'Done 04 Sep'], ['Branding pass', 'done', 'Done 11 Sep'],
            ['SSO module', 'in_progress', 'In progress'], ['UAT', 'upcoming', 'From 30 Sep'], ['Handover', 'upcoming', 'From 10 Oct'],
        ]);

        $install = ServiceProject::create([
            'customer_id' => $customer->id, 'owner_id' => $admin->id,
            'title' => 'Beacon install & configure', 'type' => 'installation', 'status' => 'launching',
            'value_cents' => 18000, 'billing_note' => 'Paid in full',
        ]);
        $this->milestones($install, [
            ['Access details', 'in_progress', 'Waiting on you'], ['Install', 'upcoming', '19 Sep'],
            ['Smoke test', 'upcoming', '19 Sep'], ['Handover call', 'upcoming', '20 Sep'],
        ]);

        $theming = ServiceProject::create([
            'customer_id' => $customer->id, 'owner_id' => $admin->id,
            'title' => 'Atlas theming', 'type' => 'customization', 'status' => 'completed',
            'value_cents' => 420000, 'billing_note' => 'Paid · 30-day cover',
        ]);
        $this->milestones($theming, [
            ['Design tokens', 'done', 'Done 18 Aug'], ['Implementation', 'done', 'Done 28 Aug'], ['Handover', 'done', 'Done 02 Sep'],
        ]);

        Order::create([
            'customer_id' => $customer->id, 'type' => 'service', 'status' => 'paid',
            'payment_method' => 'transfer', 'subtotal_cents' => 18000, 'tax_cents' => 0, 'total_cents' => 18000,
        ])->items()->create(['description' => 'Installation service — Beacon Helpdesk', 'unit_price_cents' => 18000, 'commission_cents' => 0, 'author_share_cents' => 0]);
    }

    private function milestones(ServiceProject $project, array $rows): void
    {
        foreach ($rows as $i => [$name, $status, $label]) {
            ProjectMilestone::create(['service_project_id' => $project->id, 'name' => $name, 'status' => $status, 'date_label' => $label, 'sort' => $i]);
        }
    }

    private function seedTickets($products, User $customer): void
    {
        $nimbus = $products->firstWhere('title', 'Nimbus SaaS Starter Kit');
        $ledgerly = $products->firstWhere('title', 'Ledgerly Invoicing');

        $t1 = Ticket::create([
            'opener_id' => $customer->id, 'product_id' => $nimbus->id, 'subject' => 'Stripe webhook 400s after upgrade',
            'priority' => 'urgent', 'status' => 'open', 'sla_hours' => 4,
        ]);
        $t1->messages()->create(['author_id' => $customer->id, 'body' => 'After upgrading to v3.2.0 our Stripe webhook endpoint started returning 400s intermittently.']);

        $t2 = Ticket::create([
            'opener_id' => $customer->id, 'product_id' => $ledgerly->id, 'subject' => 'Recurring invoice skips leap day',
            'priority' => 'normal', 'status' => 'breaching', 'sla_hours' => 8,
        ]);
        $t2->messages()->create(['author_id' => $customer->id, 'body' => 'A recurring invoice scheduled for Feb 29 was skipped entirely instead of rolling to Mar 1.']);
    }
}
