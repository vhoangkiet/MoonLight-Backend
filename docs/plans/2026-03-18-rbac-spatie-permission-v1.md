#!/usr/bin/env markdown
# RBAC (Spatie Permission) — Roles (V1) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement role-based access control using Spatie Permission with roles `Admin`, `Staff`, `Customer`, seed initial roles, and expose an admin endpoint to update a user's role, enforcing `Admin|Staff` on admin APIs.

**Architecture:** Keep the existing API layering (Controller → Form Request → Service → Repository) and standardized JSON responses via `ApiController::execute()` + `ApiResponse`. Use Spatie Permission for role storage/assignment and Laravel auth middleware for enforcing access.

**Tech Stack:** Laravel 12, PHP 8.3, `spatie/laravel-permission`, Passport auth (`auth:api`), PHPUnit feature tests.

---

### Task 1: Confirm Spatie Permission is installed & publish assets

**Files:**
- Create: `config/permission.php` (via vendor publish)
- Create: `database/migrations/*_create_permission_tables.php`

**Step 1: Confirm package exists**

Run:
- `composer show spatie/laravel-permission`

Expected: shows installed version.

**Step 2: Publish config**

Run:
- `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-config" --no-interaction`

Expected: creates `config/permission.php`.

**Step 3: Publish migrations**

Run:
- `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-migrations" --no-interaction`

Expected: creates `database/migrations/*_create_permission_tables.php`.

**Step 4: Run migrations**

Run:
- `php artisan migrate --no-interaction`

Expected: permission tables created (`roles`, `permissions`, `model_has_roles`, ...).

**Step 5: Commit**

Run:
- `git add config/permission.php database/migrations/*create_permission_tables.php`
- `git commit -m "chore(rbac): publish spatie permission config and migrations"`

---

