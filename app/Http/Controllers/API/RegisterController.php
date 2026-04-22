<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\AutoAssignQuizController;
use App\Models\OtpVerification;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * STEP 1: Send OTP
     * POST /api/register/send-otp
     */
    public function sendOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'phone_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $exists = DB::table('users')
        ->where('phone_number', $request->phone_number)
        ->exists();

    if ($exists) {
        return response()->json([
            'success' => false,
            'message' => 'This phone number is already registered.',
        ], 409);
    }

    $result = $this->otpService->sendOtp($request->phone_number, $request->ip()); // ← pass IP

    return response()->json($result, $result['success'] ? 200 : 500);
}
    /**
     * STEP 2: Verify OTP
     * POST /api/register/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'otp'          => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->otpService->verifyOtp($request->phone_number, $request->otp);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * STEP 3: Complete Registration
     * POST /api/register/complete
     */
public function completeRegistration(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'         => ['required', 'string', 'max:255'],
        'phone_number' => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone_number'],
        'password'     => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
        ], 422);
    }

    $phone = $request->phone_number;

    if (!$this->otpService->isPhoneVerified($phone)) {
        return response()->json([
            'success' => false,
            'message' => 'Phone number not verified. Please verify OTP first.',
        ], 403);
    }

    $now = now();

    $autoQuizIds = AutoAssignQuizController::selectedQuizIdsForStudentRegister();

    DB::transaction(function () use ($request, $phone, $now, $autoQuizIds) {
        $userId = DB::table('users')->insertGetId([
            'uuid'            => (string) Str::uuid(),
            'name'            => $request->name,
            'slug'            => Str::slug($request->name) . '-' . uniqid(),
            'email'           => null,
            'phone_number'    => $phone,
            'password'        => Hash::make($request->password),
            'role'            => 'student',
            'role_short_form' => 'STD',
            'status'          => 'active',
            'created_at_ip'   => $request->ip(),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        if (!empty($autoQuizIds)) {
            // Keep `quizz` only if that is your actual table name.
            // If your table is `quizzes`, replace `quizz` with `quizzes`.
            $validQuizIds = DB::table('quizz')
                ->whereIn('id', $autoQuizIds)
                ->pluck('id')
                ->toArray();

            foreach ($validQuizIds as $quizId) {
                $existingAssignment = DB::table('user_quiz_assignments')
                    ->where('user_id', $userId)
                    ->where('quiz_id', $quizId)
                    ->first();

                if ($existingAssignment) {
                    $updateData = [
                        'status'      => 'active',
                        'assigned_by' => $userId,
                        'assigned_at' => $now,
                        'updated_at'  => $now,
                    ];

                    if (empty($existingAssignment->assignment_code)) {
                        $updateData['assignment_code'] = strtoupper(Str::random(8));
                    }

                    DB::table('user_quiz_assignments')
                        ->where('id', $existingAssignment->id)
                        ->update($updateData);
                } else {
                    DB::table('user_quiz_assignments')->insert([
                        'uuid'            => (string) Str::uuid(),
                        'user_id'         => $userId,
                        'quiz_id'         => $quizId,
                        'assignment_code' => strtoupper(Str::random(8)),
                        'status'          => 'active',
                        'assigned_by'     => $userId,
                        'assigned_at'     => $now,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }
            }
        }
    });

    $this->otpService->clearVerified($phone);

    return response()->json([
        'success' => true,
        'message' => 'Registration successful! Please login to continue.',
    ], 201);
}
    /**
     * Resend OTP
     * POST /api/register/resend-otp
     */
    public function resendOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'phone_number' => ['required', 'regex:/^[6-9]\d{9}$/'],
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $result = $this->otpService->sendOtp($request->phone_number, $request->ip()); // ← pass IP

    return response()->json($result, $result['success'] ? 200 : 500);
}
}
