<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Str;
use Carbon\Carbon;

class UserController extends Controller
{
    /** FQCN stored in personal_access_tokens.tokenable_type */
    private const USER_TYPE = 'App\\Models\\User';

    /** Canonical roles for Unzip Exam */
    private const ROLES = ['super_admin','admin','examiner','student','author',
    'college_administrator',
    'academic_counsellor',];

    /** Short codes for roles */
    private const ROLE_SHORT = [
        'super_admin' => 'SA',
        'admin'       => 'ADM',
        'examiner'    => 'EXM',
        'student'     => 'STD',
        'author'                => 'AUT',
    'college_administrator' => 'CADM',
    'academic_counsellor'   => 'ACC',
    ];

    /* =========================================================
     |                       AUTH
     |=========================================================*/
/**
 * Reusable activity logger
 * Maps auth events to the current user_data_activity_log schema.
 */
private function logActivity(
    string $activity,
    string $title,
    string $description,
    ?int $performedBy = null,
    ?string $performedByName = null,
    $targetId = null,
    ?string $targetType = 'user',
    array $properties = [],
    ?Request $request = null,
    string $module = 'users'
): void {
    try {
        // Your table has performed_by as NOT NULL, so keep a safe fallback.
        $actorId = $performedBy ?: (is_numeric($targetId) ? (int) $targetId : 0);
        $recordId = is_numeric($targetId) ? (int) $targetId : ($performedBy ?: null);

        $noteParts = array_filter([
            trim($title),
            trim($description),
            $performedByName ? ('Actor: ' . $performedByName) : null,
        ]);
        $logNote = implode(' — ', $noteParts);

        $changedFields = !empty($properties) ? array_values(array_map('strval', array_keys($properties))) : null;
        $newValues = !empty($properties) ? $properties : null;

        DB::table('user_data_activity_log')->insert([
            'performed_by'      => $actorId,
            'performed_by_role' => isset($properties['role']) && $properties['role'] !== null
                ? (string) $properties['role']
                : null,
            'ip'                => $request?->ip(),
            'user_agent'        => $request?->userAgent(),
            'activity'          => $activity,
            'module'            => $module,
            'table_name'        => $targetType === 'user' ? 'users' : (string) ($targetType ?: 'users'),
            'record_id'         => $recordId,
            'changed_fields'    => $changedFields ? json_encode($changedFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'old_values'        => null,
            'new_values'        => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'log_note'          => $logNote ?: null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    } catch (\Throwable $e) {
        Log::error('[Activity Log] failed', [
            'activity'     => $activity,
            'performed_by' => $performedBy,
            'target_id'    => $targetId,
            'error'        => $e->getMessage(),
        ]);
    }
}
/**
 * POST /api/auth/login
 * Body: { login, password, remember?: bool }
 * 'login' can be email or phone number
 * Returns: { access_token, token_type, expires_at?, user: {...} }
 */
public function login(Request $request)
{
    Log::info('[UnzipExam Auth Login] begin', ['ip' => $request->ip()]);

    $validated = $request->validate([
        'login'    => 'required|string',   // email or phone number
        'password' => 'required|string',
        'remember' => 'sometimes|boolean',
    ]);

    $loginInput = $validated['login'];

    // Determine if input is email or phone number
    $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

    $user = DB::table('users')
        ->when($isEmail, function ($query) use ($loginInput) {
            $query->where('email', $loginInput);
        }, function ($query) use ($loginInput) {
            $query->where('phone_number', $loginInput);
        })
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        Log::warning('[UnzipExam Auth Login] user not found', ['login' => $loginInput]);

        $this->logActivity(
            activity: 'login_failed',
            title: 'Login failed - user not found',
            description: 'Login attempt failed because no user was found for the provided email or phone number.',
            performedBy: 0,
            performedByName: null,
            targetId: null,
            targetType: 'user',
            properties: [
                'login'  => $loginInput,
                'reason' => 'user_not_found',
            ],
            request: $request,
            module: 'users'
        );

        return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
    }

    if (isset($user->status) && $user->status !== 'active') {
        Log::warning('[UnzipExam Auth Login] inactive user', [
            'user_id' => $user->id,
            'status'  => $user->status
        ]);

        $this->logActivity(
            activity: 'login_blocked',
            title: 'Login blocked - inactive account',
            description: 'Login attempt blocked because the account is not active.',
            performedBy: (int) $user->id,
            performedByName: $user->name ?? null,
            targetId: $user->id,
            targetType: 'user',
            properties: [
                'login'  => $loginInput,
                'status' => $user->status,
                'reason' => 'inactive_account',
                'role'   => $user->role ?? null,
            ],
            request: $request,
            module: 'users'
        );

        return response()->json(['status' => 'error', 'message' => 'Account is not active'], 403);
    }

    if (!Hash::check($validated['password'], $user->password)) {
        Log::warning('[UnzipExam Auth Login] password mismatch', ['user_id' => $user->id]);

        $this->logActivity(
            activity: 'login_failed',
            title: 'Login failed - password mismatch',
            description: 'Login attempt failed because the password did not match.',
            performedBy: (int) $user->id,
            performedByName: $user->name ?? null,
            targetId: $user->id,
            targetType: 'user',
            properties: [
                'login'  => $loginInput,
                'reason' => 'password_mismatch',
                'role'   => $user->role ?? null,
            ],
            request: $request,
            module: 'users'
        );

        return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
    }

    $remember  = (bool) ($validated['remember'] ?? false);
    $expiresAt = $remember ? now()->addDays(30) : now()->addHours(12);

    $plainToken = $this->issueToken((int) $user->id, $expiresAt);

    DB::table('users')->where('id', $user->id)->update([
        'last_login_at' => now(),
        'last_login_ip' => $request->ip(),
        'updated_at'    => now(),
    ]);

    $payloadUser = $this->publicUserPayload($user);

    $this->logActivity(
        activity: 'login',
        title: 'User login successful',
        description: 'User logged in successfully.',
        performedBy: (int) $user->id,
        performedByName: $user->name ?? null,
        targetId: $user->id,
        targetType: 'user',
        properties: [
            'login'      => $loginInput,
            'remember'   => $remember,
            'expires_at' => $expiresAt->toIso8601String(),
            'role'       => $payloadUser['role'] ?? ($user->role ?? null),
        ],
        request: $request,
        module: 'users'
    );

    Log::info('[UnzipExam Auth Login] success', [
        'user_id' => $user->id,
        'role'    => $payloadUser['role'] ?? null
    ]);

    return response()->json([
        'status'       => 'success',
        'message'      => 'Login successful',
        'access_token' => $plainToken,
        'token_type'   => 'Bearer',
        'expires_at'   => $expiresAt->toIso8601String(),
        'user'         => $payloadUser,
    ]);
}
/**
 * POST /api/auth/student-register
 * Body:
 * {
 *   "user_folder_id": 5,
 *   "name": "Student Name",
 *   "email": "student@gmail.com",
 *   "phone_number": "9876543210",
 *   "password": "Student@123",
 *   "password_confirmation": "Student@123"
 * }
 *
 * ✅ Group = Folder (user_folder_id)
 * ✅ Registers STUDENT only
 */
public function studentRegister(Request $request)
{
    Log::info('[Student Register] begin', ['ip' => $request->ip()]);

    if ($request->has('user_folder_id')) {
        $raw = $request->input('user_folder_id');

        if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
            $request->merge(['user_folder_id' => null]);
        } else {
            $request->merge(['user_folder_id' => (int) $raw]);
        }
    }

    $v = Validator::make($request->all(), [
        'user_folder_id' => [
            'required',
            'integer',
            Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
        ],
        'name'         => 'required|string|max:255',
        'email'        => 'required|email|max:255',
        'phone_number' => 'required|string|max:32',
        'password'     => 'required|string|min:8|confirmed',
    ]);

    if ($v->fails()) {
        $this->logActivity(
            activity: 'store_failed',
            title: 'Student registration failed - validation error',
            description: 'Student registration failed due to validation errors.',
            performedBy: 0,
            performedByName: null,
            targetId: null,
            targetType: 'user',
            properties: [
                'email'        => $request->input('email'),
                'phone_number' => $request->input('phone_number'),
                'errors'       => $v->errors()->toArray(),
                'reason'       => 'validation_error',
                'role'         => 'student',
            ],
            request: $request,
            module: 'users'
        );

        return response()->json([
            'status' => 'error',
            'errors' => $v->errors(),
        ], 422);
    }

    $data = $v->validated();

    if (DB::table('users')->where('email', $data['email'])->whereNull('deleted_at')->exists()) {
        $this->logActivity(
            activity: 'store_failed',
            title: 'Student registration failed - duplicate email',
            description: 'Student registration failed because the email already exists.',
            performedBy: 0,
            performedByName: null,
            targetId: null,
            targetType: 'user',
            properties: [
                'email'  => $data['email'],
                'reason' => 'duplicate_email',
                'role'   => 'student',
            ],
            request: $request,
            module: 'users'
        );

        return response()->json([
            'status'  => 'error',
            'message' => 'Email already exists',
        ], 422);
    }

    if (DB::table('users')->where('phone_number', $data['phone_number'])->whereNull('deleted_at')->exists()) {
        $this->logActivity(
            activity: 'store_failed',
            title: 'Student registration failed - duplicate phone number',
            description: 'Student registration failed because the phone number already exists.',
            performedBy: 0,
            performedByName: null,
            targetId: null,
            targetType: 'user',
            properties: [
                'phone_number' => $data['phone_number'],
                'reason'       => 'duplicate_phone_number',
                'role'         => 'student',
            ],
            request: $request,
            module: 'users'
        );

        return response()->json([
            'status'  => 'error',
            'message' => 'Phone number already exists',
        ], 422);
    }

    do {
        $uuid = (string) Str::uuid();
    } while (DB::table('users')->where('uuid', $uuid)->exists());

    $name = trim($data['name']);

    $base = Str::slug($name ?: 'student');
    do {
        $slug = $base . '-' . Str::lower(Str::random(24));
    } while (DB::table('users')->where('slug', $slug)->exists());

    [$role, $roleShort] = $this->normalizeRole('student', null);

    $now = now();

    try {
        DB::table('users')->insert([
            'uuid'            => $uuid,
            'name'            => $name,
            'email'           => $data['email'],
            'phone_number'    => $data['phone_number'],
            'password'        => Hash::make($data['password']),
            'user_folder_id'  => (int) $data['user_folder_id'],
            'role'            => $role,
            'role_short_form' => $roleShort,
            'slug'            => $slug,
            'status'          => 'active',
            'remember_token'  => Str::random(60),
            'created_by'      => null,
            'created_at'      => $now,
            'created_at_ip'   => $request->ip(),
            'updated_at'      => $now,
            'metadata'        => json_encode([
                'timezone' => 'Asia/Kolkata',
                'source'   => 'student_register_api',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $user = DB::table('users')->where('email', $data['email'])->first();

        $expiresAt  = now()->addDays(30);
        $plainToken = $this->issueToken((int) $user->id, $expiresAt);

        $this->logActivity(
            activity: 'store',
            title: 'Student registration successful',
            description: 'A new student account was registered successfully.',
            performedBy: (int) $user->id,
            performedByName: $user->name ?? null,
            targetId: $user->id,
            targetType: 'user',
            properties: [
                'name'           => $name,
                'email'          => $data['email'],
                'phone_number'   => $data['phone_number'],
                'user_folder_id' => (int) $data['user_folder_id'],
                'role'           => $role,
                'expires_at'     => $expiresAt->toIso8601String(),
            ],
            request: $request,
            module: 'users'
        );

        // Optional auto-login log after register
        $this->logActivity(
            activity: 'login',
            title: 'User login successful after registration',
            description: 'Student account auto-logged in after successful registration.',
            performedBy: (int) $user->id,
            performedByName: $user->name ?? null,
            targetId: $user->id,
            targetType: 'user',
            properties: [
                'email'      => $data['email'],
                'expires_at' => $expiresAt->toIso8601String(),
                'role'       => $role,
                'source'     => 'student_register_auto_login',
            ],
            request: $request,
            module: 'users'
        );

        return response()->json([
            'status'       => 'success',
            'message'      => 'Student registered successfully',
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $expiresAt->toIso8601String(),
            'user'         => $this->publicUserPayload($user),
        ], 201);

    } catch (\Throwable $e) {
        Log::error('[Student Register] failed', ['error' => $e->getMessage()]);

        $this->logActivity(
            activity: 'store_failed',
            title: 'Student registration failed - server error',
            description: 'Student registration failed due to an internal server error.',
            performedBy: 0,
            performedByName: null,
            targetId: null,
            targetType: 'user',
            properties: [
                'email'        => $data['email'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'reason'       => 'server_error',
                'error'        => $e->getMessage(),
                'role'         => 'student',
            ],
            request: $request,
            module: 'users'
        );

        return response()->json([
            'status'  => 'error',
            'message' => 'Student registration failed',
        ], 500);
    }
}

/**
 * POST /api/auth/logout
 * Header: Authorization: Bearer <token>
 */
public function logout(Request $request)
{
    Log::info('[UnzipExam Auth Logout] begin', ['ip' => $request->ip()]);

    $plain = $this->extractToken($request);

    if (!$plain) {
        Log::warning('[UnzipExam Auth Logout] missing token');

        $this->logActivity(
            activity: 'logout_failed',
            title: 'Logout failed - token missing',
            description: 'Logout attempt failed because no token was provided.',
            performedBy: 0,
            performedByName: null,
            targetId: null,
            targetType: 'user',
            properties: [
                'reason' => 'token_missing',
            ],
            request: $request,
            module: 'users'
        );

        return response()->json([
            'status'  => 'error',
            'message' => 'Token not provided'
        ], 401);
    }

    $hashedToken = hash('sha256', $plain);

    $tokenRow = DB::table('personal_access_tokens')
        ->where('token', $hashedToken)
        ->where('tokenable_type', self::USER_TYPE)
        ->first();

    $user = null;
    if ($tokenRow && !empty($tokenRow->tokenable_id)) {
        $user = DB::table('users')->where('id', $tokenRow->tokenable_id)->first();
    }

    $deleted = DB::table('personal_access_tokens')
        ->where('token', $hashedToken)
        ->where('tokenable_type', self::USER_TYPE)
        ->delete();

    if ($deleted) {
        $this->logActivity(
            activity: 'logout',
            title: 'User logout successful',
            description: 'User logged out successfully.',
            performedBy: $user?->id ? (int) $user->id : 0,
            performedByName: $user?->name ?? null,
            targetId: $user?->id ?? null,
            targetType: 'user',
            properties: [
                'email'  => $user?->email ?? null,
                'reason' => 'token_revoked',
                'role'   => $user?->role ?? null,
            ],
            request: $request,
            module: 'users'
        );
    } else {
        $this->logActivity(
            activity: 'logout_failed',
            title: 'Logout failed - invalid token',
            description: 'Logout attempt failed because the token was invalid or already removed.',
            performedBy: $user?->id ? (int) $user->id : 0,
            performedByName: $user?->name ?? null,
            targetId: $user?->id ?? null,
            targetType: 'user',
            properties: [
                'email'  => $user?->email ?? null,
                'reason' => 'invalid_token',
                'role'   => $user?->role ?? null,
            ],
            request: $request,
            module: 'users'
        );
    }

    Log::info('[UnzipExam Auth Logout] token removed', ['deleted' => (bool) $deleted]);

    return response()->json([
        'status'  => $deleted ? 'success' : 'error',
        'message' => $deleted ? 'Logged out successfully' : 'Invalid token',
    ], $deleted ? 200 : 401);
}
  /**
     * GET /api/auth/my-role
     * Header: Authorization: Bearer <token>
     *
     * Returns:
     * {
     *   "status": "success",
     *   "role": "admin",
     *   "role_short_form": "ADM",
     *   "user": { ... public payload ... }
     * }
     */
    public function getMyRole(Request $request)
    {
        $plain = $this->extractToken($request);
        if (!$plain) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token not provided',
            ], 401);
        }

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (!$rec) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid token',
            ], 401);
        }

        // Check expiry (same logic as authenticateToken)
        if (!empty($rec->expires_at) && Carbon::parse($rec->expires_at)->isPast()) {
            DB::table('personal_access_tokens')->where('id', $rec->id)->delete();

            return response()->json([
                'status'  => 'error',
                'message' => 'Token expired',
            ], 401);
        }

        $user = DB::table('users')
            ->where('id', $rec->tokenable_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || (isset($user->status) && $user->status !== 'active')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'status'          => 'success',
            'role'            => (string)($user->role ?? ''),
            'role_short_form' => (string)($user->role_short_form ?? ''),
            'user'            => $this->publicUserPayload($user),
        ]);
    }


    /**
     * GET /api/auth/check
     * Header: Authorization: Bearer <token>
     * Returns user if token valid (and not expired).
     */
    public function authenticateToken(Request $request)
    {
        $plain = $this->extractToken($request);
        if (!$plain) {
            return response()->json(['status'=>'error','message'=>'Token not provided'], 401);
        }

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (!$rec) {
            return response()->json(['status'=>'error','message'=>'Invalid token'], 401);
        }

        // Expiration check (if set)
        if (!empty($rec->expires_at) && Carbon::parse($rec->expires_at)->isPast()) {
            DB::table('personal_access_tokens')->where('id', $rec->id)->delete();
            return response()->json(['status'=>'error','message'=>'Token expired'], 401);
        }

        $user = DB::table('users')
            ->where('id', $rec->tokenable_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || (isset($user->status) && $user->status !== 'active')) {
            return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
        }

        return response()->json([
            'status' => 'success',
            'user'   => $this->publicUserPayload($user),
        ]);
    }

    /* =========================================================
     |                       USERS CRUD
     |=========================================================*/
