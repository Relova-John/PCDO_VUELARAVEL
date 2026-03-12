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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('access_control_id')->nullable()->after('role')->constrained('access_controls')->nullOnDelete();

            $table->string('region_code')->nullable()->after('access_control_id');
            $table->string('province_code')->nullable()->after('region_code');
            $table->string('city_code')->nullable()->after('province_code');
            $table->string('barangay_code')->nullable()->after('city_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('access_control_id');
            $table->dropColumn([
                'region_code',
                'province_code',
                'city_code',
                'barangay_code',
            ]);
        });
    }
};
