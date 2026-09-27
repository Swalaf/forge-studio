<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
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

        if (blank($appUrl) || Str::contains($appUrl, ['localhost', '127.0.0.1', 'example.com', 'your-domain.com'])) {
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

        if (Str::contains((string) config("database.connections.{$connection}.password"), ['replace-with', 'replace_with'])) {
            return ['DB_PASSWORD must be replaced with a real production value.'];
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

        if ($mailer === 'smtp' && ! in_array(config('mail.mailers.smtp.scheme'), ['smtp', 'smtps', null], true)) {
            return ['MAIL_SCHEME must be smtp (STARTTLS) or smtps (implicit TLS).'];
        }

        if ($mailer === 'smtp' && (Str::contains((string) config('mail.mailers.smtp.host'), 'your-provider.com')
            || Str::contains((string) config('mail.mailers.smtp.password'), ['replace-with', 'replace_with']))) {
            return ['MAIL_HOST and MAIL_PASSWORD must be configured for a real SMTP provider.'];
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
            'services.stripe.key' => ['STRIPE_KEY', 'pk_live_'],
            'services.stripe.secret' => ['STRIPE_SECRET', 'sk_live_'],
            'services.stripe.webhook_secret' => ['STRIPE_WEBHOOK_SECRET', 'whsec_'],
        ];

        $failures = [];

        foreach ($requiredKeys as $configKey => [$environmentKey, $prefix]) {
            $value = config($configKey);

            if (blank($value) || ! Str::startsWith((string) $value, $prefix)
                || Str::contains((string) $value, ['your-', 'placeholder', 'change-me', 'replace_with', 'replace-with'])) {
                $failures[] = "{$environmentKey} must be set to a live production value.";
            }
        }

        if (strtoupper((string) config('services.stripe.currency')) !== 'USD') {
            $failures[] = 'STRIPE_CURRENCY must be USD because catalog prices are displayed in USD.';
        }

        $paystackKey = (string) config('services.paystack.public_key');
        $paystackSecret = (string) config('services.paystack.secret_key');

        if (filled($paystackKey) || filled($paystackSecret)) {
            if (blank($paystackKey) || blank($paystackSecret)
                || ! Str::startsWith($paystackKey, 'pk_live_') || ! Str::startsWith($paystackSecret, 'sk_live_')
                || Str::contains($paystackKey.$paystackSecret, ['placeholder', 'replace_with', 'replace-with', 'change-me'])) {
                $failures[] = 'PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY must both be real values when Paystack is enabled.';
            }

            if (strtoupper((string) config('services.paystack.currency')) !== 'USD') {
                $failures[] = 'PAYSTACK_CURRENCY must be USD when Paystack is enabled because catalog prices are displayed in USD.';
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

        $domain = ltrim((string) config('session.domain'), '.');
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($domain !== '' && ($domain === 'your-domain.com' || ! is_string($host)
            || ($host !== $domain && ! Str::endsWith($host, '.'.$domain)))) {
            $failures[] = 'SESSION_DOMAIN must match the production APP_URL host (or be unset for host-only cookies).';
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
}
