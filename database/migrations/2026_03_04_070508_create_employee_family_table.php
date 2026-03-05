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
        Schema::create('employee_family', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id')->index('employee_id');
            $table->string('father_name', 150);
            $table->string('mother_name', 150);
            $table->string('spouse_name', 150)->nullable();
            $table->string('spouse_occupation', 100)->nullable();
            $table->string('spouse_employer', 150)->nullable();
            $table->string('spouse_business_address')->nullable();
            $table->string('emergency_contact_name', 150);
            $table->string('emergency_contact_number', 20);
            $table->string('emergency_relationship', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_family');
    }
};
