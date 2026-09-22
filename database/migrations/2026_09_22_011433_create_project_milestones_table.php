<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_project_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('status')->default('upcoming'); // done | in_progress | upcoming
            $table->string('date_label')->nullable(); // "Done 04 Sep" / "From 30 Sep" etc.
            $table->unsignedSmallInteger('sort')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
