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
        Schema::table('employee_education', function (Blueprint $table) {
            $table->foreign(['employee_id'], 'employee_education_ibfk_1')->references(['id'])->on('employees')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['level_id'], 'employee_education_ibfk_2')->references(['id'])->on('education_levels')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_education', function (Blueprint $table) {
            $table->dropForeign('employee_education_ibfk_1');
            $table->dropForeign('employee_education_ibfk_2');
        });
    }
};
