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
        Schema::create('chatbot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->boolean('orders_enabled')->default(true);
            $table->string('welcome_message', 500)->nullable();
            $table->text('business_info')->nullable();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('establishment_id');
            $table->json('draft')->nullable()->after('status');
            $table->dateTime('last_activity_at')->nullable()->after('draft');
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->json('payload')->nullable();
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'draft', 'last_activity_at']);
        });

        Schema::dropIfExists('chatbot_settings');
    }
};
