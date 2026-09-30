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
        Schema::create('promotion_batch_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_section_id')->constrained('sections')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['promotion_batch_id', 'source_section_id'], 'promotion_batch_sections_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_batch_sections');
    }
};
