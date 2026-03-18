# Auth email + OTP + Sanctum Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement email-based authentication with OTP email verification and Sanctum session tokens, following the `docs/02-auth-otp-sanctum.md` spec end-to-end (controllers, requests, services, repositories, models, resources, routes, and feature tests).

**Architecture:** Use a layered structure Controller → Form Request → Service → Repository → Model, with `DomainException` for business errors and `ApiController::execute()` for consistent JSON responses. OTPs are stored hashed in `otp_verifications`, email-verified state lives on `users`, and Sanctum tokens represent sessions, all exposed via versioned `/api/v1/auth/*` routes and Laravel API Resources.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent models, Sanctum, Laravel Form Requests, Laravel API Resources, PHPUnit feature tests.

---

### Task 1: Database schema for OTP verifications

**Files:**
- Create: `database/migrations/2026_03_18_000100_create_otp_verifications_table.php`
- Modify (reference only, no change): `database/migrations/0001_01_01_000000_create_users_table.php`
- Test: `tests/Feature/Auth/OtpMigrationTest.php`

**Step 1: Write migration for `otp_verifications` table**

- Add columns: `id` (bigint, PK), `email` (string, index), `otp_hash` (string), `expires_at` (timestamp), `attempts` (unsigned tiny integer, default 0), `last_sent_at` (timestamp, nullable), `status` (string, default `active`), and timestamps.
- Add index on `email`.

**Step 2: Write minimal migration test (optional / smoke)**

- Create `tests/Feature/Auth/OtpMigrationTest.php` with a test ensuring the `otp_verifications` table exists and has expected columns using `Schema::hasColumns`.

**Step 3: Run migrations and tests**

- Run: `php artisan migrate --env=testing --no-interaction`
- Run: `php artisan test --compact tests/Feature/Auth/OtpMigrationTest.php`

**Step 4: Commit**

- `git add database/migrations tests/Feature/Auth/OtpMigrationTest.php`
- `git commit -m "feat(auth): add otp_verifications table"`

---

### Task 2: OTP verification model & repository

**Files:**
- Create: `app/Models/OtpVerification.php`
- Create: `app/Repositories/OtpVerificationRepository.php`
- Modify: `app/Models/User.php` (ensure `HasPublicUuid` trait and any needed relationship, if we later link by email only no relationship needed)
- Test: `tests/Feature/Auth/OtpVerificationRepositoryTest.php`

**Step 1: Implement `OtpVerification` model**

- Extend `BaseModel` (or `Model` following existing `User` pattern).
- Set fillable or guarded fields as appropriate (email, otp_hash, expires_at, attempts, last_sent_at, status).
- Configure table name `otp_verifications` if not using default.

**Step 2: Implement `OtpVerificationRepository`**

- Create methods like:
  - `findActiveByEmail(string $email): ?OtpVerification`
  - `createOrRefresh(string $email, string $otpHash, \DateTimeInterface $expiresAt, ?\DateTimeInterface $lastSentAt = null): OtpVerification`
  - `incrementAttempts(OtpVerification $otp): OtpVerification`
  - `markVerified(OtpVerification $otp): OtpVerification`
  - `markExpired(OtpVerification $otp): OtpVerification`
  - `lock(OtpVerification $otp): OtpVerification`

**Step 3: Write repository tests**

- In `tests/Feature/Auth/OtpVerificationRepositoryTest.php`, seed fake OTP records and assert repository methods behave as expected (create/refresh, status transitions, attempt increments).

**Step 4: Run tests**

- Run: `php artisan test --compact tests/Feature/Auth/OtpVerificationRepositoryTest.php`

**Step 5: Commit**

- `git add app/Models/OtpVerification.php app/Repositories/OtpVerificationRepository.php tests/Feature/Auth/OtpVerificationRepositoryTest.php`
- `git commit -m "feat(auth): add otp verification model and repository"`

---

### Task 3: Auth routes wiring (v1)

**Files:**
- Modify: `bootstrap/app.php` (ensure routes file for API v1 is registered if needed)
- Create: `routes/api.php` or `routes/api_v1_auth.php` (depending on existing convention in project)
- Test: `tests/Feature/Auth/AuthRoutesTest.php`

**Step 1: Inspect and configure routes**

- If there is already an `api` routes file configured in `bootstrap/app.php`, append the new auth routes to that file.
- Else, register a new routes file (e.g. `routes/api.php`) in `bootstrap/app.php` under the `withRouting()` section.

**Step 2: Define auth endpoints**

- Under `/api/v1/auth` prefix (using `Route::prefix('api/v1/auth')`):
  - `POST /register`
  - `POST /register/request-otp`
  - `POST /register/verify-otp`
  - `POST /login`
  - `GET /sessions` (auth:sanctum)
  - `POST /logout` (auth:sanctum)
  - `POST /logout-all` (auth:sanctum)
  - `POST /password/change` (auth:sanctum)
  - `POST /password/forgot`
  - `POST /password/reset`

**Step 3: Route tests**

- In `tests/Feature/Auth/AuthRoutesTest.php`, assert that each route responds with the correct HTTP method and is reachable (e.g. no 404), possibly using `Route::has` or making simple requests and asserting non-404 status.

