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
        Schema::create('coop_members', function (Blueprint $table) {
            $table->id();
            $table->string('coop_id');
            $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('position', ['Chairman', 'Treasurer', 'Manager', 'Member']);
            $table->string('first_name')->nullable();
            $table->char('middle_initial')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->boolean('is_representative')->default(false);
            $table->year('active_year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coop_members');
    }
};
