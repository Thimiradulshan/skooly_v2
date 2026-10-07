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
        Schema::create('correction_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_reversal_id')->unique()->constrained()->restrictOnDelete();
            $table->string('receipt_no')->unique();
            $table->dateTime('issued_at');
            $table->json('original_receipt_snapshot');
            $table->json('reversal_snapshot');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correction_receipts');
    }
};
