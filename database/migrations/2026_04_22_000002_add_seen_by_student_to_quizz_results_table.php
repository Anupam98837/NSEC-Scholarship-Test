<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('quizz_results')) {
            return;
        }

        Schema::table('quizz_results', function (Blueprint $table) {
            if (!Schema::hasColumn('quizz_results', 'seen_by_student')) {
                $table->boolean('seen_by_student')
                    ->default(false)
                    ->after('publish_to_student')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('quizz_results')) {
            return;
        }

        Schema::table('quizz_results', function (Blueprint $table) {
            if (Schema::hasColumn('quizz_results', 'seen_by_student')) {
                $table->dropColumn('seen_by_student');
            }
        });
    }
};
