<?php

namespace Modules\Auth\Services;

use App\Exceptions\DomainException;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Passport;
use Modules\Auth\Repositories\Interfaces\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user and send verification email.
     *
     * @param  array{first_name: string, last_name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        $data['name'] = $data['first_name'].' '.$data['last_name'];
        $data['status'] = 'active';

        $user = $this->userRepository->create($data);
        $user->assignRole('customer');

        event(new Registered($user));

        Log::info('User registered', ['user_id' => $user->id, 'email' => $user->email]);

        return $user;
    }

    /**
     * Authenticate user and return Passport tokens.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{access_token: string, refresh_token: string, expires_in: int, token_type: string}
     *
     * @throws DomainException
     */
    public function login(array $credentials, string $ip): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new DomainException('Invalid credentials.', 401);
        }

        if ($user->status !== 'active') {
            throw new DomainException('Your account has been disabled.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            throw new DomainException('Please verify your email address before logging in.', 403);
        }

        $tokenResponse = $this->issueToken([
            'grant_type' => 'password',
            'username' => $credentials['email'],
            'password' => $credentials['password'],
            'scope' => '',
        ]);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        Log::info('User logged in', ['user_id' => $user->id, 'ip' => $ip]);

        return $tokenResponse;
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * @throws DomainException
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->issueToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => '',
        ]);
    }

    /**
     * Internal helper to issue tokens via Passport.
     *
     * @throws DomainException
     */
    protected function issueToken(array $params): array
    {
        $params = array_merge([
            'client_id' => config('auth.passport.client_id'),
            'client_secret' => config('auth.passport.client_secret'),
            'scope' => '',
        ], $params);

        // Create internal request to /oauth/token route
        $request = Request::create('/oauth/token', 'POST', $params);
        $response = app()->handle($request); // Send request and receive response

        $data = json_decode($response->getContent(), true);

        if (! $response->isSuccessful()) {
            Log::error('Passport Token Error', [
                'status' => $response->getStatusCode(),
                'response' => $data,
            ]);

            $message = $data['message'] ?? $data['error_description'] ?? 'Authentication failed.';
            throw new DomainException($message, $response->getStatusCode());
        }

        return $data;
    }

    /**
     * Logout the current user by revoking their token.
     */
    public function logout(User $user): void
    {
        $user->token()->revoke();

        Log::info('User logged out', ['user_id' => $user->id]);
    }

    /**
     * Update user profile data and optionally upload avatar.
     *
     * @param  array{first_name?: string, last_name?: string}  $data
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        $updateData = collect($data)->only(['first_name', 'last_name'])->toArray();

        if (isset($updateData['first_name']) || isset($updateData['last_name'])) {
            $firstName = $updateData['first_name'] ?? $user->first_name;
            $lastName = $updateData['last_name'] ?? $user->last_name;
            $updateData['name'] = trim("{$firstName} {$lastName}");
        }

        $this->userRepository->update($user->id, $updateData);

        if ($avatar) {
            $user->addMedia($avatar)->toMediaCollection('avatar');
        }

        Log::info('User profile updated', ['user_id' => $user->id]);

        return $user->fresh();
    }

    /**
     * Change the user's password.
     *
     * @throws DomainException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new DomainException('The current password is incorrect.', 422);
        }

        $this->userRepository->update($user->id, ['password' => $newPassword]);

        Log::info('User changed password', ['user_id' => $user->id]);
    }

    /**
     * Send a password reset link to the user's email.
     * Uses Laravel's built-in Password Broker.
     *
     * @throws DomainException
     */
    public function forgotPassword(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new DomainException(trans($status), 400);
        }

        Log::info('Password reset link sent', ['email' => $email]);
    }

    /**
     * Reset the user's password using a token.
     * Uses Laravel's built-in Password Broker.
     *
     * @param  array{token: string, email: string, password: string, password_confirmation: string}  $data
     *
     * @throws DomainException
     */
    public function resetPassword(array $data): void
    {
        $status = Password::reset($data, function ($user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw new DomainException(trans($status), 400);
        }

        Log::info('Password reset successful', ['email' => $data['email']]);
    }
}