/**
 * POST /api/users
 * Create user (with optional image). Stores image in /Public/UserProfileImage.
 */
public function store(Request $request)
{
    // ✅ Normalize folder id coming from FormData/JSON
    if ($request->has('user_folder_id')) {
        $raw = $request->input('user_folder_id');

        if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
            $request->merge(['user_folder_id' => null]);
        } else {
            $request->merge(['user_folder_id' => (int)$raw]);
        }
    }

    $v = Validator::make($request->all(), [
        'name'                     => 'required|string|max:150',
        'email'                    => 'required|email|max:255',
        'password'                 => 'required|string|min:8',
        'phone_number'             => 'sometimes|nullable|string|max:32',
        'alternative_email'        => 'sometimes|nullable|email|max:255',
        'alternative_phone_number' => 'sometimes|nullable|string|max:32',
        'whatsapp_number'          => 'sometimes|nullable|string|max:32',
        'address'                  => 'sometimes|nullable|string',
        'role'                     => 'sometimes|nullable|string|max:50',
        'role_short_form'          => 'sometimes|nullable|string|max:10',
        'status'                   => 'sometimes|in:active,inactive',
        'image'                    => 'sometimes|file|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',

        // ✅ Folder assignment: exists + not deleted
        'user_folder_id' => [
            'sometimes',
            'nullable',
            'integer',
            Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
        ],
    ]);

    if ($v->fails()) {
        return response()->json(['status'=>'error','errors'=>$v->errors()], 422);
    }

    $data = $v->validated();

    // Uniqueness pre-checks
    if (DB::table('users')->where('email', $data['email'])->exists()) {
        return response()->json(['status'=>'error','message'=>'Email already exists'], 422);
    }

    if (!empty($data['phone_number']) &&
        DB::table('users')->where('phone_number', $data['phone_number'])->exists()) {
        return response()->json(['status'=>'error','message'=>'Phone number already exists'], 422);
    }

    // UUID & unique slug
    do { $uuid = (string) Str::uuid(); }
    while (DB::table('users')->where('uuid', $uuid)->exists());

    $base = Str::slug($data['name']);
    do { $slug = $base . '-' . Str::lower(Str::random(24)); }
    while (DB::table('users')->where('slug', $slug)->exists());

    // Role normalization (Unzip Exam)
    [$role, $roleShort] = $this->normalizeRole(
        $data['role'] ?? 'student',
        $data['role_short_form'] ?? null
    );

    // Optional image upload
    $imageUrl = null;
    if ($request->hasFile('image')) {
        $imageUrl = $this->saveProfileImage($request->file('image'));
        if ($imageUrl === false) {
            return response()->json(['status'=>'error','message'=>'Invalid image upload'], 422);
        }
    }

    // Creator (from token)
    $createdBy = $this->currentUserId($request);

    try {
        $now = now();

        DB::table('users')->insert([
            'uuid'                     => $uuid,
            'name'                     => $data['name'],
            'email'                    => $data['email'],
            'phone_number'             => $data['phone_number'] ?? null,
            'alternative_email'        => $data['alternative_email'] ?? null,
            'alternative_phone_number' => $data['alternative_phone_number'] ?? null,
            'whatsapp_number'          => $data['whatsapp_number'] ?? null,
            'password'                 => Hash::make($data['password']),
            'image'                    => $imageUrl,
            'address'                  => $data['address'] ?? null,

            // ✅ Saves properly
            'user_folder_id'           => $data['user_folder_id'] ?? null,

            'role'                     => $role,
            'role_short_form'          => $roleShort,
            'slug'                     => $slug,
            'status'                   => $data['status'] ?? 'active',
            'remember_token'           => Str::random(60),
            'created_by'               => $createdBy,
            'created_at'               => $now,
            'created_at_ip'            => $request->ip(),
            'updated_at'               => $now,
            'metadata'                 => json_encode([
                'timezone' => 'Asia/Kolkata',
                'source'   => 'unzip_exam_api_store',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $user = DB::table('users')->where('email', $data['email'])->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'User created',
            'user'    => $this->publicUserPayload($user),
        ], 201);

    } catch (\Throwable $e) {
        if ($imageUrl) $this->deleteManagedProfileImage($imageUrl);
        Log::error('[UnzipExam Users Store] failed', ['error'=>$e->getMessage()]);
        return response()->json(['status'=>'error','message'=>'Could not create user'], 500);
    }
}

    /**
     * POST /api/users/{uuid}/cv
     * multipart/form-data:
     *   - cv (file)
     *
     * Uploads CV to: /public/assets/images/usercv
     * Saves relative path in users.cv (e.g. /assets/images/usercv/cv_xxx.pdf)
     */
    public function uploadCvByUuid(Request $request, string $uuid)
    {
        // ✅ Validate file (CV)
        $v = Validator::make($request->all(), [
            'cv' => 'required|file|max:10240|mimes:pdf,doc,docx',
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $v->errors(),
            ], 422);
        }

        // ✅ Find user by UUID (ignore soft deleted)
        $user = DB::table('users')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $file = $request->file('cv');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid CV upload',
            ], 422);
        }

        // ✅ Destination: public/assets/images/usercv
        $destDir = public_path('assets/images/usercv');
        if (!File::isDirectory($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = 'cv_' . date('Ymd_His') . '_' . Str::lower(Str::random(18)) . '.' . $ext;

        try {
            DB::beginTransaction();

            // Lock row (avoid race conditions)
            $locked = DB::table('users')
                ->where('id', $user->id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            // ✅ Move file
            $file->move($destDir, $filename);

            // ✅ Store relative path in DB
            $relativePath = '/assets/images/usercv/' . $filename;

            // ✅ Delete old CV (if any) AFTER new file is saved
            $oldCv = $locked->cv ?? null;

            DB::table('users')->where('id', $locked->id)->update([
                'cv'           => $relativePath,
                'updated_at'   => now(),
            ]);

            DB::commit();

            // ✅ Remove previous CV file if it's managed by us
            if (!empty($oldCv)) {
                $this->deleteManagedCv($oldCv);
            }

            $fresh = DB::table('users')->where('id', $locked->id)->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'CV uploaded successfully',
                'data'    => [
                    'user_id' => (int) $fresh->id,
                    'uuid'    => (string) ($fresh->uuid ?? ''),
                    'cv'      => $this->publicFileUrl($fresh->cv ?? null),
                    'cv_path' => (string) ($fresh->cv ?? ''),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Cleanup uploaded file if it exists
            $maybeAbs = $destDir . DIRECTORY_SEPARATOR . $filename;
            if (File::exists($maybeAbs)) {
                @File::delete($maybeAbs);
            }

            Log::error('[Upload CV] failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to upload CV',
            ], 500);
        }
    }



    /**
     * GET /api/users/all?q=&status=&limit=
     * Lightweight list (no pagination).
     */
    public function all(Request $request)
    {
        $q      = trim((string)$request->query('q', ''));
        $status = (string)$request->query('status', 'active'); // '' to disable filter
        $limit  = min(1000, max(1, (int)$request->query('limit', 1000)));

        $rows = DB::table('users')
            ->whereNull('deleted_at')
            ->when($status !== '', fn($w) => $w->where('status', $status))
            ->when($q !== '', function($w) use ($q){
                $like = "%{$q}%";
                $w->where(function($x) use ($like){
                    $x->where('name','LIKE',$like)->orWhere('email','LIKE',$like);
                });
            })
            ->select('id','name','email','image','role','role_short_form','status','user_folder_id')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'status'=>'success',
            'data'  => $rows,
            'meta'  => ['count' => $rows->count()],
        ]);
    }
