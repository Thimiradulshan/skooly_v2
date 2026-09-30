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
        Schema::create('promotion_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('target_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('discarded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_batches');
    }
};
