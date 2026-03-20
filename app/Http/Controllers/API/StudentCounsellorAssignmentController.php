<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentCounsellorAssignmentController extends Controller
{
    /**
     * POST /api/counsellors/{counsellorId}/students/{studentId}/assign
     */
    public function assign(Request $request, string $counsellorId, string $studentId)
{
    $actorId = (int) ($request->attributes->get('auth_tokenable_id') ?? 0);

    if (!$actorId) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    // ── Resolve student: support both UUID and numeric ID ──
    $student = DB::table('users')
        ->whereNull('deleted_at')
        ->where(function($q) use ($studentId) {
            $q->where('uuid', $studentId)
              ->orWhere('id', is_numeric($studentId) ? (int)$studentId : -1);
        })
        ->first();

    if (!$student) {
        return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
    }

    // ── Resolve counsellor: support both UUID and numeric ID ──
    $counsellor = DB::table('users')
        ->whereNull('deleted_at')
        ->where(function($q) use ($counsellorId) {
            $q->where('uuid', $counsellorId)
              ->orWhere('id', is_numeric($counsellorId) ? (int)$counsellorId : -1);
        })
        ->first();

    if (!$counsellor) {
        return response()->json(['status' => 'error', 'message' => 'Counsellor not found'], 404);
    }

    // ── Use resolved numeric IDs from here on ──
    $studentNumericId    = (int) $student->id;
    $counsellorNumericId = (int) $counsellor->id;

    if ($studentNumericId === $counsellorNumericId) {
        return response()->json(['status' => 'error', 'message' => 'Student and counsellor cannot be same user'], 422);
    }

    if (($student->status ?? 'active') !== 'active') {
        return response()->json(['status' => 'error', 'message' => 'Student is not active'], 403);
    }
    if (($student->role ?? '') !== 'student') {
        return response()->json(['status' => 'error', 'message' => 'Selected user is not a student'], 422);
    }

    if (($counsellor->status ?? 'active') !== 'active') {
        return response()->json(['status' => 'error', 'message' => 'Counsellor is not active'], 403);
    }
    if (($counsellor->role ?? '') !== 'academic_counsellor') {
        return response()->json(['status' => 'error', 'message' => 'Selected user is not an academic counsellor'], 422);
    }

    $already = DB::table('student_counsellor_assignments')
        ->where('student_id', $studentNumericId)
        ->whereNull('deleted_at')
        ->first();

    if ($already) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Student is already assigned to a counsellor and cannot be reassigned or unassigned.',
            'data'    => [
                'assignment_uuid' => (string) ($already->uuid ?? ''),
                'counsellor_id'   => (int) ($already->counsellor_id ?? 0),
            ],
        ], 409);
    }

    try {
        DB::beginTransaction();

        DB::table('users')->where('id', $studentNumericId)->lockForUpdate()->first();

        $again = DB::table('student_counsellor_assignments')
            ->where('student_id', $studentNumericId)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if ($again) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Student is already assigned to a counsellor.'], 409);
        }

        $uuid = (string) Str::uuid();

        DB::table('student_counsellor_assignments')->insert([
            'uuid'              => $uuid,
            'student_id'        => $studentNumericId,
            'counsellor_id'     => $counsellorNumericId,
            'assignment_status' => 'assigned',
            'assigned_at'       => now(),
            'ended_at'          => null,
            'created_by'        => $actorId,
            'created_at'        => now(),
            'created_at_ip'     => $request->ip(),
            'updated_at'        => now(),
            'metadata'          => json_encode(['source' => 'student_counsellor_assign_api'], JSON_UNESCAPED_UNICODE),
        ]);

        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => 'Student assigned to counsellor successfully (one-time assignment).',
            'data'    => [
                'assignment_uuid' => $uuid,
                'student'         => ['id' => $studentNumericId,    'uuid' => (string)($student->uuid ?? ''),    'name' => (string)($student->name ?? ''),    'email' => (string)($student->email ?? '')],
                'counsellor'      => ['id' => $counsellorNumericId, 'uuid' => (string)($counsellor->uuid ?? ''), 'name' => (string)($counsellor->name ?? ''), 'email' => (string)($counsellor->email ?? '')],
            ],
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('[Assign Student Counsellor] failed', [
            'student_id'    => $studentNumericId,
            'counsellor_id' => $counsellorNumericId,
            'error'         => $e->getMessage(),
        ]);

        $msg = strtolower($e->getMessage());
        if (str_contains($msg, 'uniq_student_one_active') || str_contains($msg, 'duplicate')) {
            return response()->json(['status' => 'error', 'message' => 'Student is already assigned to a counsellor.'], 409);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to assign student to counsellor.'], 500);
    }
}
    /**
     * GET /api/my-assignments
     *
     * For a counsellor actor  → returns their assigned students + each student's counsellor info
     * For a student actor     → returns their assigned counsellor + counsellor's full student list
     *
     * Query params:
     *   ?role=counsellor  (force counsellor view)
     *   ?role=student     (force student view)
     *   — if omitted, role is resolved from the auth token's user record
     */
    public function myAssignments(Request $request)
    {
        $actorId = (int) ($request->attributes->get('auth_tokenable_id') ?? 0);

        if (!$actorId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $actor = DB::table('users')->where('id', $actorId)->whereNull('deleted_at')->first();

        if (!$actor) {
            return response()->json(['status' => 'error', 'message' => 'Actor not found'], 404);
        }

        $role = $request->query('role', $actor->role ?? '');

        // ── COUNSELLOR VIEW ──────────────────────────────────────────────────────
        if ($role === 'academic_counsellor') {

            $students = DB::table('student_counsellor_assignments as a')
                ->join('users as s', 's.id', '=', 'a.student_id')
                ->where('a.counsellor_id', $actorId)
                ->whereNull('a.deleted_at')
                ->whereNull('s.deleted_at')
                ->select([
                    'a.uuid as assignment_uuid',
                    'a.assigned_at',
                    'a.assignment_status',
                    's.id as student_id',
                    's.uuid as student_uuid',
                    's.name as student_name',
                    's.email as student_email',
                    's.phone_number as student_phone',
                    's.status as student_status',
                ])
                ->orderBy('s.name')
                ->get();

            return response()->json([
                'status' => 'success',
                'view'   => 'counsellor',
                'data'   => [
                    'counsellor' => [
                        'id'    => (int) $actor->id,
                        'uuid'  => $actor->uuid ?? null,
                        'name'  => $actor->name ?? '',
                        'email' => $actor->email ?? '',
                    ],
                    'my_students' => $students,
                ],
                'meta' => ['count' => $students->count()],
            ]);
        }

        // ── STUDENT VIEW ─────────────────────────────────────────────────────────
        if ($role === 'student') {

            $assignment = DB::table('student_counsellor_assignments as a')
                ->join('users as c', 'c.id', '=', 'a.counsellor_id')
                ->where('a.student_id', $actorId)
                ->whereNull('a.deleted_at')
                ->select([
                    'a.uuid as assignment_uuid',
                    'a.assigned_at',
                    'a.assignment_status',
                    'c.id as counsellor_id',
                    'c.uuid as counsellor_uuid',
                    'c.name as counsellor_name',
                    'c.email as counsellor_email',
                ])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'status'  => 'success',
                    'view'    => 'student',
                    'message' => 'No counsellor assigned yet.',
                    'data'    => [
                        'my_counsellor'  => null,
                        'fellow_students' => [],
                    ],
                    'meta' => ['count' => 0],
                ]);
            }

            // Bonus: also return fellow students under the same counsellor
            $fellowStudents = DB::table('student_counsellor_assignments as a')
                ->join('users as s', 's.id', '=', 'a.student_id')
                ->where('a.counsellor_id', $assignment->counsellor_id)
                ->where('a.student_id', '!=', $actorId)  // exclude self
                ->whereNull('a.deleted_at')
                ->whereNull('s.deleted_at')
                ->select([
                    's.id as student_id',
                    's.uuid as student_uuid',
                    's.name as student_name',
                    's.email as student_email',
                    's.status as student_status',
                ])
                ->orderBy('s.name')
                ->get();

            return response()->json([
                'status' => 'success',
                'view'   => 'student',
                'data'   => [
                    'my_counsellor'   => $assignment,
                    'fellow_students' => $fellowStudents,
                ],
                'meta' => ['count' => $fellowStudents->count()],
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Role not eligible to view assignments.',
        ], 403);
    }
}