<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_counsellor_assignments', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();

            // both reference users.id
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('counsellor_id');

            // ✅ VARCHAR (NOT enum)
            $table->string('assignment_status', 20)->default('assigned'); // assigned|paused|transferred|ended
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // audit
            $table->unsignedBigInteger('created_by'); // who assigned
            $table->timestamp('created_at')->useCurrent();
            $table->string('created_at_ip', 45)->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes(); // deleted_at
            $table->json('metadata')->nullable();

            // indexes
            $table->index(['student_id', 'counsellor_id']);
            $table->index('counsellor_id');
            $table->index('assignment_status');

            // FKs
            $table->foreign('student_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('counsellor_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        // ✅ MySQL-safe: enforce ONE active counsellor per student (deleted rows allowed as history)
        // alive = 1 when deleted_at IS NULL else NULL (so UNIQUE(student_id, alive) blocks multiple active rows)
        try {
            DB::statement("ALTER TABLE student_counsellor_assignments
                ADD COLUMN alive TINYINT
                GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END) STORED");
            DB::statement("ALTER TABLE student_counsellor_assignments
                ADD UNIQUE KEY uniq_student_one_active (student_id, alive)");
        } catch (\Throwable $e) {}

        // ✅ CHECK constraints (still NOT enum)
        try {
            DB::statement("ALTER TABLE student_counsellor_assignments
                ADD CONSTRAINT chk_sca_status
                CHECK (assignment_status IN ('assigned','paused','transferred','ended'))");
        } catch (\Throwable $e) {}

        // ✅ prevent self-assign
        try {
            DB::statement("ALTER TABLE student_counsellor_assignments
                ADD CONSTRAINT chk_sca_not_self
                CHECK (student_id <> counsellor_id)");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::dropIfExists('student_counsellor_assignments');
    }
};