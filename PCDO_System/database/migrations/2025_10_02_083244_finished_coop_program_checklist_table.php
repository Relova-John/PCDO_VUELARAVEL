<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finished_coop_program_checklist', function (Blueprint $table) {
            $table->id();

            // Keep reference to original coop program (optional)
            $table->foreignId('coop_program_id')
                ->constrained('coop_programs')
                ->cascadeOnDelete();

            // Reference the checklist definition
            $table->foreignId('checklist_id')
                ->constrained('checklists')
                ->cascadeOnDelete();

            // Uploaded file details
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->binary('file_content')->nullable();

            $table->timestamps();
        });

        // Convert to LONGBLOB in mysql
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finished_coop_program_checklist MODIFY file_content LONGBLOB NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finished_coop_program_checklist');
    }
};
