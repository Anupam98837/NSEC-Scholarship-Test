<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_assign_quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('trigger', 50)->index();
            $table->string('user_role', 50)->default('student')->index();
            $table->unsignedBigInteger('quiz_id')->index();
            $table->timestamps();

            $table->unique(['trigger', 'user_role', 'quiz_id'], 'aaq_trigger_role_quiz_unique');

            $table->foreign('quiz_id')
                ->references('id')
                ->on('quizz')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_assign_quizzes');
    }
};
