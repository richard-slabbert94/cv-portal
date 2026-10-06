<?php

namespace Tests\Unit\Support;

use App\Support\Pin;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PinTest extends TestCase
{
    public function test_generated_pins_use_only_unambiguous_characters(): void
    {
        $pins = collect(range(1, 200))->map(fn () => Pin::generate())->implode('');

        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]+$/', $pins);
    }

    #[TestWith([8])]
    #[TestWith([12])]
    public function test_generated_pin_has_the_requested_length(int $length): void
    {
        $this->assertSame($length, strlen(Pin::generate($length)));
    }

    #[TestWith(['abcd-2345', 'ABCD2345'])]
    #[TestWith([' ab cd 23 45 ', 'ABCD2345'])]
    #[TestWith(['ab_cd.23/45', 'ABCD2345'])]
    #[TestWith(['ábcd', 'BCD'])]
    #[TestWith(['----', ''])]
    public function test_normalize_uppercases_and_strips_everything_but_letters_and_digits(string $input, string $expected): void
    {
        $this->assertSame($expected, Pin::normalize($input));
    }

    #[TestWith(['abcd2345', 'ABCD-2345'])]
    #[TestWith(['ABCD2345WXYZ', 'ABCD-2345-WXYZ'])]
    #[TestWith(['ABCDEF', 'ABCD-EF'])]
    public function test_format_groups_the_pin_in_blocks_of_four(string $input, string $expected): void
    {
        $this->assertSame($expected, Pin::format($input));
    }

    public function test_lookup_hash_is_the_same_for_equivalent_spellings_of_a_pin(): void
    {
        $this->assertSame(Pin::lookupHash('ABCD2345'), Pin::lookupHash('abcd-2345'));
    }

    public function test_lookup_hash_differs_for_different_pins(): void
    {
        $this->assertNotSame(Pin::lookupHash('ABCD2345'), Pin::lookupHash('ABCD2346'));
    }

    public function test_lookup_hash_depends_on_the_app_key(): void
    {
        $original = Pin::lookupHash('ABCD2345');

        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);

        $this->assertNotSame($original, Pin::lookupHash('ABCD2345'));
    }
}
