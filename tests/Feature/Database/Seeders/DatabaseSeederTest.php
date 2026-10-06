<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\AccessCode;
use App\Models\CvVersion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_master_cv_from_the_json_file(): void
    {
        $this->seed(DatabaseSeeder::class);

        $master = CvVersion::sole();
        $this->assertSame('Master', $master->name);
        $this->assertSame(json_decode(file_get_contents(database_path('data/master-cv.json')), true), $master->content);
        $this->assertDatabaseCount('access_codes', 0);
    }

    public function test_adds_a_demo_access_code_in_local_development(): void
    {
        $this->app['env'] = 'local';
        config(['cv.owner_name' => 'Alex Morgan']);

        $this->seed(DatabaseSeeder::class);

        $demo = AccessCode::findByPin('DEMO-2026');
        $this->assertSame('Demo Company', $demo->company);
        $this->assertStringEndsWith("Kind regards,\nAlex Morgan", $demo->cover_letter);
    }
}
