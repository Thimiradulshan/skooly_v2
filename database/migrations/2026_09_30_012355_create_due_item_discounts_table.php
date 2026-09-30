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
        Schema::create('due_item_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_due_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('value', 12, 2);
            $table->string('value_type')->nullable();
            $table->decimal('amount_applied', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('due_item_discounts');
    }
};
