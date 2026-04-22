<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AutoAssignQuizController extends Controller
{
    private const TRIGGER_STUDENT_REGISTER = 'student_register';
    private const TARGET_ROLE = 'student';

    public function index()
    {
        $selectedIds = DB::table('auto_assign_quizzes')
            ->where('trigger', self::TRIGGER_STUDENT_REGISTER)
            ->where('user_role', self::TARGET_ROLE)
            ->pluck('quiz_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $quizzes = DB::table('quizz')
            ->select([
                'id',
                'uuid',
                'quiz_name',
                'status',
                'is_public',
                'total_time',
                'total_attempts',
                'total_questions',
                'created_at',
            ])
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($quiz) use ($selectedIds) {
                return [
                    'id'              => (int) $quiz->id,
                    'uuid'            => (string) ($quiz->uuid ?? ''),
                    'quiz_name'       => (string) ($quiz->quiz_name ?? ''),
                    'status'          => (string) ($quiz->status ?? ''),
                    'is_public'       => (string) ($quiz->is_public ?? 'no'),
                    'total_time'      => $quiz->total_time !== null ? (int) $quiz->total_time : null,
                    'total_attempts'  => $quiz->total_attempts !== null ? (int) $quiz->total_attempts : null,
                    'total_questions' => $quiz->total_questions !== null ? (int) $quiz->total_questions : null,
                    'created_at'      => (string) ($quiz->created_at ?? ''),
                    'selected'        => in_array((int) $quiz->id, $selectedIds, true),
                ];
            })
            ->values();

        return response()->json([
            'status'       => 'success',
            'trigger'      => self::TRIGGER_STUDENT_REGISTER,
            'target_role'  => self::TARGET_ROLE,
            'selected_ids' => $selectedIds,
            'quizzes'      => $quizzes,
        ]);
    }

    public function update(Request $request)
    {
        $v = Validator::make($request->all(), [
            'quiz_ids'   => ['sometimes', 'array'],
            'quiz_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('quizz', 'id')
                    ->whereNull('deleted_at')
                    ->where('status', 'active'),
            ],
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $v->errors(),
            ], 422);
        }

        $quizIds = collect($request->input('quiz_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        DB::transaction(function () use ($quizIds) {
            DB::table('auto_assign_quizzes')
                ->where('trigger', self::TRIGGER_STUDENT_REGISTER)
                ->where('user_role', self::TARGET_ROLE)
                ->delete();

            if ($quizIds->isEmpty()) {
                return;
            }

            $now = now();
            $rows = $quizIds->map(fn ($quizId) => [
                'trigger'    => self::TRIGGER_STUDENT_REGISTER,
                'user_role'  => self::TARGET_ROLE,
                'quiz_id'    => $quizId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('auto_assign_quizzes')->insert($rows);
        });

        return response()->json([
            'status'       => 'success',
            'message'      => 'Auto assign quizzes updated.',
            'selected_ids' => $quizIds->all(),
        ]);
    }

    public static function selectedQuizIdsForStudentRegister(): array
    {
        return DB::table('auto_assign_quizzes as aaq')
            ->join('quizz as q', 'q.id', '=', 'aaq.quiz_id')
            ->where('aaq.trigger', self::TRIGGER_STUDENT_REGISTER)
            ->where('aaq.user_role', self::TARGET_ROLE)
            ->whereNull('q.deleted_at')
            ->where('q.status', 'active')
            ->orderByDesc('q.created_at')
            ->pluck('aaq.quiz_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
