<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX categories_name_unique ON categories (LOWER(name)) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX payment_methods_name_unique ON payment_methods (LOWER(name)) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS categories_name_unique');
        DB::statement('DROP INDEX IF EXISTS payment_methods_name_unique');
    }
};
