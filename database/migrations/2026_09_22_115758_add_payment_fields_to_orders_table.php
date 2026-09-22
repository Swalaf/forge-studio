<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('usd')->after('payment_method');
            $table->string('payment_gateway')->nullable()->after('currency'); // stripe | paystack
            $table->string('gateway_reference')->nullable()->unique()->after('payment_gateway');
            $table->timestamp('paid_at')->nullable()->after('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'payment_gateway', 'gateway_reference', 'paid_at']);
        });
    }
};
