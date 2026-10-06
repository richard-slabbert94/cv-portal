<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\AccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_tell_search_engines_not_to_index_them(): void
    {
        $code = AccessCode::factory()->create();

        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->withSession(['access_code_id' => $code->id])->get('/cv')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
