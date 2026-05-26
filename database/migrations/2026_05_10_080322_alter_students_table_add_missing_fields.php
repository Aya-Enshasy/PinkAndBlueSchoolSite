<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'full_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('full_name')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('students', 'student_id_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('student_id_number')->nullable()->after('full_name');
            });
        }

        if (! Schema::hasColumn('students', 'father_id_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('father_id_number')->nullable()->after('student_id_number');
            });
        }

        if (! Schema::hasColumn('students', 'mobile_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('mobile_number')->nullable()->after('father_id_number');
            });
        }

        if (! Schema::hasColumn('students', 'alternative_mobile_number')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('alternative_mobile_number')->nullable()->after('mobile_number');
            });
        }

        if (! Schema::hasColumn('students', 'birth_date')) {
            Schema::table('students', function (Blueprint $table) {
                $table->date('birth_date')->nullable()->after('alternative_mobile_number');
            });
        }

        if (! Schema::hasColumn('students', 'student_id_image')) {
            Schema::table('students', function (Blueprint $table) {
                $table->text('student_id_image')->nullable()->after('birth_date');
            });
        }

        if (! Schema::hasColumn('students', 'father_id_image')) {
            Schema::table('students', function (Blueprint $table) {
                $table->text('father_id_image')->nullable()->after('student_id_image');
            });
        }

        if (! Schema::hasColumn('students', 'birth_certificate_image')) {
            Schema::table('students', function (Blueprint $table) {
                $table->text('birth_certificate_image')->nullable()->after('father_id_image');
            });
        }

        try {
            Schema::table('students', function (Blueprint $table) {
                $table->unique('student_id_number');
            });
        } catch (\Throwable $e) {
            // Ignore if the unique index already exists.
        }
    }

    public function down(): void
    {
        // Keep data-safe rollback for production-like local environments.
    }
};
