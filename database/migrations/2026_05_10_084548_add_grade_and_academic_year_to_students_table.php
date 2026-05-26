<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'grade')) {
                $table->string('grade')->nullable()->after('full_name');
            }

            if (! Schema::hasColumn('students', 'academic_year')) {
                $table->string('academic_year')->nullable()->after('grade');
            }
        });

        try {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_student_id_number_unique');
            });
        } catch (\Throwable $e) {
            // unique index may already be removed
        }

        try {
            Schema::table('students', function (Blueprint $table) {
                $table->unique(['student_id_number', 'academic_year'], 'students_id_year_unique');
            });
        } catch (\Throwable $e) {
            // already exists
        }
    }

    public function down(): void
    {
        try {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_id_year_unique');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'academic_year')) {
                $table->dropColumn('academic_year');
            }

            if (Schema::hasColumn('students', 'grade')) {
                $table->dropColumn('grade');
            }
        });

        try {
            Schema::table('students', function (Blueprint $table) {
                $table->unique('student_id_number');
            });
        } catch (\Throwable $e) {
        }
    }
};
