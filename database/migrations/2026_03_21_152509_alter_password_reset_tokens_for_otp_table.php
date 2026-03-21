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
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->string('phone_no', 20)->nullable()->after('email');
            $table->string('otp', 20)->nullable()->after('token');
            $table->timestamp('expires_at')->nullable()->after('created_at');
            $table->timestamp('verified_at')->nullable()->after('expires_at');

            $table->index('phone_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropIndex(['phone_no']);
            $table->dropColumn(['phone_no', 'otp', 'expires_at', 'verified_at']);
        });
    }
};