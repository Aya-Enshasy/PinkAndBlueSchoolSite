<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 96)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'revoked_at']);
        });

        Schema::create('student_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('lesson_key');
            $table->string('lesson_title')->nullable();
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('subject', 40)->nullable();
            $table->unsignedTinyInteger('unit_no')->nullable();
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('streak')->default(1);
            $table->unsignedTinyInteger('hearts')->default(3);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedSmallInteger('current_block')->default(0);
            $table->boolean('completed')->default(false);
            $table->json('sections')->nullable();
            $table->json('activity')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'lesson_key'], 'student_progress_unique_lesson');
            $table->index(['grade', 'subject', 'completed']);
            $table->index(['student_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
        Schema::dropIfExists('student_sessions');
    }
};
