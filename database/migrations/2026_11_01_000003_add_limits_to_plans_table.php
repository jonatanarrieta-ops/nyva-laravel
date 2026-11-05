<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los planes existentes conservan todas las funcionalidades y quedan sin límites.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(30)->after('annual_price');
            $table->unsignedInteger('product_limit')->nullable()->after('duration_days');
            $table->unsignedInteger('user_limit')->nullable()->after('product_limit');
            $table->json('features')->nullable()->after('user_limit');
        });

        DB::table('plans')->update([
            'features' => json_encode(['chatbot', 'reports', 'public_order', 'team']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['duration_days', 'product_limit', 'user_limit', 'features']);
        });
    }
};
