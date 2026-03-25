<?php

namespace Modules\Auth\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class VerificationController extends BaseController
{
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        $frontendUrl = env('FRONTEND_URL', config('app.url'));

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->to($frontendUrl.'/verify-status?status=error&message=Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->to($frontendUrl.'/verify-status?status=success&message=Email already verified');
        }

        $user->markEmailAsVerified();

        return redirect()->to($frontendUrl.'/verify-status?status=success&message=Email verified successfully');
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request): JsonResponse {
            $user = $request->user();

            if ($user->hasVerifiedEmail()) {
                return $this->successResponse(null, 'Email already verified.');
            }

            $user->sendEmailVerificationNotification();

            return $this->successResponse(null, 'Verification email resent.');
        });
    }
}
