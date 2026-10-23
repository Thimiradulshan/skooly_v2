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
        if (Schema::hasTable('payment_reminder_delivery_attempts')) {
            Schema::table('payment_reminder_delivery_attempts', function (Blueprint $table) {
                $table->unique(['payment_reminder_id', 'attempt_number'], 'payment_reminder_attempt_no_unique');
            });

            return;
        }

        Schema::create('payment_reminder_delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_reminder_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->foreignId('guardian_id')->constrained()->restrictOnDelete();
            $table->text('recipient_email');
            $table->string('status')->default('queued');
            $table->timestamp('transport_accepted_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_classification')->nullable();
            $table->string('failure_message')->nullable();
            $table->timestamps();

            $table->unique(['payment_reminder_id', 'attempt_number'], 'payment_reminder_attempt_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminder_delivery_attempts');
    }
};