public function index(Request $request)
{
    $page   = max(1, (int)$request->query('page', 1));
    $pp     = min(100, max(1, (int)$request->query('per_page', 20)));
    $q      = trim((string)$request->query('q', ''));
    $status = $request->has('status') ? (string)$request->query('status') : 'active';
    $role   = trim((string)$request->query('role', ''));

    // ── Only join assignment data when fetching students ──
    $isStudentQuery = ($role === 'student');

    if ($isStudentQuery) {
        $base = DB::table('users as u')
            ->leftJoin('student_counsellor_assignments as a', function ($join) {
                $join->on('a.student_id', '=', 'u.id')
                     ->whereNull('a.deleted_at');
            })
            ->leftJoin('users as c', 'c.id', '=', 'a.counsellor_id')
            ->whereNull('u.deleted_at');
    } else {
        $base = DB::table('users as u')->whereNull('u.deleted_at');
    }

    if ($status !== 'all' && $status !== '') {
        $base->where('u.status', $status);
    }
    if ($q !== '') {
        $like = "%{$q}%";
        $base->where(function ($w) use ($like) {
            $w->where('u.name', 'LIKE', $like)
              ->orWhere('u.email', 'LIKE', $like);
        });
    }
    if ($role !== '' && $role !== 'all') {
        $base->where('u.role', $role);
    }

    $total = (clone $base)->count();

    if ($isStudentQuery) {
        $rows = $base->orderBy('u.name')
            ->offset(($page - 1) * $pp)->limit($pp)
            ->select([
                'u.id',
                'u.uuid',
                'u.cv',
                'u.name',
                'u.email',
                'u.image',
                'u.role',
                'u.role_short_form',
                'u.status',
                'u.user_folder_id',
                // ── Assignment fields ──
                'a.counsellor_id',
                'a.assignment_status',
                'c.name  as counsellor_name',   // normalize() picks this up as assignedTo
                'c.uuid  as counsellor_uuid',
            ])
            ->get();
    } else {
        $rows = $base->orderBy('u.name')
            ->offset(($page - 1) * $pp)->limit($pp)
            ->select('u.id','u.uuid','u.cv','u.name','u.email','u.image','u.role','u.role_short_form','u.status','u.user_folder_id')
            ->get();
    }

    return response()->json([
        'status' => 'success',
        'data'   => $rows,
        'meta'   => [
            'page'        => $page,
            'per_page'    => $pp,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $pp),
        ],
    ]);
}
/**
 * GET /api/users/{id}
 */
