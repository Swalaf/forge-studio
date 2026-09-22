<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description'); // e.g. product title, or "Installation service"
            $table->string('license_type')->nullable(); // regular | extended | null for services
            $table->unsignedInteger('unit_price_cents')->default(0);
            $table->unsignedInteger('commission_cents')->default(0);
            $table->unsignedInteger('author_share_cents')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
