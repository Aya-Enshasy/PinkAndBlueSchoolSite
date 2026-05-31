<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('grade');
            $table->string('subject', 40);
            $table->unsignedTinyInteger('unit_no')->default(1);
            $table->string('unit_title')->nullable();
            $table->string('title');
            $table->string('status', 24)->default('published');
            $table->unsignedSmallInteger('xp_reward')->default(20);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['grade', 'subject', 'unit_no']);
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
