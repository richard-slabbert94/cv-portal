<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UnlockControllerTest extends TestCase
{
    use RefreshDatabase;

    private const INVALID_MESSAGE = 'That code is not valid. Please check it and try again.';

    public function test_visitor_sees_the_pin_entry_page(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Unlock'));
    }

    public function test_visitor_with_an_unlocked_code_is_sent_straight_to_the_cv(): void
    {
        $code = AccessCode::factory()->create();

        $this->withSession(['access_code_id' => $code->id])->get('/')->assertRedirect('/cv');
    }

    public function test_visitor_whose_code_was_disabled_sees_the_pin_entry_page_again(): void
    {
        $code = AccessCode::factory()->inactive()->create();

        $this->withSession(['access_code_id' => $code->id])
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page->component('Unlock'));
    }

    public function test_empty_pin_is_rejected_with_a_required_message(): void
    {
        $this->post('/unlock', ['pin' => ''])->assertSessionHasErrors(['pin' => 'The access code field is required.']);
    }

    public function test_overlong_pin_is_rejected(): void
    {
        $this->post('/unlock', ['pin' => str_repeat('A', 33)])
            ->assertSessionHasErrors(['pin' => 'The access code field must not be greater than 32 characters.']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableCodeStates(): array
    {
        return [
            'inactive' => ['inactive'],
            'expired' => ['expired'],
        ];
    }

    #[DataProvider('unusableCodeStates')]
    public function test_unusable_codes_get_the_same_generic_message_as_wrong_pins(string $state): void
    {
        $code = AccessCode::factory()->{$state}()->withPin('ABCD2345')->create();

        $this->post('/unlock', ['pin' => 'ABCD2345'])->assertSessionHasErrors(['pin' => self::INVALID_MESSAGE]);

        $this->assertSame(0, $code->fresh()->view_count);
        $this->assertSame(0, $code->logs()->count());
        $this->get('/cv')->assertRedirect('/');
    }

    public function test_wrong_pin_gets_the_generic_message(): void
    {
        AccessCode::factory()->withPin('ABCD2345')->create();

        $this->post('/unlock', ['pin' => 'ABCD2346'])->assertSessionHasErrors(['pin' => self::INVALID_MESSAGE]);

        $this->get('/cv')->assertRedirect('/');
    }

    public function test_valid_pin_opens_the_cv_it_belongs_to(): void
    {
        AccessCode::factory()->withPin('ABCD2345')->create(['company' => 'Acme']);
        AccessCode::factory()->withPin('WXYZ6789')->create(['company' => 'Globex']);

        $this->post('/unlock', ['pin' => 'abcd-2345'])->assertRedirect('/cv');

        $this->get('/cv')->assertInertia(fn (Assert $page) => $page->where('company', 'Acme'));
    }

    public function test_successful_unlock_records_the_visit(): void
    {
        $this->freezeSecond();
        $code = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->withHeader('User-Agent', str_repeat('a', 600))
            ->post('/unlock', ['pin' => 'abcd 2345'])
            ->assertRedirect('/cv');

        $code->refresh();
        $this->assertSame(1, $code->view_count);
        $this->assertTrue($code->last_viewed_at->equalTo(now()));
        $log = $code->logs()->sole();
        $this->assertSame('127.0.0.1', $log->ip);
        $this->assertSame(str_repeat('a', 512), $log->user_agent);
    }

    public function test_each_unlock_adds_to_the_view_count(): void
    {
        $code = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->post('/unlock', ['pin' => 'ABCD2345']);
        $this->post('/lock');
        $this->post('/unlock', ['pin' => 'ABCD2345']);

        $this->assertSame(2, $code->fresh()->view_count);
        $this->assertSame(2, $code->logs()->count());
    }

    public function test_lock_forgets_the_unlocked_code(): void
    {
        $code = AccessCode::factory()->create();

        $this->withSession(['access_code_id' => $code->id])
            ->post('/lock')
            ->assertRedirect('/')
            ->assertSessionMissing('access_code_id');

        $this->get('/cv')->assertRedirect('/');
    }

    public function test_pin_attempts_are_limited_to_five_per_minute(): void
    {
        foreach (range(1, 5) as $_) {
            $this->post('/unlock', ['pin' => 'NOPE1234']);
        }

        $this->post('/unlock', ['pin' => 'NOPE1234'])->assertTooManyRequests();
    }

    public function test_forged_forwarded_for_header_does_not_reset_the_limit_without_a_trusted_proxy(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->withHeader('X-Forwarded-For', "198.51.100.{$attempt}")->post('/unlock', ['pin' => 'NOPE1234']);
        }

        $this->withHeader('X-Forwarded-For', '198.51.100.99')
            ->post('/unlock', ['pin' => 'NOPE1234'])
            ->assertTooManyRequests();
    }

    public function test_visitors_behind_a_trusted_proxy_get_their_own_limits(): void
    {
        config(['trustedproxy.proxies' => 'REMOTE_ADDR']);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1']);

        foreach (range(1, 5) as $_) {
            $this->withHeader('X-Forwarded-For', '203.0.113.1')->post('/unlock', ['pin' => 'NOPE1234']);
        }

        $this->withHeader('X-Forwarded-For', '203.0.113.1')->post('/unlock', ['pin' => 'NOPE1234'])->assertTooManyRequests();
        $this->withHeader('X-Forwarded-For', '203.0.113.2')->post('/unlock', ['pin' => 'NOPE1234'])->assertSessionHasErrors('pin');
    }

    public function test_visitor_behind_a_trusted_proxy_cannot_forge_their_ip(): void
    {
        config(['trustedproxy.proxies' => 'REMOTE_ADDR']);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1']);

        foreach (range(1, 5) as $attempt) {
            $this->withHeader('X-Forwarded-For', "198.51.100.{$attempt}, 203.0.113.1")->post('/unlock', ['pin' => 'NOPE1234']);
        }

        $this->withHeader('X-Forwarded-For', '198.51.100.99, 203.0.113.1')
            ->post('/unlock', ['pin' => 'NOPE1234'])
            ->assertTooManyRequests();
    }

    public function test_visit_log_records_the_visitors_ip_behind_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.0/8']);
        $code = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->post('/unlock', ['pin' => 'ABCD2345']);

        $this->assertSame('203.0.113.7', $code->logs()->sole()->ip);
    }

    public function test_pin_attempts_are_capped_at_thirty_per_day(): void
    {
        foreach (range(1, 6) as $_) {
            foreach (range(1, 5) as $_) {
                $this->post('/unlock', ['pin' => 'NOPE1234'])->assertSessionHasErrors('pin');
            }
            $this->travel(61)->seconds();
        }

        $this->post('/unlock', ['pin' => 'NOPE1234'])->assertTooManyRequests();
    }
}
