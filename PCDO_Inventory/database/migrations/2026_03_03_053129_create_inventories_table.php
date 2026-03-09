<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\Location;
use PHPUnit\Logging\OpenTestReporting\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_instance_id')->constrained('inventory_instances')->onDelete('cascade');
            $table->string('name');
            $table->string('category');
            $table->string('location');
            $table->decimal('value', 15, 2);
            $table->integer('quantity');
            $table->string('status');
            $table->date('acquired_date');
            $table->string('guarantor_agency');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
