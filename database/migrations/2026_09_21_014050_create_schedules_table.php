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
        Schema::hasTable('schedules') || Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('establishment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('day_of_week');

            $table->time('opens_at');

            $table->time('closes_at');

            $table->string('status', 30)->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
