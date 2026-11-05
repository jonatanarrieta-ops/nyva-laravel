<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alinea las bases creadas con el esquema anterior sin eliminar datos.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });

        Schema::table('establishments', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        foreach (['categories', 'products'] as $table) {
            DB::table($table)->where('status', '1')->update(['status' => 'active']);
            DB::table($table)->where('status', '0')->update(['status' => 'inactive']);
        }
    }

    /**
     * Las columnas agregadas no se eliminan para conservar la información.
     */
    public function down(): void
    {
        //
    }
};
