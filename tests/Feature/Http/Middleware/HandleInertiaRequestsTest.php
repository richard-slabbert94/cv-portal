<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shares_the_logged_in_admins_name_and_email_only(): void
    {
        $admin = User::factory()->create(['name' => 'Admin', 'email' => 'admin@example.com']);

        $this->actingAs($admin)
            ->get('/admin/access-codes')
            ->assertInertia(fn (Assert $page) => $page->where('admin', ['name' => 'Admin', 'email' => 'admin@example.com']));
    }

    public function test_shares_no_admin_with_visitors(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('admin', null));
    }

    public function test_shares_the_configured_owner_with_visitors(): void
    {
        config(['cv.owner_name' => 'Alex Morgan', 'cv.contact_url' => 'https://example.com/alex', 'cv.contact_label' => 'GitHub']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('owner', [
            'name' => 'Alex Morgan',
            'contactUrl' => 'https://example.com/alex',
            'contactLabel' => 'GitHub',
        ]));
    }

    public function test_shares_flashed_messages(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['success' => 'Saved.', 'pin' => 'ABCD-2345'])
            ->get('/admin/access-codes')
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Saved.')
                ->where('flash.pin', 'ABCD-2345'));
    }
}
