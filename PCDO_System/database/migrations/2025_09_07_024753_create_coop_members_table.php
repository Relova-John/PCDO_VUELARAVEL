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

            // Personal Information
            $table->enum('position', ['Chairman', 'Treasurer', 'Manager', 'Member']);
            $table->string('contact');
            $table->string('email')->nullable();
            $table->string('first_name');
            $table->char('middle_name');
            $table->string('last_name');
            $table->string('marital_status');
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('telephone')->nullable();
            $table->date('birthdate');
            $table->integer('age');
            $table->enum('sex', ['Male', 'Female']);
            $table->enum('citizenship', ['Filipino', 'Others']);
            $table->string('birthplace');
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->string('religion');
            $table->string('spouse_name')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->integer('spouse_age')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->integer('father_age')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->integer('mother_age')->nullable();
            $table->string('parent_address')->nullable();
            $table->string('emergency_name');
            $table->string('emergency_contact');
            $table->string('dependent1_name')->nullable();
            $table->string('dependent1_relationship')->nullable();
            $table->date('dependent1_birthdate')->nullable();
            $table->integer('dependent1_age')->nullable();
            $table->string('dependent2_name')->nullable();
            $table->string('dependent2_relationship')->nullable();
            $table->date('dependent2_birthdate')->nullable();
            $table->integer('dependent2_age')->nullable();

            // Educational Attainment
            $table->year('elementary_start')->nullable();
            $table->year('elementary_end')->nullable();
            $table->string('elementary_name')->nullable();
            $table->string('elementary_degree')->nullable();

            $table->year('hs_start')->nullable();
            $table->year('hs_end')->nullable();
            $table->string('hs_name')->nullable();
            $table->string('hs_degree')->nullable();

            $table->year('college_start')->nullable();
            $table->year('college_end')->nullable();
            $table->string('college_name')->nullable();
            $table->string('college_degree')->nullable();

            $table->year('course_start')->nullable();
            $table->year('course_end')->nullable();
            $table->string('course_name')->nullable();
            $table->string('course_degree')->nullable();

            $table->year('others_start')->nullable();
            $table->year('others_end')->nullable();
            $table->string('others_name')->nullable();
            $table->string('others_degree')->nullable();

            // Employement Records
            $table->date('company1_start')->nullable();
            $table->date('company1_end')->nullable();
            $table->string('company1_name')->nullable();
            $table->string('company1_position')->nullable();
            $table->string('company1_rfl')->nullable();

            $table->date('company2_start')->nullable();
            $table->date('company2_end')->nullable();
            $table->string('company2_name')->nullable();
            $table->string('company2_position')->nullable();
            $table->string('company2_rfl')->nullable();

            $table->date('company3_start')->nullable();
            $table->date('company3_end')->nullable();
            $table->string('company3_name')->nullable();
            $table->string('company3_position')->nullable();
            $table->string('company3_rfl')->nullable();

            $table->date('company4_start')->nullable();
            $table->date('company4_end')->nullable();
            $table->string('company4_name')->nullable();
            $table->string('company4_position')->nullable();
            $table->string('company4_rfl')->nullable();

            $table->date('company5_start')->nullable();
            $table->date('company5_end')->nullable();
            $table->string('company5_name')->nullable();
            $table->string('company5_position')->nullable();
            $table->string('company5_rfl')->nullable();

            // Character References
            $table->string('ref1_name')->nullable();
            $table->string('ref1_company')->nullable();
            $table->string('ref1_position')->nullable();
            $table->string('ref1_contact')->nullable();

            $table->string('ref2_name')->nullable();
            $table->string('ref2_company')->nullable();
            $table->string('ref2_position')->nullable();
            $table->string('ref2_contact')->nullable();

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
