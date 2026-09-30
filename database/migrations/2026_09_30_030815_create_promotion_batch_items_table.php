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
        Schema::create('promotion_batch_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_enrollment_id')->constrained('enrollments')->restrictOnDelete();
            $table->foreignId('source_section_id')->constrained('sections')->restrictOnDelete();
            $table->foreignId('target_grade_id')->nullable()->constrained('grades')->restrictOnDelete();
            $table->foreignId('target_section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $table->string('action');
            $table->string('status')->default('pending');
            $table->foreignId('applied_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->timestamps();

            $table->unique(['promotion_batch_id', 'student_id'], 'promotion_batch_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_batch_items');
    }
};