**Step 4: Run tests**

- Run: `php artisan test --compact tests/Feature/Auth/AuthRoutesTest.php`

**Step 5: Commit**

- `git add bootstrap/app.php routes tests/Feature/Auth/AuthRoutesTest.php`
- `git commit -m "feat(auth): add v1 auth routes"`

---

### Task 4: Form Requests for auth flows

**Files:**
- Create: `app/Http/Requests/Auth/RegisterRequest.php`
- Create: `app/Http/Requests/Auth/RequestOtpRequest.php`
- Create: `app/Http/Requests/Auth/VerifyOtpRequest.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Requests/Auth/ChangePasswordRequest.php`
- Create: `app/Http/Requests/Auth/ForgotPasswordRequest.php`
- Create: `app/Http/Requests/Auth/ResetPasswordRequest.php`
- Modify: language files `lang/en/api.php`, `lang/vi/api.php` for validation messages if needed
- Test: `tests/Feature/Auth/AuthFormRequestsTest.php`

**Step 1: Implement each Form Request**

- Extend `BaseRequest`.
- Implement `rules()` and `messages()` using translation keys, e.g.:
  - `RegisterRequest`: `email` (required, email, unique:users,email), `password` (required, string, min:8).
  - `RequestOtpRequest`: `email` (required, email, exists:users,email).
  - `VerifyOtpRequest`: `email` (required, email), `otp` (required, digits:6).
  - `LoginRequest`: `email` (required, email), `password` (required, string).
  - `ChangePasswordRequest`: `current_password`, `password` (confirmed, min length).
  - `ForgotPasswordRequest`: `email` (required, email, exists).
  - `ResetPasswordRequest`: `token`, `email`, `password` (confirmed).

**Step 2: Add translation messages**

- Add keys under `auth.validation.*` or similar in `lang/en/api.php` and `lang/vi/api.php` for the messages referenced in `messages()`.

**Step 3: Form Request tests**

- In `tests/Feature/Auth/AuthFormRequestsTest.php`, test at least one happy and one failure case per request (e.g. missing fields, invalid formats) by hitting the routes and asserting `422` with the validation error structure from `ApiResponse::validationError`.

**Step 4: Run tests**

- Run: `php artisan test --compact tests/Feature/Auth/AuthFormRequestsTest.php`

**Step 5: Commit**

- `git add app/Http/Requests/Auth lang tests/Feature/Auth/AuthFormRequestsTest.php`
- `git commit -m "feat(auth): add form requests and validation messages"`

---

### Task 5: Auth service and domain logic

**Files:**
- Create: `app/Services/Auth/AuthService.php`
- Modify: `app/Models/User.php` (ensure `email_verified_at`, `status` usage where needed)
- Modify: `config/auth.php` if Sanctum guards need configuration
- Test: `tests/Feature/Auth/AuthServiceRegisterLoginTest.php`, `tests/Feature/Auth/AuthServiceOtpTest.php`

**Step 1: Implement `AuthService` skeleton**

- Methods (signatures, later filled in):
  - `register(string $email, string $password): void`
  - `requestRegisterOtp(string $email): void`
  - `verifyRegisterOtp(string $email, string $otp): void`
  - `login(string $email, string $password): array` (returns data for `LoginResource`)
  - `listSessions(User $user): array`
  - `logoutCurrent(User $user): void`
  - `logoutAll(User $user): void`
  - `changePassword(User $user, string $currentPassword, string $newPassword): void`
  - `forgotPassword(string $email): void`
  - `resetPassword(string $email, string $token, string $password): void`

**Step 2: Implement OTP policy logic**

- Apply policy from spec:
  - OTP is 6 digits, generated randomly.
  - TTL 10 minutes.
  - Resend cooldown 60 seconds (if last_sent_at within 60s, throw `DomainException` with e.g. `auth.otp.cooldown`).
  - Max attempts 5; after exceeding, mark OTP as locked and throw `DomainException` with `auth.otp.locked`.
- Hash OTP before saving (e.g. using `hash('sha256', ...)` or `password_hash`) and compare hashed values only.
- Throw `DomainException` with appropriate `status`, `translationKey`, `code`, and context when:
  - OTP invalid.
  - OTP expired.
  - OTP locked.
  - User already verified.
  - User blocked.

**Step 3: Integrate Sanctum token management**

- Use `$user->createToken('auth')` to create tokens and return token string plus session metadata.
- For `listSessions`, return an array of sessions (e.g. token name, created at, last used at if available).
- For `logoutCurrent`, delete the current access token.
- For `logoutAll`, delete all tokens for the user.

**Step 4: Implement register/login flows**

- `register`:
  - Create user with `status = 'active'`, `email_verified_at = null`, hashed password.
  - Create or refresh OTP via `OtpVerificationRepository` and dispatch email (placeholder for mailing).
- `verifyRegisterOtp`:
  - Validate OTP via repository and policy.
  - If valid, set `email_verified_at` on user and mark OTP as verified.
- `login`:
  - Ensure `email_verified_at` not null and `status` is not `blocked`.
  - Verify password; on success, create Sanctum token and return payload for `LoginResource`.

