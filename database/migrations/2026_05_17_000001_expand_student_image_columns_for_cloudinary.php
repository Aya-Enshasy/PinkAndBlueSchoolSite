<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep each ALTER outside Laravel's migration transaction so a PostgreSQL
     * column issue cannot leave the whole migration connection in an aborted
     * transaction state on Neon.
     */
    public $withinTransaction = false;

    /** @var string[] */
    private array $imageColumns = [
        'student_id_image',
        'father_id_image',
        'birth_certificate_image',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        foreach ($this->imageColumns as $column) {
            $this->ensureNullableTextColumn($column);
        }
    }

    public function down(): void
    {
        // Data-safe rollback: keep image URLs as nullable text to avoid truncating
        // existing Cloudinary URLs in production.
    }

    private function ensureNullableTextColumn(string $column): void
    {
        if (! Schema::hasColumn('students', $column)) {
            Schema::table('students', function (Blueprint $table) use ($column) {
                $table->text($column)->nullable();
            });

            return;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            $quotedColumn = str_replace('"', '""', $column);

            DB::statement(sprintf(
                'ALTER TABLE "students" ALTER COLUMN "%s" TYPE TEXT USING "%s"::TEXT',
                $quotedColumn,
                $quotedColumn
            ));

            DB::statement(sprintf(
                'ALTER TABLE "students" ALTER COLUMN "%s" DROP NOT NULL',
                $quotedColumn
            ));

            return;
        }

        Schema::table('students', function (Blueprint $table) use ($column) {
            $table->text($column)->nullable()->change();
        });
    }
};