### Task 2: Add roles capability to `User` model

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Auth/*` (ensure auth still passes)

**Step 1: Write failing test**

Create: `tests/Feature/Rbac/UserRoleTraitTest.php`

```php
<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_role(): void
    {
        Role::create(['name' => 'Admin']);

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->assertTrue($user->hasRole('Admin'));
    }
}
```

**Step 2: Run test to verify it fails**

Run:
- `php artisan test --compact tests/Feature/Rbac/UserRoleTraitTest.php`

Expected: FAIL because `assignRole` / `hasRole` missing.

**Step 3: Implement minimal code**

Modify `app/Models/User.php`:
- Add `use Spatie\Permission\Traits\HasRoles;`
- Add `use HasRoles;` to class traits

**Step 4: Run test to verify it passes**

Run:
- `php artisan test --compact tests/Feature/Rbac/UserRoleTraitTest.php`

Expected: PASS.

**Step 5: Run impacted auth tests**

Run:
- `php artisan test --compact tests/Feature/Auth/`

Expected: PASS.

**Step 6: Commit**

Run:
- `git add app/Models/User.php tests/Feature/Rbac/UserRoleTraitTest.php`
- `git commit -m "feat(rbac): add roles support to User"`

---

### Task 3: Seed base roles (Admin, Staff, Customer)

**Files:**
- Create: `database/seeders/RolesSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Rbac/RolesSeederTest.php`

**Step 1: Write failing test**

Create `tests/Feature/Rbac/RolesSeederTest.php`:

```php
<?php

namespace Tests\Feature\Rbac;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_seeder_creates_base_roles(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Staff']);
        $this->assertDatabaseHas('roles', ['name' => 'Customer']);
    }
}
```

**Step 2: Run test to verify it fails**

Run:
- `php artisan test --compact tests/Feature/Rbac/RolesSeederTest.php`

Expected: FAIL because seeder doesn’t exist.

**Step 3: Implement `RolesSeeder`**

Create `database/seeders/RolesSeeder.php`:
- Create `Admin`, `Staff`, `Customer` roles if missing.
- Optional: assign `Admin` to a configured email (e.g. `RBAC_BOOTSTRAP_ADMIN_EMAIL`), only if present.

**Step 4: Wire into `DatabaseSeeder`**

Modify `database/seeders/DatabaseSeeder.php` to call `RolesSeeder` before other seed logic.

**Step 5: Run test to verify it passes**

Run:
- `php artisan test --compact tests/Feature/Rbac/RolesSeederTest.php`

Expected: PASS.

**Step 6: Commit**

Run:
- `git add database/seeders/RolesSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/Rbac/RolesSeederTest.php`
- `git commit -m "feat(rbac): seed base roles"`

---

### Task 4: Admin API endpoint to update a user's role

**API:** `PATCH /api/v1/admin/users/{id}/role`

**Files:**
- Create: `app/Http/Controllers/Admin/UserRoleController.php`
- Create: `app/Http/Requests/Admin/UpdateUserRoleRequest.php`
- Create: `app/Services/Admin/UserRoleService.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Rbac/AdminUpdateUserRoleTest.php`

**Step 1: Write failing tests**

Create `tests/Feature/Rbac/AdminUpdateUserRoleTest.php` with cases:
- Customer calling endpoint → 403
- Staff calling endpoint → 200 and updates role
- Admin calling endpoint → 200 and updates role
- Invalid role input → 422

Use Passport login flow to obtain `access_token` or create user + token as per existing auth tests conventions.

**Step 2: Run test to verify it fails**

Run:
- `php artisan test --compact tests/Feature/Rbac/AdminUpdateUserRoleTest.php`

Expected: FAIL (route/controller missing).

**Step 3: Implement request validation**

Create `app/Http/Requests/Admin/UpdateUserRoleRequest.php`:
- Validate `role` is required and in `{Admin, Staff, Customer}`
- Use translations: `__('api.validation_failed')` style consistent with `BaseRequest`

**Step 4: Implement service**

Create `app/Services/Admin/UserRoleService.php`:
- Load `User` by id
- Assign role via `$user->syncRoles([$role])`
- Enforce policy “Staff cannot assign roles” if you want that restriction (doc says “tuỳ chính sách”). For V1, implement exactly as doc: allow Admin|Staff to call.

**Step 5: Implement controller**

Create `app/Http/Controllers/Admin/UserRoleController.php` extending `ApiController`:
- Call service
- Return `ApiResponse::ok()` with updated user summary (id, email, role)

**Step 6: Add route & middleware**

Modify `routes/api.php`:
- Add group `Route::prefix('admin')->as('admin.')->middleware(['auth:api', 'role:Admin|Staff'])`
- Register `PATCH users/{id}/role`

**Step 7: Run tests**

Run:
- `php artisan test --compact tests/Feature/Rbac/AdminUpdateUserRoleTest.php`

Expected: PASS.

**Step 8: Commit**

Run:
- `git add app/Http/Controllers/Admin/UserRoleController.php app/Http/Requests/Admin/UpdateUserRoleRequest.php app/Services/Admin/UserRoleService.php routes/api.php tests/Feature/Rbac/AdminUpdateUserRoleTest.php`
- `git commit -m "feat(rbac): add admin endpoint to update user role"`

---

### Task 5: Enforce `Admin|Staff` on future admin APIs

**Files:**
- Modify: `routes/api.php`
- Test: Add one smoke test for 403 on admin prefix if route exists

**Step 1: Ensure the admin route group uses `role:Admin|Staff`**

If you later add admin CRUD catalog endpoints, place them inside the same admin group.

**Step 2: Optional throttling**

Consider adding `throttle` middleware on role update endpoint (e.g. `throttle:30,1`) if needed.

---

### Task 6: Formatting & full verification

**Step 1: Run Pint**

Run:
- `./vendor/bin/pint --dirty --format agent`

Expected: `{"result":"fixed"}` or `{"result":"pass"}`

**Step 2: Run full test suite**

Run:
- `php artisan test --compact`

Expected: PASS.

---

## Execution handoff

Plan complete and saved to `docs/plans/2026-03-18-rbac-spatie-permission-v1.md`. Two execution options:

1. **Subagent-Driven (this session)** — dispatch fresh subagent per task, review between tasks
2. **Parallel Session (separate)** — open a new session using superpowers:executing-plans and run tasks with checkpoints

Which approach?

