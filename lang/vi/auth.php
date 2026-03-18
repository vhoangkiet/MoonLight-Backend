<?php

return [
    'otp' => [
        'too_many_requests' => 'Vui lòng đợi trước khi gửi lại OTP.',
        'not_found' => 'Không tìm thấy OTP.',
        'too_many_attempts' => 'Quá nhiều lần nhập sai OTP. Vui lòng thử lại sau.',
        'expired' => 'OTP đã hết hạn.',
        'invalid' => 'OTP không hợp lệ.',
    ],
    'user_not_found' => 'Không tìm thấy người dùng.',
    'login_failed' => 'Email hoặc mật khẩu không đúng.',
    'login_email_not_verified' => 'Địa chỉ email chưa được xác minh.',
    'token_issue_failed' => 'Không thể cấp token xác thực.',
    'refresh_token_invalid' => 'Refresh token không hợp lệ hoặc đã hết hạn.',
    'password' => [
        'current_invalid' => 'Mật khẩu hiện tại không đúng.',
        'forgot_failed' => 'Không thể gửi link đặt lại mật khẩu.',
        'reset_failed' => 'Không thể đặt lại mật khẩu.',
    ],
];
