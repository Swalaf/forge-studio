<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PaystackClient
{
    public function initialize(array $payload): array
    {
        return $this->request()->post('/transaction/initialize', $payload)->throw()->json();
    }

    public function verify(string $reference): array
    {
        return $this->request()->get('/transaction/verify/'.$reference)->throw()->json();
    }

    public function refund(string $transactionReference): array
    {
        return $this->request()->post('/refund', ['transaction' => $transactionReference])->throw()->json();
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (! $signature || ! config('services.paystack.secret_key')) {
            return false;
        }

        $expected = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

        return hash_equals($expected, $signature);
    }

    private function request(): PendingRequest
    {
        return Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl(config('services.paystack.base_url'));
    }
}
