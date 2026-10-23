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
        Schema::table('guardians', function (Blueprint $table) {
            $table->timestamp('payment_reminder_email_opt_in_at')->nullable();
            $table->timestamp('payment_reminder_email_verified_at')->nullable();
            $table->timestamp('payment_reminder_email_opt_out_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn([
                'payment_reminder_email_opt_in_at',
                'payment_reminder_email_verified_at',
                'payment_reminder_email_opt_out_at',
            ]);
        });
    }
};
