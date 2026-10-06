<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AccessCode;
use App\Models\AccessLog;
use App\Models\CvVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessCodeControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['get', '/admin/access-codes'],
            'create' => ['get', '/admin/access-codes/create'],
            'store' => ['post', '/admin/access-codes'],
            'edit' => ['get', '/admin/access-codes/1/edit'],
            'update' => ['put', '/admin/access-codes/1'],
            'destroy' => ['delete', '/admin/access-codes/1'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_sent_to_the_login_page(string $method, string $uri): void
    {
        $code = AccessCode::factory()->create();

        $this->{$method}($uri)->assertRedirect('/admin/login');

        $this->assertModelExists($code);
    }

    public function test_index_lists_codes_with_their_status(): void
    {
        $version = CvVersion::factory()->create(['name' => 'Backend']);
        AccessCode::factory()->for($version)->withPin('ABCD2345')->expired()->withHiddenCoverLetter()->create(['company' => 'Acme']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/access-codes')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AccessCodes/Index')
                ->has('codes', 1)
                ->where('codes.0.company', 'Acme')
                ->where('codes.0.pin', 'ABCD-2345')
                ->where('codes.0.cv_version', 'Backend')
                ->where('codes.0.has_cover_letter', false)
                ->where('codes.0.is_active', true)
                ->where('codes.0.is_usable', false));
    }

    public function test_create_page_suggests_a_formatted_pin_and_lists_versions(): void
    {
        CvVersion::factory()->create(['name' => 'Master']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/access-codes/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AccessCodes/Form')
                ->where('code', null)
                ->where('suggestedPin', fn (string $pin) => preg_match('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $pin) === 1)
                ->where('versions.0.name', 'Master'));
    }

    public function test_store_saves_every_field_and_flashes_the_pin(): void
    {
        $this->freezeTime();
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/admin/access-codes', [
                'cv_version_id' => $version->id,
                'company' => 'Globex',
                'role' => 'Platform Engineer',
                'pin' => 'glbx-7788',
                'cover_letter' => 'Dear Globex',
                'show_cover_letter' => false,
                'notes' => 'Recruiter: Sam',
                'is_active' => false,
                'expires_at' => '2026-12-31',
            ])
            ->assertRedirect('/admin/access-codes')
            ->assertSessionHas('success', 'Access code created for Globex.')
            ->assertSessionHas('pin', 'GLBX-7788');

        $code = AccessCode::sole();
        $this->assertSame('Platform Engineer', $code->role);
        $this->assertSame('Dear Globex', $code->cover_letter);
        $this->assertFalse($code->show_cover_letter);
        $this->assertSame('Recruiter: Sam', $code->notes);
        $this->assertFalse($code->is_active);
        $this->assertSame('2026-12-31', $code->expires_at->toDateString());
        $this->assertTrue(AccessCode::findByPin('GLBX7788')->is($code));
    }

    public function test_store_rejects_a_pin_already_in_use(): void
    {
        $existing = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->actingAs(User::factory()->create())
            ->post('/admin/access-codes', ['cv_version_id' => $existing->cv_version_id, 'company' => 'Other', 'pin' => 'abcd-2345'])
            ->assertSessionHasErrors(['pin' => 'This PIN is already in use.']);

        $this->assertDatabaseCount('access_codes', 1);
    }

    public function test_store_ignores_counters_sent_in_the_request(): void
    {
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())->post('/admin/access-codes', [
            'cv_version_id' => $version->id,
            'company' => 'Globex',
            'pin' => 'GLBX7788',
            'view_count' => 999,
        ]);

        $this->assertSame(0, AccessCode::sole()->view_count);
    }

    public function test_empty_form_reports_every_required_field(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/access-codes', [])
            ->assertSessionHasErrors([
                'cv_version_id' => 'The CV version field is required.',
                'company' => 'The company field is required.',
                'pin' => 'The PIN field is required.',
            ]);

        $this->assertDatabaseCount('access_codes', 0);
    }

    public function test_pin_shorter_than_six_characters_after_removing_dashes_is_rejected(): void
    {
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/admin/access-codes', ['cv_version_id' => $version->id, 'company' => 'Acme', 'pin' => 'AB-CD-E'])
            ->assertSessionHasErrors(['pin' => 'The PIN field must be at least 6 characters.']);
    }

    public function test_pin_longer_than_sixteen_characters_is_rejected(): void
    {
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/admin/access-codes', ['cv_version_id' => $version->id, 'company' => 'Acme', 'pin' => str_repeat('A', 17)])
            ->assertSessionHasErrors(['pin' => 'The PIN field must not be greater than 16 characters.']);
    }

    public function test_unknown_cv_version_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/access-codes', ['cv_version_id' => 999, 'company' => 'Acme', 'pin' => 'ABCD2345'])
            ->assertSessionHasErrors(['cv_version_id' => 'The selected CV version is invalid.']);
    }

    public function test_invalid_expiry_date_is_rejected(): void
    {
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/admin/access-codes', ['cv_version_id' => $version->id, 'company' => 'Acme', 'pin' => 'ABCD2345', 'expires_at' => 'next-ish'])
            ->assertSessionHasErrors(['expires_at' => 'The expiry date field must be a valid date.']);

        $this->assertDatabaseCount('access_codes', 0);
    }

    public function test_edit_page_shows_the_code_and_its_recent_views_newest_first(): void
    {
        $code = AccessCode::factory()->withPin('ABCD2345')->create(['company' => 'Acme', 'expires_at' => '2026-12-31']);
        AccessLog::create(['access_code_id' => $code->id, 'ip' => '10.0.0.1'])->forceFill(['created_at' => now()->subDay()])->save();
        AccessLog::create(['access_code_id' => $code->id, 'ip' => '10.0.0.2']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/access-codes/{$code->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AccessCodes/Form')
                ->where('code.company', 'Acme')
                ->where('code.pin', 'ABCD-2345')
                ->where('code.expires_at', '2026-12-31')
                ->where('code.show_cover_letter', true)
                ->has('logs', 2)
                ->where('logs.0.ip', '10.0.0.2')
                ->where('logs.1.ip', '10.0.0.1'));
    }

    public function test_update_can_keep_the_codes_own_pin(): void
    {
        $code = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/access-codes/{$code->id}", ['cv_version_id' => $code->cv_version_id, 'company' => 'Renamed', 'pin' => 'ABCD-2345'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/access-codes');

        $this->assertSame('Renamed', $code->fresh()->company);
    }

    public function test_changing_the_pin_retires_the_old_one(): void
    {
        $code = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/access-codes/{$code->id}", ['cv_version_id' => $code->cv_version_id, 'company' => $code->company, 'pin' => 'WXYZ6789']);

        $this->assertNull(AccessCode::findByPin('ABCD2345'));
        $this->assertTrue(AccessCode::findByPin('WXYZ6789')->is($code));
    }

    public function test_update_can_hide_the_cover_letter_without_losing_it(): void
    {
        $code = AccessCode::factory()->withPin('ABCD2345')->create(['cover_letter' => 'Dear Acme']);

        $this->actingAs(User::factory()->create())
            ->put("/admin/access-codes/{$code->id}", [
                'cv_version_id' => $code->cv_version_id,
                'company' => $code->company,
                'pin' => 'ABCD2345',
                'cover_letter' => 'Dear Acme',
                'show_cover_letter' => false,
            ])
            ->assertSessionHasNoErrors();

        $code->refresh();
        $this->assertFalse($code->show_cover_letter);
        $this->assertSame('Dear Acme', $code->cover_letter);
    }

    public function test_update_rejects_a_pin_used_by_another_code(): void
    {
        AccessCode::factory()->withPin('TAKEN234')->create();
        $code = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/access-codes/{$code->id}", ['cv_version_id' => $code->cv_version_id, 'company' => $code->company, 'pin' => 'taken-234'])
            ->assertSessionHasErrors(['pin' => 'This PIN is already in use.']);

        $this->assertSame('ABCD2345', $code->fresh()->pin);
    }

    public function test_destroy_deletes_the_code_and_its_view_log(): void
    {
        $code = AccessCode::factory()->create();
        $log = $code->logs()->create(['ip' => '10.0.0.1']);

        $this->actingAs(User::factory()->create())
            ->delete("/admin/access-codes/{$code->id}")
            ->assertRedirect('/admin/access-codes')
            ->assertSessionHas('success', 'Access code deleted.');

        $this->assertModelMissing($code);
        $this->assertModelMissing($log);
    }
}