public function show(Request $request, int $id)
{
    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'user'   => [
            'id'                       => (int) $user->id,
            'uuid'                     => $user->uuid,
            'name'                     => $user->name,
            'email'                    => $user->email,
            'phone_number'             => $user->phone_number,
            'alternative_email'        => $user->alternative_email,
            'alternative_phone_number' => $user->alternative_phone_number,
            'whatsapp_number'          => $user->whatsapp_number,
            'image'                    => $user->image,
            'address'                  => $user->address,
            'role'                     => $user->role,
            'role_short_form'          => $user->role_short_form,
            'slug'                     => $user->slug,
            'status'                   => $user->status,
            'last_login_at'            => $user->last_login_at,
            'last_login_ip'            => $user->last_login_ip,
            'created_by'               => $user->created_by,
            'user_folder_id'           => $user->user_folder_id !== null ? (int) $user->user_folder_id : null,
            'created_at'               => $user->created_at,
            'updated_at'               => $user->updated_at,
            'deleted_at'               => null,
        ],
    ]);
}    /**
     * GET /api/users/{id}/quizzes
     * For ADMIN / SUPER_ADMIN.
     * Returns all quizzes + whether this user is assigned to each.
     */
    public function userQuizzes(Request $request, int $id)
    {
        // Ensure user exists & not deleted
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        // All quizzes (excluding soft-deleted)
        $quizzes = DB::table('quizz')
            ->whereNull('deleted_at')
            ->orderBy('quiz_name')
            ->get();

        // Existing assignments (any status, not hard deleted)
        $assignments = DB::table('user_quiz_assignments')
            ->where('user_id', $id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('quiz_id');

        $data = $quizzes->map(function ($q) use ($assignments) {
            $a = $assignments->get($q->id);

            return [
                'quiz_id'         => (int) $q->id,
                'quiz_uuid'       => (string) ($q->uuid ?? ''),
                'quiz_name'       => (string) ($q->quiz_name ?? ''),
                'total_time'      => $q->total_time,
                'total_questions' => $q->total_questions,
                'is_public'       => (string) ($q->is_public ?? 'no'),
                'status'          => (string) ($q->status ?? 'active'),

                'assigned' => $a && in_array($a->status, ['active', 'completed', 'attempted', 'used']),
                'assignment_code' => $a && $a->status === 'active'
                                        ? (string) $a->assignment_code
                                        : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

/**
 * PUT/PATCH /api/users/{id}
 * Partial update. If name changes, slug is regenerated.
 */
public function update(Request $request, int $id)
{
    // ✅ Normalize folder id coming from FormData/JSON
    if ($request->has('user_folder_id')) {
        $raw = $request->input('user_folder_id');

        if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
            $request->merge(['user_folder_id' => null]);
        } else {
            $request->merge(['user_folder_id' => (int)$raw]);
        }
    }

    $v = Validator::make($request->all(), [
        'name'                     => 'sometimes|string|max:150',
        'email'                    => 'sometimes|email|max:255',
        'password'                 => 'sometimes|nullable|string|min:8|same:password_confirmation',
        'password_confirmation'    => 'sometimes|nullable|string|min:8',
        'phone_number'             => 'sometimes|nullable|string|max:32',
        'alternative_email'        => 'sometimes|nullable|email|max:255',
        'alternative_phone_number' => 'sometimes|nullable|string|max:32',
        'whatsapp_number'          => 'sometimes|nullable|string|max:32',
        'address'                  => 'sometimes|nullable|string',
        'role'                     => 'sometimes|nullable|string|max:50',
        'role_short_form'          => 'sometimes|nullable|string|max:10',
        'status'                   => 'sometimes|in:active,inactive',
        'image'                    => 'sometimes|file|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',

        // ✅ Folder assignment: exists + not deleted
        'user_folder_id' => [
            'sometimes',
            'nullable',
            'integer',
            Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
        ],
    ]);

    if ($v->fails()) {
        return response()->json(['status'=>'error','errors'=>$v->errors()], 422);
    }

    $data = $v->validated();

    $existing = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();
    if (!$existing) {
        return response()->json(['status'=>'error','message'=>'User not found'], 404);
    }

    // Uniqueness if changed
    if (array_key_exists('email', $data)) {
        if (DB::table('users')->where('email', $data['email'])->where('id','!=',$id)->exists()) {
            return response()->json(['status'=>'error','message'=>'Email already exists'], 422);
        }
    }

    if (array_key_exists('phone_number', $data) && !empty($data['phone_number'])) {
        if (DB::table('users')->where('phone_number', $data['phone_number'])->where('id','!=',$id)->exists()) {
            return response()->json(['status'=>'error','message'=>'Phone number already exists'], 422);
        }
    }

    $updates = [];
    foreach ([
        'name','email','phone_number','alternative_email','alternative_phone_number',
        'whatsapp_number','address','status',
    ] as $key) {
        if (array_key_exists($key, $data)) {
            $updates[$key] = $data[$key];
        }
    }

    if (!empty($data['password'] ?? null)) {
        $updates['password'] = Hash::make($data['password']);
    }

    // ✅ Folder update (supports unassign: null)
    if (array_key_exists('user_folder_id', $data)) {
        $updates['user_folder_id'] = $data['user_folder_id']; // null allowed ✅
    }

    // Role normalization if provided
    if (array_key_exists('role', $data) || array_key_exists('role_short_form', $data)) {
        [$normRole, $normShort] = $this->normalizeRole(
            $data['role'] ?? $existing->role,
            $data['role_short_form'] ?? $existing->role_short_form
        );
        $updates['role'] = $normRole;
        $updates['role_short_form'] = $normShort;
    }

    // Regenerate slug if name changed
    if (array_key_exists('name', $updates) && $updates['name'] !== $existing->name) {
        $base = Str::slug($updates['name']);
        do { $slug = $base . '-' . Str::lower(Str::random(24)); }
        while (DB::table('users')->where('slug', $slug)->where('id','!=',$id)->exists());
        $updates['slug'] = $slug;
    }

    // Optional image update
    if ($request->hasFile('image')) {
        $newUrl = $this->saveProfileImage($request->file('image'));
        if ($newUrl === false) {
            return response()->json(['status'=>'error','message'=>'Invalid image upload'], 422);
        }
        $this->deleteManagedProfileImage($existing->image);
        $updates['image'] = $newUrl;
    }

    if (empty($updates)) {
        return response()->json(['status'=>'error','message'=>'Nothing to update'], 400);
    }

    $updates['updated_at'] = now();

    DB::table('users')->where('id', $id)->update($updates);

    $fresh = DB::table('users')->where('id', $id)->first();

    return response()->json([
        'status'  => 'success',
        'message' => 'User updated',
        'user'    => $this->publicUserPayload($fresh),
    ]);
}

    /**
     * DELETE /api/users/{id}
     * Soft delete (prevents self-delete).
     */
    public function destroy(Request $request, int $id)
    {
        $actorId = $this->currentUserId($request);
        if ($actorId !== null && $actorId === $id) {
            return response()->json(['status'=>'error','message'=>"You can't delete your own account"], 422);
        }

        $user = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        DB::table('users')->where('id', $id)->update([
            'deleted_at' => now(),
            'status'     => 'inactive',
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success','message'=>'User soft-deleted']);
    }

    /**
     * POST /api/users/{id}/restore
     */
    public function restore(Request $request, int $id)
    {
        $user = DB::table('users')->where('id', $id)->whereNotNull('deleted_at')->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found or not deleted'], 404);
        }

        DB::table('users')->where('id', $id)->update([
            'deleted_at' => null,
            'status'     => 'active',
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success','message'=>'User restored']);
    }

    /**
     * DELETE /api/users/{id}/force
     * Permanently delete (also removes managed profile image).
     */
    public function forceDelete(Request $request, int $id)
    {
        $actorId = $this->currentUserId($request);
        if ($actorId !== null && $actorId === $id) {
            return response()->json(['status'=>'error','message'=>"You can't delete your own account"], 422);
        }

        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        $this->deleteManagedProfileImage($user->image);

        DB::table('users')->where('id', $id)->delete();

        return response()->json(['status'=>'success','message'=>'User permanently deleted']);
    }

    /**
     * PATCH /api/users/{id}/password
     * Body: { password }
     */
    public function updatePassword(Request $request, int $id)
    {
        $v = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);
        if ($v->fails()) {
            return response()->json(['status'=>'error','errors'=>$v->errors()], 422);
        }

        $user = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        DB::table('users')->where('id', $id)->update([
            'password'   => Hash::make($v->validated()['password']),
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success','message'=>'Password updated']);
    }

    /**
     * POST /api/users/{id}/image
     * file: image (multipart/form-data)
     */
   public function updateImage(Request $request, int $id)
{
    $v = Validator::make($request->all(), [
        // If your saveProfileImage() cannot handle SVG, REMOVE svg from here.
        'image' => 'required|file|max:5120|mimes:jpg,jpeg,png,webp,gif,svg',
    ]);

    if ($v->fails()) {
        return response()->json(['status' => 'error', 'errors' => $v->errors()], 422);
    }

    $file = $request->file('image');
    if (!$file || !$file->isValid()) {
        return response()->json(['status' => 'error', 'message' => 'Invalid image upload'], 422);
    }

    $newUrl = $this->saveProfileImage($file);
    if ($newUrl === false) {
        // Common cause: saveProfileImage() can’t process SVG/GIF/WebP if it tries to resize.
        return response()->json(['status' => 'error', 'message' => 'Invalid image upload'], 422);
    }

    $oldUrl = null;

    try {
        DB::beginTransaction();

        // Lock row to avoid race updates
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (!$user) {
            DB::rollBack();
            // Cleanup newly uploaded file since user doesn't exist
            $this->deleteManagedProfileImage($newUrl);

            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $oldUrl = $user->image;

        DB::table('users')->where('id', $id)->update([
            'image'      => $newUrl,
            'updated_at' => now(),
        ]);

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();

        // If DB failed, remove newly uploaded file so you don't leave junk
        $this->deleteManagedProfileImage($newUrl);

        report($e);
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to update image. Please try again.',
        ], 500);
    }

    // Delete old image ONLY after DB commit succeeds
    if (!empty($oldUrl)) {
        $this->deleteManagedProfileImage($oldUrl);
    }

    $fresh = DB::table('users')->where('id', $id)->first();

    return response()->json([
        'status'  => 'success',
        'message' => 'Image updated',
        'user'    => $this->publicUserPayload($fresh),
    ]);
}

    /* =========================================================
     |                     Helper methods
     |=========================================================*/

    /** Issue a personal access token; returns the plain token. */
    protected function issueToken(int $userId, ?Carbon $expiresAt = null): string
    {
        $plain = bin2hex(random_bytes(40));

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => self::USER_TYPE,
            'tokenable_id'   => $userId,
            'name'           => 'unzip_exam_user_token',
            'token'          => hash('sha256', $plain),
            'abilities'      => json_encode(['*']),
            'last_used_at'   => null,
            'expires_at'     => $expiresAt ? $expiresAt->toDateTimeString() : null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $plain;
    }

    /** Extract Bearer token from Authorization header. */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
            return null;
        }
        return $m[1];
    }

    /** Resolve current user id from the provided Bearer token. */
    protected function currentUserId(Request $request): ?int
    {
        $plain = $this->extractToken($request);
        if (!$plain) return null;

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        return $rec ? (int)$rec->tokenable_id : null;
    }

    /** Public payload sent to FE (no sensitive fields). */
   protected function publicUserPayload(object $user): array
{
    return [
        'id'              => (int)$user->id,
        'uuid'            => (string)($user->uuid ?? ''),
        'name'            => (string)($user->name ?? ''),
        'email'           => (string)($user->email ?? ''),
        'role'            => (string)($user->role ?? ''),
        'role_short_form' => (string)($user->role_short_form ?? ''),
        'slug'            => (string)($user->slug ?? ''),
        'image'           => $this->publicImageUrl($user->image ?? null),
        'status'          => (string)($user->status ?? ''),

        // ✅ ADD THIS
        'user_folder_id'  => isset($user->user_folder_id) ? (int)$user->user_folder_id : null,
    ];
}

        /** Generate unique 10-char UPPERCASE alphanumeric assignment code. */
    protected function generateAssignmentCode(): string
    {
        do {
            $code = strtoupper(Str::random(10)); // A–Z + 0–9
        } while (
            DB::table('user_quiz_assignments')
              ->where('assignment_code', $code)
              ->exists()
        );

        return $code;
    }
/**
 * Normalize role & short code against allowed set.
 * Accepts synonyms and variants like:
 *  - "college administrator", "college-admin", "college_administrator", "cadm"
 *  - "academic counsellor", "academic counselor", "acc"
 *  - "author", "writer", "content writer", "aut"
 */
protected function normalizeRole(?string $role, ?string $short = null): array
{
    // Normalize input to a comparable key:
    // - lowercase
    // - underscores/hyphens -> spaces
    // - collapse multiple spaces
    $key = Str::of((string)$role)
        ->lower()
        ->trim()
        ->replace(['_', '-'], ' ')
        ->replaceMatches('/\s+/', ' ')
        ->toString();

    $map = [
        // super admin
        'super admin'          => 'super_admin',
        'superadmin'           => 'super_admin',
        'super administrator'  => 'super_admin',
        'sa'                   => 'super_admin',

        // admin
        'admin'                => 'admin',
        'administrator'        => 'admin',
        'adm'                  => 'admin',

        // examiner
        'examiner'             => 'examiner',
        'invigilator'          => 'examiner',
        'proctor'              => 'examiner',
        'exam controller'      => 'examiner',
        'exam admin'           => 'examiner',
        'exm'                  => 'examiner',

        // student
        'student'              => 'student',
        'students'             => 'student',
        'candidate'            => 'student',
        'learner'              => 'student',
        'std'                  => 'student',
        'stu'                  => 'student',

        // NEW: author
        'author'               => 'author',
        'writer'               => 'author',
        'content writer'       => 'author',
        'contentwriter'        => 'author',
        'editor'               => 'author',
        'aut'                  => 'author',

        // NEW: college administrator
        'college administrator' => 'college_administrator',
        'college admin'         => 'college_administrator',
        'collegeadmin'          => 'college_administrator',
        'coladmin'              => 'college_administrator',
        'cadm'                  => 'college_administrator',

        // NEW: academic counsellor (both spellings)
        'academic counsellor'   => 'academic_counsellor',
        'academic counselor'    => 'academic_counsellor',
        'academic advisor'      => 'academic_counsellor',
        'academic adviser'      => 'academic_counsellor',
        'acc'                   => 'academic_counsellor',
    ];

    $r = $map[$key] ?? $key;

    // Final allowlist guard
    if (!in_array($r, self::ROLES, true)) {
        $r = 'student'; // fallback
    }

    // Short form: prefer incoming, else default mapping
    $short = $short ?: (self::ROLE_SHORT[$r] ?? 'STD');

    return [$r, strtoupper($short)];
}

    /** Save profile image into /Public/UserProfileImage and return absolute URL (or false on failure). */
    /** Save profile image into /public/UserProfileImage and return RELATIVE path (or false). */
protected function saveProfileImage($uploadedFile)
{
    if (!$uploadedFile || !$uploadedFile->isValid()) return false;

    $destDir = public_path('UserProfileImage');
    if (!File::isDirectory($destDir)) {
        File::makeDirectory($destDir, 0755, true);
    }

    $ext      = strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');
    $filename = 'usr_' . date('Ymd_His') . '_' . Str::lower(Str::random(16)) . '.' . $ext;

    $uploadedFile->move($destDir, $filename);

    // ✅ store relative path (works on any port/domain)
    return '/UserProfileImage/' . $filename;
}
/** Convert stored image (absolute/relative) into current-host absolute URL. */
protected function publicImageUrl(?string $value): string
{
    if (empty($value)) return '';

    // If DB contains absolute url, extract only path
    $path = parse_url($value, PHP_URL_PATH);
    $path = $path ?: $value;

    // force leading slash
    $path = '/' . ltrim($path, '/');

    // ✅ uses current request host/port automatically
    return url($path);
}

    /** Delete a managed profile image if it resides in /Public/UserProfileImage. */
    protected function deleteManagedProfileImage(?string $url): void
{
    if (empty($url)) return;

    $path = parse_url($url, PHP_URL_PATH);
    $path = $path ?: $url; // if it's already relative
    $path = '/' . ltrim($path, '/');

    if (Str::startsWith($path, '/UserProfileImage/')) {
        $abs = public_path(ltrim($path, '/'));
        if (File::exists($abs)) @File::delete($abs);
    }
}

        /**
     * POST /api/users/{id}/quizzes/assign
     * Body: { quiz_id:int }
     * Admin/Super Admin only.
     */
    public function assignQuiz(Request $request, int $id)
    {
        // Confirm user exists
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'quiz_id' => 'required|integer',
        ]);
        $quizId = (int) $validated['quiz_id'];

        $quiz = DB::table('quizz')
            ->where('id', $quizId)
            ->whereNull('deleted_at')
            ->first();

        if (!$quiz) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Quiz not found',
            ], 404);
        }

        $now        = now();
        $assignedBy = $this->currentUserId($request);

        // We want at most one row per (user, quiz), even if soft-deleted.
        $existing = DB::table('user_quiz_assignments')
            ->where('user_id', $id)
            ->where('quiz_id', $quizId)
            ->first();

        if ($existing) {
            // If already active, just return its code
            if ($existing->status === 'active' && !$existing->deleted_at) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Already assigned',
                    'data'    => [
                        'assignment_code' => (string) $existing->assignment_code,
                    ],
                ]);
            }

            // Reactivate existing row (even if soft-deleted / revoked)
            $code = $existing->assignment_code ?: $this->generateAssignmentCode();

            DB::table('user_quiz_assignments')
                ->where('id', $existing->id)
                ->update([
                    'assignment_code' => $code,
                    'status'          => 'active',
                    'assigned_by'     => $assignedBy,
                    'assigned_at'     => $now,
                    'deleted_at'      => null,
                    'updated_at'      => $now,
                ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Assignment updated',
                'data'    => [
                    'assignment_code' => $code,
                ],
            ]);
        }

        // Fresh assignment
        $code = $this->generateAssignmentCode();

        DB::table('user_quiz_assignments')->insert([
            'uuid'            => (string) Str::uuid(),
            'user_id'         => $id,
            'quiz_id'         => $quizId,
            'assignment_code' => $code,
            'status'          => 'active',
            'assigned_by'     => $assignedBy,
            'assigned_at'     => $now,
            'metadata'        => json_encode(new \stdClass()),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Quiz assigned to user',
            'data'    => [
                'assignment_code' => $code,
            ],
        ], 201);
    }

        /**
     * POST /api/users/{id}/quizzes/unassign
     * Body: { quiz_id:int }
     * Marks assignment as revoked (keeps row for audit).
     */
    public function unassignQuiz(Request $request, int $id)
    {
        // Confirm user exists
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'quiz_id' => 'required|integer',
        ]);
        $quizId = (int) $validated['quiz_id'];

        $existing = DB::table('user_quiz_assignments')
            ->where('user_id', $id)
            ->where('quiz_id', $quizId)
            ->whereNull('deleted_at')
            ->first();

        if (!$existing) {
            // no-op, but not an error (helps keep FE simple)
            return response()->json([
                'status'  => 'noop',
                'message' => 'No active assignment found',
            ], 200);
        }

        DB::table('user_quiz_assignments')
            ->where('id', $existing->id)
            ->update([
                'status'     => 'revoked',
                'updated_at' => now(),
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Assignment revoked',
        ]);
    }

