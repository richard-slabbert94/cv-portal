<?php

namespace Tests\Feature\Models;

use App\Models\AccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_code_without_expiry_is_usable(): void
    {
        $code = AccessCode::factory()->create();

        $this->assertTrue($code->isUsable());
    }

    public function test_inactive_code_is_not_usable(): void
    {
        $code = AccessCode::factory()->inactive()->create();

        $this->assertFalse($code->isUsable());
    }

    public function test_code_is_usable_until_the_moment_it_expires(): void
    {
        $this->freezeTime();
        $code = AccessCode::factory()->create(['expires_at' => now()->addMinute()]);

        $this->assertTrue($code->isUsable());

        $this->travel(1)->minute();

        $this->assertFalse($code->isUsable());
    }

    /**
     * @return array<string, array{array<string, mixed>, ?string}>
     */
    public static function coverLetterStates(): array
    {
        return [
            'shown with text' => [['cover_letter' => 'Dear Acme', 'show_cover_letter' => true], 'Dear Acme'],
            'hidden with text' => [['cover_letter' => 'Dear Acme', 'show_cover_letter' => false], null],
            'shown but empty' => [['cover_letter' => '', 'show_cover_letter' => true], null],
            'shown but whitespace' => [['cover_letter' => "  \n ", 'show_cover_letter' => true], null],
            'shown but null' => [['cover_letter' => null, 'show_cover_letter' => true], null],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    #[DataProvider('coverLetterStates')]
    public function test_visible_cover_letter_respects_the_toggle_and_content(array $attributes, ?string $expected): void
    {
        $code = AccessCode::factory()->create($attributes);

        $this->assertSame($expected, $code->visibleCoverLetter());
    }

    public function test_find_by_pin_ignores_case_and_dashes(): void
    {
        $code = AccessCode::factory()->withPin('ABCD2345')->create();

        $this->assertTrue(AccessCode::findByPin('abcd-2345')->is($code));
        $this->assertNull(AccessCode::findByPin('ABCD2346'));
    }

    public function test_set_pin_stores_the_pin_encrypted_and_normalized(): void
    {
        $code = AccessCode::factory()->create();

        $code->setPin('wxyz-6789')->save();

        $this->assertSame('WXYZ6789', $code->fresh()->pin);
        $this->assertStringNotContainsString('WXYZ6789', $code->getRawOriginal('pin'));
    }

    public function test_pin_and_lookup_hash_are_hidden_from_serialization(): void
    {
        $code = AccessCode::factory()->create();

        $this->assertArrayNotHasKey('pin', $code->toArray());
        $this->assertArrayNotHasKey('pin_lookup', $code->toArray());
    }
}
