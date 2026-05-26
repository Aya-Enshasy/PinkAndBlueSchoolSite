<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $columns = ['address', 'notes', 'personal_photo'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'address')) {
                $table->text('address')->nullable();
            }

            if (! Schema::hasColumn('students', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('students', 'personal_photo')) {
                $table->string('personal_photo')->nullable();
            }
        });
    }
};
