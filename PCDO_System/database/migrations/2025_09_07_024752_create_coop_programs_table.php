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
        Schema::create('coop_programs', function (Blueprint $table) {
            $table->id();
            $table->string('coop_id');
            $table->foreign('coop_id')->references('id')->on('cooperatives');
            $table->foreignId('program_id')->constrained('programs');
            $table->string('project')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date');
            $table->enum('program_status', ['Finished', 'Ongoing', 'Resolved'])->nullable();
            $table->boolean('exported')->default(false);
            $table->boolean('archived')->default(false);
            $table->string('consenter')->nullable();
            $table->integer('loan_amount')->nullable();
            $table->integer('with_grace')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coop_programs');
    }
};