<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_admin_with_the_given_credentials(): void
    {
        $this->artisan('cv:admin', ['--email' => 'admin@example.com', '--password' => 'a-long-password', '--no-interaction' => true])
            ->expectsOutput('Admin admin@example.com is ready. Log in at /admin/login')
            ->assertSuccessful();

        $admin = User::sole();
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue(Hash::check('a-long-password', $admin->password));
    }

    public function test_resets_the_password_of_an_existing_admin(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('cv:admin', ['--email' => 'admin@example.com', '--password' => 'a-new-long-password', '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('a-new-long-password', $admin->fresh()->password));
    }

    public function test_generates_and_prints_a_password_when_none_is_given(): void
    {
        Artisan::call('cv:admin', ['--email' => 'admin@example.com', '--no-interaction' => true]);

        preg_match('/Generated password: (\S+)/', Artisan::output(), $matches);
        $this->assertSame(20, strlen($matches[1]));
        $this->assertTrue(Hash::check($matches[1], User::sole()->password));
    }

    public function test_fails_without_an_email_when_not_interactive(): void
    {
        $this->artisan('cv:admin', ['--no-interaction' => true])
            ->expectsOutput('Pass --email=you@example.com when running without a terminal.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_rejects_a_password_shorter_than_twelve_characters(): void
    {
        $this->artisan('cv:admin', ['--email' => 'admin@example.com', '--password' => 'short', '--no-interaction' => true])
            ->expectsOutput('The password field must be at least 12 characters.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_rejects_an_invalid_email(): void
    {
        $this->artisan('cv:admin', ['--email' => 'not-an-email', '--password' => 'a-long-password', '--no-interaction' => true])
            ->expectsOutput('The email field must be a valid email address.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
