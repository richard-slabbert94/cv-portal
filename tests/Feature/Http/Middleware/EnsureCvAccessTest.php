<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\AccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureCvAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_without_an_unlocked_code_is_sent_to_the_pin_page(): void
    {
        $this->get('/cv')->assertRedirect('/');
    }

    public function test_disabling_a_code_ends_an_open_session(): void
    {
        $code = AccessCode::factory()->create();
        $code->update(['is_active' => false]);

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertRedirect('/')
            ->assertSessionMissing('access_code_id');
    }

    public function test_deleted_code_ends_the_session(): void
    {
        $code = AccessCode::factory()->create();
        $code->delete();

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertRedirect('/')
            ->assertSessionMissing('access_code_id');
    }

    public function test_code_that_expires_while_open_ends_the_session(): void
    {
        $this->freezeTime();
        $code = AccessCode::factory()->create(['expires_at' => now()->addHour()]);

        $this->travel(2)->hours();

        $this->withSession(['access_code_id' => $code->id])
            ->get('/cv')
            ->assertRedirect('/')
            ->assertSessionMissing('access_code_id');
    }
}
