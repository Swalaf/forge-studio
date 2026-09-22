<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedInteger('compare_at_price_cents')->nullable();
            $table->unsignedInteger('extended_price_cents')->nullable();

            $table->string('status')->default('draft');
            // draft | in_review | changes_requested | rejected | scheduled | live | hidden
            $table->boolean('is_studio_original')->default(false);
            $table->boolean('is_featured')->default(false);

            $table->json('tech_stack')->nullable();
            $table->json('requirements')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('current_version')->default('1.0.0');

            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
