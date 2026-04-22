<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ExamController extends Controller
{
    /* ============================================
     | Auth helpers (student via personal tokens)
     |============================================ */
    private const USER_TYPE = 'App\\Models\\User';

    /* ============================================
     | Auth helpers (role via CheckRole middleware)
     |============================================ */
    private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    private function requireRole(Request $request, array $allowed)
    {
        $a = $this->actor($request);

        if (!$a['role'] || !in_array($a['role'], $allowed, true)) {
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        return null;
    }

    private function getUserFromToken(Request $request): ?object
    {
        $header = (string) $request->header('Authorization', '');
        $token  = null;

        if (stripos($header, 'Bearer ') === 0) {
            $token = trim(substr($header, 7));
        } else {
            $token = trim($header);
        }
        if ($token === '') return null;

        $hashed = hash('sha256', $token);

        $pat = DB::table('personal_access_tokens')
            ->where('token', $hashed)
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (!$pat) return null;

        // expiry (optional column)
        if (isset($pat->expires_at) && $pat->expires_at !== null) {
            try {
                if (now()->greaterThan(Carbon::parse($pat->expires_at))) {
                    DB::table('personal_access_tokens')->where('id', $pat->id)->delete();
                    return null;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        $user = DB::table('users')
            ->where('id', $pat->tokenable_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) return null;
        if (isset($user->status) && $user->status !== 'active') return null;

        return $user;
    }
/* ============================================
 | Activity Log helpers
 |============================================ */
private function logActivity(
    string  $module,
    string  $activity,
    string  $note,
    Request $request,
    ?object $user        = null,
    ?object $attempt     = null,
    ?array  $details     = null,
    ?string $recordTable = null,
    mixed   $recordId    = null,
    mixed   $target      = null,
): void {
    try {
        $now = now()->toDateTimeString();

        $payload = [
            'performed_by'      => $user ? (int) $user->id          : 0,
            'performed_by_role' => $user ? ($user->role ?? null)     : null,
            'activity'          => $activity,
            'module'            => $module,
            'table_name'        => $recordTable ?? ($attempt ? 'quizz_attempts' : null),
            'record_id'         => $recordId    ?? ($attempt ? $attempt->id     : null),
            'log_note'          => $note ?: null,
            'new_values'        => $details
                                    ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                    : null,
            'old_values'        => null,
            'changed_fields'    => $details
                                    ? json_encode(array_keys($details))
                                    : null,
            'ip'                => $request->ip(),
            'user_agent'        => substr((string) $request->userAgent(), 0, 500),
            'created_at'        => $now,
            'updated_at'        => $now,
        ];

        // Remove keys whose value is null so we don't hit NOT NULL column errors
        // BUT keep keys that have actual values including 0
        $payload = array_filter($payload, fn($v) => $v !== null);

        DB::table('user_data_activity_log')->insert($payload);

    } catch (\Throwable $e) {
        Log::warning('[logActivity] failed', [
            'error'    => $e->getMessage(),
            'module'   => $module,
            'activity' => $activity,
            'note'     => $note,
        ]);
    }
}
    private function isStudent(object $user): bool
    {
        $role = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)($user->role ?? '')));
        return in_array($role, ['student','std','stu'], true);
    }

    private function quizByKey(string|int $key): ?object
    {
        $q = DB::table('quizz')->whereNull('deleted_at');

        if (is_numeric($key)) {
            $q->where('id', (int) $key);
        } else {
            $key = (string) $key;

            $q->where(function ($w) use ($key) {
                $w->where('uuid', $key);

                // ALSO accept old columns if they exist
                if (Schema::hasColumn('quizz', 'quiz_uuid')) {
                    $w->orWhere('quiz_uuid', $key);
                }
                if (Schema::hasColumn('quizz', 'quiz_key')) {
                    $w->orWhere('quiz_key', $key);
                }
            });
        }

        return $q->first();
    }

    private function normalizeText(?string $s): string
    {
        $s = (string)$s;
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return mb_strtolower($s, 'UTF-8');
    }

    private function questionType(int $questionId): string
    {
        return (string) (DB::table('quizz_questions')->where('id', $questionId)->value('question_type') ?? 'mcq');
    }

    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function attemptQuestionOrderIds(object $attempt): array
    {
        return array_values(array_map(
            fn ($qid) => (int) $qid,
            array_filter(
                $this->decodeJsonArray($attempt->questions_order ?? null),
                fn ($qid) => (int) $qid > 0
            )
        ));
    }

    private function attemptIndexForQuestionId(object $attempt, ?int $questionId): int
    {
        $questionId = (int) ($questionId ?? 0);
        if ($questionId <= 0) {
            return 0;
        }

        $order = $this->attemptQuestionOrderIds($attempt);
        if (empty($order)) {
            return 0;
        }

        $idx = array_search($questionId, $order, true);
        return $idx === false ? 0 : (int) $idx;
    }

    private function hasSelectionValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->hasSelectionValue($item)) {
                    return true;
                }
            }
            return false;
        }

        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function sanitizeSelectionsMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $clean = [];
        foreach ($value as $qid => $selected) {
            $qid = (int) $qid;
            if ($qid <= 0) {
                continue;
            }

            if (is_array($selected)) {
                $clean[$qid] = array_values(array_map(function ($item) {
                    return is_scalar($item) || $item === null ? $item : (string) $item;
                }, $selected));
                continue;
            }

            $clean[$qid] = is_scalar($selected) || $selected === null
                ? $selected
                : (string) $selected;
        }

        return $clean;
    }

    private function sanitizeBooleanMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $clean = [];
        foreach ($value as $qid => $flag) {
            $qid = (int) $qid;
            if ($qid <= 0) {
                continue;
            }
            $clean[$qid] = (bool) $flag;
        }

        return $clean;
    }

    private function sanitizeTimeSpentMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $clean = [];
        foreach ($value as $qid => $seconds) {
            $qid = (int) $qid;
            if ($qid <= 0) {
                continue;
            }
            $clean[$qid] = max(0, (int) $seconds);
        }

        return $clean;
    }

    private function parseComparableDate(mixed $value): ?Carbon
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function examClientId(Request $request): string
    {
        return trim((string) $request->header('X-Exam-Client-Id', ''));
    }

    private function examClientLockTtlSeconds(): int
    {
        return 35;
    }

    private function claimExamClientLock(object $attempt, Request $request, array $state = []): array
    {
        $clientId = $this->examClientId($request);
        if ($clientId === '') {
            return [
                'locked' => false,
                'session' => $this->writeAttemptSession($attempt, $state),
            ];
        }

        $existing      = $this->readAttemptSession((string) $attempt->uuid);
        $activeClient  = trim((string) ($existing['active_client_id'] ?? ''));
        $activeSeenAt  = $this->parseComparableDate($existing['active_client_seen_at'] ?? null);
        $now           = Carbon::now();
        $ttl           = $this->examClientLockTtlSeconds();
        $lockIsFresh   = $activeClient !== '' && $activeSeenAt && $activeSeenAt->diffInSeconds($now) <= $ttl;

        if ($lockIsFresh && $activeClient !== $clientId) {
            $retryAfter = max(1, $ttl - $activeSeenAt->diffInSeconds($now));

            return [
                'locked' => true,
                'retry_after_sec' => $retryAfter,
                'active_client_seen_at' => $activeSeenAt->toDateTimeString(),
            ];
        }

        $state['active_client_id']      = $clientId;
        $state['active_client_seen_at'] = $now->toDateTimeString();

        return [
            'locked' => false,
            'session' => $this->writeAttemptSession($attempt, $state),
        ];
    }

    private function examClientLockResponse(array $lock): \Illuminate\Http\JsonResponse
    {
        $retryAfter = max(1, (int) ($lock['retry_after_sec'] ?? $this->examClientLockTtlSeconds()));

        return response()->json([
            'success' => false,
            'message' => 'Exam is active on another device. Please wait until that device goes offline or submits the exam.',
            'lock' => [
                'retry_after_sec' => $retryAfter,
                'active_client_seen_at' => $lock['active_client_seen_at'] ?? null,
            ],
        ], 423);
    }

    private function runningAttemptForUser(int $userId, ?int $excludeAttemptId = null): ?object
    {
        $attempts = DB::table('quizz_attempts as qa')
            ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
            ->where('qa.user_id', $userId)
            ->where('qa.status', 'in_progress')
            ->whereNull('q.deleted_at')
            ->when($excludeAttemptId, fn ($query) => $query->where('qa.id', '!=', $excludeAttemptId))
            ->orderByDesc('qa.last_activity_at')
            ->orderByDesc('qa.updated_at')
            ->orderByDesc('qa.id')
            ->select([
                'qa.*',
                'q.uuid as live_quiz_uuid',
                'q.quiz_name',
            ])
            ->get();

        foreach ($attempts as $attempt) {
            if ($this->deadlinePassed($attempt)) {
                $this->autoFinalize($attempt, true);
                continue;
            }

            return $attempt;
        }

        return null;
    }

    private function activeAttemptMeta(object $attempt): array
    {
        $quizUuid = trim((string) ($attempt->live_quiz_uuid ?? $attempt->quiz_uuid ?? ''));
        $quizKey  = $quizUuid !== '' ? $quizUuid : (string) $attempt->quiz_id;

        return [
            'attempt_id'        => (int) ($attempt->id ?? 0),
            'attempt_uuid'      => (string) ($attempt->uuid ?? ''),
            'quiz_id'           => (int) ($attempt->quiz_id ?? 0),
            'quiz_uuid'         => $quizUuid !== '' ? $quizUuid : null,
            'quiz_name'         => (string) ($attempt->quiz_name ?? 'Exam'),
            'server_deadline_at'=> !empty($attempt->server_deadline_at)
                ? Carbon::parse($attempt->server_deadline_at)->toDateTimeString()
                : null,
            'time_left_sec'     => $this->timeLeftSec($attempt),
            'continue_url'      => '/exam/' . rawurlencode($quizKey),
        ];
    }

    private function activeExamAlreadyRunningResponse(object $attempt, string $message, ?array $lock = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'active_attempt' => $this->activeAttemptMeta($attempt),
            'lock' => $lock ? [
                'retry_after_sec' => max(1, (int) ($lock['retry_after_sec'] ?? $this->examClientLockTtlSeconds())),
                'active_client_seen_at' => $lock['active_client_seen_at'] ?? null,
            ] : null,
        ], 423);
    }

    private function attemptSelectionSnapshot(int $attemptId): array
    {
        $rows = DB::table('quizz_attempt_answers')
            ->where('attempt_id', $attemptId)
            ->get(['question_id', 'selected_raw', 'time_spent_sec']);

        $selections = [];
        $timeSpent  = [];

        foreach ($rows as $row) {
            $qid = (int) $row->question_id;
            if ($qid <= 0) {
                continue;
            }

            try {
                $selections[$qid] = json_decode((string) $row->selected_raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $selections[$qid] = null;
            }

            $timeSpent[$qid] = max(0, (int) ($row->time_spent_sec ?? 0));
        }

        return [
            'selections'     => $selections,
            'time_spent_sec' => $timeSpent,
        ];
    }

    private function examSessionDirectory(): string
    {
        $dir = storage_path('app/exam-sessions');

        if (!is_dir($dir)) {
            File::ensureDirectoryExists($dir);
        }

        return $dir;
    }

    private function examSessionPath(string $attemptUuid): string
    {
        return $this->examSessionDirectory() . DIRECTORY_SEPARATOR . trim($attemptUuid) . '.json';
    }

    private function readAttemptSession(string $attemptUuid): array
    {
        $attemptUuid = trim($attemptUuid);
        if ($attemptUuid === '') {
            return [];
        }

        $path = $this->examSessionPath($attemptUuid);
        if (!is_file($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            Log::warning('[Exam readAttemptSession] failed', [
                'attempt_uuid' => $attemptUuid,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function baseAttemptSessionPayload(object $attempt): array
    {
        $snapshot = $this->attemptSelectionSnapshot((int) $attempt->id);

        $currentQuestionId = (int) ($attempt->current_question_id ?? 0);
        $visited = [];
        foreach ($snapshot['selections'] as $qid => $selected) {
            if ($this->hasSelectionValue($selected)) {
                $visited[(int) $qid] = true;
            }
        }
        if ($currentQuestionId > 0) {
            $visited[$currentQuestionId] = true;
        }

        return [
            'version'             => 1,
            'attempt_uuid'        => (string) $attempt->uuid,
            'quiz_id'             => (int) $attempt->quiz_id,
            'quiz_uuid'           => (string) ($attempt->quiz_uuid ?? ''),
            'user_id'             => (int) $attempt->user_id,
            'status'              => (string) ($attempt->status ?? 'in_progress'),
            'server_end_at'       => !empty($attempt->server_deadline_at)
                ? Carbon::parse($attempt->server_deadline_at)->toDateTimeString()
                : null,
            'started_at'          => !empty($attempt->started_at)
                ? Carbon::parse($attempt->started_at)->toDateTimeString()
                : null,
            'last_activity_at'    => !empty($attempt->last_activity_at)
                ? Carbon::parse($attempt->last_activity_at)->toDateTimeString()
                : null,
            'current_question_id' => $currentQuestionId > 0 ? $currentQuestionId : null,
            'current_index'       => $this->attemptIndexForQuestionId($attempt, $currentQuestionId),
            'selections'          => $snapshot['selections'],
            'reviews'             => [],
            'visited'             => $visited,
            'time_spent_sec'      => $snapshot['time_spent_sec'],
            'saved_at'            => now()->toDateTimeString(),
        ];
    }

    private function writeAttemptSession(object $attempt, array $state = []): array
    {
        $payload  = $this->baseAttemptSessionPayload($attempt);
        $existing = $this->readAttemptSession((string) $attempt->uuid);

        $apply = function (array $source) use (&$payload, $attempt): void {
            if (array_key_exists('status', $source)) {
                $payload['status'] = (string) $source['status'];
            }
            if (array_key_exists('server_end_at', $source) && $source['server_end_at']) {
                $payload['server_end_at'] = (string) $source['server_end_at'];
            }
            if (array_key_exists('started_at', $source) && $source['started_at']) {
                $payload['started_at'] = (string) $source['started_at'];
            }
            if (array_key_exists('last_activity_at', $source) && $source['last_activity_at']) {
                $payload['last_activity_at'] = (string) $source['last_activity_at'];
            }
            if (array_key_exists('current_question_id', $source)) {
                $qid = (int) ($source['current_question_id'] ?? 0);
                $payload['current_question_id'] = $qid > 0 ? $qid : null;
            }
            if (array_key_exists('current_index', $source)) {
                $payload['current_index'] = max(0, (int) ($source['current_index'] ?? 0));
            } elseif (!empty($payload['current_question_id'])) {
                $payload['current_index'] = $this->attemptIndexForQuestionId($attempt, (int) $payload['current_question_id']);
            }
            if (array_key_exists('selections', $source)) {
                $payload['selections'] = $this->sanitizeSelectionsMap($source['selections']);
            }
            if (array_key_exists('reviews', $source)) {
                $payload['reviews'] = $this->sanitizeBooleanMap($source['reviews']);
            }
            if (array_key_exists('visited', $source)) {
                $payload['visited'] = $this->sanitizeBooleanMap($source['visited']);
            }
            if (array_key_exists('time_spent_sec', $source)) {
                $payload['time_spent_sec'] = $this->sanitizeTimeSpentMap($source['time_spent_sec']);
            }
            if (array_key_exists('saved_at', $source) && $source['saved_at']) {
                $payload['saved_at'] = (string) $source['saved_at'];
            }
            if (array_key_exists('active_client_id', $source) && $source['active_client_id']) {
                $payload['active_client_id'] = (string) $source['active_client_id'];
            }
            if (array_key_exists('active_client_seen_at', $source) && $source['active_client_seen_at']) {
                $payload['active_client_seen_at'] = (string) $source['active_client_seen_at'];
            }
        };

        if (!empty($existing)) {
            $apply($existing);
        }
        if (!empty($state)) {
            $apply($state);
        }

        $payload['attempt_uuid'] = (string) $attempt->uuid;
        $payload['quiz_id']      = (int) $attempt->quiz_id;
        $payload['quiz_uuid']    = (string) ($attempt->quiz_uuid ?? '');
        $payload['user_id']      = (int) $attempt->user_id;

        try {
            file_put_contents(
                $this->examSessionPath((string) $attempt->uuid),
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                LOCK_EX
            );
        } catch (\Throwable $e) {
            Log::warning('[Exam writeAttemptSession] failed', [
                'attempt_uuid' => (string) $attempt->uuid,
                'error' => $e->getMessage(),
            ]);
        }

        return $payload;
    }

    private function deleteAttemptSession(object|string|null $attempt): void
    {
        $attemptUuid = is_object($attempt)
            ? (string) ($attempt->uuid ?? '')
            : trim((string) $attempt);

        if ($attemptUuid === '') {
            return;
        }

        $path = $this->examSessionPath($attemptUuid);
        if (!is_file($path)) {
            return;
        }

        try {
            @unlink($path);
        } catch (\Throwable $e) {
            Log::warning('[Exam deleteAttemptSession] failed', [
                'attempt_uuid' => $attemptUuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* ============================================
     | POST /api/exam/start/{quizKey}
     |============================================ */
    public function start(Request $request, string $quizKey)
    {
        $user = $this->getUserFromToken($request);

        $quiz = $this->quizByKey($quizKey);
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
            ], 404);
        }

        if (($quiz->status ?? 'active') !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Quiz is not active',
            ], 409);
        }

        // Attempt limit
        $globalAllowed = max(1, (int) ($quiz->total_attempts ?? 1));

        $globalUsed = (int) DB::table('quizz_results')
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->count();

        if ($globalUsed >= $globalAllowed) {
            return response()->json([
                'success' => false,
                'message' => "Attempt limit reached for this quiz ({$globalUsed}/{$globalAllowed})",
            ], 429);
        }

        $now         = Carbon::now();
        $durationMin = (int) ($quiz->total_time ?? 0);

        if ($durationMin <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz has no total_time set',
            ], 422);
        }

        $deadline = $now->copy()->addMinutes($durationMin);

        $running = $this->runningAttemptForUser((int) $user->id);

        if ($running) {
            $lock = $this->claimExamClientLock($running, $request, [
                'saved_at' => $now->toDateTimeString(),
            ]);
            if (!empty($lock['locked'])) {
                return $this->activeExamAlreadyRunningResponse(
                    $running,
                    'Another device is already running an exam for this user. Please wait until it goes offline or submits the exam.',
                    $lock
                );
            }

            if ((int) $running->quiz_id !== (int) $quiz->id) {
                return $this->activeExamAlreadyRunningResponse(
                    $running,
                    'Another exam is already running for this user. Please finish that exam before starting a new one.'
                );
            }

            return response()->json([
                'success' => true,
                'attempt' => [
                    'attempt_uuid'   => $running->uuid,
                    'quiz_id'        => (int) $quiz->id,
                    'quiz_uuid'      => (string) $quiz->uuid,
                    'quiz_name'      => (string) ($quiz->quiz_name ?? 'Quiz'),
                    'total_time_sec' => $durationMin * 60,
                    'server_end_at'  => (string) $running->server_deadline_at,
                    'time_left_sec'  => max(
                        0,
                        Carbon::parse($running->server_deadline_at)->diffInSeconds($now, false) * -1
                    ),
                ],
            ], 200);
        }

        // Build per-attempt layout (questions + options order)
        $qRows = DB::table('quizz_questions')
            ->where('quiz_id', $quiz->id)
            ->orderBy('question_order')
            ->get(['id', 'question_type']);

        if ($qRows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz has no questions',
            ], 422);
        }

        $questionIds = $qRows->pluck('id')->map(fn ($v) => (int) $v)->all();

        if (($quiz->is_question_random ?? 'no') === 'yes') {
            shuffle($questionIds);
        }

        $qTypes = [];
        foreach ($qRows as $qRow) {
            $qTypes[(int) $qRow->id] = (string) ($qRow->question_type ?? '');
        }

        $aRows = DB::table('quizz_question_answers')
            ->whereIn('belongs_question_id', $questionIds)
            ->orderBy('belongs_question_id')
            ->orderBy('answer_order')
            ->get(['id', 'belongs_question_id', 'answer_order']);

        $answersByQ = $aRows->groupBy('belongs_question_id');

        $optionsOrder = [];
        foreach ($questionIds as $qid) {
            $answers = $answersByQ[$qid] ?? collect();
            if ($answers->isEmpty()) {
                $optionsOrder[$qid] = [];
                continue;
            }

            $answerIds = $answers->pluck('id')->map(fn ($v) => (int) $v)->all();
            $qType     = $qTypes[$qid] ?? '';

            if (($quiz->is_option_random ?? 'no') === 'yes' && $qType !== 'fill_in_the_blank') {
                shuffle($answerIds);
            }

            $optionsOrder[$qid] = $answerIds;
        }

        $attemptUuid = (string) Str::uuid();

        $attemptId = DB::table('quizz_attempts')->insertGetId([
            'uuid'                => $attemptUuid,
            'quiz_id'             => (int) $quiz->id,
            'quiz_uuid'           => (string) ($quiz->uuid ?? null),
            'user_id'             => (int) $user->id,
            'status'              => 'in_progress',
            'total_time_sec'      => $durationMin * 60,
            'started_at'          => $now,
            'server_deadline_at'  => $deadline,
            'current_question_id' => null,
            'current_q_started_at'=> null,
            'last_activity_at'    => $now,
            'questions_order'     => json_encode($questionIds, JSON_UNESCAPED_UNICODE),
            'options_order'       => json_encode($optionsOrder, JSON_UNESCAPED_UNICODE),
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        $attempt = DB::table('quizz_attempts')->where('id', $attemptId)->first();
        if ($attempt) {
            $lock = $this->claimExamClientLock($attempt, $request, [
                'current_index' => 0,
                'saved_at'      => $now->toDateTimeString(),
            ]);
            if (!empty($lock['locked'])) {
                return $this->examClientLockResponse($lock);
            }
        }

        return response()->json([
            'success' => true,
            'attempt' => [
                'attempt_id'     => $attemptId,
                'attempt_uuid'   => $attemptUuid,
                'quiz_id'        => (int) $quiz->id,
                'quiz_uuid'      => (string) $quiz->uuid,
                'quiz_name'      => (string) ($quiz->quiz_name ?? 'Quiz'),
                'total_time_sec' => $durationMin * 60,
                'server_end_at'  => (string) $deadline,
                'time_left_sec'  => max(0, $deadline->diffInSeconds($now, false) * -1),
            ],
        ], 201);
    }

    /* ============================================
     | GET /api/exam/quizzes/{quizKey}/my-attempts
     |============================================ */
    public function myAttemptsForQuiz(Request $request, string $quizKey)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$this->isStudent($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized (student token required)'
            ], 401);
        }

        $quiz = $this->quizByKey($quizKey);
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found'
            ], 404);
        }

        $q = DB::table('quizz_attempts as qa')
            ->where('qa.quiz_id', $quiz->id)
            ->where('qa.user_id', $user->id)
            ->leftJoin('quizz_results as qr', 'qr.attempt_id', '=', 'qa.id');

        $rows = $q->orderByDesc('qa.created_at')
            ->orderByDesc('qa.id')
            ->select([
                'qa.id as attempt_id',
                'qa.uuid as attempt_uuid',
                'qa.status',
                'qa.started_at',
                'qa.finished_at',
                'qa.server_deadline_at',
                'qa.total_time_sec',
                'qa.created_at',
                'qa.updated_at',

                'qr.id as result_id',
                'qr.marks_obtained',
                'qr.total_marks',
                'qr.percentage',
                'qr.attempt_number',
                'qr.publish_to_student',
            ])
            ->get();

        $allowViewNow = $this->shouldPublishToStudent((int)$quiz->id);

        $attempts = $rows->map(function ($row) use ($allowViewNow) {
            $canView = false;
            if ($row->result_id) {
                $canView = ((int)$row->publish_to_student === 1) || $allowViewNow;
            }

            return [
                'attempt_id'         => (int) $row->attempt_id,
                'attempt_uuid'       => (string) $row->attempt_uuid,
                'status'             => (string) $row->status,
                'started_at'         => $row->started_at ? Carbon::parse($row->started_at)->toDateTimeString() : null,
                'finished_at'        => $row->finished_at ? Carbon::parse($row->finished_at)->toDateTimeString() : null,
                'created_at'         => $row->created_at ? Carbon::parse($row->created_at)->toDateTimeString() : null,
                'server_deadline_at' => $row->server_deadline_at ? Carbon::parse($row->server_deadline_at)->toDateTimeString() : null,
                'total_time_sec'     => (int) ($row->total_time_sec ?? 0),

                'result' => $row->result_id ? [
                    'result_id'          => (int) $row->result_id,
                    'marks_obtained'     => (int) $row->marks_obtained,
                    'total_marks'        => (int) $row->total_marks,
                    'percentage'         => $row->total_marks
                        ? (float) ($row->percentage ?? round($row->marks_obtained / max(1, $row->total_marks) * 100, 2))
                        : 0.0,
                    'attempt_number'     => (int) ($row->attempt_number ?? 0),
                    'publish_to_student' => (int) $row->publish_to_student,
                    'can_view_detail'    => $canView,
                ] : null,
            ];
        })->values();

        $totalMarks = (int) DB::table('quizz_questions')
            ->where('quiz_id', $quiz->id)
            ->sum('question_mark');

        return response()->json([
            'success'  => true,
            'quiz'     => [
                'id'                     => (int) $quiz->id,
                'uuid'                   => (string) $quiz->uuid,
                'name'                   => (string) ($quiz->quiz_name ?? 'Quiz'),
                'total_marks'            => $totalMarks,
                'total_attempts_allowed' => (int) ($quiz->total_attempts ?? 1),
            ],
            'batch'    => null,
            'attempts' => $attempts,
        ], 200);
    }

    /* ============================================
     | GET /api/exam/my-active-attempt
     |============================================ */
    public function myActiveAttempt(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$this->isStudent($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized (student token required)',
            ], 401);
        }

        $runningAttempts = DB::table('quizz_attempts as qa')
            ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
            ->where('qa.user_id', (int) $user->id)
            ->where('qa.status', 'in_progress')
            ->whereNull('q.deleted_at')
            ->orderByDesc('qa.last_activity_at')
            ->orderByDesc('qa.updated_at')
            ->orderByDesc('qa.id')
            ->select([
                'qa.id',
                'qa.uuid as attempt_uuid',
                'qa.quiz_id',
                'qa.quiz_uuid',
                'qa.status',
                'qa.started_at',
                'qa.last_activity_at',
                'qa.server_deadline_at',
                'qa.total_time_sec',
                'q.uuid as live_quiz_uuid',
                'q.quiz_name',
            ])
            ->get();

        foreach ($runningAttempts as $attempt) {
            if ($this->deadlinePassed($attempt)) {
                $this->autoFinalize($attempt, true);
                continue;
            }

            $quizUuid = trim((string) ($attempt->live_quiz_uuid ?? $attempt->quiz_uuid ?? ''));
            $quizKey  = $quizUuid !== '' ? $quizUuid : (string) $attempt->quiz_id;

            return response()->json([
                'success' => true,
                'active_attempt' => [
                    'attempt_id'        => (int) $attempt->id,
                    'attempt_uuid'      => (string) $attempt->attempt_uuid,
                    'quiz_id'           => (int) $attempt->quiz_id,
                    'quiz_uuid'         => $quizUuid !== '' ? $quizUuid : null,
                    'quiz_name'         => (string) ($attempt->quiz_name ?? 'Exam'),
                    'status'            => (string) $attempt->status,
                    'started_at'        => $attempt->started_at ? Carbon::parse($attempt->started_at)->toDateTimeString() : null,
                    'last_activity_at'  => $attempt->last_activity_at ? Carbon::parse($attempt->last_activity_at)->toDateTimeString() : null,
                    'server_deadline_at'=> $attempt->server_deadline_at ? Carbon::parse($attempt->server_deadline_at)->toDateTimeString() : null,
                    'time_left_sec'     => $this->timeLeftSec($attempt),
                    'continue_url'      => '/exam/' . rawurlencode($quizKey),
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'active_attempt' => null,
        ], 200);
    }

    /* ============================================
     | GET /api/exam/attempts/{attempt}/questions
     |============================================ */
    public function questions(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }

        if ($this->deadlinePassed($attempt)) {
            $attempt = $this->autoFinalize($attempt, true);
        }

        if ($attempt->status !== 'in_progress') {
            return response()->json(['success'=>false,'message'=>'Attempt is not running'], 409);
        }

        $lock = $this->claimExamClientLock($attempt, $request);
        if (!empty($lock['locked'])) {
            return $this->examClientLockResponse($lock);
        }

        $rows = DB::table('quizz_questions as q')
            ->leftJoin('quizz_question_answers as a', 'a.belongs_question_id', '=', 'q.id')
            ->where('q.quiz_id', $attempt->quiz_id)
            ->orderBy('q.question_order')
            ->orderBy('a.answer_order')
            ->select([
                'q.id as question_id',
                'q.question_title',
                'q.question_description',
                'q.answer_explanation',
                'q.question_type',
                'q.question_mark',
                'q.question_order',
                DB::raw("(
                    SELECT COUNT(*) FROM quizz_question_answers
                    WHERE belongs_question_id = q.id AND is_correct = 1
                ) as correct_count"),

                'a.id as answer_id',
                'a.answer_title',
                'a.answer_order',
            ])
            ->get();

        $questionsById = [];
        foreach ($rows as $r) {
            $qid = (int)$r->question_id;

            if (!isset($questionsById[$qid])) {
                $questionsById[$qid] = [
                    'question_id'                 => $qid,
                    'question_title'              => $r->question_title,
                    'question_description'        => $r->question_description,
                    'question_type'               => $r->question_type,
                    'question_mark'               => (int)$r->question_mark,
                    'question_order'              => (int)$r->question_order,
                    'has_multiple_correct_answer' => ((int)$r->correct_count > 1),
                    'answers'                     => [],
                ];
            }

            if ($r->answer_id !== null) {
                $questionsById[$qid]['answers'][] = [
                    'answer_id'    => (int)$r->answer_id,
                    'answer_title' => $r->answer_title,
                    'answer_order' => (int)($r->answer_order ?? 0),
                ];
            }
        }

        $savedRows = DB::table('quizz_attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->get(['question_id', 'selected_raw', 'time_spent_sec']);

        $dbSelections = [];
        $dbTimeSpent  = [];
        foreach ($savedRows as $row) {
            $qid = (int) $row->question_id;
            try {
                $dbSelections[$qid] = json_decode((string) $row->selected_raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $dbSelections[$qid] = null;
            }
            $dbTimeSpent[$qid] = max(0, (int) ($row->time_spent_sec ?? 0));
        }

        $layoutQuestions = null;
        $layoutOptions   = null;

        if (!empty($attempt->questions_order)) {
            try { $layoutQuestions = json_decode($attempt->questions_order, true); }
            catch (\Throwable $e) { $layoutQuestions = null; }
        }

        if (!empty($attempt->options_order)) {
            try { $layoutOptions = json_decode($attempt->options_order, true); }
            catch (\Throwable $e) { $layoutOptions = null; }
        }

        $orderedQuestions = [];
        $hasLayout = is_array($layoutQuestions) && !empty($layoutQuestions);

        if ($hasLayout) {
            $usedQids = [];
            foreach ($layoutQuestions as $qid) {
                $qid = (int)$qid;
                if (!isset($questionsById[$qid])) continue;

                $qArr = $questionsById[$qid];

                if (is_array($layoutOptions)) {
                    $keyInt = $qid;
                    $keyStr = (string)$qid;
                    $ansOrder = $layoutOptions[$keyInt] ?? ($layoutOptions[$keyStr] ?? null);

                    if (is_array($ansOrder) && !empty($qArr['answers'])) {
                        $answersById = [];
                        foreach ($qArr['answers'] as $ans) {
                            $answersById[(int)$ans['answer_id']] = $ans;
                        }

                        $newAnswers = [];
                        foreach ($ansOrder as $aid) {
                            $aid = (int)$aid;
                            if (isset($answersById[$aid])) {
                                $newAnswers[] = $answersById[$aid];
                                unset($answersById[$aid]);
                            }
                        }

                        foreach ($answersById as $aRow) $newAnswers[] = $aRow;
                        $qArr['answers'] = $newAnswers;
                    }
                }

                $orderedQuestions[] = $qArr;
                $usedQids[$qid] = true;
            }

            foreach ($questionsById as $qid => $qArr) {
                if (!isset($usedQids[$qid])) $orderedQuestions[] = $qArr;
            }
        } else {
            $orderedQuestions = array_values($questionsById);
        }

        $draft = $this->readAttemptSession((string) $attempt->uuid);

        $selections = array_replace(
            $dbSelections,
            $this->sanitizeSelectionsMap($draft['selections'] ?? null)
        );

        $timeSpentSec = array_replace(
            $dbTimeSpent,
            $this->sanitizeTimeSpentMap($draft['time_spent_sec'] ?? null)
        );

        $visited = [];
        foreach ($selections as $qid => $selected) {
            if ($this->hasSelectionValue($selected)) {
                $visited[(int) $qid] = true;
            }
        }
        if (!empty($attempt->current_question_id)) {
            $visited[(int) $attempt->current_question_id] = true;
        }
        $visited = array_replace(
            $visited,
            $this->sanitizeBooleanMap($draft['visited'] ?? null)
        );

        $reviews = $this->sanitizeBooleanMap($draft['reviews'] ?? null);
        $currentQuestionId = (int) (($draft['current_question_id'] ?? null) ?: ($attempt->current_question_id ?? 0));
        $currentIndex = array_key_exists('current_index', $draft)
            ? max(0, (int) ($draft['current_index'] ?? 0))
            : $this->attemptIndexForQuestionId($attempt, $currentQuestionId);

        if (!empty($orderedQuestions)) {
            $maxIndex = count($orderedQuestions) - 1;
            $currentIndex = min($currentIndex, $maxIndex);
        } else {
            $currentIndex = 0;
        }

        $resume = $this->writeAttemptSession($attempt, [
            'current_question_id' => $currentQuestionId > 0 ? $currentQuestionId : null,
            'current_index'       => $currentIndex,
            'selections'          => $selections,
            'reviews'             => $reviews,
            'visited'             => $visited,
            'time_spent_sec'      => $timeSpentSec,
            'saved_at'            => (string) ($draft['saved_at'] ?? now()->toDateTimeString()),
        ]);

        return response()->json([
            'success'=>true,
            'attempt'=>[
                'status'        => $attempt->status,
                'time_left_sec' => $this->timeLeftSec($attempt),
                'server_end_at' => (string)$attempt->server_deadline_at,
            ],
            'questions'  => $orderedQuestions,
            'selections' => $selections,
            'resume'     => [
                'current_index'       => (int) ($resume['current_index'] ?? 0),
                'current_question_id' => !empty($resume['current_question_id']) ? (int) $resume['current_question_id'] : null,
                'reviews'             => $resume['reviews'] ?? [],
                'visited'             => $resume['visited'] ?? [],
                'time_spent_sec'      => $resume['time_spent_sec'] ?? [],
                'saved_at'            => $resume['saved_at'] ?? null,
            ],
        ], 200);
    }

    /* ==========================================================
     | NEW: POST /api/exam/attempts/{attempt}/bulk-answer
     | Does NOT affect result APIs. Only saves answers faster.
     |========================================================== */
    public function bulkAnswer(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $v = Validator::make($request->all(), [
            'answers'                  => ['sometimes','array'],
            'answers.*.question_id'    => ['required','integer','min:1'],
            'answers.*.selected'       => ['nullable'],
            'answers.*.time_spent_sec' => ['nullable','integer','min:0'],
            'current_index'            => ['nullable','integer','min:0'],
            'current_question_id'      => ['nullable','integer','min:1'],
            'selections'               => ['nullable','array'],
            'reviews'                  => ['nullable','array'],
            'visited'                  => ['nullable','array'],
            'time_spent_sec'           => ['nullable','array'],
            'saved_at'                 => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json(['success'=>false,'message'=>'Attempt is not running'], 409);
        }

        $lock = $this->claimExamClientLock($attempt, $request);
        if (!empty($lock['locked'])) {
            return $this->examClientLockResponse($lock);
        }

        $now = Carbon::now();
        if ($this->deadlinePassed($attempt)) {
            $attempt = $this->autoFinalize($attempt, true);
            return response()->json([
                'success'=>false,
                'message'=>'Time over — attempt auto-submitted',
                'attempt'=>['status'=>$attempt->status,'time_left_sec'=>0]
            ], 409);
        }

        $incomingSavedAt = $this->parseComparableDate($request->input('saved_at'));
        $existingDraft   = $this->readAttemptSession((string) $attempt->uuid);
        $existingSavedAt = $this->parseComparableDate($existingDraft['saved_at'] ?? null);

        if (
            $incomingSavedAt &&
            $existingSavedAt &&
            $incomingSavedAt->lt($existingSavedAt)
        ) {
            return response()->json([
                'success' => true,
                'stale_ignored' => true,
                'attempt' => [
                    'time_left_sec' => $this->timeLeftSec($attempt),
                    'server_end_at' => (string) $attempt->server_deadline_at,
                ],
                'resume' => [
                    'current_index'       => (int) ($existingDraft['current_index'] ?? 0),
                    'current_question_id' => !empty($existingDraft['current_question_id']) ? (int) $existingDraft['current_question_id'] : null,
                    'saved_at'            => $existingDraft['saved_at'] ?? null,
                ],
            ], 200);
        }

        $payload = $request->input('answers', []);
        $qIds = array_values(array_unique(array_map(fn($x)=> (int)($x['question_id'] ?? 0), $payload)));
        $qIds = array_values(array_filter($qIds, fn($x)=> $x > 0));

        $qMap = collect();
        if (!empty($qIds)) {
            $qMap = DB::table('quizz_questions')
                ->where('quiz_id', $attempt->quiz_id)
                ->whereIn('id', $qIds)
                ->get(['id','question_type'])
                ->keyBy('id');

            if ($qMap->count() !== count($qIds)) {
                return response()->json([
                    'success'=>false,
                    'message'=>'One or more questions are invalid for this quiz',
                ], 422);
            }
        }

        $currentQuestionId = (int) $request->input('current_question_id', 0);
        if ($currentQuestionId > 0) {
            $currentQuestionValid = DB::table('quizz_questions')
                ->where('quiz_id', $attempt->quiz_id)
                ->where('id', $currentQuestionId)
                ->exists();

            if (!$currentQuestionValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid current question for this quiz',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($payload as $row) {
                $qid = (int)($row['question_id'] ?? 0);
                if ($qid <= 0) continue;

                $selected  = $row['selected'] ?? null;
                $timeSpent = (int)($row['time_spent_sec'] ?? 0);
                if ($timeSpent < 0) $timeSpent = 0;

                $qType = (string)($qMap[$qid]->question_type ?? 'mcq');
                $selectedJson = json_encode($selected, JSON_UNESCAPED_UNICODE);

                $existing = DB::table('quizz_attempt_answers')
                    ->where('attempt_id', $attempt->id)
                    ->where('question_id', $qid)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    DB::table('quizz_attempt_answers')->where('id', $existing->id)->update([
                        'selected_raw'   => $selectedJson,
                        'question_type'  => $existing->question_type ?: $qType,
                        'time_spent_sec' => max((int)($existing->time_spent_sec ?? 0), $timeSpent),
                        'answered_at'    => $existing->answered_at ?: $now,
                        'updated_at'     => $now,
                    ]);
                } else {
                    DB::table('quizz_attempt_answers')->insert([
                        'attempt_id'     => $attempt->id,
                        'question_id'    => $qid,
                        'question_type'  => $qType,
                        'selected_raw'   => $selectedJson,
                        'time_spent_sec' => $timeSpent,
                        'answered_at'    => $now,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }
            }

            $attemptUpdate = [
                'last_activity_at' => $now,
                'updated_at'       => $now,
            ];
            if ($currentQuestionId > 0) {
                $attemptUpdate['current_question_id']  = $currentQuestionId;
                $attemptUpdate['current_q_started_at'] = $now;
            }

            DB::table('quizz_attempts')->where('id', $attempt->id)->update($attemptUpdate);

            DB::commit();

            $attempt = DB::table('quizz_attempts')->where('id', $attempt->id)->first() ?? $attempt;
            $sessionState = [
                'last_activity_at' => $now->toDateTimeString(),
                'saved_at'         => $request->input('saved_at', $now->toDateTimeString()),
            ];
            if ($currentQuestionId > 0) {
                $sessionState['current_question_id'] = $currentQuestionId;
            }
            if ($request->exists('current_index')) {
                $sessionState['current_index'] = $request->input('current_index');
            }
            if ($request->exists('selections')) {
                $sessionState['selections'] = $request->input('selections');
            }
            if ($request->exists('reviews')) {
                $sessionState['reviews'] = $request->input('reviews');
            }
            if ($request->exists('visited')) {
                $sessionState['visited'] = $request->input('visited');
            }
            if ($request->exists('time_spent_sec')) {
                $sessionState['time_spent_sec'] = $request->input('time_spent_sec');
            }

            $resume = $this->writeAttemptSession($attempt, $sessionState);

            return response()->json([
                'success' => true,
                'attempt' => [
                    'time_left_sec' => $this->timeLeftSec($attempt),
                    'server_end_at' => (string)$attempt->server_deadline_at,
                ],
                'resume' => [
                    'current_index'       => (int) ($resume['current_index'] ?? 0),
                    'current_question_id' => !empty($resume['current_question_id']) ? (int) $resume['current_question_id'] : null,
                    'saved_at'            => $resume['saved_at'] ?? null,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Exam bulkAnswer] failed', ['e'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to save answers'], 500);
        }
    }

    /* ============================================
     | Legacy: focus
     |============================================ */
    public function focus(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $v = Validator::make($request->all(), [
            'question_id' => ['required','integer','min:1'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json(['success'=>false,'message'=>'Attempt is not running'], 409);
        }

        $now = Carbon::now();
        if ($this->deadlinePassed($attempt)) {
            $attempt = $this->autoFinalize($attempt, true);
            return response()->json([
                'success'=>false,
                'message'=>'Time over — attempt auto-submitted',
                'attempt'=>['status'=>$attempt->status,'time_left_sec'=>0]
            ], 409);
        }

        $questionId = (int)$request->input('question_id');

        $qExists = DB::table('quizz_questions')
            ->where('id', $questionId)
            ->where('quiz_id', $attempt->quiz_id)
            ->exists();
        if (!$qExists) return response()->json(['success'=>false,'message'=>'Invalid question'], 422);

        DB::table('quizz_attempts')->where('id', $attempt->id)->update([
            'current_question_id'  => $questionId,
            'current_q_started_at' => $now,
            'last_activity_at'     => $now,
            'updated_at'           => $now,
        ]);

        return response()->json([
            'success'=>true,
            'attempt'=>[
                'time_left_sec' => $this->timeLeftSec($attempt),
                'server_end_at' => (string)$attempt->server_deadline_at,
            ]
        ], 200);
    }

    /* ============================================
     | Legacy: saveAnswer
     |============================================ */
    public function saveAnswer(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $v = Validator::make($request->all(), [
            'question_id' => ['required','integer','min:1'],
            'selected'    => ['nullable'],
            'time_spent'  => ['nullable','integer','min:0'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json(['success'=>false,'message'=>'Attempt is not running'], 409);
        }

        $now = Carbon::now();
        if ($this->deadlinePassed($attempt)) {
            $attempt = $this->autoFinalize($attempt, true);
            return response()->json([
                'success'=>false,
                'message'=>'Time over — attempt auto-submitted',
                'attempt'=>['status'=>$attempt->status,'time_left_sec'=>0]
            ], 409);
        }

        $questionId = (int)$request->input('question_id');

        $qRow = DB::table('quizz_questions')
            ->where('id', $questionId)
            ->where('quiz_id', $attempt->quiz_id)
            ->first(['id','question_type']);
        if (!$qRow) return response()->json(['success'=>false,'message'=>'Invalid question'], 422);

        $qType = (string)($qRow->question_type ?? 'mcq');
        $slice = (int) $request->input('time_spent', 0);
        if ($slice < 0) $slice = 0;

        DB::beginTransaction();
        try {
            $selectedJson = json_encode($request->input('selected', null), JSON_UNESCAPED_UNICODE);

            $row = DB::table('quizz_attempt_answers')
                ->where('attempt_id', $attempt->id)
                ->where('question_id', $questionId)
                ->lockForUpdate()
                ->first();

            if ($row) {
                DB::table('quizz_attempt_answers')->where('id',$row->id)->update([
                    'selected_raw'   => $selectedJson,
                    'question_type'  => $row->question_type ?: $qType,
                    'time_spent_sec' => (int)($row->time_spent_sec ?? 0) + $slice,
                    'answered_at'    => $row->answered_at ?: $now,
                    'updated_at'     => $now,
                ]);
            } else {
                DB::table('quizz_attempt_answers')->insert([
                    'attempt_id'     => $attempt->id,
                    'question_id'    => $questionId,
                    'question_type'  => $qType,
                    'selected_raw'   => $selectedJson,
                    'time_spent_sec' => $slice,
                    'answered_at'    => $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            DB::table('quizz_attempts')->where('id', $attempt->id)->update([
                'current_question_id'  => $questionId,
                'current_q_started_at' => $now,
                'last_activity_at'     => $now,
                'updated_at'           => $now,
            ]);

            DB::commit();

            return response()->json([
                'success'=>true,
                'attempt'=>[
                    'time_left_sec' => $this->timeLeftSec($attempt),
                    'server_end_at' => (string)$attempt->server_deadline_at,
                ]
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Exam saveAnswer] failed', ['e'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to save answer'], 500);
        }
    }

    /* ============================================
     | Submit (keeps old behavior + OPTIONAL answers payload)
     | POST /api/exam/attempts/{attempt}/submit
     | body optional: { answers: [...] } same as bulkAnswer
     |============================================ */
    private function writeAttemptAnswerDerived(int $attemptId, array $breakdown): void
    {
        $now = now();
        foreach ($breakdown as $row) {
            $qid = (int) $row['question_id'];
            $upd = [
                'is_correct'          => $row['is_correct'],
                'awarded_mark'        => (int) $row['awarded_mark'],
                'selected_answer_ids' => isset($row['selected_answer_ids']) ? json_encode($row['selected_answer_ids'], JSON_UNESCAPED_UNICODE) : null,
                'selected_text'       => isset($row['selected_text']) ? (string)$row['selected_text'] : null,
                'updated_at'          => $now,
            ];

            $exists = DB::table('quizz_attempt_answers')
                ->where('attempt_id', $attemptId)
                ->where('question_id', $qid)
                ->exists();

            if ($exists) {
                DB::table('quizz_attempt_answers')
                    ->where('attempt_id', $attemptId)->where('question_id', $qid)
                    ->update($upd + ['answered_at' => DB::raw('COALESCE(answered_at, NOW())')]);
            } else {
                DB::table('quizz_attempt_answers')->insert([
                    'attempt_id'     => $attemptId,
                    'question_id'    => $qid,
                    'question_type'  => DB::table('quizz_questions')->where('id',$qid)->value('question_type') ?? 'mcq',
                    'selected_raw'   => null,
                    'time_spent_sec' => 0,
                    'answered_at'    => $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ] + $upd);
            }
        }
    }

   public function submit(Request $request, string $attemptUuid)
{
    $user = $this->getUserFromToken($request);
    if (!$user) {
        $this->logActivity('quiz', 'default', 'Unauthorized submit attempt', $request);
        return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);
    }

    $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
    if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
        $this->logActivity('quiz', 'default', 'Attempt not found or unauthorized', $request, $user);
        return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
    }

    if (in_array($attempt->status, ['submitted','auto_submitted'], true)) {
        $this->deleteAttemptSession($attempt);
        $this->logActivity('quiz', 'default', 'Re-submit on already completed attempt', $request, $user, $attempt, [
            'status' => $attempt->status,
        ]);
        $summary = $this->resultSummaryForAttempt($attempt);
        return response()->json(['success'=>true] + $summary, 200);
    }

    if ($this->deadlinePassed($attempt)) {
        $attempt = $this->autoFinalize($attempt, true);
        $this->logActivity('quiz', 'default', 'Auto-submitted due to deadline expiry', $request, $user, $attempt);
        $summary = $this->resultSummaryForAttempt($attempt);
        return response()->json(['success'=>true] + $summary, 200);
    }

    $lock = $this->claimExamClientLock($attempt, $request);
    if (!empty($lock['locked'])) {
        return $this->examClientLockResponse($lock);
    }

    $now = Carbon::now();

    DB::beginTransaction();
    try {
        if ($request->has('answers') && is_array($request->input('answers'))) {
            $payload = $request->input('answers', []);
            $qIds = array_values(array_unique(array_map(fn($x) => (int)($x['question_id'] ?? 0), $payload)));
            $qIds = array_values(array_filter($qIds, fn($x) => $x > 0));

            $qMap = DB::table('quizz_questions')
                ->where('quiz_id', $attempt->quiz_id)
                ->whereIn('id', $qIds)
                ->get(['id','question_type'])
                ->keyBy('id');

            if ($qMap->count() !== count($qIds)) {
                DB::rollBack();
                $this->logActivity('quiz', 'default', 'Submit rejected: invalid question IDs', $request, $user, $attempt, [
                    'question_ids' => $qIds,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'One or more questions are invalid for this quiz',
                ], 422);
            }

            foreach ($payload as $row) {
                $qid = (int)($row['question_id'] ?? 0);
                if ($qid <= 0) continue;

                $selected  = $row['selected'] ?? null;
                $timeSpent = max(0, (int)($row['time_spent_sec'] ?? 0));
                $qType     = (string)($qMap[$qid]->question_type ?? 'mcq');
                $selectedJson = json_encode($selected, JSON_UNESCAPED_UNICODE);

                $existing = DB::table('quizz_attempt_answers')
                    ->where('attempt_id', $attempt->id)
                    ->where('question_id', $qid)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    DB::table('quizz_attempt_answers')->where('id', $existing->id)->update([
                        'selected_raw'   => $selectedJson,
                        'question_type'  => $existing->question_type ?: $qType,
                        'time_spent_sec' => max((int)($existing->time_spent_sec ?? 0), $timeSpent),
                        'answered_at'    => $existing->answered_at ?: $now,
                        'updated_at'     => $now,
                    ]);
                } else {
                    DB::table('quizz_attempt_answers')->insert([
                        'attempt_id'     => $attempt->id,
                        'question_id'    => $qid,
                        'question_type'  => $qType,
                        'selected_raw'   => $selectedJson,
                        'time_spent_sec' => $timeSpent,
                        'answered_at'    => $now,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }
            }

            DB::table('quizz_attempts')->where('id', $attempt->id)->update([
                'last_activity_at' => $now,
                'updated_at'       => $now,
            ]);
        }

        $scored  = $this->scoreAttempt($attempt->id);
        $this->writeAttemptAnswerDerived($attempt->id, $scored['answers']);
        $publish = $this->shouldPublishToStudent((int)$attempt->quiz_id);

        $cnt = $scored['counters'];
        $pct = $scored['total_marks'] ? round($scored['marks_obtained'] / $scored['total_marks'] * 100, 2) : 0;

        $resultId = DB::table('quizz_results')->insertGetId([
            'uuid'               => (string) Str::uuid(),
            'attempt_id'         => (int) $attempt->id,
            'quiz_id'            => (int) $attempt->quiz_id,
            'user_id'            => (int) $attempt->user_id,
            'marks_obtained'     => (int) $scored['marks_obtained'],
            'total_marks'        => (int) $scored['total_marks'],
            'marks_total'        => (int) $scored['total_marks'],
            'total_questions'    => (int) $cnt['total_questions'],
            'total_correct'      => (int) $cnt['total_correct'],
            'total_incorrect'    => (int) $cnt['total_incorrect'],
            'total_skipped'      => (int) $cnt['total_skipped'],
            'percentage'         => $pct,
            'attempt_number'     => (int) $this->attemptNumberForUser((int)$attempt->quiz_id, (int)$attempt->user_id) + 1,
            'students_answer'    => json_encode($scored['answers'], JSON_UNESCAPED_UNICODE),
            'publish_to_student' => $publish ? 1 : 0,
            'result_set_up_type' => DB::table('quizz')->where('id', $attempt->quiz_id)->value('result_set_up_type') ?? 'Immediately',
            'result_release_date'=> DB::table('quizz')->where('id', $attempt->quiz_id)->value('result_release_date'),
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        DB::table('quizz_attempts')->where('id', $attempt->id)->update([
            'status'      => 'submitted',
            'finished_at' => $now,
            'updated_at'  => $now,
            'result_id'   => $resultId,
        ]);

        // ✅ Inside transaction — rolls back automatically if commit fails
        $this->logActivity('quiz', 'store', 'Attempt submitted and scored successfully', $request, $user, $attempt, [
            'result_id'       => $resultId,
            'marks_obtained'  => $scored['marks_obtained'],
            'total_marks'     => $scored['total_marks'],
            'percentage'      => $pct,
            'total_correct'   => $cnt['total_correct'],
            'total_incorrect' => $cnt['total_incorrect'],
            'total_skipped'   => $cnt['total_skipped'],
        ]);

        DB::commit();

        $this->deleteAttemptSession($attempt);

        $attempt = DB::table('quizz_attempts')->where('id', $attempt->id)->first();
        $summary = $this->resultSummaryForAttempt($attempt);

        return response()->json(['success'=>true] + $summary, 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('[Exam submit] failed', ['e' => $e->getMessage()]);

        // ❌ Outside transaction — always persists even after rollback
        $this->logActivity('quiz', 'default', 'Submit transaction failed and rolled back', $request, $user, $attempt, [
            'error' => $e->getMessage(),
        ]);

        return response()->json(['success'=>false,'message'=>'Failed to submit'], 500);
    }
}
    /* ============================================
     | Status
     |============================================ */
    public function status(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }

        if ($this->deadlinePassed($attempt) && $attempt->status === 'in_progress') {
            $attempt = $this->autoFinalize($attempt, true);
        }

        return response()->json([
            'success'=>true,
            'attempt'=>[
                'status'        => $attempt->status,
                'time_left_sec' => $this->timeLeftSec($attempt),
                'server_end_at' => (string)$attempt->server_deadline_at,
            ]
        ], 200);
    }

    /* ============================================
     | Answer Sheet (RESULT API - unchanged)
     |============================================ */
    public function answerSheet(int $resultId)
    {
        $res = DB::table('quizz_results')->where('id', $resultId)->first();
        if (!$res) abort(404, 'Result not found');

        $user  = DB::table('users')->where('id', $res->user_id)->first();
        $quiz  = DB::table('quizz')->where('id', $res->quiz_id)->first();
        $ans   = json_decode($res->students_answer ?? '[]', true) ?: [];

        $questions = DB::table('quizz_questions')
            ->where('quiz_id', $res->quiz_id)
            ->orderBy('question_order')
            ->get();

        $answerRows = DB::table('quizz_question_answers')
            ->whereIn('belongs_question_id', $questions->pluck('id'))
            ->orderBy('belongs_question_id')
            ->orderBy('answer_order')
            ->get()
            ->groupBy('belongs_question_id');

        $totalMarks = (int) $questions->sum('question_mark');
        $pct = $totalMarks ? round(((int)$res->marks_obtained / $totalMarks) * 100) : 0;
        $passFail = $pct >= 60 ? 'PASS' : 'FAIL';

        return Response::streamDownload(function () use ($user,$quiz,$ans,$questions,$answerRows,$res,$totalMarks,$pct,$passFail) {
            $safe = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
            echo "<!doctype html><html><head><meta charset='utf-8'><title>Answer Sheet</title>
<style>
@page { size:A4; margin:0 }
body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:20px;color:#222}
h1,h2{margin:6px 0}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:12px}
.badge{display:inline-block;padding:4px 8px;border-radius:14px;background:#eef2ff}
.meta{font-size:13px;color:#666}
.sep{height:1px;background:#eee;margin:10px 0}
.correct{color:#166534}
.wrong{color:#991b1b}
</style></head><body>";

            echo "<div class='card'><h1>{$safe($quiz->quiz_name ?? 'Exam')}</h1>
<div class='meta'>Student: {$safe($user->name ?? ('#'.$user->id))}</div>
<div class='meta'>Score: <b>{$res->marks_obtained}/{$totalMarks}</b> • {$pct}% • <span class='badge'>{$passFail}</span></div>
</div>";

            foreach ($questions as $q) {
                $qAns = $answerRows[$q->id] ?? collect();
                $correctIds = $qAns->where('is_correct', 1)->pluck('id')->values()->all();

                $stuSel = null;
                foreach ($ans as $row) {
                    if ((int)($row['question_id'] ?? 0) === (int)$q->id) {
                        $stuSel = $row['selected'] ?? null;
                        break;
                    }
                }

                // derive correctness (for print)
                if ($q->question_type === 'fill_in_the_blank') {
                    $ex = $this->fibExplain($q, $qAns, $stuSel);
                    $correct     = $ex['correct'];
                    $stuDisplay  = $ex['student_display'];
                    $corrDisplay = $ex['correct_display'];
                } else {
                    $correct = false;
                    if (is_array($stuSel)) {
                        $l = $stuSel; sort($l);
                        $r = $correctIds; sort($r);
                        $correct = ($l === $r);
                        $labels = [];
                        foreach ($qAns as $a) if (in_array($a->id, $l)) $labels[] = $a->answer_title;
                        $stuDisplay = implode(', ', $labels);
                    } else {
                        $correct = ((int)$stuSel === (int)($correctIds[0] ?? -1));
                        $label = '';
                        foreach ($qAns as $a) if ((int)$a->id === (int)$stuSel) { $label = $a->answer_title; break; }
                        $stuDisplay = $label;
                    }
                    $corrDisplay = '';
                    foreach ($qAns as $a) if (in_array($a->id, $correctIds)) { $corrDisplay = $a->answer_title; break; }
                }

                echo "<div class='card'><div><b>Q{$q->question_order}.</b> {$safe($q->question_title)}</div>
<div class='meta'>Marks: {$q->question_mark} • Type: {$q->question_type}</div><div class='sep'></div>";

                if ($correct) {
                    echo "<div class='correct'><b>Correct</b></div>";
                } else {
                    echo "<div class='wrong'><b>Incorrect</b></div>";
                    echo "<div>Correct: {$safe($corrDisplay)}</div>";
                }
                if ($stuDisplay !== '') echo "<div>Your answer: {$safe($stuDisplay)}</div>";
                echo "</div>";
            }
            echo "</body></html>";
        }, "answer_sheet_{$resultId}.html", ['Content-Type' => 'text/html']);
    }

    /* ==================== internals ==================== */

    private function deadlinePassed(object $attempt): bool
    {
        try { return Carbon::now()->gte(Carbon::parse($attempt->server_deadline_at)); }
        catch (\Throwable $e) { return false; }
    }

    private function timeLeftSec(object $attempt): int
    {
        try {
            $left = Carbon::now()->diffInSeconds(Carbon::parse($attempt->server_deadline_at), false);
            return max(0, $left);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function autoFinalize(object $attempt, bool $refresh = false): object
    {
        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $scored  = $this->scoreAttempt($attempt->id);
            $this->writeAttemptAnswerDerived($attempt->id, $scored['answers']);
            $publish = $this->shouldPublishToStudent((int)$attempt->quiz_id);

            $cnt = $scored['counters'];
            $pct = $scored['total_marks'] ? round($scored['marks_obtained'] / $scored['total_marks'] * 100, 2) : 0;

            $resultId = DB::table('quizz_results')->insertGetId([
                'uuid'               => (string) Str::uuid(),
                'attempt_id'         => (int) $attempt->id,
                'quiz_id'            => (int) $attempt->quiz_id,
                'user_id'            => (int) $attempt->user_id,
                'marks_obtained'     => (int) $scored['marks_obtained'],
                'total_marks'        => (int) $scored['total_marks'],
                'marks_total'        => (int) $scored['total_marks'],
                'total_questions'    => (int) $cnt['total_questions'],
                'total_correct'      => (int) $cnt['total_correct'],
                'total_incorrect'    => (int) $cnt['total_incorrect'],
                'total_skipped'      => (int) $cnt['total_skipped'],
                'percentage'         => $pct,
                'attempt_number'     => (int) $this->attemptNumberForUser((int)$attempt->quiz_id, (int)$attempt->user_id) + 1,
                'students_answer'    => json_encode($scored['answers'], JSON_UNESCAPED_UNICODE),
                'publish_to_student' => $publish ? 1 : 0,
                'result_set_up_type' => DB::table('quizz')->where('id',$attempt->quiz_id)->value('result_set_up_type') ?? 'Immediately',
                'result_release_date'=> DB::table('quizz')->where('id',$attempt->quiz_id)->value('result_release_date'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            DB::table('quizz_attempts')->where('id', $attempt->id)->update([
                'status'      => 'auto_submitted',
                'finished_at' => $now,
                'updated_at'  => $now,
                'result_id'   => $resultId,
            ]);

            DB::commit();
            $this->deleteAttemptSession($attempt);

            return $refresh
                ? DB::table('quizz_attempts')->where('id',$attempt->id)->first()
                : $attempt;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Exam autoFinalize] failed', ['e'=>$e->getMessage()]);
            return $attempt;
        }
    }

    private function attemptNumberForUser(int $quizId, int $userId): int
    {
        return (int) DB::table('quizz_results')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->count();
    }

    private function shouldPublishToStudent(int $quizId): bool
    {
        $q = DB::table('quizz')->where('id', $quizId)->first(['result_set_up_type','result_release_date']);
        $type = (string)($q->result_set_up_type ?? 'Immediately');
        if ($type === 'Immediately' || $type === 'Now') return true;
        if ($type === 'Schedule' && !empty($q->result_release_date)) {
            try {
                return Carbon::now()->gte(Carbon::parse($q->result_release_date));
            } catch (\Throwable $e) { return false; }
        }
        return false;
    }

    /* ===== FIB helpers ===== */

    private function fibCountGaps(object $q, $answers): int
    {
        $title = (string)($q->question_title ?? '');
        $desc  = (string)($q->question_description ?? '');
        $n = 0;
        try {
            $n += preg_match_all('/\{dash\}/i', $title);
            $n += preg_match_all('/\{dash\}/i', $desc);
        } catch (\Throwable $e) {}
        if ($n > 0) return (int)$n;

        if ($answers && method_exists($answers, 'pluck')) {
            $orders = $answers->pluck('answer_order')
                ->filter(fn($v) => is_numeric($v) && (int)$v > 0)
                ->unique()->count();
            if ($orders > 0) return (int)$orders;
            return max(1, (int)$answers->count());
        }
        return 1;
    }

    private function fibExplain(object $q, $answers, $selRaw): array
    {
        $stuArr = [];
        if (is_array($selRaw))       $stuArr = array_values(array_map('strval', $selRaw));
        else if (trim((string)$selRaw) !== '') $stuArr = [ (string)$selRaw ];

        $gaps = $this->fibCountGaps($q, $answers);

        $distinctOrders = 0;
        if ($answers && method_exists($answers, 'pluck')) {
            $distinctOrders = (int) $answers->pluck('answer_order')
                ->filter(fn($v) => is_numeric($v) && (int)$v > 0)
                ->unique()->count();
        }
        $perGap = ($gaps > 1) || ($distinctOrders > 1);
        $norm = fn($s) => $this->normalizeText((string)$s);

        if (!$perGap) {
            $stuCombined = $norm(is_array($selRaw) ? implode(' ', $selRaw) : (string)$selRaw);
            $correct = false; $correctText = '';
            if ($answers) {
                foreach ($answers as $a) {
                    $src = (string) ($a->answer_two_gap_match ?? $a->answer_title ?? '');
                    if ($correctText === '' && $src !== '') $correctText = $src;
                    if ($stuCombined !== '' && $norm($src) !== '' && $stuCombined === $norm($src)) {
                        $correct = true; break;
                    }
                }
            }
            return [
                'correct'         => $correct,
                'student_display' => is_array($selRaw) ? implode(' | ', $stuArr) : (string)$selRaw,
                'correct_display' => $correctText,
            ];
        }

        $allOk = true; $studentParts = []; $correctParts = [];
        for ($i = 1; $i <= $gaps; $i++) {
            $allowed = ($answers && method_exists($answers, 'where')) ? $answers->where('answer_order', $i) : null;
            if (!$allowed || (method_exists($allowed, 'isEmpty') && $allowed->isEmpty())) {
                $row = ($answers && method_exists($answers, 'values')) ? $answers->values()->get($i-1) : null;
                $allowed = $row ? collect([$row]) : collect();
            }

            $stuRaw = (string)($stuArr[$i-1] ?? '');
            $studentParts[] = $stuRaw;
            $first = (method_exists($allowed, 'first') ? $allowed->first() : null);
            $correctParts[] = $first ? (string)($first->answer_two_gap_match ?? $first->answer_title ?? '') : '';

            $stu = $norm($stuRaw);
            $hit = false;
            if ($stu !== '' && $allowed) {
                foreach ($allowed as $a) {
                    $needle = $norm($a->answer_two_gap_match ?? $a->answer_title ?? '');
                    if ($needle !== '' && $stu === $needle) { $hit = true; break; }
                }
            }
            if (!$hit) $allOk = false;
        }

        return [
            'correct'         => $allOk,
            'student_display' => implode(' | ', $studentParts),
            'correct_display' => implode(' | ', $correctParts),
        ];
    }

    private function scoreAttempt(int $attemptId): array
    {
        $attempt = DB::table('quizz_attempts')->where('id', $attemptId)->first();
        if (!$attempt) {
            return [
                'marks_obtained'=>0,'total_marks'=>0,'answers'=>[],
                'counters'=>['total_questions'=>0,'total_correct'=>0,'total_incorrect'=>0,'total_skipped'=>0]
            ];
        }

        $qRows = DB::table('quizz_questions')
            ->where('quiz_id', $attempt->quiz_id)
            ->orderBy('question_order')
            ->get();

        $aRows = DB::table('quizz_question_answers')
            ->whereIn('belongs_question_id', $qRows->pluck('id'))
            ->orderBy('belongs_question_id')
            ->orderBy('answer_order')
            ->get()
            ->groupBy('belongs_question_id');

        $saved = DB::table('quizz_attempt_answers')
            ->where('attempt_id', $attemptId)
            ->get()
            ->keyBy('question_id');

        $marksObtained = 0;
        $totalMarks    = (int) $qRows->sum('question_mark');

        $totalQuestions = (int) $qRows->count();
        $totalCorrect = 0; $totalIncorrect = 0; $totalSkipped = 0;

        $snapshot = [];

        foreach ($qRows as $q) {
            $qid = (int)$q->id;
            $answers = $aRows[$qid] ?? collect();
            $selRaw  = null;

            if (isset($saved[$qid])) {
                try { $selRaw = json_decode($saved[$qid]->selected_raw ?? 'null', true); }
                catch (\Throwable $e) { $selRaw = null; }
            }

            $type = (string)$q->question_type;
            $awarded = 0; $isCorrect = false;
            $selectedIds = null; $selectedText = null;

            if ($type === 'fill_in_the_blank') {
                $fib = $this->fibExplain($q, $answers, $selRaw);
                $isCorrect    = $fib['correct'];
                $awarded      = $isCorrect ? (int)$q->question_mark : 0;
                $selectedIds  = null;
                $selectedText = $fib['student_display'];
            } else {
                $correctIds = $answers->where('is_correct',1)->pluck('id')->values()->all();
                if (count($correctIds) > 1) {
                    $l = is_array($selRaw) ? array_values(array_map('intval', $selRaw)) : [];
                    sort($l);
                    $r = $correctIds; sort($r);
                    $isCorrect = ($l === $r);
                    $selectedIds = $l;
                } else {
                    $isCorrect = ((int)$selRaw === (int)($correctIds[0] ?? -1));
                    $selectedIds = is_null($selRaw) ? null : [(int)$selRaw];
                }
                if ($isCorrect) $awarded = (int)$q->question_mark;

                if (is_array($selectedIds) && !empty($selectedIds)) {
                    $labels = [];
                    foreach ($answers as $a) if (in_array((int)$a->id, $selectedIds, true)) $labels[] = (string)$a->answer_title;
                    $selectedText = implode(', ', $labels);
                }
            }

            $isEmpty = ($selRaw === null) || (is_array($selRaw) && count(array_filter($selRaw, fn($v)=>trim((string)$v) !== '')) === 0);
            if ($isEmpty) $totalSkipped++;
            else $isCorrect ? $totalCorrect++ : $totalIncorrect++;

            $marksObtained += $awarded;

            $snapshot[] = [
                'question_id'         => $qid,
                'selected'            => $selRaw,
                'is_correct'          => $isCorrect ? 1 : 0,
                'awarded_mark'        => $awarded,
                'selected_answer_ids' => $selectedIds,
                'selected_text'       => $selectedText,
            ];
        }

        return [
            'marks_obtained' => $marksObtained,
            'total_marks'    => $totalMarks,
            'answers'        => $snapshot,
            'counters'       => [
                'total_questions' => $totalQuestions,
                'total_correct'   => $totalCorrect,
                'total_incorrect' => $totalIncorrect,
                'total_skipped'   => $totalSkipped,
            ],
        ];
    }

    private function resultSummaryForAttempt(object $attempt): array
    {
        $res = $attempt->result_id
            ? DB::table('quizz_results')->where('id', $attempt->result_id)->first()
            : null;

        return [
            'attempt' => [
                'status'      => $attempt->status,
                'finished_at' => (string)($attempt->finished_at ?? ''),
                'result_id'   => $attempt->result_id ?? null,
            ],
            'result' => $res ? [
                'result_id'         => $res->id,
                'marks_obtained'    => (int)$res->marks_obtained,
                'total_marks'       => (int)$res->total_marks,
                'percentage'        => ($res->total_marks ? round($res->marks_obtained / $res->total_marks * 100) : 0),
                'publish_to_student'=> (int)$res->publish_to_student,
            ] : null,
        ];
    }

    /* ==========================================================
     | RESULT APIs BELOW — kept same as your previous version
     | resultDetail(), resultDetailForInstructor(), export(),
     | assignedResultsForQuiz()
     |========================================================== */

     public function resultDetail(Request $request, string $resultKey)
{
    $viewer = $this->getUserFromToken($request);

    // ---------- 1. Load result + attempt + quiz (by ID or UUID) ----------
    $row = DB::table('quizz_results as r')
        ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
        ->join('quizz as q', 'q.id', '=', 'r.quiz_id')
        ->where(function ($w) use ($resultKey) {
            if (is_numeric($resultKey)) {
                $w->where('r.id', (int) $resultKey);
            } else {
                $w->where('r.uuid', (string) $resultKey);
            }
        })
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.quiz_id',
            'r.attempt_id',
            'r.user_id',
            'r.marks_obtained',
            'r.total_marks',
            'r.total_questions',
            'r.total_correct',
            'r.total_incorrect',
            'r.total_skipped',
            'r.percentage',
            'r.attempt_number',
            'r.students_answer',
            'r.publish_to_student',
            Schema::hasColumn('quizz_results', 'seen_by_student')
                ? 'r.seen_by_student'
                : DB::raw('0 as seen_by_student'),
            'r.result_set_up_type',
            'r.result_release_date',
            'r.created_at as result_created_at',

            'a.uuid as attempt_uuid',
            'a.status as attempt_status',
            'a.started_at',
            'a.finished_at',
            'a.total_time_sec',
            'a.server_deadline_at',

            'q.quiz_name',
            'q.quiz_description',
            'q.total_time',
            'q.total_questions as quiz_total_questions',
        ])
        ->first();

    if (!$row) {
        return response()->json([
            'success' => false,
            'message' => 'Result not found'
        ], 404);
    }

    // ---------- 2. Decode stored per-question snapshot ----------
    try {
        $snapshot = json_decode($row->students_answer ?? '[]', true) ?: [];
    } catch (\Throwable $e) {
        $snapshot = [];
    }

    $snapByQ = [];
    foreach ($snapshot as $s) {
        if (isset($s['question_id'])) {
            $snapByQ[(int)$s['question_id']] = $s;
        }
    }

    // ---------- 3. Load questions + answer options ----------
    $questions = DB::table('quizz_questions')
        ->where('quiz_id', $row->quiz_id)
        ->orderBy('question_order')
        ->get();

    $answerRows = DB::table('quizz_question_answers')
        ->whereIn('belongs_question_id', $questions->pluck('id'))
        ->orderBy('belongs_question_id')
        ->orderBy('answer_order')
        ->get()
        ->groupBy('belongs_question_id');

    // time_spent per question
    $timeRows = DB::table('quizz_attempt_answers')
        ->where('attempt_id', $row->attempt_id)
        ->get()
        ->keyBy('question_id');

    $questionPayload = [];
    $totalTimeUsed   = 0;

    foreach ($questions as $q) {
        $qid      = (int) $q->id;
        $ansRows  = $answerRows[$qid] ?? collect();
        $snap     = $snapByQ[$qid] ?? null;
        $timeRow  = $timeRows[$qid] ?? null;

        if ($timeRow) {
            $totalTimeUsed += (int) $timeRow->time_spent_sec;
        }

        // --- Compute correct_text for ALL types, including FIB ---
        $correctText = '';
        $qType = (string) ($q->question_type ?? '');

        if ($qType === 'fill_in_the_blank') {
            $selRaw = $snap['selected'] ?? null;
            $fib    = $this->fibExplain($q, $ansRows, $selRaw);
            $correctText = (string) ($fib['correct_display'] ?? '');
        } else {
            $correctLabels = $ansRows
                ->where('is_correct', 1)
                ->pluck('answer_title')
                ->values()
                ->all();
            $correctText = implode(', ', $correctLabels);
        }

        $questionPayload[] = [
            'question_id'         => $qid,
            'order'               => (int) $q->question_order,
            'title'               => $q->question_title,
            'description'         => $q->question_description,
            'type'                => $qType,
            'mark'                => (int) $q->question_mark,
            'time_spent_sec'      => $timeRow ? (int) $timeRow->time_spent_sec : 0,
            'is_correct'          => $snap ? (int) ($snap['is_correct'] ?? 0) : 0,
            'awarded_mark'        => $snap ? (int) ($snap['awarded_mark'] ?? 0) : 0,
            'selected_answer_ids' => $snap['selected_answer_ids'] ?? null,
            'selected_text'       => $snap['selected_text'] ?? null,
            'correct_text'        => $correctText,
            'answers'             => $ansRows->map(function ($a) {
                return [
                    'answer_id'    => (int) $a->id,
                    'title'        => $a->answer_title,
                    'is_correct'   => (int) $a->is_correct,
                    'answer_order' => (int) ($a->answer_order ?? 0),
                ];
            })->values(),
        ];
    }

    // ---------- 4. OPTIONAL: also include student details from DB ----------
        $student = DB::table('users')
            ->where('id', (int) $row->user_id)
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'email']);

    $seenByStudent = (int) ($row->seen_by_student ?? 0);
    if (
        $viewer &&
        (int) $viewer->id === (int) $row->user_id &&
        $this->isStudent($viewer) &&
        Schema::hasColumn('quizz_results', 'seen_by_student')
    ) {
        DB::table('quizz_results')
            ->where('id', (int) $row->result_id)
            ->update([
                'seen_by_student' => 1,
                'updated_at' => now(),
            ]);
        $seenByStudent = 1;
    }

    // ---------- 5. Response payload ----------
    return response()->json([
        'success'  => true,

        'quiz'     => [
            'id'               => (int) $row->quiz_id,
            'name'             => $row->quiz_name,
            'description'      => $row->quiz_description,
            'total_questions'  => (int) ($row->quiz_total_questions ?? $row->total_questions),
            'total_time'       => (int) ($row->total_time ?? 0), // minutes
        ],

        'attempt'  => [
            'attempt_id'         => (int) $row->attempt_id,
            'attempt_uuid'       => (string) $row->attempt_uuid,
            'status'             => (string) $row->attempt_status,
            'started_at'         => $row->started_at ? Carbon::parse($row->started_at)->toDateTimeString() : null,
            'finished_at'        => $row->finished_at ? Carbon::parse($row->finished_at)->toDateTimeString() : null,
            'total_time_sec'     => (int) $row->total_time_sec,
            'server_deadline_at' => $row->server_deadline_at ? Carbon::parse($row->server_deadline_at)->toDateTimeString() : null,
            'time_used_sec'      => $totalTimeUsed,
        ],

        'result'   => [
            'result_id'        => (int) $row->result_id,
            'result_uuid'      => (string) ($row->result_uuid ?? ''),
            'user_id'          => (int) $row->user_id,
            'marks_obtained'   => (int) $row->marks_obtained,
            'total_marks'      => (int) $row->total_marks,
            'percentage'       => $row->total_marks
                ? (float) round($row->marks_obtained / max(1, $row->total_marks) * 100, 2)
                : 0.0,
            'attempt_number'   => (int) ($row->attempt_number ?? 0),
            'total_questions'  => (int) $row->total_questions,
            'total_correct'    => (int) $row->total_correct,
            'total_incorrect'  => (int) $row->total_incorrect,
            'total_skipped'    => (int) $row->total_skipped,
            'publish_to_student' => (int) ($row->publish_to_student ?? 0),
            'seen_by_student'    => $seenByStudent,
            'result_created_at'  => $row->result_created_at
                ? Carbon::parse($row->result_created_at)->toDateTimeString()
                : null,
        ],

        'student' => $student ? [
            'id'    => (int) $student->id,
            'name'  => (string) $student->name,
            'email' => (string) $student->email,
        ] : null,

        'questions' => $questionPayload,
    ], 200);
}

     

     public function resultDetailForInstructor(Request $request, int $resultId)
     {
    
         $actor = $this->actor($request);
         $role  = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)($actor['role'] ?? '')));
         $userId = (int)($actor['id'] ?? 0);
     
         if ($userId <= 0) {
             return response()->json([
                 'success' => false,
                 'message' => 'Unauthorized',
             ], 401);
         }
     
         // 2) Load result + attempt + quiz (+ student)
         $row = DB::table('quizz_results as r')
             ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
             ->join('quizz as q', 'q.id', '=', 'r.quiz_id')
             ->join('users as u', 'u.id', '=', 'r.user_id')
             ->where('r.id', $resultId)
             ->select([
                 'r.id as result_id',
                 'r.quiz_id',
                 'r.attempt_id',
                 'r.user_id',
                 'r.marks_obtained',
                 'r.total_marks',
                 'r.total_questions',
                 'r.total_correct',
                 'r.total_incorrect',
                 'r.total_skipped',
                 'r.percentage',
                 'r.attempt_number',
                 'r.students_answer',
                 'r.publish_to_student',
                 'r.result_set_up_type',
                 'r.result_release_date',
                 'r.created_at as result_created_at',
     
                 'a.uuid as attempt_uuid',
                 'a.status as attempt_status',
                 'a.started_at',
                 'a.finished_at',
                 'a.total_time_sec',
                 'a.server_deadline_at',
     
                 'q.quiz_name',
                 'q.quiz_description',
                 'q.total_time',
                 'q.total_questions as quiz_total_questions',
     
                 'u.name as student_name',
                 'u.email as student_email',
             ])
             ->first();
     
         if (!$row) {
             return response()->json([
                 'success' => false,
                 'message' => 'Result not found',
             ], 404);
         }
     
         // 3) Extra guard: instructor/examiner must be assigned to this quiz
         if (in_array($role, ['instructor','examiner'], true)) {
             $assigned = DB::table('user_quiz_assignments')
                 ->where('quiz_id', $row->quiz_id)
                 ->where('user_id', $userId)
                 ->whereNull('deleted_at')
                 ->where('status', 'active')
                 ->exists();
     
             if (!$assigned) {
                 return response()->json([
                     'success' => false,
                     'message' => 'You are not assigned to this quiz',
                 ], 403);
             }
         }
     
         // 4) Decode stored per-question snapshot
         try {
             $snapshot = json_decode($row->students_answer ?? '[]', true) ?: [];
         } catch (\Throwable $e) {
             $snapshot = [];
         }
         $snapByQ = [];
         foreach ($snapshot as $s) {
             if (isset($s['question_id'])) {
                 $snapByQ[(int)$s['question_id']] = $s;
             }
         }
     
         // 5) Load questions + answers
         $questions = DB::table('quizz_questions')
             ->where('quiz_id', $row->quiz_id)
             ->orderBy('question_order')
             ->get();
     
         $answerRows = DB::table('quizz_question_answers')
             ->whereIn('belongs_question_id', $questions->pluck('id'))
             ->orderBy('belongs_question_id')
             ->orderBy('answer_order')
             ->get()
             ->groupBy('belongs_question_id');
     
         // time_spent per question
         $timeRows = DB::table('quizz_attempt_answers')
             ->where('attempt_id', $row->attempt_id)
             ->get()
             ->keyBy('question_id');
     
         $questionPayload = [];
         $totalTimeUsed = 0;
     
         foreach ($questions as $q) {
             $qid     = (int)$q->id;
             $ansRows = $answerRows[$qid] ?? collect();
             $snap    = $snapByQ[$qid] ?? null;
             $timeRow = $timeRows[$qid] ?? null;
     
             if ($timeRow) {
                 $totalTimeUsed += (int)$timeRow->time_spent_sec;
             }
     
             // Build correct_text for MCQ + FIB
             $correctText = '';
             $qType = (string)($q->question_type ?? '');
     
             if ($qType === 'fill_in_the_blank') {
                 $selRaw = $snap['selected'] ?? null;
                 $fib    = $this->fibExplain($q, $ansRows, $selRaw);
                 $correctText = (string)($fib['correct_display'] ?? '');
             } else {
                 $correctLabels = $ansRows
                     ->where('is_correct', 1)
                     ->pluck('answer_title')
                     ->values()
                     ->all();
                 $correctText = implode(', ', $correctLabels);
             }
     
             $questionPayload[] = [
                 'question_id'         => $qid,
                 'order'               => (int)$q->question_order,
                 'title'               => $q->question_title,
                 'description'         => $q->question_description,
                 'type'                => $qType,
                 'mark'                => (int)$q->question_mark,
                 'time_spent_sec'      => $timeRow ? (int)$timeRow->time_spent_sec : 0,
                 'is_correct'          => $snap ? (int)($snap['is_correct'] ?? 0) : 0,
                 'awarded_mark'        => $snap ? (int)($snap['awarded_mark'] ?? 0) : 0,
                 'selected_answer_ids' => $snap['selected_answer_ids'] ?? null,
                 'selected_text'       => $snap['selected_text'] ?? null,
                 'correct_text'        => $correctText,
                 'answers'             => $ansRows->map(function ($a) {
                     return [
                         'answer_id'    => (int)$a->id,
                         'title'        => $a->answer_title,
                         'is_correct'   => (int)$a->is_correct,
                         'answer_order' => (int)($a->answer_order ?? 0),
                     ];
                 })->values(),
             ];
         }
     
         return response()->json([
             'success'  => true,
             'quiz'     => [
                 'id'               => (int)$row->quiz_id,
                 'name'             => $row->quiz_name,
                 'description'      => $row->quiz_description,
                 'total_questions'  => (int)($row->quiz_total_questions ?? $row->total_questions),
                 'total_time'       => (int)($row->total_time ?? 0),
             ],
             'attempt'  => [
                 'attempt_id'         => (int)$row->attempt_id,
                 'attempt_uuid'       => (string)$row->attempt_uuid,
                 'status'             => $row->attempt_status,
                 'started_at'         => $row->started_at
                                          ? Carbon::parse($row->started_at)->toDateTimeString()
                                          : null,
                 'finished_at'        => $row->finished_at
                                          ? Carbon::parse($row->finished_at)->toDateTimeString()
                                          : null,
                 'total_time_sec'     => (int)$row->total_time_sec,
                 'server_deadline_at' => $row->server_deadline_at
                                          ? Carbon::parse($row->server_deadline_at)->toDateTimeString()
                                          : null,
                 'time_used_sec'      => $totalTimeUsed,
             ],
             'result'   => [
                 'result_id'       => (int)$row->result_id,
                 'marks_obtained'  => (int)$row->marks_obtained,
                 'total_marks'     => (int)$row->total_marks,
                 'percentage'      => $row->total_marks
                     ? (float)round($row->marks_obtained / max(1, $row->total_marks) * 100, 2)
                     : 0.0,
                 'attempt_number'  => (int)($row->attempt_number ?? 0),
                 'total_questions' => (int)$row->total_questions,
                 'total_correct'   => (int)$row->total_correct,
                 'total_incorrect' => (int)$row->total_incorrect,
                 'total_skipped'   => (int)$row->total_skipped,
             ],
             'questions' => $questionPayload,
             'student'   => [
                 'id'    => (int)$row->user_id,
                 'name'  => (string)$row->student_name,
                 'email' => (string)$row->student_email,
             ],
         ], 200);
     }

    public function export(Request $request, int $resultId)
    {
        // Auth (same as resultDetail)
        $user = $this->getUserFromToken($request);
    
        // Load row & enforce ownership
        $row = DB::table('quizz_results as r')
            ->join('quizz as q', 'q.id', '=', 'r.quiz_id')
            ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
            ->where('r.id', $resultId)
            ->where('r.user_id', $user->id)
            ->select([
                'r.*',
                'a.started_at','a.finished_at','a.total_time_sec',
                'q.quiz_name','q.quiz_description','q.total_time'
            ])->first();
    
        if (!$row) {
            return response()->json(['success'=>false,'message'=>'Result not found'], 404);
        }
    
        // respect publish logic
        $allowViewNow = $this->shouldPublishToStudent((int)$row->quiz_id);
        if (!((int)$row->publish_to_student === 1 || $allowViewNow)) {
            return response()->json(['success'=>false,'message'=>'Result is not yet published for students'], 403);
        }
    
        // Load question snapshot for the document (optional/simple)
        $answers = json_decode($row->students_answer ?? '[]', true) ?: [];
        $questions = DB::table('quizz_questions')
            ->where('quiz_id', $row->quiz_id)
            ->orderBy('question_order')
            ->get();
        $answerRows = DB::table('quizz_question_answers')
            ->whereIn('belongs_question_id', $questions->pluck('id'))
            ->orderBy('belongs_question_id')->orderBy('answer_order')
            ->get()->groupBy('belongs_question_id');
    
        // Build a minimal normalized structure for export
        $mapSnap = [];
        foreach ($answers as $a) if (isset($a['question_id'])) $mapSnap[(int)$a['question_id']] = $a;
    
        $items = [];
        foreach ($questions as $q) {
            $qid = (int)$q->id;
            $snap = $mapSnap[$qid] ?? null;
            $corr = $answerRows[$qid] ?? collect();
            $correctLabels = $corr->where('is_correct',1)->pluck('answer_title')->values()->all();
            $items[] = [
                'no'         => (int)$q->question_order,
                'title'      => (string)$q->question_title,
                'mark'       => (int)$q->question_mark,
                'is_correct' => (int)($snap['is_correct'] ?? 0),
                'awarded'    => (int)($snap['awarded_mark'] ?? 0),
                'your'       => isset($snap['selected_text']) ? (string)$snap['selected_text'] : '',
                'correct'    => implode(', ', $correctLabels),
            ];
        }
    
        $format = strtolower($request->query('format','docx'));
        $safeName = 'exam_result_'.$resultId;
    
        if ($format === 'docx') {
            // Prefer PhpWord if available
            if (class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
                $phpWord  = new \PhpOffice\PhpWord\PhpWord();
                $section  = $phpWord->addSection();
                $fontH    = ['bold' => true, 'size' => 16];
                $fontB    = ['bold' => true];
                $fontM    = ['size' => 11];
    
                $section->addText('Exam Result', $fontH);
                $section->addTextBreak(1);
    
                $section->addText('Quiz: '.$row->quiz_name, $fontB);
                $section->addText('Student: '.($user->name ?? ('#'.$user->id)), $fontM);
                $section->addText('Score: '.$row->marks_obtained.' / '.$row->total_marks.'  ('.($row->total_marks ? round($row->marks_obtained/max(1,$row->total_marks)*100,2) : 0).'%)', $fontM);
                if ($row->started_at)  $section->addText('Started at: '.\Carbon\Carbon::parse($row->started_at)->toDayDateTimeString(), $fontM);
                if ($row->finished_at) $section->addText('Finished at: '.\Carbon\Carbon::parse($row->finished_at)->toDayDateTimeString(), $fontM);
                $section->addTextBreak(1);
    
                // Table
                $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'cccccc', 'cellMargin' => 60]);
                $table->addRow();
                foreach (['Q#','Question','Your Answer','Correct Answer','Marks'] as $col) {
                    $table->addCell(2000)->addText($col, ['bold'=>true]);
                }
                foreach ($items as $it) {
                    $table->addRow();
                    $table->addCell(800)->addText((string)$it['no']);
                    $table->addCell(7000)->addText(strip_tags((string)$it['title']));
                    $table->addCell(4000)->addText($it['your'] !== '' ? $it['your'] : '—');
                    $table->addCell(4000)->addText($it['correct'] !== '' ? $it['correct'] : '—');
                    $table->addCell(1800)->addText($it['awarded'].' / '.$it['mark']);
                }
    
                $tmp = tempnam(sys_get_temp_dir(), 'res_').'.docx';
                $phpWord->save($tmp, 'Word2007');
    
                return response()->download($tmp, $safeName.'.docx')->deleteFileAfterSend(true);
            }
    
            // Fallback: Word-compatible HTML (.doc)
            $html = view('exports.result_doc_fallback', [
                'row' => $row, 'user' => $user, 'items' => $items
            ])->render();
    
            return response($html, 200, [
                'Content-Type' => 'application/msword',
                'Content-Disposition' => 'attachment; filename="'.$safeName.'.doc"',
            ]);
        }
    
        // PDF: simple HTML fallback (users can “Save as PDF”)
        $html = view('exports.result_pdf_fallback', [
            'row' => $row, 'user' => $user, 'items' => $items
        ])->render();
    
        // If you use dompdf, replace the below with real PDF generation.
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="'.$safeName.'.html"',
        ]);
    }

    public function assignedResultsForQuiz(Request $request, string $quizKey)
    {
        // ---------- 1. Role via CheckRole ----------
        if ($resp = $this->requireRole($request, ['instructor','examiner','admin','super_admin'])) {
            return $resp; // 403 if not allowed
        }
    
        $actor = $this->actor($request);
        $role  = (string) ($actor['role'] ?? '');
        $userId= (int) ($actor['id'] ?? 0);
    
        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
    
        // ---------- 2. Resolve quiz (id | uuid) ----------
        $quiz = $this->quizByKey($quizKey);
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
            ], 404);
        }
    
        // ---------- 3. Ensure this quiz is assigned to this instructor/examiner ----------
        // Admin / super_admin can see all; instructors/examiners must be assigned via user_quiz_assignments
        $normalizedRole = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', $role));
    
        if (in_array($normalizedRole, ['instructor','examiner'], true)) {
            $assigned = DB::table('user_quiz_assignments')
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->exists();
    
            if (!$assigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this quiz',
                ], 403);
            }
        }
    
        // ---------- 4. Filters: search, pagination, sorting ----------
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $qText   = trim((string) $request->query('q', ''));
        $sort    = (string) $request->query('sort', '-result_created_at'); 
        // allowed: student_name, percentage, marks_obtained, result_created_at
    
        $dir = 'asc';
        $col = $sort;
        if (str_starts_with($sort, '-')) {
            $dir = 'desc';
            $col = ltrim($sort, '-');
        }
    
        $sortMap = [
            'student_name'      => 'u.name',
            'percentage'        => 'r.percentage',
            'marks_obtained'    => 'r.marks_obtained',
            'result_created_at' => 'r.created_at',
        ];
        if (!isset($sortMap[$col])) {
            $col = 'result_created_at';
            $dir = 'desc';
        }
        $orderByCol = $sortMap[$col];
    
        // ---------- 5. Base query: all results for this quiz ----------
        $q = DB::table('quizz_results as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
            ->where('r.quiz_id', $quiz->id)
            ->whereNull('u.deleted_at');
    
        if ($qText !== '') {
            $q->where(function ($w) use ($qText) {
                $w->where('u.name', 'like', "%{$qText}%")
                  ->orWhere('u.email', 'like', "%{$qText}%");
            });
        }
    
        $total = (clone $q)->count('r.id');
    
        $rows = $q->orderBy($orderByCol, $dir)
            ->orderBy('r.id', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->select([
                'r.id as result_id',
                'r.marks_obtained',
                'r.total_marks',
                'r.total_questions',
                'r.total_correct',
                'r.total_incorrect',
                'r.total_skipped',
                'r.percentage',
                'r.attempt_number',
                'r.publish_to_student',
                'r.created_at as result_created_at',
    
                'u.id   as student_id',
                'u.name as student_name',
                'u.email as student_email',
    
                'a.id   as attempt_id',
                'a.uuid as attempt_uuid',
                'a.status as attempt_status',
                'a.started_at',
                'a.finished_at',
                'a.total_time_sec',
            ])
            ->get();
    
        // ---------- 6. Optional: time_used_sec per attempt ----------
        $timeMap = [];
        if ($rows->count() > 0) {
            $timeMap = DB::table('quizz_attempt_answers')
                ->whereIn('attempt_id', $rows->pluck('attempt_id'))
                ->groupBy('attempt_id')
                ->select('attempt_id', DB::raw('SUM(time_spent_sec) as total_time'))
                ->pluck('total_time', 'attempt_id');
        }
    
        $attempts = $rows->map(function ($r) use ($timeMap) {
            $timeUsed = isset($timeMap[$r->attempt_id]) ? (int) $timeMap[$r->attempt_id] : 0;
    
            return [
                'result_id'        => (int) $r->result_id,
                'attempt_id'       => (int) $r->attempt_id,
                'attempt_uuid'     => (string) $r->attempt_uuid,
                'attempt_status'   => (string) $r->attempt_status,
    
                'student_id'       => (int) $r->student_id,
                'student_name'     => (string) $r->student_name,
                'student_email'    => (string) $r->student_email,
    
                'marks_obtained'   => (int) $r->marks_obtained,
                'total_marks'      => (int) $r->total_marks,
                'percentage'       => (float) $r->percentage,
                'total_questions'  => (int) $r->total_questions,
                'total_correct'    => (int) $r->total_correct,
                'total_incorrect'  => (int) $r->total_incorrect,
                'total_skipped'    => (int) $r->total_skipped,
                'attempt_number'   => (int) $r->attempt_number,
                'publish_to_student'=> (int) $r->publish_to_student,
    
                'started_at'       => $r->started_at
                                        ? Carbon::parse($r->started_at)->toDateTimeString()
                                        : null,
                'finished_at'      => $r->finished_at
                                        ? Carbon::parse($r->finished_at)->toDateTimeString()
                                        : null,
                'total_time_sec'   => (int) ($r->total_time_sec ?? 0),
                'time_used_sec'    => $timeUsed,
    
                'result_created_at'=> Carbon::parse($r->result_created_at)->toDateTimeString(),
            ];
        })->values();
    
        // ---------- 7. Aggregate stats for analysis (top card on UI) ----------
        $agg = DB::table('quizz_results')
            ->where('quiz_id', $quiz->id)
            ->selectRaw('
                COUNT(*)                           as total_attempts,
                COUNT(DISTINCT user_id)            as unique_students,
                MAX(percentage)                    as max_percentage,
                MIN(percentage)                    as min_percentage,
                AVG(percentage)                    as avg_percentage
            ')
            ->first();
    
        return response()->json([
            'success' => true,
            'quiz'    => [
                'id'            => (int) $quiz->id,
                'uuid'          => (string) $quiz->uuid,
                'name'          => (string) ($quiz->quiz_name ?? 'Quiz'),
                'description'   => (string) ($quiz->quiz_description ?? ''),
                'total_time'    => (int) ($quiz->total_time ?? 0), // minutes
            ],
            'stats'   => [
                'total_attempts'   => (int) ($agg->total_attempts ?? 0),
                'unique_students'  => (int) ($agg->unique_students ?? 0),
                'max_percentage'   => $agg->max_percentage !== null ? (float)$agg->max_percentage : null,
                'min_percentage'   => $agg->min_percentage !== null ? (float)$agg->min_percentage : null,
                'avg_percentage'   => $agg->avg_percentage !== null ? round((float)$agg->avg_percentage, 2) : null,
            ],
            'attempts' => $attempts,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ], 200);
    }
/**
 * GET /api/exam/group-wise-result
 * ?exam_key=bb7ca2fe-3e6d-494b-b62b-b4767e833470
 * &student_key=c11cff07-d0e7-41db-bad0-cdc43efe6acd
 * &attempt_number=1  (optional - filter by specific attempt)
 */
public function groupWiseResult(Request $request)
{
    // 1) Who is calling?
    $student         = $this->getUserFromToken($request);
    $isStudentCaller = $student && $this->isStudent($student);

    $actor   = $this->actor($request);
    $role    = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)($actor['role'] ?? '')));
    $actorId = (int)($actor['id'] ?? 0);

    // 2) Validate required params
    $examKey    = $request->query('exam_key');
    $studentKey = $request->query('student_key');

    if (!$examKey || !$studentKey) {
        return response()->json([
            'success' => false,
            'message' => 'exam_key and student_key are required'
        ], 422);
    }

    // 3) Resolve quiz from exam_key
    $quiz = DB::table('quizz')
        ->where('uuid', $examKey)
        ->first();

    if (!$quiz) {
        return response()->json(['success'=>false,'message'=>'Exam not found'], 404);
    }

    // 4) Resolve student from student_key
    $studentRow = DB::table('users')
        ->where('uuid', $studentKey)
        ->first();

    if (!$studentRow) {
        return response()->json(['success'=>false,'message'=>'Student not found'], 404);
    }

    // 5) Find ALL results for this student + quiz
    $resultsQuery = DB::table('quizz_results as r')
        ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
        ->join('quizz as qz', 'qz.id', '=', 'r.quiz_id')
        ->where('r.quiz_id', $quiz->id)
        ->where('r.user_id', $studentRow->id)
        ->orderBy('r.attempt_number', 'asc')
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.quiz_id',
            'r.attempt_id',
            'r.user_id',
            'r.marks_obtained',
            'r.total_marks',
            'r.percentage',
            'r.attempt_number',
            'r.publish_to_student',
            'r.created_at as result_created_at',

            'a.id as attempt_id',
            'a.uuid as attempt_uuid',
            'a.status as attempt_status',
            'a.started_at',
            'a.finished_at',

            'qz.uuid as quiz_uuid',
            'qz.quiz_name',
            'qz.total_time',
            'qz.result_set_up_type',
            'qz.result_release_date',
        ]);

    // optional: filter by attempt_number
    $attemptNumberFilter = $request->query('attempt_number');
    if ($attemptNumberFilter !== null) {
        $resultsQuery->where('r.attempt_number', (int)$attemptNumberFilter);
    }

    $results = $resultsQuery->get();

    if ($results->isEmpty()) {
        return response()->json(['success'=>false,'message'=>'No results found for this student'], 404);
    }

    // 6) Access rules (check against first result)
    $firstRow = $results->first();

    if ($isStudentCaller) {
        if ((int)$firstRow->user_id !== (int)$student->id) {
            return response()->json(['success'=>false,'message'=>'Forbidden'], 403);
        }
        $allowViewNow = $this->shouldPublishToStudent((int)$firstRow->quiz_id);
        if (!((int)$firstRow->publish_to_student === 1 || $allowViewNow)) {
            return response()->json([
                'success' => false,
                'message' => 'Result is not yet published for students'
            ], 403);
        }
    } else {
        if (in_array($role, ['instructor','examiner'], true)) {
            $assigned = DB::table('user_quiz_assignments')
                ->where('quiz_id', (int)$firstRow->quiz_id)
                ->where('user_id', $actorId)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->exists();

            if (!$assigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this quiz'
                ], 403);
            }
        }
    }

    // 7) Helpers
    $decodeSelected = function ($raw) {
        if ($raw === null) return null;
        $raw = (string)$raw;
        if (trim($raw) === '') return null;
        try { return json_decode($raw, true); } catch (\Throwable $e) { return null; }
    };

    $isAttempted = function ($qType, $selected) {
        $qType = (string)$qType;
        if ($qType === 'fill_in_the_blank') {
            if ($selected === null) return false;
            if (is_array($selected)) {
                foreach ($selected as $v) if (trim((string)$v) !== '') return true;
                return false;
            }
            return trim((string)$selected) !== '';
        }
        if ($selected === null) return false;
        if (is_array($selected)) {
            foreach ($selected as $v) {
                if (is_numeric($v) && (int)$v > 0) return true;
            }
            return false;
        }
        if (is_numeric($selected)) return (int)$selected > 0;
        return trim((string)$selected) !== '';
    };

    // 8) Build group-wise stats per attempt
    $allAttempts = [];

    foreach ($results as $row) {

        $qRows = DB::table('quizz_questions as q')
            ->leftJoin('quizz_attempt_answers as aa', function ($j) use ($row) {
                $j->on('aa.question_id', '=', 'q.id')
                  ->where('aa.attempt_id', '=', (int)$row->attempt_id);
            })
            ->where('q.quiz_id', (int)$row->quiz_id)
            ->orderBy('q.question_order')
            ->select([
                'q.id as question_id',
                'q.question_order',
                'q.question_type',
                'q.question_mark',
                'q.group_title',
                'aa.selected_raw',
                'aa.is_correct',
                'aa.awarded_mark',
            ])
            ->get();

        $groups  = [];
        $overall = [
            'total_questions' => 0,
            'attempted'       => 0,
            'left'            => 0,
            'correct'         => 0,
            'incorrect'       => 0,
            'marks_obtained'  => 0,
            'total_marks'     => 0,
            'percentage'      => 0.0,
        ];

        foreach ($qRows as $r) {
            $group = trim((string)($r->group_title ?? ''));
            if ($group === '') $group = 'Ungrouped';

            if (!isset($groups[$group])) {
                $groups[$group] = [
                    'group_title'     => $group,
                    'total_questions' => 0,
                    'attempted'       => 0,
                    'left'            => 0,
                    'correct'         => 0,
                    'incorrect'       => 0,
                    'marks_obtained'  => 0,
                    'total_marks'     => 0,
                    'percentage'      => 0.0,
                ];
            }

            $mark      = (int)($r->question_mark ?? 0);
            $sel       = $decodeSelected($r->selected_raw);
            $attempted = $isAttempted($r->question_type, $sel);
            $isCorrect = (int)($r->is_correct ?? 0) === 1;
            $awarded   = $r->awarded_mark !== null ? (int)$r->awarded_mark : ($isCorrect ? $mark : 0);

            $groups[$group]['total_questions']++;
            $groups[$group]['total_marks'] += $mark;
            $overall['total_questions']++;
            $overall['total_marks'] += $mark;

            if ($attempted) {
                $groups[$group]['attempted']++;
                $overall['attempted']++;
                if ($isCorrect) {
                    $groups[$group]['correct']++;
                    $overall['correct']++;
                } else {
                    $groups[$group]['incorrect']++;
                    $overall['incorrect']++;
                }
            }

            $groups[$group]['marks_obtained'] += $awarded;
            $overall['marks_obtained']        += $awarded;
        }

        foreach ($groups as $k => $g) {
            $groups[$k]['left'] = max(0, (int)$g['total_questions'] - (int)$g['attempted']);
            $groups[$k]['percentage'] = $g['total_marks'] > 0
                ? round($g['marks_obtained'] / max(1, $g['total_marks']) * 100, 2)
                : 0.0;
        }

        $overall['left'] = max(0, (int)$overall['total_questions'] - (int)$overall['attempted']);
        $overall['percentage'] = $overall['total_marks'] > 0
            ? round($overall['marks_obtained'] / max(1, $overall['total_marks']) * 100, 2)
            : 0.0;

        $allAttempts[] = [
            'result' => [
                'id'                 => (int)$row->result_id,
                'uuid'               => (string)$row->result_uuid,
                'marks_obtained'     => (int)$row->marks_obtained,
                'total_marks'        => (int)$row->total_marks,
                'percentage'         => (float)($row->percentage ?? 0),
                'attempt_number'     => (int)($row->attempt_number ?? 0),
                'publish_to_student' => (int)($row->publish_to_student ?? 0),
            ],
            'attempt' => [
                'id'         => (int)$row->attempt_id,
                'uuid'       => (string)$row->attempt_uuid,
                'status'     => (string)$row->attempt_status,
                'started_at' => $row->started_at,
                'finished_at'=> $row->finished_at,
            ],
            'overall' => $overall,
            'groups'  => array_values($groups),
        ];
    }

    return response()->json([
        'success' => true,
        'quiz' => [
            'id'   => (int)$firstRow->quiz_id,
            'uuid' => (string)$firstRow->quiz_uuid,
            'name' => (string)($firstRow->quiz_name ?? 'Quiz'),
        ],
        'student' => [
            'id'   => (int)$studentRow->id,
            'uuid' => (string)$studentRow->uuid,
            'name' => (string)($studentRow->name ?? ''),
        ],
        'total_attempts' => count($allAttempts),
        'attempts'       => $allAttempts,
    ], 200);
}
}
