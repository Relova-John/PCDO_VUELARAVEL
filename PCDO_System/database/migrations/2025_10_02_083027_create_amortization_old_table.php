<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_program_id')->constrained()->onDelete('cascade'); // reference the cooperative
            $table->binary('file_content')->nullable();
            $table->timestamps();
        });
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE `old` MODIFY `file_content` LONGBLOB');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old');
    }
};
