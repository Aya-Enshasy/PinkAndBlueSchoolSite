<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('grade');
            $table->string('academic_year');
            $table->string('student_id_number');
            $table->string('father_id_number');
            $table->string('mobile_number');
            $table->string('alternative_mobile_number')->nullable();
            $table->date('birth_date');
            $table->text('student_id_image')->nullable();
            $table->text('father_id_image')->nullable();
            $table->text('birth_certificate_image')->nullable();
            $table->timestamps();

            $table->unique(['student_id_number', 'academic_year'], 'students_id_year_unique');
            $table->index(['grade', 'academic_year']);
            $table->index(['full_name', 'father_id_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