/**
 * POST /api/users/import-csv
 * multipart/form-data:
 *  - file: CSV (required)
 *  - default_password: optional (used when password column is empty)
 *  - default_role: optional (used when role column is empty)
 *
 * ✅ CSV header required:
 *  name,email,password,role,folder_uuid
 *
 * ✅ folder_uuid is optional per row:
 *  - If provided: it is converted into user_folder_id (int FK)
 *  - If missing/blank: user_folder_id remains NULL
 *
 * Example row:
 *  John Doe,john@gmail.com,Pass@123,student,58f1040d-c0b3-4076-88c8-2edb5a5792f2
 */
public function importUsersCsv(Request $request)
{
    $v = Validator::make($request->all(), [
        'file'             => 'required|file|max:10240|mimes:csv,txt',
        'default_password' => 'sometimes|nullable|string|min:6|max:100',
        'default_role'     => 'sometimes|nullable|string|max:50',
    ]);

    if ($v->fails()) {
        return response()->json(['status' => 'error', 'errors' => $v->errors()], 422);
    }

    $file = $request->file('file');

    $defaultPassword = (string)($request->input('default_password') ?: 'Student@123');
    $defaultRoleIn   = (string)($request->input('default_role') ?: 'student');
    [$defaultRole, $defaultRoleShort] = $this->normalizeRole($defaultRoleIn, null);

    $path = $file->getRealPath();
    if (!$path || !file_exists($path)) {
        return response()->json(['status'=>'error','message'=>'Uploaded file not found'], 422);
    }

    $handle = fopen($path, 'r');
    if (!$handle) {
        return response()->json(['status'=>'error','message'=>'Unable to read CSV'], 422);
    }

    // ✅ Read header
    $header = fgetcsv($handle);
    if (!$header || !is_array($header)) {
        fclose($handle);
        return response()->json(['status'=>'error','message'=>'CSV header missing'], 422);
    }

    // ✅ Normalize header keys (spaces -> underscore, lowercase)
    $header = array_map(function ($h) {
        $h = strtolower(trim((string)$h));
        $h = preg_replace('/\s+/', '_', $h);
        return $h;
    }, $header);

    // ✅ Required columns (minimum)
    foreach (['name','email'] as $req) {
        if (!in_array($req, $header, true)) {
            fclose($handle);
            return response()->json([
                'status'  => 'error',
                'message' => "CSV must contain '{$req}' column in header",
            ], 422);
        }
    }

    // ✅ Detect folder uuid column name (support both variants)
    $folderCol = null;
    if (in_array('folder_uuid', $header, true)) {
        $folderCol = 'folder_uuid';
    } elseif (in_array('user_folder_uuid', $header, true)) {
        $folderCol = 'user_folder_uuid';
    }

    // ✅ Preload folder_uuid => id map (fast lookup)
    // only non-deleted folders
    $folderMap = [];
    if ($folderCol) {
        $folderMap = DB::table('user_folders')
            ->whereNull('deleted_at')
            ->pluck('id', 'uuid')  // [uuid => id]
            ->toArray();
    }

    $actorId = $this->currentUserId($request);
    $now     = now();

    $imported = 0;
    $skipped  = 0;
    $errors   = [];

    DB::beginTransaction();

    try {
        $rowIndex = 1; // header = row 1

        while (($data = fgetcsv($handle)) !== false) {
            $rowIndex++;

            // ✅ skip blank lines
            if (!is_array($data) || count(array_filter($data, fn($x)=>trim((string)$x)!=='')) === 0) {
                continue;
            }

            // ✅ map row => associative by header
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $name     = trim((string)($row['name'] ?? ''));
            $email    = trim((string)($row['email'] ?? ''));
            $password = (string)($row['password'] ?? '');
            $roleIn   = (string)($row['role'] ?? '');

            // ✅ folder_uuid -> convert to folder id
            $folderUuid = null;
            $folderId   = null;

            if ($folderCol) {
                $folderUuid = trim((string)($row[$folderCol] ?? ''));

                // normalize nullish values
                if ($folderUuid === '' || in_array(strtolower($folderUuid), ['null','undefined','none'], true)) {
                    $folderUuid = null;
                }
            }

            if ($name === '' || $email === '') {
                $skipped++;
                $errors[] = "Row {$rowIndex}: name/email missing";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $errors[] = "Row {$rowIndex}: invalid email {$email}";
                continue;
            }

            // ✅ duplicate email (ignore soft-deleted)
            if (DB::table('users')->where('email', $email)->whereNull('deleted_at')->exists()) {
                $skipped++;
                $errors[] = "Row {$rowIndex}: email already exists {$email}";
                continue;
            }

            // ✅ Resolve folder_uuid -> id (FK)
            if ($folderUuid) {
                // if someone mistakenly puts numeric id inside folder_uuid column, accept it
                if (ctype_digit($folderUuid)) {
                    $folderId = (int)$folderUuid;

                    $exists = DB::table('user_folders')
                        ->where('id', $folderId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$exists) {
                        $skipped++;
                        $errors[] = "Row {$rowIndex}: folder id not found ({$folderUuid})";
                        continue;
                    }
                } else {
                    $folderId = $folderMap[$folderUuid] ?? null;
                    if (!$folderId) {
                        $skipped++;
                        $errors[] = "Row {$rowIndex}: invalid folder_uuid ({$folderUuid})";
                        continue;
                    }
                }
            }

            $finalPassword = trim($password) !== '' ? $password : $defaultPassword;

            // ✅ role
            if (trim($roleIn) !== '') {
                [$role, $roleShort] = $this->normalizeRole($roleIn, null);
            } else {
                $role      = $defaultRole;
                $roleShort = $defaultRoleShort;
            }

            // ✅ uuid + slug
            do { $uuid = (string) Str::uuid(); }
            while (DB::table('users')->where('uuid', $uuid)->exists());

            $base = Str::slug($name);
            do { $slug = $base . '-' . Str::lower(Str::random(24)); }
            while (DB::table('users')->where('slug', $slug)->exists());

            DB::table('users')->insert([
                'uuid'            => $uuid,
                'name'            => $name,
                'email'           => $email,
                'password'        => Hash::make($finalPassword),

                // ✅ HERE: folder_uuid converted to FK id
                'user_folder_id'  => $folderId,

                'role'            => $role,
                'role_short_form' => $roleShort,
                'slug'            => $slug,
                'status'          => 'active',
                'remember_token'  => Str::random(60),
                'created_by'      => $actorId,
                'created_at'      => $now,
                'created_at_ip'   => $request->ip(),
                'updated_at'      => $now,
                'metadata'        => json_encode([
                    'timezone' => 'Asia/Kolkata',
                    'source'   => 'unzip_exam_api_import_csv',
                    'import'   => [
                        'row'         => $rowIndex,
                        'folder_uuid' => $folderUuid, // keeps audit info
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $imported++;
        }

        fclose($handle);
        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => 'CSV import completed',
            'meta'    => [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
                'supports' => [
                    'folder_uuid_column' => $folderCol ? true : false,
                    'folder_uuid_to_id'  => true,
                ],
            ],
        ]);
    } catch (\Throwable $e) {
        fclose($handle);
        DB::rollBack();

        Log::error('[UnzipExam Users Import CSV] failed', ['error' => $e->getMessage()]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Import failed',
        ], 500);
    }
}

public function getProfile(Request $request)
{
    $userId = $this->currentUserId($request);

    if (!$userId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    $user = DB::table('users')->where('id', $userId)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    // Role-based frontend permissions
    $isEditable = in_array($user->role, [
    'admin','super_admin','student',
    'author','college_administrator','academic_counsellor'
]);
    $permissions = [
        'can_edit_profile'   => $isEditable,
        'can_change_image'   => $isEditable,
        'can_change_password'=> $isEditable,
        'can_view_profile'   => true
    ];

    // API endpoints to be used by frontend
    $endpoints = [
        'update_profile' => "/api/users/{$user->id}",
        'update_image'   => "/api/users/{$user->id}/image",
        'update_password'=> "/api/users/{$user->id}/password"
    ];

    return response()->json([
        'status' => 'success',
        'user' => [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'phone_number'    => $user->phone_number,
            'address'         => $user->address,
            'role'            => $user->role,
            'role_short_form' => $user->role_short_form,
'image' => $this->publicImageUrl($user->image ?? null), // ✅ FIXED
            'status'          => $user->status,
        ],
        'permissions' => $permissions,
        'endpoints' => $endpoints
    ]);
}

    /**
     * POST /api/users/{id}/bubble-games/assign
     * Body: { bubble_game_id:int }
     * Admin/Super Admin only.
     */
    public function assignBubbleGame(Request $request, int $id)
    {
        // Confirm user exists
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
            

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'bubble_game_id' => 'required|integer',
        ]);
        $gameId = (int) $validated['bubble_game_id'];

        // Confirm bubble game exists
        $game = DB::table('bubble_game')
            ->where('id', $gameId)
            ->whereNull('deleted_at')
            ->first();

        if (!$game) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bubble game not found',
            ], 404);
        }

        $now        = now();
        $assignedBy = $this->currentUserId($request);

        // We want at most one row per (user, bubble_game), even if soft-deleted.
        $existing = DB::table('user_bubble_game_assignments')
            ->where('user_id', $id)
            ->where('bubble_game_id', $gameId)
            ->first();

        if ($existing) {
            // If already active, just return its code
            if ($existing->status === 'active' && !$existing->deleted_at) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Already assigned',
                    'data'    => [
                        'assignment_code' => (string) $existing->assignment_code,
                    ],
                ]);
            }

            // Reactivate existing row (even if soft-deleted / revoked)
            $code = $existing->assignment_code ?: $this->generateAssignmentCode();

            DB::table('user_bubble_game_assignments')
                ->where('id', $existing->id)
                ->update([
                    'assignment_code' => $code,
                    'status'          => 'active',
                    'assigned_by'     => $assignedBy,
                    'assigned_at'     => $now,
                    'deleted_at'      => null,
                    'updated_at'      => $now,
                ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Assignment updated',
                'data'    => [
                    'assignment_code' => $code,
                ],
            ]);
        }

        // Fresh assignment
        $code = $this->generateAssignmentCode();

        DB::table('user_bubble_game_assignments')->insert([
            'uuid'            => (string) Str::uuid(),
            'user_id'         => $id,
            'bubble_game_id'  => $gameId,
            'assignment_code' => $code,
            'status'          => 'active',
            'assigned_by'     => $assignedBy,
            'assigned_at'     => $now,
            'metadata'        => json_encode(new \stdClass()),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Bubble game assigned to user',
            'data'    => [
                'assignment_code' => $code,
            ],
        ], 201);
    }

    /**
     * POST /api/users/{id}/bubble-games/unassign
     * Body: { bubble_game_id:int }
     * Marks assignment as revoked (keeps row for audit).
     */
    public function unassignBubbleGame(Request $request, int $id)
    {
        // Confirm user exists
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'bubble_game_id' => 'required|integer',
        ]);
        $gameId = (int) $validated['bubble_game_id'];

        $existing = DB::table('user_bubble_game_assignments')
            ->where('user_id', $id)
            ->where('bubble_game_id', $gameId)
            ->whereNull('deleted_at')
            ->first();

        if (!$existing) {
            // no-op, but not an error (helps keep FE simple)
            return response()->json([
                'status'  => 'noop',
                'message' => 'No active assignment found',
            ], 200);
        }

        DB::table('user_bubble_game_assignments')
            ->where('id', $existing->id)
            ->update([
                'status'     => 'revoked',
                'updated_at' => now(),
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Assignment revoked',
        ]);
    }
