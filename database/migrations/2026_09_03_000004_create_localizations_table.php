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
        Schema::create('localizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('localization_key_id')->constrained('localization_keys')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->text('content');
            $table->string('status', 20)->default('approved');
            $table->text('context')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexing Techniques:
            // 1. Unique Composite Constraint (Integrity)
            $table->unique(['localization_key_id', 'language_id'], 'uniq_key_lang');

            // 2. Covering Composite Index for Hot Export Path & Filtering (Performance)
            $table->index(['language_id', 'status', 'localization_key_id'], 'idx_localizations_lang_status_key');

            // 3. Composite Index for Status & Date Sorting
            $table->index(['status', 'created_at'], 'idx_localizations_status_created');

            // 4. Foreign Key & Version Audit Indexes
            $table->index('created_by_user_id', 'idx_localizations_created_by');
            $table->index('version', 'idx_localizations_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localizations');
    }
};
