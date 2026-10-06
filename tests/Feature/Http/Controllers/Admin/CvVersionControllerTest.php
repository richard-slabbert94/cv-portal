<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AccessCode;
use App\Models\CvVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CvVersionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['get', '/admin/cv-versions'],
            'create' => ['get', '/admin/cv-versions/create'],
            'store' => ['post', '/admin/cv-versions'],
            'edit' => ['get', '/admin/cv-versions/1/edit'],
            'update' => ['put', '/admin/cv-versions/1'],
            'destroy' => ['delete', '/admin/cv-versions/1'],
            'duplicate' => ['post', '/admin/cv-versions/1/duplicate'],
            'upload photo' => ['post', '/admin/cv-versions/1/photo'],
            'remove photo' => ['delete', '/admin/cv-versions/1/photo'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_sent_to_the_login_page(string $method, string $uri): void
    {
        $version = CvVersion::factory()->create();

        $this->{$method}($uri)->assertRedirect('/admin/login');

        $this->assertModelExists($version);
        $this->assertDatabaseCount('cv_versions', 1);
    }

    public function test_index_lists_versions_with_how_many_codes_use_them(): void
    {
        $version = CvVersion::factory()->create(['name' => 'Backend', 'content' => [...CvVersion::blankContent(), 'headline' => 'Laravel dev']]);
        AccessCode::factory()->count(2)->for($version)->create();

        $this->actingAs(User::factory()->create())
            ->get('/admin/cv-versions')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CvVersions/Index')
                ->where('versions.0.name', 'Backend')
                ->where('versions.0.headline', 'Laravel dev')
                ->where('versions.0.access_codes_count', 2));
    }

    public function test_create_page_starts_from_the_blank_template(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/cv-versions/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CvVersions/Form')
                ->where('version.id', null)
                ->where('version.content', CvVersion::blankContent()));
    }

    public function test_store_creates_a_version_and_opens_it_for_editing(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post('/admin/cv-versions', ['name' => 'Fresh', 'content' => [...CvVersion::blankContent(), 'name' => 'Alex Morgan']]);

        $version = CvVersion::sole();
        $response->assertRedirect("/admin/cv-versions/{$version->id}/edit")->assertSessionHas('success', 'CV version created.');
        $this->assertSame('Fresh', $version->name);
        $this->assertSame('Alex Morgan', $version->content['name']);
    }

    public function test_the_master_cv_passes_validation_unchanged(): void
    {
        $version = CvVersion::factory()->master()->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/cv-versions/{$version->id}", ['name' => 'Master', 'content' => $version->content])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'CV version saved.');
    }

    public function test_update_saves_the_name_and_content(): void
    {
        $version = CvVersion::factory()->master()->create();
        $original = $version->content;
        $reference = ['name' => 'Jane Doe', 'title' => 'CTO', 'company' => 'Acme', 'relationship' => 'Line manager', 'email' => 'jane@example.com', 'phone' => '+27 82 000 0000'];

        $this->actingAs(User::factory()->create())
            ->put("/admin/cv-versions/{$version->id}", ['name' => 'Backend-focused', 'content' => [...$version->content, 'headline' => 'New headline', 'references' => [$reference]]])
            ->assertSessionHasNoErrors();

        $version->refresh();
        $this->assertSame('Backend-focused', $version->name);
        $this->assertSame('New headline', $version->content['headline']);
        $this->assertSame([$reference], $version->content['references']);
        $this->assertSame(array_column($original['experience'], 'role'), array_column($version->content['experience'], 'role'));
    }

    public function test_edit_page_fills_sections_missing_from_older_records(): void
    {
        $version = CvVersion::factory()->create(['content' => ['name' => 'Alex Morgan']]);

        $this->actingAs(User::factory()->create())
            ->get("/admin/cv-versions/{$version->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->where('version.content.name', 'Alex Morgan')
                ->where('version.content.references', [])
                ->where('version.photo_url', null));
    }

    public function test_empty_form_reports_the_required_names(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/cv-versions', ['name' => '', 'content' => ['name' => '']])
            ->assertSessionHasErrors([
                'name' => 'The version name field is required.',
                'content.name' => 'The name field is required.',
            ]);

        $this->assertDatabaseCount('cv_versions', 0);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidContent(): array
    {
        return [
            'bad email' => [['email' => 'not-an-email'], 'content.email', 'The email field must be a valid email address.'],
            'bad link url' => [['links' => [['label' => 'GitHub', 'url' => 'javascript:alert(1)']]], 'content.links.0.url', 'The link #1 URL field must be a valid URL.'],
            'role without company' => [['experience' => [['role' => 'Dev', 'company' => '']]], 'content.experience.0.company', 'The role #1 company field is required.'],
            'bad project url' => [['projects' => [['name' => 'Site', 'url' => 'nope']]], 'content.projects.0.url', 'The project #1 URL field must be a valid URL.'],
            'reference without name' => [['references' => [['name' => '', 'email' => 'jane@example.com']]], 'content.references.0.name', 'The reference #1 name field is required.'],
            'achievement too long' => [['experience' => [['role' => 'Dev', 'company' => 'Acme', 'bullets' => ['ok', 'ok', str_repeat('x', 1001)]], ['role' => 'Dev', 'company' => 'Acme', 'bullets' => ['ok', 'ok', str_repeat('x', 1001)]]]], 'content.experience.1.bullets.2', 'The role #2 achievement #3 field must not be greater than 1000 characters.'],
            'bad reference email' => [['references' => [['name' => 'Jane', 'email' => 'jane']]], 'content.references.0.email', 'The reference #1 email field must be a valid email address.'],
            'summary too long' => [['summary' => str_repeat('a', 5001)], 'content.summary', 'The summary field must not be greater than 5000 characters.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    #[DataProvider('invalidContent')]
    public function test_invalid_content_is_rejected_with_a_readable_message(array $content, string $field, string $message): void
    {
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/cv-versions/{$version->id}", ['name' => 'Master', 'content' => [...$version->content, ...$content]])
            ->assertSessionHasErrors([$field => $message]);
    }

    public function test_update_cannot_set_the_photo_path(): void
    {
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/cv-versions/{$version->id}", ['name' => 'Master', 'content' => $version->content, 'photo_path' => '../../.env'])
            ->assertSessionHasNoErrors();

        $this->assertNull($version->fresh()->photo_path);
    }

    public function test_duplicate_copies_the_content_under_a_new_name(): void
    {
        $version = CvVersion::factory()->master()->create();

        $response = $this->actingAs(User::factory()->create())->post("/admin/cv-versions/{$version->id}/duplicate");

        $copy = CvVersion::firstWhere('name', 'Master (copy)');
        $response->assertRedirect("/admin/cv-versions/{$copy->id}/edit");
        $this->assertSame($version->content, $copy->content);
        $this->assertNull($copy->photo_path);
    }

    public function test_destroy_deletes_an_unused_version(): void
    {
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/admin/cv-versions/{$version->id}")
            ->assertRedirect('/admin/cv-versions')
            ->assertSessionHas('success', 'CV version deleted.');

        $this->assertModelMissing($version);
    }

    public function test_destroy_keeps_a_version_that_access_codes_still_use(): void
    {
        $version = CvVersion::factory()->create();
        AccessCode::factory()->for($version)->create();

        $this->actingAs(User::factory()->create())
            ->delete("/admin/cv-versions/{$version->id}")
            ->assertSessionHas('success', 'This version is still used by access codes, so it was not deleted.');

        $this->assertModelExists($version);
    }

    /**
     * @return array<string, array{\Closure(): UploadedFile, string}>
     */
    public static function invalidPhotos(): array
    {
        return [
            'too small' => [fn () => UploadedFile::fake()->image('tiny.jpg', 50, 50), 'The photo field has invalid image dimensions.'],
            'too large' => [fn () => UploadedFile::fake()->image('huge.jpg', 400, 400)->size(5121), 'The photo field must not be greater than 5120 kilobytes.'],
            'pdf' => [fn () => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'), 'The photo field must be an image.'],
            'gif' => [fn () => UploadedFile::fake()->image('me.gif', 400, 400), 'The photo field must be a file of type: jpg, jpeg, png, webp.'],
        ];
    }

    /**
     * @param  \Closure(): UploadedFile  $file
     */
    #[DataProvider('invalidPhotos')]
    public function test_unsuitable_photos_are_rejected(\Closure $file, string $message): void
    {
        Storage::fake();
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/admin/cv-versions/{$version->id}/photo", ['photo' => $file()])
            ->assertSessionHasErrors(['photo' => $message]);

        $this->assertNull($version->fresh()->photo_path);
        $this->assertSame([], Storage::allFiles());
    }

    public function test_uploaded_photo_is_stored_privately_and_served_by_signed_url_only(): void
    {
        Storage::fake();
        $version = CvVersion::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/admin/cv-versions/{$version->id}/photo", ['photo' => UploadedFile::fake()->image('me.jpg', 400, 400)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Profile picture updated.');

        $version->refresh();
        Storage::assertExists($version->photo_path);
        $this->get($version->photoUrl())->assertOk();
        $this->get("/cv-photos/{$version->id}")->assertForbidden();
    }

    public function test_removing_a_photo_deletes_the_file(): void
    {
        Storage::fake();
        $version = CvVersion::factory()->create();
        $admin = User::factory()->create();
        $this->actingAs($admin)->post("/admin/cv-versions/{$version->id}/photo", ['photo' => UploadedFile::fake()->image('me.jpg', 400, 400)]);
        $path = $version->fresh()->photo_path;

        $this->actingAs($admin)
            ->delete("/admin/cv-versions/{$version->id}/photo")
            ->assertSessionHas('success', 'Profile picture removed.');

        Storage::assertMissing($path);
        $this->assertNull($version->fresh()->photo_path);
    }

    public function test_duplicating_a_version_gives_the_copy_its_own_photo(): void
    {
        Storage::fake();
        $version = CvVersion::factory()->create(['name' => 'Master']);
        $admin = User::factory()->create();
        $this->actingAs($admin)->post("/admin/cv-versions/{$version->id}/photo", ['photo' => UploadedFile::fake()->image('me.jpg', 400, 400)]);

        $this->actingAs($admin)->post("/admin/cv-versions/{$version->id}/duplicate");

        $copy = CvVersion::firstWhere('name', 'Master (copy)');
        $this->assertNotSame($version->fresh()->photo_path, $copy->photo_path);
        Storage::assertExists($copy->photo_path);
        Storage::assertExists($version->fresh()->photo_path);
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        Storage::fake();
        $version = CvVersion::factory()->create();
        $admin = User::factory()->create();
        $this->actingAs($admin)->post("/admin/cv-versions/{$version->id}/photo", ['photo' => UploadedFile::fake()->image('old.jpg', 200, 200)]);
        $oldPath = $version->fresh()->photo_path;

        $this->actingAs($admin)->post("/admin/cv-versions/{$version->id}/photo", ['photo' => UploadedFile::fake()->image('new.png', 200, 200)]);

        Storage::assertMissing($oldPath);
        Storage::assertExists($version->fresh()->photo_path);
        $this->assertCount(1, Storage::allFiles());
    }

    public function test_photo_upload_keeps_the_saved_content_untouched(): void
    {
        Storage::fake();
        $version = CvVersion::factory()->master()->create();

        $this->actingAs(User::factory()->create())
            ->post("/admin/cv-versions/{$version->id}/photo", ['photo' => UploadedFile::fake()->image('me.jpg', 200, 200)]);

        $this->assertSame($version->content, $version->fresh()->content);
    }

    public function test_signed_photo_url_for_a_version_without_a_photo_returns_404(): void
    {
        $version = CvVersion::factory()->create();

        $this->get(URL::signedRoute('cv-versions.photo', ['cvVersion' => $version->id]))->assertNotFound();
    }

    public function test_photo_url_signed_for_one_version_does_not_open_another(): void
    {
        Storage::fake();
        $version = CvVersion::factory()->create();
        $version->forceFill(['photo_path' => UploadedFile::fake()->image('me.jpg', 200, 200)->store('cv-photos')])->save();
        $other = CvVersion::factory()->create();
        $other->forceFill(['photo_path' => UploadedFile::fake()->image('other.jpg', 200, 200)->store('cv-photos')])->save();

        $tampered = str_replace("/cv-photos/{$version->id}?", "/cv-photos/{$other->id}?", $version->photoUrl());

        $this->get($tampered)->assertForbidden();
    }
}
