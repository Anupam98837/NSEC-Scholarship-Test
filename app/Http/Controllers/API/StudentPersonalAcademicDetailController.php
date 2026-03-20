<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StudentPersonalAcademicDetailController extends Controller
{
    private string $table = 'student_personal_academic_details';

    private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    /**
     * GET: /api/student/personal-academic-details
     * Returns logged-in student's record (or null if not created yet).
     */
    public function show(Request $request)
{
    $actor = $this->actor($request);

    if (!$actor['id']) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    // Admins can pass ?student_id=XX to view any student's details
    // Students can only view their own
    $targetId = $actor['id'];

    if ($request->has('student_id')) {
        if ($actor['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only admins can view other students.',
            ], 403);
        }
        $targetId = (int) $request->query('student_id');
    }

    $detail = DB::table($this->table)
        ->where('user_id', $targetId)
        ->first();

    return response()->json([
        'success' => true,
        'data'    => $detail,
    ]);
}
    /**
     * POST: /api/student/personal-academic-details
     * Create or update (upsert) logged-in student's personal academic details.
     */
    public function upsert(Request $request)
    {
        $actor = $this->actor($request);

        if (!$actor['id']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'guardian_name'   => ['nullable', 'string', 'max:255'],
            'guardian_number' => ['nullable', 'string', 'max:20'],
            'class'           => ['nullable', 'string', 'max:80'],
            'board'           => [
                'nullable',
                'string',
                'max:60',
                Rule::in(['CBSC', 'ISC', 'WBHSC', 'Bihar', 'Jharkhand', 'Open University', 'Others']),
            ],
            'exam_type'       => ['nullable', 'string', 'max:30'],
            'year_of_passout' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $now = now();

        $payload = [
            'guardian_name'   => $request->input('guardian_name'),
            'guardian_number' => $request->input('guardian_number'),
            'class'           => $request->input('class'),
            'board'           => $request->input('board'),
            'exam_type'       => $request->input('exam_type'),
            'year_of_passout' => $request->input('year_of_passout'),
            'updated_at'      => $now,
        ];

        $exists = DB::table($this->table)
            ->where('user_id', $actor['id'])
            ->exists();

        if ($exists) {
            DB::table($this->table)
                ->where('user_id', $actor['id'])
                ->update($payload);
        } else {
            $payload['user_id']    = $actor['id'];
            $payload['created_at'] = $now;
            DB::table($this->table)->insert($payload);
        }

        $detail = DB::table($this->table)
            ->where('user_id', $actor['id'])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Personal academic details saved',
            'data'    => $detail,
        ]);
    }

    /**
     * DELETE: /api/student/personal-academic-details
     * Delete the logged-in student's record.
     */
    public function destroy(Request $request)
    {
        $actor = $this->actor($request);

        if (!$actor['id']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $deleted = DB::table($this->table)
            ->where('user_id', $actor['id'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted
                ? 'Personal academic details deleted.'
                : 'No record found to delete.',
        ]);
    }
      public function showStudentDetails(Request $request)
{
    $uuid = trim((string) $request->query('uuid', ''));

    if (!$uuid) {
        abort(404, 'No student UUID provided.');
    }

    // ── Fetch student by UUID ──
    $student = DB::table('users')
        ->where('uuid', $uuid)
        ->where('role', 'student')
        ->whereNull('deleted_at')
        ->first();

    if (!$student) {
        abort(404, 'Student not found.');
    }

    // ── Fetch personal/academic details + join users (return user data too) ──
    // StudentPersonalAcademicDetailController uses 'user_id' as the FK
    $pad = DB::table('student_personal_academic_details as p')
        ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
        ->where('p.user_id', $student->id)
        ->select([
            'p.*',

            // user fields (aliased so they don't clash with p.* columns)
            'u.id    as user_id',
            'u.uuid  as user_uuid',
            'u.name  as user_name',
            'u.email as user_email',
            'u.role  as user_role',
        ])
        ->first();

    // ── Fetch counsellor assignment ──
    $assignment = DB::table('student_counsellor_assignments as a')
        ->join('users as c', 'c.id', '=', 'a.counsellor_id')
        ->where('a.student_id', $student->id)
        ->whereNull('a.deleted_at')
        ->select([
            'a.uuid           as assignment_uuid',
            'a.assigned_at',
            'a.assignment_status',
            'c.id             as counsellor_id',
            'c.uuid           as counsellor_uuid',
            'c.name           as counsellor_name',
            'c.email          as counsellor_email',
        ])
        ->first();

    // ── Exam results — empty for now, extend when table exists ──
    $examResults = [];

    return response()->json([
    'student'     => $student,
    'pad'         => $pad,
    'assignment'  => $assignment,
    'examResults' => $examResults,
    'editUrl'     => '#',
    'reportUrl'   => '#',
]);
}
}