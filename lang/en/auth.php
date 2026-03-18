<?php

return [
    'otp' => [
        'too_many_requests' => 'Please wait before requesting another OTP.',
        'not_found' => 'OTP not found.',
        'too_many_attempts' => 'Too many invalid OTP attempts. Please try again later.',
        'expired' => 'OTP has expired.',
        'invalid' => 'Invalid OTP.',
    ],
    'user_not_found' => 'User not found.',
    'login_failed' => 'Invalid email or password.',
    'login_email_not_verified' => 'Email address has not been verified.',
    'token_issue_failed' => 'Failed to issue authentication tokens.',
    'refresh_token_invalid' => 'Refresh token is invalid or expired.',
    'password' => [
        'current_invalid' => 'Current password is incorrect.',
        'forgot_failed' => 'Unable to send password reset link.',
        'reset_failed' => 'Unable to reset password.',
    ],
];
