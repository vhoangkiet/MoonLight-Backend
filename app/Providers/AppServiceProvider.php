<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        Passport::enablePasswordGrant();

        // Customize Verification Email
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('🌖 Welcome to MoonLight!')
                ->greeting('Hello '.($notifiable->first_name ?: 'friend').'!')
                ->line('We are very happy that you have agreed to become a member of MoonLight.')
                ->line('To get started, please click the email verification button below:')
                ->action('🚀 Verify Account Now', $url)
                ->line('This link will expire after '.config('auth.verification.expire', 60).' minutes.')
                ->line('If you did not register an account, please ignore this email, it is okay!')
                ->salutation('Best regards,'.PHP_EOL.'MoonLight Team 🌓');
        });

        // Customize Reset Password Email
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('🌖 MoonLight Password Reset Request')
                ->greeting('Hello friend!')
                ->line('It seems you have forgotten your password?')
                ->line('Don\'t worry, this happens all the time. Click the button below to reset your password:')
                ->action('🔑 Reset Password', $url)
                ->line('This link will expire after '.config('auth.passwords.users.expire', 60).' minutes.')
                ->line('If you did not request a password reset, please ignore this email, your old password is still safe!')
                ->salutation('Best regards,'.PHP_EOL.'MoonLight Team 🌓');
        });

        // Configure Bearer Token for Scramble
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });
    }
}
