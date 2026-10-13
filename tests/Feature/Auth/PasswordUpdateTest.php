<?php

namespace Tests\Feature\Auth;

use App\Models\AccountRecoveryCode;
use App\Models\User;
use App\Services\AccountRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $rememberToken = $user->remember_token;
        $oldCode = app(AccountRecoveryService::class)->generate($user);

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/password', [
                'current_password' => '123',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.recovery.replacement'));

        $this->assertTrue(Hash::check('NewPass123', $user->refresh()->password));
        $this->assertNotEquals($rememberToken, $user->remember_token);
        $this->assertDatabaseCount('account_recovery_codes', 1);
        $this->assertFalse(Hash::check($oldCode, AccountRecoveryCode::first()->code_hash));
        $this->assertDatabaseCount('admin_recovery_codes', 0);
        $this->assertGuest();
        $replacement = app(AccountRecoveryService::class)->revealDisplay(session('replacement_recovery_code'));
        $this->get(route('password.recovery.replacement'))->assertOk()->assertSee($replacement['code']);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/settings/password');
    }
}
