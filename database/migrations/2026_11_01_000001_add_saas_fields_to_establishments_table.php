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
        Schema::table('establishments', function (Blueprint $table) {
            $table->string('slug', 180)->nullable()->after('name');
            $table->string('logo')->nullable()->after('email');
            $table->string('cover_image')->nullable()->after('logo');
            $table->string('operating_status', 30)->default('open')->after('status');
        });

        $used = [];

        foreach (DB::table('establishments')->orderBy('id')->get(['id', 'name']) as $establishment) {
            $base = Str::slug($establishment->name) ?: 'establecimiento';
            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $used, true)) {
                $slug = $base.'-'.$suffix++;
            }

            $used[] = $slug;

            DB::table('establishments')->where('id', $establishment->id)->update(['slug' => $slug]);
        }

        Schema::table('establishments', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establishments', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'logo', 'cover_image', 'operating_status']);
        });
    }
};