**Step 5: Service tests**

- In `tests/Feature/Auth/AuthServiceRegisterLoginTest.php`:
  - Happy path: `register → request OTP → verify → login`, asserting expected DB changes and token creation.
  - Login blocked if email not verified or status blocked.
- In `tests/Feature/Auth/AuthServiceOtpTest.php`:
  - OTP expired case.
  - Incorrect OTP attempts up to lock.
  - Resend cooldown enforced.

**Step 6: Run tests**

- Run: `php artisan test --compact tests/Feature/Auth/AuthServiceRegisterLoginTest.php tests/Feature/Auth/AuthServiceOtpTest.php`

**Step 7: Commit**

- `git add app/Services/Auth/AuthService.php app/Repositories/OtpVerificationRepository.php tests/Feature/Auth/AuthService*`
- `git commit -m "feat(auth): implement auth service with OTP policy and Sanctum"`

---

### Task 6: Auth controller and resource

**Files:**
- Create: `app/Http/Controllers/Auth/AuthController.php`
- Create: `app/Http/Resources/Auth/LoginResource.php`
- Modify: `routes/api.php` (wire routes to controller methods)
- Test: `tests/Feature/Auth/AuthControllerTest.php`

**Step 1: Implement `AuthController`**

- Extend `ApiController`.
- Inject `AuthService` in the constructor via type-hint.
- Implement methods:
  - `register(RegisterRequest $request)`
  - `requestOtp(RequestOtpRequest $request)`
  - `verifyOtp(VerifyOtpRequest $request)`
  - `login(LoginRequest $request)`
  - `sessions(Request $request)` (current authenticated user via Sanctum)
  - `logout(Request $request)`
  - `logoutAll(Request $request)`
  - `changePassword(ChangePasswordRequest $request)`
  - `forgotPassword(ForgotPasswordRequest $request)`
  - `resetPassword(ResetPasswordRequest $request)`
- Each method should call `$this->execute(fn () => ...)` and return either `ApiResponse::ok()` data or a `LoginResource`.

**Step 2: Implement `LoginResource`**

- Under `App\Http\Resources\Auth`, return:
  - `user` (using `uuid` and selected public fields).
  - `token` (Sanctum plain-text token).
  - Optional extra fields for roles/permissions in future (keep structure extensible).

**Step 3: Wire routes to controller**

- Update `routes/api.php` to point each auth route to corresponding controller method, applying `auth:sanctum` middleware to protected ones.

**Step 4: Controller tests**

- In `tests/Feature/Auth/AuthControllerTest.php`, write end-to-end tests using HTTP JSON calls to:
  - Register and assert 200 and OTP record creation.
  - Request OTP and verify constraints like cooldown.
  - Verify OTP and assert `email_verified_at` set.
  - Login and assert structure of `LoginResource` response and token existence.
  - Sessions listing, logout, logout-all, password change, forgot/reset flows.

**Step 5: Run tests**

- Run: `php artisan test --compact tests/Feature/Auth/AuthControllerTest.php`

**Step 6: Commit**

- `git add app/Http/Controllers/Auth/AuthController.php app/Http/Resources/Auth/LoginResource.php routes/api.php tests/Feature/Auth/AuthControllerTest.php`
- `git commit -m "feat(auth): add auth controller and login resource"`

---

### Task 7: Translations, error messages, and API response consistency

**Files:**
- Modify: `lang/en/api.php`
- Modify: `lang/vi/api.php`
- Possibly modify: `app/Support/Api/ApiResponse.php` or `ApiUtil` if new helpers needed
- Test: Covered by previous feature tests asserting translated messages and codes

**Step 1: Add translation keys**

- Add keys for domain errors, e.g.:
  - `auth.otp.invalid`
  - `auth.otp.expired`
  - `auth.otp.locked`
  - `auth.otp.cooldown`
  - `auth.login.blocked`
  - `auth.login.unverified`
  - `auth.password.invalid`

**Step 2: Align error codes**

- Ensure `DomainException` usages in `AuthService` use consistent `code` values (e.g. `OTP_INVALID`, `OTP_EXPIRED`, `USER_BLOCKED`, etc.).

**Step 3: Verify via tests**

- Re-run all auth-related tests, ensuring assertions inspect `code` and `message` fields from `ApiResponse`.

**Step 4: Commit**

- `git add lang/en/api.php lang/vi/api.php app/Services/Auth/AuthService.php`
- `git commit -m "feat(auth): add auth translation messages and error codes"`

---

### Task 8: Final integration, linting, and regression tests

**Files:**
- No new files; use existing ones
- Commands only

**Step 1: Run Pint on changed PHP files**

- Run: `./vendor/bin/pint --dirty --format agent`

**Step 2: Run targeted auth tests**

- Run: `php artisan test --compact tests/Feature/Auth`

**Step 3: Run full test suite (optional but recommended)**

- Run: `php artisan test --compact`

**Step 4: Final commit (if needed)**

- If there were any remaining adjustments: `git add .` and `git commit -m "chore(auth): finalize auth otp sanctum module"`

