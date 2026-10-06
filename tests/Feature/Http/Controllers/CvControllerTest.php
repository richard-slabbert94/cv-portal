<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AccessCode;
use App\Models\CvVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CvControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlocked_cv_shows_the_codes_company_role_letter_and_version(): void
    {
        $version = CvVersion::factory()->create(['content' => [...CvVersion::blankContent(), 'name' => 'Alex Morgan', 'headline' => 'Engineer']]);
        $code = AccessCode::factory()->for($version)->create(['company' => 'Acme', 'role' => 'Lead Dev', 'cover_letter' => 'Dear Acme']);

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cv/Show')
                ->where('company', 'Acme')
                ->where('role', 'Lead Dev')
                ->where('coverLetter', 'Dear Acme')
                ->where('cv.headline', 'Engineer')
                ->where('photoUrl', null)
                ->where('preview', false));
    }

    public function test_hidden_cover_letter_is_not_sent_to_the_company(): void
    {
        $code = AccessCode::factory()->withHiddenCoverLetter()->create();

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertInertia(fn (Assert $page) => $page->where('coverLetter', null));
    }

    public function test_older_cv_content_is_filled_with_empty_sections(): void
    {
        $version = CvVersion::factory()->create(['content' => ['name' => 'Alex Morgan']]);
        $code = AccessCode::factory()->for($version)->create();

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertInertia(fn (Assert $page) => $page
                ->where('cv.name', 'Alex Morgan')
                ->where('cv.references', [])
                ->where('cv.experience', []));
    }

    public function test_cv_includes_a_photo_url_when_the_version_has_a_photo(): void
    {
        $version = CvVersion::factory()->create();
        $version->forceFill(['photo_path' => 'cv-photos/me.jpg'])->save();
        $code = AccessCode::factory()->for($version)->create();

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertInertia(fn (Assert $page) => $page->where('photoUrl', fn (string $url) => str_contains($url, "/cv-photos/{$version->id}?") && str_contains($url, 'signature=')));
    }

    public function test_cv_content_is_escaped_in_the_html_response(): void
    {
        $code = AccessCode::factory()->create(['cover_letter' => "<script>alert('x')</script>", 'company' => '<img src=x onerror=alert(1)>']);

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertDontSee("<script>alert('x')</script>", false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_admin_preview_shows_the_cv_without_counting_a_view(): void
    {
        $code = AccessCode::factory()->create(['company' => 'Acme']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/access-codes/{$code->id}/preview")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cv/Show')
                ->where('company', 'Acme')
                ->where('preview', true));

        $this->assertSame(0, $code->fresh()->view_count);
        $this->assertSame(0, $code->logs()->count());
    }

    public function test_admin_can_preview_an_inactive_code(): void
    {
        $code = AccessCode::factory()->inactive()->create();

        $this->actingAs(User::factory()->create())
            ->get("/admin/access-codes/{$code->id}/preview")
            ->assertInertia(fn (Assert $page) => $page->component('Cv/Show'));
    }

    public function test_preview_requires_an_admin_login(): void
    {
        $code = AccessCode::factory()->create();

        $this->get("/admin/access-codes/{$code->id}/preview")->assertRedirect('/admin/login');
    }

    public function test_preview_of_a_missing_code_returns_404(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/access-codes/999/preview')->assertNotFound();
    }
}
