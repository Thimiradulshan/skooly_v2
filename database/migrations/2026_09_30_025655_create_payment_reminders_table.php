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
        Schema::create('payment_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->foreignId('guardian_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_due_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reminder_type');
            $table->string('status')->default('pending');
            $table->json('due_item_ids');
            $table->json('message_snapshot');
            $table->string('reminder_key')->unique();
            $table->date('scheduled_for')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