/**
 * GET /api/users/{id}/bubble-games
 * For ADMIN / SUPER_ADMIN.
 * Returns all bubble games + whether this user is assigned to each.
 */
public function userBubbleGames(Request $request, int $id)
{
    // Ensure user exists & not deleted
    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    // All bubble games (excluding soft-deleted)
    $games = DB::table('bubble_game')
        ->whereNull('deleted_at')
        ->orderBy('title') // change to 'title' if that's your column
        ->get();

    // Existing assignments (any status, not hard deleted)
    $assignments = DB::table('user_bubble_game_assignments')
        ->where('user_id', $id)
        ->whereNull('deleted_at')
        ->get()
        ->keyBy('bubble_game_id');

    $data = $games->map(function ($g) use ($assignments) {
        $a = $assignments->get($g->id);

        return [
            'bubble_game_id'   => (int) $g->id,
            'bubble_game_uuid' => (string) ($g->uuid ?? ''),
            'bubble_game_name' => (string) (($g->game_name ?? $g->title ?? '') ),

            // keep these aligned with your UI columns
            'total_time'       => $g->total_time ?? null,       // duration/minutes
            'total_questions'  => $g->total_questions ?? null,
            'is_public'        => (string) ($g->is_public ?? 'no'),
            'status'           => (string) ($g->status ?? 'active'),

            'assigned'         => $a && $a->status === 'active',
            'assignment_code'  => $a && $a->status === 'active'
                                    ? (string) $a->assignment_code
                                    : null,
        ];
    });

    return response()->json([
        'status' => 'success',
        'data'   => $data,
    ]);
}
/**
 * POST /api/users/{id}/door-games/assign
 * Body: { door_game_id:int }
 * Admin/Super Admin only.
 */
