<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginLockoutAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_user_can_login_with_username_and_password_123(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
            'password' => bcrypt('123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => '123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_cashier_redirects_to_pos_from_root(): void
    {
        $cashier = User::factory()->create([
            'username' => 'cashier',
            'role' => 'Cashier',
            'password' => bcrypt('123'),
        ]);

        $response = $this->actingAs($cashier)->get('/');
        $response->assertRedirect(route('pos.index'));
    }

    public function test_escalating_lockout_after_3_failed_attempts(): void
    {
        User::factory()->create([
            'username' => 'lockout_user',
            'password' => bcrypt('123'),
        ]);

        // Attempt 1: Fail
        $res1 = $this->from('/login')->post('/login', [
            'username' => 'lockout_user',
            'password' => 'wrong',
        ]);
        $res1->assertSessionHasErrors('username');

        // Attempt 2: Fail
        $res2 = $this->from('/login')->post('/login', [
            'username' => 'lockout_user',
            'password' => 'wrong',
        ]);
        $res2->assertSessionHasErrors('username');

        // Attempt 3: Fail -> Triggers 1 minute lockout
        $res3 = $this->from('/login')->post('/login', [
            'username' => 'lockout_user',
            'password' => 'wrong',
        ]);
        $res3->assertSessionHasErrors('username');
        $this->assertTrue(str_contains(session('errors')->first('username'), 'blocked for 1 minute'));

        // Attempt 4 while locked out: Should be blocked immediately
        $res4 = $this->from('/login')->post('/login', [
            'username' => 'lockout_user',
            'password' => '123', // even with correct password, blocked while lockout is active
        ]);
        $res4->assertSessionHasErrors('username');
        $this->assertTrue(str_contains(session('errors')->first('username'), 'temporarily blocked'));
    }

    public function test_cashier_cannot_access_admin_routes(): void
    {
        $cashier = User::factory()->create([
            'username' => 'cashier_user',
            'role' => 'Cashier',
        ]);

        // Accessing Backup -> 403 Forbidden
        $responseBackup = $this->actingAs($cashier)->get('/backup');
        $responseBackup->assertStatus(403);

        // Accessing Dashboard -> 403 Forbidden
        $responseDash = $this->actingAs($cashier)->get('/dashboard');
        $responseDash->assertStatus(403);
    }

    public function test_cashier_can_access_pos_and_inventory(): void
    {
        $cashier = User::factory()->create([
            'username' => 'cashier_user2',
            'role' => 'Cashier',
        ]);

        // Accessing POS -> 200 OK
        $responsePos = $this->actingAs($cashier)->get('/pos');
        $responsePos->assertStatus(200);

        // Accessing Products list -> 200 OK
        $responseProducts = $this->actingAs($cashier)->get('/products');
        $responseProducts->assertStatus(200);

        // Accessing Stock-In list -> 200 OK
        $responseStockIn = $this->actingAs($cashier)->get('/stock-in');
        $responseStockIn->assertStatus(200);
    }

    public function test_admin_has_full_access(): void
    {
        $admin = User::factory()->create([
            'username' => 'superadmin',
            'role' => 'Admin',
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/backup')->assertStatus(200);
        $this->actingAs($admin)->get('/pos')->assertStatus(200);
        $this->actingAs($admin)->get('/products')->assertStatus(200);
    }

    public function test_tier_2_lockout_blocks_for_5_minutes(): void
    {
        User::factory()->create([
            'username' => 'tier2_user',
            'password' => bcrypt('123'),
        ]);

        $throttleKey = \Illuminate\Support\Str::transliterate('tier2_user|127.0.0.1');
        // Simulate already having 1 previous lockout tier (3 attempts already occurred)
        Cache::put('login_attempts:' . $throttleKey, 5); // 5 attempts so next is 6th
        Cache::put('login_tier:' . $throttleKey, 1);

        $response = $this->from('/login')->post('/login', [
            'username' => 'tier2_user',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertTrue(str_contains(session('errors')->first('username'), '5 minute(s)'));
    }

    public function test_tier_3_lockout_blocks_for_10_minutes(): void
    {
        User::factory()->create([
            'username' => 'tier3_user',
            'password' => bcrypt('123'),
        ]);

        $throttleKey = \Illuminate\Support\Str::transliterate('tier3_user|127.0.0.1');
        // Simulate already having 2 previous lockout tiers (8 attempts already occurred)
        Cache::put('login_attempts:' . $throttleKey, 8);
        Cache::put('login_tier:' . $throttleKey, 2);

        $response = $this->from('/login')->post('/login', [
            'username' => 'tier3_user',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertTrue(str_contains(session('errors')->first('username'), '10 minute(s)'));
    }

    public function test_tier_4_lockout_blocks_for_20_minutes(): void
    {
        User::factory()->create([
            'username' => 'tier4_user',
            'password' => bcrypt('123'),
        ]);

        $throttleKey = \Illuminate\Support\Str::transliterate('tier4_user|127.0.0.1');
        // Simulate already having 3 previous lockout tiers (11 attempts already occurred)
        Cache::put('login_attempts:' . $throttleKey, 11);
        Cache::put('login_tier:' . $throttleKey, 3);

        $response = $this->from('/login')->post('/login', [
            'username' => 'tier4_user',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertTrue(str_contains(session('errors')->first('username'), '20 minute(s)'));
    }
}
