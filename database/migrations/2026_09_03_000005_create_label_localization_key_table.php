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
        Schema::create('label_localization_key', function (Blueprint $table) {
            $table->id();
            $table->foreignId('localization_key_id')->constrained('localization_keys')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('labels')->cascadeOnDelete();

            // Indexing Techniques:
            // 1. Unique Primary Composite Constraint
            $table->unique(['localization_key_id', 'label_id'], 'uniq_key_label');

            // 2. Reverse Composite Index for fast WHERE IN label filters
            $table->index(['label_id', 'localization_key_id'], 'idx_pivot_label_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_localization_key');
    }
};
