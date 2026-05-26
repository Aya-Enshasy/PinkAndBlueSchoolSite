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
        // If this database was initialized outside Laravel migrations (e.g. an existing schema
        // but no `migrations` table), make this migration idempotent so deploys don't fail.
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            // Ensure the expected columns exist; do not try to (re)create constraints here,
            // since they may already exist and/or existing data may violate them.
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('users', 'email')) {
                    $table->string('email')->nullable();
                }
                if (! Schema::hasColumn('users', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable();
                }
                if (! Schema::hasColumn('users', 'password')) {
                    $table->string('password')->nullable();
                }
                if (! Schema::hasColumn('users', 'remember_token')) {
                    $table->rememberToken();
                }
                if (! Schema::hasColumn('users', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
