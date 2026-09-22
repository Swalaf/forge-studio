<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Production-safe seeding: base settings, real categories, and one real
 * admin account — none of the fake products/orders/reviews/tickets that
 * DatabaseSeeder creates for local development and demos.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put('site_name', 'Forge Market', 'general');
        Setting::put('support_email', 'support@forgemarket.test', 'general');
        Setting::put('default_commission_pct', '70', 'general');
        Setting::put('payout_threshold_cents', '5000', 'general');

        Setting::put('home_headline', 'Production software you can ship this week.', 'content');
        Setting::put('home_subhead', 'Scripts, SaaS starters, apps and plugins — built in-house, plus hand-approved work from independent developers.', 'content');
        Setting::put('studio_bio', 'A product studio building and reviewing every listing on this marketplace.', 'content');
        Setting::put('hire_us_headline', 'Need customization, development or installation? Hire us.', 'content');

        $defs = [
            ['SaaS starters', '◈'], ['Scripts', '▤'], ['Mobile apps', '▥'], ['Templates', '▦'],
            ['Plugins', '⌸'], ['APIs', '⇄'], ['CRM', '◫'], ['E-commerce', '✦'],
        ];
        foreach ($defs as [$name, $mark]) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'mark' => $mark]);
        }

        if (User::where('role', 'admin')->exists()) {
            $this->command?->info('An admin account already exists — skipping admin creation.');

            return;
        }

        $email = $this->command?->ask('Admin email', 'admin@'.parse_url(config('app.url'), PHP_URL_HOST) ?: 'example.com')
            ?? 'admin@example.com';
        $password = Str::password(20);

        User::create([
            'name' => 'Site Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'standing' => 'good',
        ]);

        $this->command?->warn("Admin created — email: {$email} / password: {$password}");
        $this->command?->warn('Save that password now — it is only shown here, this once. Change it after first login.');
    }
}
