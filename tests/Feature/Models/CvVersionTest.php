<?php

namespace Tests\Feature\Models;

use App\Models\CvVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvVersionTest extends TestCase
{
    use RefreshDatabase;

    private function versionWithPhoto(): CvVersion
    {
        $version = CvVersion::factory()->create();
        $version->photo_path = UploadedFile::fake()->image('me.jpg', 200, 200)->store('cv-photos');
        $version->save();

        return $version;
    }

    public function test_photo_url_is_null_without_a_photo(): void
    {
        $version = CvVersion::factory()->create();

        $this->assertNull($version->photoUrl());
    }

    public function test_photo_url_stops_working_after_twelve_hours(): void
    {
        Storage::fake();
        $version = $this->versionWithPhoto();
        $url = $version->photoUrl();

        $this->travel(11)->hours();
        $this->get($url)->assertOk();

        $this->travel(2)->hours();
        $this->get($url)->assertForbidden();
    }

    public function test_deleting_a_version_deletes_its_photo(): void
    {
        Storage::fake();
        $version = $this->versionWithPhoto();

        $version->delete();

        Storage::assertMissing($version->photo_path);
    }
}
