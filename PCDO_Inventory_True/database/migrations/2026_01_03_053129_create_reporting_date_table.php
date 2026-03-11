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
        Schema::create('reporting_dates', function (Blueprint $table) {
            $table->id();
            $table->integer('reporting_year');
            $table->integer('reporting_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporting_dates');
    }
};
