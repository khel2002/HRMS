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
        Schema::create('employee_education', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id')->index('employee_id');
            $table->unsignedBigInteger('level_id')->index('level_id');
            $table->string('school_name');
            $table->string('degree_course', 150)->nullable();
            $table->year('period_from')->nullable();
            $table->year('period_to')->nullable();
            $table->string('highest_level_units', 50)->nullable();
            $table->year('year_graduated')->nullable();
            $table->string('scholarship_honors')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_education');
    }
};
