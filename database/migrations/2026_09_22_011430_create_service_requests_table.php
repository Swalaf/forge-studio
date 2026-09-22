<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();

            // Guest submissions from the public "Hire the studio" form keep contact details here.
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();

            $table->string('project_type'); // customization | installation | custom_build
            $table->string('budget_range')->nullable();
            $table->text('message');
            $table->string('status')->default('open'); // open | quoted | accepted | declined

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
