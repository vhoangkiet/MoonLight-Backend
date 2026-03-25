<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->uncompromised();
        });

        Passport::tokensExpireIn(CarbonInterval::days(15));
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::personalAccessTokensExpireIn(CarbonInterval::months(6));

        Log::debug('Enabling Passport Password Grant');
        Passport::enablePasswordGrant();

        // Customize Verification Email
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('🌖 Chào mừng fen đến với MoonLight!')
                ->greeting('Chào '.($notifiable->first_name ?: 'fen').' ơi!')
                ->line('Rất vui vì fen đã đồng ý làm một thành viên của MoonLight.')
                ->line('Để bắt đầu "quẩy", fen vui lòng bấm nút xác thực email bên dưới giúp mình nhé:')
                ->action('🚀 Xác thực tài khoản ngay', $url)
                ->line('Link này sẽ hết hạn sau '.config('auth.verification.expire', 60).' phút nha.')
                ->line('Nếu fen không đăng ký tài khoản thì hãy cứ bỏ qua mail này, hông sao hết nè!')
                ->salutation('Trân trọng,'.PHP_EOL.'Team MoonLight 🌓');
        });

        // Customize Reset Password Email
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('🌖 Yêu cầu đặt lại mật khẩu MoonLight')
                ->greeting('Chào fen!')
                ->line('Có vẻ như fen đã quên mật khẩu của mình?')
                ->line('Đừng lo, chuyện này xảy ra như cơm bữa ấy mà. Bấm nút dưới đây để đặt lại mật khẩu mới nha:')
                ->action('🔑 Đặt lại mật khẩu', $url)
                ->line('Link này sẽ hết hạn sau '.config('auth.passwords.users.expire').' phút.')
                ->line('Nếu fen hổng có yêu cầu đổi mật khẩu, thì cứ kệ cái mail này, mật khẩu cũ vẫn an toàn nhé!')
                ->salutation('Thân ái,'.PHP_EOL.'Team MoonLight 🌓');
        });
    }
}
