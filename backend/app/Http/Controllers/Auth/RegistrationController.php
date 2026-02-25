<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    // POST /register (web, CSRF) — backend-only
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Gán role mặc định GV (nếu có)
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('LECTURER');
        }

        event(new Registered($user)); // gửi email verify

        return response()->json(['message' => 'registered'], Response::HTTP_CREATED);
    }

    // GET /email/verify/{id}/{hash} (signed)
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return response()->json(['message' => 'verified'], Response::HTTP_OK);
    }

    // POST /email/verification-notification (đòi hỏi auth)
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'already verified'], Response::HTTP_BAD_REQUEST);
        }
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'sent'], Response::HTTP_OK);
    }
}
