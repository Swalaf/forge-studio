<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('app:production-readiness-check')]
#[Description('Validate critical production configuration before launch')]
class ProductionReadinessCheck extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $failures = collect([
            $this->checkAppEnvironment(),
            $this->checkAppKey(),
            $this->checkAppUrl(),
            $this->checkDatabase(),
            $this->checkMail(),
            $this->checkPayments(),
            $this->checkSessionSecurity(),
            $this->checkQueues(),
            $this->checkCache(),
            $this->checkBuildManifest(),
            $this->checkStorageLink(),
        ])->flatten()->filter()->values();

        if ($failures->isEmpty()) {
            $this->info('Production readiness checks passed.');

            return self::SUCCESS;
        }

        $this->error('Production readiness checks failed:');

        foreach ($failures as $failure) {
            $this->line("- {$failure}");
        }

        return self::FAILURE;
    }

    /**
     * @return list<string>
     */
    private function checkAppEnvironment(): array
    {
        $failures = [];

        if (! app()->environment('production')) {
            $failures[] = 'APP_ENV must be production.';
        }

        if (config('app.debug') !== false) {
            $failures[] = 'APP_DEBUG must be false.';
        }

        return $failures;
    }

    /**
     * @return list<string>
     */
    private function checkAppKey(): array
    {
        if (filled(config('app.key'))) {
            return [];
        }

        return ['APP_KEY must be generated with php artisan key:generate.'];
    }

    /**
     * @return list<string>
     */
    private function checkAppUrl(): array
    {
        $appUrl = (string) config('app.url');

        if (blank($appUrl) || Str::contains($appUrl, ['localhost', '127.0.0.1', 'example.com'])) {
            return ['APP_URL must be set to the real production domain.'];
        }

        if (! Str::startsWith($appUrl, 'https://')) {
            return ['APP_URL must use HTTPS in production.'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function checkDatabase(): array
    {
        $connection = (string) config('database.default');

        if ($connection === 'sqlite') {
            return ['DB_CONNECTION should use a managed production database such as mysql, mariadb, or pgsql.'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function checkMail(): array
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['array', 'log'], true)) {
            return ['MAIL_MAILER must use a real provider such as smtp, postmark, ses, or resend.'];
        }

        if (blank(config('mail.from.address')) || config('mail.from.address') === 'hello@example.com') {
            return ['MAIL_FROM_ADDRESS must be a real sender address for the production domain.'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function checkPayments(): array
    {
        $requiredKeys = [
            'services.stripe.key' => 'STRIPE_KEY',
            'services.stripe.secret' => 'STRIPE_SECRET',
            'services.stripe.webhook_secret' => 'STRIPE_WEBHOOK_SECRET',
            'services.paystack.public_key' => 'PAYSTACK_PUBLIC_KEY',
            'services.paystack.secret_key' => 'PAYSTACK_SECRET_KEY',
        ];

        $failures = [];

        foreach ($requiredKeys as $configKey => $environmentKey) {
            $value = config($configKey);

            if (blank($value) || Str::contains((string) $value, ['your-', 'placeholder', 'change-me'])) {
                $failures[] = "{$environmentKey} must be set to a live production value.";
            }
        }

        return $failures;
    }

    /**
     * @return list<string>
     */
    private function checkSessionSecurity(): array
    {
        $failures = [];

        if (config('session.secure') !== true) {
            $failures[] = 'SESSION_SECURE_COOKIE must be true.';
        }

        if (config('session.encrypt') !== true) {
            $failures[] = 'SESSION_ENCRYPT must be true.';
        }

        if (config('session.http_only') !== true) {
            $failures[] = 'SESSION_HTTP_ONLY must be true.';
        }

        return $failures;
    }

    /**
     * @return list<string>
     */
    private function checkQueues(): array
    {
        if (config('queue.default') === 'sync') {
            return ['QUEUE_CONNECTION should not be sync in production; run a queue worker.'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function checkCache(): array
    {
        if (config('cache.default') === 'array') {
            return ['CACHE_STORE should not be array in production.'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function checkBuildManifest(): array
    {
        if (File::exists(public_path('build/manifest.json'))) {
            return [];
        }

        return ['Production assets are missing; run npm ci && npm run build before deploy.'];
    }

    /**
     * @return list<string>
     */
    private function checkStorageLink(): array
    {
        if (File::exists(public_path('storage'))) {
            return [];
        }

        return ['Public storage link is missing; run php artisan storage:link.'];
    }
}
