<?php

namespace Tests\Feature\Auth;

use App\Models\AccountRecoveryCode;
use App\Models\User;
use App\Services\AccountRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['mail.mailers.smtp.password' => null]);
    }

    private function verifyCode(User $user, ?string $code = null): array
    {
        $code ??= app(AccountRecoveryService::class)->generate($user);
        $this->post(route('password.recovery.verify'), ['username' => $user->username, 'recovery_code' => $code])
            ->assertSessionHasNoErrors()->assertRedirect(route('password.reset'));

        return session('account_password_reset_grant');
    }

    public static function roles(): array
    {
        return [['Admin'], ['Employee']];
    }

    #[DataProvider('roles')]
    public function test_each_role_can_generate_a_private_code_with_the_current_password(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get(route('settings.password'))->assertOk()->assertSee('Generate Recovery Code');
        $this->post(route('password.recovery.generate'), ['current_password' => '123'])
            ->assertSessionHasNoErrors()->assertRedirect(route('settings.password'));
        $encrypted = session('generated_recovery_code');
        $display = app(AccountRecoveryService::class)->revealDisplay($encrypted);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}(-[A-Z0-9]{4}){3}$/', $display['code']);
        $this->assertNotEquals($display['code'], $encrypted['code']);
        $this->assertTrue(Hash::check($display['code'], AccountRecoveryCode::first()->code_hash));
        $this->get(route('settings.password'))->assertOk()->assertSee($display['code'])->assertSee('Save Code (.txt)')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('settings.password'))->assertOk()->assertDontSee($display['code']);
        $this->assertDatabaseCount('account_recovery_codes', 1);
        Mail::assertNothingSent();
    }

    #[DataProvider('roles')]
    public function test_recovery_is_offline_one_time_and_rotates_code_after_password_reset(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $oldCode = app(AccountRecoveryService::class)->generate($user);
        $rememberToken = $user->remember_token;
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $grant = $this->verifyCode($user, $oldCode);
        $this->get(route('password.reset'))->assertOk()->assertSee('Create New Password');
        $this->post(route('password.store'), ['password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHasNoErrors()->assertRedirect(route('password.recovery.replacement'));
        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
        $this->assertNotEquals($rememberToken, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertDatabaseCount('account_recovery_codes', 1);
        $this->assertFalse(Hash::check($oldCode, AccountRecoveryCode::first()->code_hash));
        $this->assertNull(app(AccountRecoveryService::class)->validGrant($grant));
        $display = app(AccountRecoveryService::class)->revealDisplay(session('replacement_recovery_code'));
        $this->get(route('password.recovery.replacement'))->assertOk()->assertSee($display['code'])
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('password.recovery.replacement'))->assertRedirect(route('login'));
        $this->post(route('password.recovery.verify'), ['username' => $user->username, 'recovery_code' => $oldCode])
            ->assertSessionHasErrors('recovery_code');
        $this->verifyCode($user, $display['code']);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('admin_recovery_codes', 0);
        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    public function test_generation_requires_authentication_and_the_current_password(): void
    {
        $this->post(route('password.recovery.generate'), ['current_password' => '123'])->assertRedirect(route('login'));
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('password.recovery.generate'), ['current_password' => 'wrong'])
            ->assertSessionHasErrorsIn('generateRecoveryCode', 'current_password');
        $this->assertDatabaseCount('account_recovery_codes', 0);
    }

    public function test_regenerating_invalidates_the_previous_code_and_pending_reset(): void
    {
        $user = User::factory()->create();
        $old = app(AccountRecoveryService::class)->generate($user);
        $grant = $this->verifyCode($user, $old);
        $new = app(AccountRecoveryService::class)->generate($user);
        $this->assertNotEquals($old, $new);
        $this->assertNull(app(AccountRecoveryService::class)->validGrant($grant));
        $this->assertNull(app(AccountRecoveryService::class)->verify($user->username, $old));
        $this->assertNotNull(app(AccountRecoveryService::class)->verify($user->username, $new));
    }

    public function test_five_wrong_guesses_lock_recovery_for_fifteen_minutes(): void
    {
        $user = User::factory()->create();
        $code = app(AccountRecoveryService::class)->generate($user);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.recovery.verify'), ['username' => $user->username, 'recovery_code' => 'AAAA-BBBB-CCCC-DDDD'])
                ->assertSessionHasErrors('recovery_code');
        }
        $this->assertSame(5, AccountRecoveryCode::first()->attempts);
        $this->assertNull(app(AccountRecoveryService::class)->verify($user->username, $code));
        $this->travel(15)->minutes();
        $this->assertNotNull(app(AccountRecoveryService::class)->verify($user->username, $code));
        $this->assertSame(0, AccountRecoveryCode::first()->attempts);
    }

    public function test_saved_codes_do_not_expire_but_verified_reset_grants_do(): void
    {
        $user = User::factory()->create();
        $code = app(AccountRecoveryService::class)->generate($user);
        $this->travel(1)->years();
        $grant = $this->verifyCode($user, strtolower($code));
        $this->travel(10)->minutes();
        $this->withSession(['account_password_reset_grant' => $grant])->post(route('password.store'), [
            'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('password.request'));
        $this->verifyCode($user, $code);
        $this->assertTrue(Hash::check('123', $user->fresh()->password));
    }

    public function test_unknown_disabled_and_missing_code_accounts_cannot_reset(): void
    {
        $disabled = User::factory()->create(['is_active' => false]);
        app(AccountRecoveryService::class)->generate($disabled);
        $missing = User::factory()->create();
        foreach (['unknown', $disabled->username, $missing->username] as $username) {
            $this->post(route('password.recovery.verify'), ['username' => $username, 'recovery_code' => 'AAAA-BBBB-CCCC-DDDD'])
                ->assertSessionHasErrors('recovery_code');
        }
        $this->get(route('password.reset'))->assertRedirect(route('password.request'));
        Mail::assertNothingSent();
    }

    public function test_old_code_sessions_and_forged_grants_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->withSession(['password_reset_verified_user_id' => $user->id])->post(route('password.store'), [
            'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('password.request'));
        $grant = $this->verifyCode($user);
        $grant['token'] = 'forged';
        $this->withSession(['account_password_reset_grant' => $grant])->post(route('password.store'), [
            'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('password.request'));
        $this->assertTrue(Hash::check('123', $user->fresh()->password));
    }

    public function test_disabling_an_account_invalidates_the_verified_reset(): void
    {
        $user = User::factory()->create(['role' => 'Employee']);
        $grant = $this->verifyCode($user);
        $admin = User::factory()->create();
        $this->actingAs($admin)->patch(route('security.users.access', $user))->assertSessionHas('success');
        $this->assertNull(app(AccountRecoveryService::class)->validGrant($grant));
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_password_rules_and_confirmation_are_preserved(): void
    {
        $user = User::factory()->create(['password' => 'Current123']);
        $this->verifyCode($user);
        foreach ([['Current123', 'Current123'], ['short', 'short'], ['NewPass123', 'Different123'], ['BadPass!!', 'BadPass!!']] as [$password, $confirmation]) {
            $this->post(route('password.store'), ['password' => $password, 'password_confirmation' => $confirmation])
                ->assertSessionHasErrors('password');
        }
        $this->assertTrue(Hash::check('Current123', $user->fresh()->password));
    }

    public function test_recovery_code_is_not_flashed_back_on_invalid_input(): void
    {
        $this->post(route('password.recovery.verify'), ['username' => 'test', 'recovery_code' => 'not-valid'])
            ->assertSessionHasErrors('recovery_code')->assertSessionMissing('_old_input.recovery_code');
        $this->post(route('password.recovery.verify'), ['username' => ['bad'], 'recovery_code' => 'AAAA-BBBB-CCCC-DDDD'])
            ->assertSessionHasErrors('username');
    }

    public function test_new_employee_receives_a_private_code_at_account_creation(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->post(route('security.users.store'), [
            'name' => 'New Employee', 'username' => 'newemployee', 'email' => 'employee@example.com',
            'password' => 'Employee123', 'password_confirmation' => 'Employee123', 'admin_password' => '123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('security.index'));
        $employee = User::where('username', 'newemployee')->firstOrFail();
        $display = app(AccountRecoveryService::class)->revealDisplay(session('created_employee_recovery_code'));
        $this->assertTrue(Hash::check($display['code'], $employee->recoveryCode->code_hash));
        $this->get(route('security.index'))->assertOk()->assertSee($display['code']);
        $this->get(route('security.index'))->assertOk()->assertDontSee($display['code']);
        Mail::assertNothingSent();
    }

    public function test_gmail_otp_and_admin_approval_routes_are_removed(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Verify Recovery Code')->assertDontSee('Send OTP');
        $this->get('/forgot-password/verify-otp')->assertNotFound();
        $this->get('/forgot-password/verify-code')->assertNotFound();
        $this->get('/password/new-recovery-codes')->assertNotFound();
        $this->post('/recovery-codes/download')->assertNotFound();
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('security.index'))->assertOk()->assertDontSee('Admin Recovery Codes')
            ->assertDontSee('Employee Password Reset Requests');
        $this->post('/security/recovery-codes/regenerate')->assertNotFound();
        $this->post('/security/password-reset/1/approve')->assertNotFound();
    }
}