public function assignDoorGame(Request $request, int $id)
{
    // Confirm user exists
    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    $validated = $request->validate([
        'door_game_id' => 'required|integer',
    ]);
    $gameId = (int) $validated['door_game_id'];

    // Confirm door game exists
    $game = DB::table('door_game')
        ->where('id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$game) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Door game not found',
        ], 404);
    }

    $now        = now();
    $assignedBy = $this->currentUserId($request);

    // One row per (user, door_game) even if soft-deleted
    $existing = DB::table('user_door_game_assignments')
        ->where('user_id', $id)
        ->where('door_game_id', $gameId)
        ->first();

    if ($existing) {
        // If already active and not deleted
        if ($existing->status === 'active' && !$existing->deleted_at) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Already assigned',
                'data'    => [
                    'assignment_code' => (string) $existing->assignment_code,
                ],
            ]);
        }

        // Reactivate existing row (even if soft-deleted / revoked)
        $code = $existing->assignment_code ?: $this->generateAssignmentCode();

        DB::table('user_door_game_assignments')
            ->where('id', $existing->id)
            ->update([
                'assignment_code' => $code,
                'status'          => 'active',
                'assigned_by'     => $assignedBy,
                'assigned_at'     => $now,
                'deleted_at'      => null,
                'updated_at'      => $now,
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Assignment updated',
            'data'    => [
                'assignment_code' => $code,
            ],
        ]);
    }

    // Fresh assignment
    $code = $this->generateAssignmentCode();

    DB::table('user_door_game_assignments')->insert([
        'uuid'            => (string) Str::uuid(),
        'user_id'         => $id,
        'door_game_id'    => $gameId,
        'assignment_code' => $code,
        'status'          => 'active',
        'assigned_by'     => $assignedBy,
        'assigned_at'     => $now,
        'metadata'        => json_encode(new \stdClass()),
        'created_at'      => $now,
        'updated_at'      => $now,
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Door game assigned to user',
        'data'    => [
            'assignment_code' => $code,
        ],
    ], 201);
}

/**
 * POST /api/users/{id}/door-games/unassign
 * Body: { door_game_id:int }
 * Marks assignment as revoked (keeps row for audit).
 */
public function unassignDoorGame(Request $request, int $id)
{
    // Confirm user exists
    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    $validated = $request->validate([
        'door_game_id' => 'required|integer',
    ]);
    $gameId = (int) $validated['door_game_id'];

    $existing = DB::table('user_door_game_assignments')
        ->where('user_id', $id)
        ->where('door_game_id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$existing) {
        // no-op
        return response()->json([
            'status'  => 'noop',
            'message' => 'No active assignment found',
        ], 200);
    }

    DB::table('user_door_game_assignments')
        ->where('id', $existing->id)
        ->update([
            'status'     => 'revoked',
            'updated_at' => now(),
        ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Assignment revoked',
    ]);
}

/**
 * GET /api/users/{id}/door-games
 * For ADMIN / SUPER_ADMIN.
 * Returns all door games + whether this user is assigned to each.
 */
public function userDoorGames(Request $request, int $id)
{
    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    // All door games (excluding soft-deleted)
    $games = DB::table('door_game')
        ->whereNull('deleted_at')
        ->orderBy('title')
        ->get();

    // Existing assignments (not hard deleted)
    $assignments = DB::table('user_door_game_assignments')
        ->where('user_id', $id)
        ->whereNull('deleted_at')
        ->get()
        ->keyBy('door_game_id');

    $data = $games->map(function ($g) use ($assignments) {
        $a = $assignments->get($g->id);

        // Map to your modal columns (same as bubble)
        $timeSec = $g->time_limit_sec ?? null;
        $durationMin = is_numeric($timeSec) ? (int) ceil(((int)$timeSec) / 60) : null;

        return [
            'door_game_id'   => (int) $g->id,
            'door_game_uuid' => (string) ($g->uuid ?? ''),
            'door_game_name' => (string) ($g->title ?? ''),

            // UI column compatibility
            'total_time'      => $durationMin, // minutes (like bubble total_time)
            'total_questions' => isset($g->grid_dim) ? ((int)$g->grid_dim * (int)$g->grid_dim) : null,
            'is_public'       => (string) ($g->is_public ?? 'no'), // if you don't have, stays 'no'
            'status'          => (string) ($g->status ?? 'active'),

            'assigned'        => $a && $a->status === 'active',
            'assignment_code' => $a && $a->status === 'active'
                                ? (string) $a->assignment_code
                                : null,
        ];
    });

    return response()->json([
        'status' => 'success',
        'data'   => $data,
    ]);
}
/**
 * GET /api/users/{id}/path-games
 * For ADMIN / SUPER_ADMIN.
 * Returns all path games + whether this user is assigned to each.
 */
