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
        Schema::create('section_year_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_in_charge_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['academic_year_id', 'section_id']);
            $table->index(
                ['class_in_charge_id', 'academic_year_id'],
                'section_year_in_charge_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_year_assignments');
    }
};
