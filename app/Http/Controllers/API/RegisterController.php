<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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

        // Check if already registered
        $exists = DB::table('users')
            ->where('phone_number', $request->phone_number)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered.',
            ], 409);
        }

        $result = $this->otpService->sendOtp($request->phone_number);

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
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $phone = $request->phone_number;

    // Check OTP was verified
    if (!$this->otpService->isPhoneVerified($phone)) {
        return response()->json([
            'success' => false,
            'message' => 'Phone number not verified. Please verify OTP first.',
        ], 403);
    }

    $this->otpService->clearVerified($phone);

    $now = now();

    DB::table('users')->insert([
        'uuid'            => (string) Str::uuid(),
        'name'            => $request->name,
        'slug'            => Str::slug($request->name) . '-' . uniqid(),
        'email'           => $phone . '@placeholder.local',
        'phone_number'    => $phone,
        'password'        => Hash::make($request->password),
        'role'            => 'student',
        'role_short_form' => 'STD',
        'status'          => 'active',
        'created_at_ip'   => $request->ip(),
        'created_at'      => $now,
        'updated_at'      => $now,
    ]);

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

        $result = $this->otpService->sendOtp($request->phone_number);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}