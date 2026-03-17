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
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropIndex(['email', 'used_at', 'expires_at']);
            $table->renameColumn('email', 'phone_number');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->index(['phone_number', 'used_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropIndex(['phone_number', 'used_at', 'expires_at']);
            $table->renameColumn('phone_number', 'email');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->index(['email', 'used_at', 'expires_at']);
        });
    }
};
