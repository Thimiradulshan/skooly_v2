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
        Schema::create('teacher_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id');
            $table->foreignId('subject_id');
            $table->timestamps();

            $table->unique(
                ['academic_year_id', 'section_id', 'subject_id', 'teacher_id'],
                'teacher_assignments_unique'
            );
            $table->index(['teacher_id', 'academic_year_id']);
            $table->foreign(
                ['teacher_id', 'subject_id'],
                'teacher_assignments_qualification_foreign'
            )->references(['teacher_id', 'subject_id'])
                ->on('subject_teacher')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
