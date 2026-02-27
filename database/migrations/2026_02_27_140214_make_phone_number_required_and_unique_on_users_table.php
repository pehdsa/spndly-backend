<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE users SET phone_number = id WHERE phone_number IS NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 50)->nullable(false)->change();
        });

        DB::statement('CREATE UNIQUE INDEX users_phone_number_unique ON users (phone_number) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_phone_number_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 50)->nullable()->change();
        });
    }
};
