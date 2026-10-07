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
        foreach (['academic_years', 'terms', 'grades', 'sections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('is_archived')->default(false)->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['academic_years', 'terms', 'grades', 'sections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['is_archived']);
                $table->dropColumn('is_archived');
            });
        }
    }
};
