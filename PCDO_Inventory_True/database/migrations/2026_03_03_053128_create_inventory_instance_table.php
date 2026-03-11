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
        Schema::create('inventory_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->onDelete('cascade');
            $table->foreignId('reporting_date_id')->constrained('reporting_dates')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_instances');
    }
};
