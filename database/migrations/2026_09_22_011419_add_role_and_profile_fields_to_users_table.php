<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('email'); // admin | author | customer
            $table->string('company')->nullable()->after('role');
            $table->string('country')->nullable()->after('company');

            // Author-specific
            $table->string('author_tier')->default('standard')->after('country'); // standard | exclusive | studio_original
            $table->unsignedTinyInteger('commission_pct')->default(70)->after('author_tier');
            $table->string('standing')->default('good')->after('commission_pct'); // good | probation | suspended
            $table->string('payout_method')->nullable()->after('standing');
            $table->boolean('two_factor_enabled')->default(false)->after('payout_method');

            // Author application workflow (customer -> author)
            $table->string('author_application_status')->nullable()->after('two_factor_enabled'); // pending | approved | rejected
            $table->text('author_application_note')->nullable()->after('author_application_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'company', 'country', 'author_tier', 'commission_pct',
                'standing', 'payout_method', 'two_factor_enabled',
                'author_application_status', 'author_application_note',
            ]);
        });
    }
};
