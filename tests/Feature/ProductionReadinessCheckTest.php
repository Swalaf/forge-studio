<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionReadinessCheckTest extends TestCase
{
    public function test_rejects_production_template_placeholders_and_invalid_smtp_scheme(): void
    {
        config()->set('app.url', 'https://your-domain.com');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.password', 'replace-with-a-strong-database-password');
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.scheme', 'tls');
        config()->set('mail.mailers.smtp.host', 'smtp.your-provider.com');
        config()->set('services.stripe.key', 'pk_live_replace_with_live_publishable_key');
        config()->set('services.stripe.secret', 'sk_live_replace_with_live_secret_key');
        config()->set('services.stripe.webhook_secret', 'whsec_replace_with_live_webhook_secret');
        config()->set('session.domain', '.your-domain.com');

        $this->assertSame(1, Artisan::call('app:production-readiness-check'));
        $output = Artisan::output();
        $this->assertStringContainsString('APP_URL must be set to the real production domain.', $output);
        $this->assertStringContainsString('DB_PASSWORD must be replaced', $output);
        $this->assertStringContainsString('MAIL_SCHEME must be smtp', $output);
        $this->assertStringContainsString('STRIPE_KEY must be set', $output);
        $this->assertStringContainsString('SESSION_DOMAIN must match', $output);
    }

    public function test_rejects_paystack_credentials_without_usd_pricing(): void
    {
        config()->set('services.paystack.public_key', 'pk_live_123');
        config()->set('services.paystack.secret_key', 'sk_live_123');
        config()->set('services.paystack.currency', 'NGN');

        $this->assertSame(1, Artisan::call('app:production-readiness-check'));
        $this->assertStringContainsString('PAYSTACK_CURRENCY must be USD', Artisan::output());
    }

    public function test_rejects_test_mode_gateway_keys_in_production(): void
    {
        config()->set('services.stripe.key', 'pk_test_123');
        config()->set('services.stripe.secret', 'sk_test_123');
        config()->set('services.paystack.public_key', 'pk_test_123');
        config()->set('services.paystack.secret_key', 'sk_test_123');

        $this->assertSame(1, Artisan::call('app:production-readiness-check'));
        $output = Artisan::output();
        $this->assertStringContainsString('STRIPE_KEY must be set', $output);
        $this->assertStringContainsString('STRIPE_SECRET must be set', $output);
        $this->assertStringContainsString('PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY must both be real values', $output);
    }
}
