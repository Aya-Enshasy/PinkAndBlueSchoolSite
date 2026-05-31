<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_blocks', 'category')) {
                $table->string('category', 40)->default('explanation')->after('subject');
            }

            if (! Schema::hasColumn('lesson_blocks', 'subtype')) {
                $table->string('subtype', 64)->nullable()->after('category');
            }

            if (! Schema::hasColumn('lesson_blocks', 'is_graded')) {
                $table->boolean('is_graded')->default(false)->after('settings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_blocks', 'is_graded')) {
                $table->dropColumn('is_graded');
            }

            if (Schema::hasColumn('lesson_blocks', 'subtype')) {
                $table->dropColumn('subtype');
            }

            if (Schema::hasColumn('lesson_blocks', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
