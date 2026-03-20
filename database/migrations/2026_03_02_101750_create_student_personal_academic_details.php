<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_personal_academic_details', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK -> users(id) (student role validation should be enforced in app logic)
            $table->unsignedBigInteger('user_id')->unique();

            // All nullable (as requested)
            $table->string('guardian_name', 255)->nullable();
            $table->string('guardian_number', 20)->nullable();

            // Store selected option text (nullable)
            // e.g. "Currently XII 2026 Passout", "Currently XII 2027 Passout"
            $table->string('class', 80)->nullable();

            // board is varchar
            // e.g. CBSC, ISC, WBHSC, Bihar, Jharkhand, Open University, Others
            $table->string('board', 60)->nullable();

            // exam_type (nullable) e.g. Science/Commerce/Arts/etc.
            $table->string('exam_type', 80)->nullable();

            // year of passout (nullable)
            $table->year('year_of_passout')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->index('board');
            $table->index('class');
            $table->index('exam_type');
            $table->index('year_of_passout');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_personal_academic_details');
    }
};