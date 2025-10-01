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
        Schema::create('coop_details', function (Blueprint $table) {
            $table->string('coop_id')->primary();
            $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('cascade')->onUpdate('cascade');
            $table->string('region_code');
            $table->foreign('region_code')->references('code')->on('regions')->onDelete('cascade');
            $table->string('province_code')->nullable();
            $table->foreign('province_code')->references('code')->on('provinces')->onDelete('cascade');
            $table->string('city_code');
            $table->foreign('city_code')->references('code')->on('cities')->onDelete('cascade');
            $table->string('barangay_code');
            $table->foreign('barangay_code')->references('code')->on('barangays')->onDelete('cascade');
            $table->enum('asset_size', ['Micro', 'Small', 'Medium', 'Large', 'Unclassified']);
            $table->enum('coop_type', ['Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose', 'Advocacy', 'Agrarian Reform', 'Bank', 'Diary', 'Education', 'Electric', 'Financial', 'Fishermen', 'Health Services', 'Housing', 'Insurance', 'Water Service', 'Workers', 'Others']);
            $table->enum('status_category', ['Reporting', 'Non-Reporting', 'New']);
            $table->enum('bond_of_membership', ['Residential', 'Insitutional', 'Associational', 'Occupational', 'Unspecified']);
            $table->enum('area_of_operation', ['Municipal', 'Provincial']);
            $table->enum('citizenship', ['Filipino', 'Foreign', 'Others']);
            $table->bigInteger('members_count')->nullable();
            $table->bigInteger('total_asset')->nullable();
            $table->bigInteger('net_surplus')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coop_details');
    }
};
