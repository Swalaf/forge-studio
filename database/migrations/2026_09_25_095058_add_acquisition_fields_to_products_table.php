<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('listing_type')->default('software')->after('extended_price_cents')->index();
            $table->string('acquisition_sale_type')->nullable()->after('listing_type')->index();
            $table->string('acquisition_status')->default('available')->after('acquisition_sale_type')->index();
            $table->unsignedInteger('asking_price_cents')->nullable()->after('acquisition_status');
            $table->unsignedInteger('reserve_price_cents')->nullable()->after('asking_price_cents');
            $table->unsignedInteger('minimum_offer_cents')->nullable()->after('reserve_price_cents');
            $table->timestamp('auction_starts_at')->nullable()->after('minimum_offer_cents');
            $table->timestamp('auction_ends_at')->nullable()->after('auction_starts_at');
            $table->foreignId('sold_to_user_id')->nullable()->after('auction_ends_at')->constrained('users')->nullOnDelete();
            $table->timestamp('sold_at')->nullable()->after('sold_to_user_id');
            $table->unsignedInteger('monthly_revenue_cents')->nullable()->after('sold_at');
            $table->unsignedInteger('monthly_profit_cents')->nullable()->after('monthly_revenue_cents');
            $table->unsignedInteger('monthly_visitors')->nullable()->after('monthly_profit_cents');
            $table->unsignedInteger('monthly_pageviews')->nullable()->after('monthly_visitors');
            $table->date('business_started_on')->nullable()->after('monthly_pageviews');
            $table->string('repository_url')->nullable()->after('demo_url');
            $table->json('transfer_assets')->nullable()->after('requirements');
            $table->json('verified_metrics')->nullable()->after('transfer_assets');
            $table->text('seller_disclosures')->nullable()->after('verified_metrics');
            $table->text('transfer_notes')->nullable()->after('seller_disclosures');
            $table->text('due_diligence_notes')->nullable()->after('transfer_notes');

            $table->index(['listing_type', 'status', 'acquisition_status']);
            $table->index(['acquisition_sale_type', 'auction_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sold_to_user_id']);
            $table->dropIndex(['listing_type', 'status', 'acquisition_status']);
            $table->dropIndex(['acquisition_sale_type', 'auction_ends_at']);
            $table->dropColumn([
                'listing_type',
                'acquisition_sale_type',
                'acquisition_status',
                'asking_price_cents',
                'reserve_price_cents',
                'minimum_offer_cents',
                'auction_starts_at',
                'auction_ends_at',
                'sold_to_user_id',
                'sold_at',
                'monthly_revenue_cents',
                'monthly_profit_cents',
                'monthly_visitors',
                'monthly_pageviews',
                'business_started_on',
                'repository_url',
                'transfer_assets',
                'verified_metrics',
                'seller_disclosures',
                'transfer_notes',
                'due_diligence_notes',
            ]);
        });
    }
};
