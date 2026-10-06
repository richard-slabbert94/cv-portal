<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private const CREDENTIALS_MESSAGE = 'These credentials do not match our records.';

    public function test_guest_sees_the_login_page(): void
    {
        $this->get('/admin/login')->assertInertia(fn (Assert $page) => $page->component('Admin/Login'));
    }

    public function test_logged_in_admin_is_redirected_away_from_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/login')->assertRedirect('/admin/access-codes');
    }

    public function test_valid_credentials_log_the_admin_in(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect('/admin/access-codes');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_returns_to_the_page_the_admin_originally_asked_for(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);
        $this->get('/admin/cv-versions');

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect('/admin/cv-versions');
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => self::CREDENTIALS_MESSAGE]);

        $this->assertGuest();
    }

    public function test_unknown_email_gets_the_same_message_as_a_wrong_password(): void
    {
        $this->post('/admin/login', ['email' => 'nobody@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => self::CREDENTIALS_MESSAGE]);
    }

    public function test_empty_login_form_requires_email_and_password(): void
    {
        $this->post('/admin/login', [])->assertSessionHasErrors([
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);
    }

    public function test_login_attempts_are_limited_to_five_per_minute(): void
    {
        foreach (range(1, 5) as $_) {
            $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertTooManyRequests();
    }

    public function test_logout_signs_the_admin_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_guest_cannot_log_out(): void
    {
        $this->post('/admin/logout')->assertRedirect('/admin/login');
    }
}
