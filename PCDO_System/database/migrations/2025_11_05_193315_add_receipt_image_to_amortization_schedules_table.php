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
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->binary('receipt_image')->nullable()->after('notes');
        });

        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE amortization_schedules MODIFY receipt_image LONGBLOB NULL;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->dropColumn('receipt_image');
        });
    }
};
