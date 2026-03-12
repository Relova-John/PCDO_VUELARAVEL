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
        Schema::create('access_controls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('type')->index();
            $table->string('token', 64)->unique()->nullable();
            $table->string('code')->nullable()->index();

            $table->boolean('is_active')->default(true);
            $table->boolean('one_time')->default(false);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->string('region_code', 20)->nullable()->index();
            $table->string('province_code', 20)->nullable()->index();
            $table->string('city_code', 20)->nullable()->index();
            $table->string('barangay_code', 20)->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_controls');
    }
};