public function userPathGames(Request $request, int $id)
{
    // ✅ Only admin / super_admin
    // if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    // ✅ All path games (excluding soft-deleted)
    $games = DB::table('path_games')
        ->whereNull('deleted_at')
        ->orderBy('title')
        ->get();

    // ✅ Existing assignments
    $assignments = DB::table('user_path_game_assignments')
        ->where('user_id', $id)
        ->whereNull('deleted_at')
        ->get()
        ->keyBy('path_game_id');

    $data = $games->map(function ($g) use ($assignments) {
        $a = $assignments->get($g->id);

        // ✅ time_limit_sec -> minutes
        $timeSec = $g->time_limit_sec ?? null;
        $durationMin = is_numeric($timeSec) ? (int) ceil(((int)$timeSec) / 60) : null;

        // ✅ total_questions (if grid_dim exists in path_games)
        $totalQuestions = null;
        if (isset($g->grid_dim) && is_numeric($g->grid_dim)) {
            $totalQuestions = ((int)$g->grid_dim * (int)$g->grid_dim);
        }

        return [
            'path_game_id'   => (int) $g->id,
            'path_game_uuid' => (string) ($g->uuid ?? ''),
            'path_game_name' => (string) ($g->title ?? ''),

            // UI column compatibility (same keys style)
            'total_time'      => $durationMin,      // minutes
            'total_questions' => $totalQuestions,   // optional
            'is_public'       => (string) ($g->is_public ?? 'no'),
            'status'          => (string) ($g->status ?? 'active'),

            'assigned'        => $a && $a->status === 'active',
            'assignment_code' => $a && $a->status === 'active'
                                ? (string) $a->assignment_code
                                : null,
        ];
    });

    return response()->json([
        'status' => 'success',
        'data'   => $data,
    ]);
}
/**
 * ✅ Role Guard Helper
 * usage:
 *   if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;
 */
private function requireRole(Request $r, array $allowed)
{
    // ✅ read role from middleware attrs OR user model
    $role = (string) (
        $r->attributes->get('auth_role')
        ?? optional($r->user())->role
        ?? ''
    );

    $role = strtolower(trim($role));
    $allowedNorm = array_map(fn($x) => strtolower(trim($x)), $allowed);

    \Log::info('UserController.requireRole: check', [
        'role'    => $role,
        'allowed' => $allowedNorm,
        'actor'   => [
            'id'   => (int) ($r->attributes->get('auth_tokenable_id') ?? optional($r->user())->id ?? 0),
            'type' => (string) ($r->attributes->get('auth_tokenable_type') ?? (optional($r->user()) ? get_class($r->user()) : '')),
        ],
    ]);

    if (!$role) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Authentication required.',
        ], 401);
    }

    if (!in_array($role, $allowedNorm, true)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Access denied.',
        ], 403);
    }

    return null; // ✅ allowed
}

/**
 * POST /api/users/{id}/path-games/unassign
 * Body: { path_game_id:int }
 * Marks assignment as revoked (keeps row for audit).
 */
public function unassignPathGame(Request $request, int $id)
{
    // ✅ Only admin/super_admin
    // if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

    // Confirm user exists
    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    $validated = $request->validate([
        'path_game_id' => 'required|integer',
    ]);
    $gameId = (int) $validated['path_game_id'];

    $existing = DB::table('user_path_game_assignments')
        ->where('user_id', $id)
        ->where('path_game_id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$existing) {
        return response()->json([
            'status'  => 'noop',
            'message' => 'No active assignment found',
        ], 200);
    }

    DB::table('user_path_game_assignments')
        ->where('id', $existing->id)
        ->update([
            'status'     => 'revoked',
            'updated_at' => now(),
        ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Assignment revoked',
    ]);
}
/**
 * POST /api/users/{id}/path-games/assign
 * Body: { path_game_id:int }
 * Creates/activates assignment row (keeps row for audit).
 */
public function assignPathGame(Request $request, int $id)
{
    // ✅ Only admin/super_admin
//    if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

    // ✅ Confirm user exists
    $user = DB::table('users')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'message' => 'User not found',
        ], 404);
    }

    // ✅ Validate input
    $validated = $request->validate([
        'path_game_id' => 'required|integer',
    ]);

    $gameId = (int) $validated['path_game_id'];

    // ✅ Confirm game exists
    $game = DB::table('path_games')
        ->where('id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$game) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Path game not found',
        ], 404);
    }

    // ✅ Check existing assignment
    $existing = DB::table('user_path_game_assignments')
        ->where('user_id', $id)
        ->where('path_game_id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    // ✅ generate assignment code (like others)
    $assignmentCode = 'PG-' . strtoupper(\Illuminate\Support\Str::random(8));

    // ✅ If already ACTIVE => no-op
    if ($existing && strtolower($existing->status) === 'active') {
        return response()->json([
            'status'  => 'noop',
            'message' => 'Already assigned',
            'data'    => [
                'assignment_code' => $existing->assignment_code,
            ]
        ], 200);
    }

    // ✅ If exists but revoked => reactivate
    if ($existing) {
        DB::table('user_path_game_assignments')
            ->where('id', $existing->id)
            ->update([
                'status'          => 'active',
                'assignment_code' => $assignmentCode,
                'assigned_by'     => $request->attributes->get('auth_tokenable_id') ?? null,
                'assigned_at'     => now(),
                'updated_at'      => now(),
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Assignment re-activated',
            'data'    => [
                'assignment_code' => $assignmentCode,
            ]
        ], 200);
    }

    // ✅ Insert fresh assignment
    DB::table('user_path_game_assignments')->insert([
        'uuid'            => (string) \Illuminate\Support\Str::uuid(),
        'user_id'         => $id,
        'path_game_id'    => $gameId,
        'assignment_code' => $assignmentCode,
        'status'          => 'active',
        'assigned_by'     => $request->attributes->get('auth_tokenable_id') ?? null,
        'assigned_at'     => now(),
        'metadata'        => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Path Game assigned successfully',
        'data'    => [
            'assignment_code' => $assignmentCode,
        ]
    ], 200);
}

/** Convert stored file path (absolute/relative) into current-host absolute URL. */
protected function publicFileUrl(?string $value): string
{
    if (empty($value)) return '';

    $path = parse_url($value, PHP_URL_PATH);
    $path = $path ?: $value;
    $path = '/' . ltrim($path, '/');

    return url($path);
}

/** Delete a managed CV if it resides in /public/assets/images/usercv */
protected function deleteManagedCv(?string $pathOrUrl): void
{
    if (empty($pathOrUrl)) return;

    $path = parse_url($pathOrUrl, PHP_URL_PATH);
    $path = $path ?: $pathOrUrl;
    $path = '/' . ltrim($path, '/');

    if (Str::startsWith($path, '/assets/images/usercv/')) {
        $abs = public_path(ltrim($path, '/'));
        if (File::exists($abs)) {
            @File::delete($abs);
        }
    }
}
public function freshLeads(Request $request)
{
    $page = max(1, (int) $request->query('page', 1));
    $pp   = min(100, max(1, (int) $request->query('per_page', 20)));
    $q    = trim((string) $request->query('q', ''));

    // ✅ detect phone-like column
    $phoneCol = null;
    if (Schema::hasColumn('users', 'phone')) {
        $phoneCol = 'u.phone';
    } elseif (Schema::hasColumn('users', 'phone_number')) {
        $phoneCol = 'u.phone_number';
    } elseif (Schema::hasColumn('users', 'mobile')) {
        $phoneCol = 'u.mobile';
    }

    $base = DB::table('users as u')
        ->leftJoin('student_personal_academic_details as sp', 'sp.user_id', '=', 'u.id')

        // ✅ one active assignment per student
        ->leftJoin('student_counsellor_assignments as a', function ($join) {
            $join->on('a.student_id', '=', 'u.id')
                 ->whereNull('a.deleted_at');
        })

        ->leftJoin('users as c', 'c.id', '=', 'a.counsellor_id')

        ->whereNull('u.deleted_at')
        ->whereIn('u.role', ['student', 'students'])
        ->where('u.status', 'active')

        // ✅ fresh leads (no active assignment row)
        ->whereNull('a.counsellor_id')

        // ✅ only students who have at least one result in quizz_results
        ->whereExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('quizz_results as qr')
                ->whereColumn('qr.user_id', 'u.id');
        });

    if ($q !== '') {
        $like = "%{$q}%";
        $base->where(function ($w) use ($like, $phoneCol) {
            $w->where('u.name', 'LIKE', $like)
              ->orWhere('u.email', 'LIKE', $like);

            if ($phoneCol) {
                $w->orWhere($phoneCol, 'LIKE', $like);
            }

            $w->orWhere('sp.guardian_name', 'LIKE', $like)
              ->orWhere('sp.guardian_number', 'LIKE', $like)
              ->orWhere('sp.class', 'LIKE', $like)
              ->orWhere('sp.board', 'LIKE', $like)
              ->orWhere('sp.exam_type', 'LIKE', $like);
        });
    }

    $total = (clone $base)->count();

    $select = [
        'u.id',
        'u.uuid',
        'u.cv',
        'u.name',
        'u.email',
        'u.image',
        'u.role',
        'u.role_short_form',
        'u.status',
        'u.user_folder_id',
        'u.created_at',

        // academic/personal details
        'sp.guardian_name',
        'sp.guardian_number',
        'sp.class as student_class',
        'sp.board',
        'sp.exam_type',
        'sp.year_of_passout',

        // assignment + counsellor
        'a.uuid as assignment_uuid',
        'a.counsellor_id',
        'a.assignment_status',
        'a.assigned_at',
        'a.ended_at',
        'c.name as academic_counsellor_name',
        'c.uuid as academic_counsellor_uuid',
    ];

    // ✅ add phone field only if exists
    if ($phoneCol) {
        $select[] = DB::raw($phoneCol . ' as phone');
    }

    $rows = $base
        ->orderBy('u.created_at', 'asc')
        ->offset(($page - 1) * $pp)
        ->limit($pp)
        ->select($select)
        ->get();

    return response()->json([
        'status' => 'success',
        'data'   => $rows,
        'meta'   => [
            'page'        => $page,
            'per_page'    => $pp,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $pp),
        ],
    ]);
}
}
