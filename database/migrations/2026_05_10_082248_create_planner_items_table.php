<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('scheduled_for');
            $table->enum('type', ['meeting', 'deadline', 'task'])->default('task');
            $table->timestamps();

            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_items');
    }
};
