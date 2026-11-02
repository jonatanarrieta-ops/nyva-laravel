<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('public_token', 40)->nullable()->after('user_id');
            $table->string('idempotency_key', 64)->nullable()->after('public_token');
            $table->string('channel', 20)->default('web')->after('idempotency_key');
        });

        foreach (DB::table('orders')->pluck('id') as $id) {
            DB::table('orders')->where('id', $id)->update(['public_token' => Str::random(40)]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('public_token');
            $table->unique('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['public_token', 'idempotency_key', 'channel']);
        });
    }
};
