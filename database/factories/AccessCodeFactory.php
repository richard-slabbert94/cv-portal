<?php

namespace Database\Factories;

use App\Models\AccessCode;
use App\Models\CvVersion;
use App\Support\Pin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessCode>
 */
class AccessCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cv_version_id' => CvVersion::factory(),
            'company' => fake()->company(),
            'role' => fake()->jobTitle(),
            'pin' => Pin::generate(),
            'pin_lookup' => fn (array $attributes) => Pin::lookupHash($attributes['pin']),
            'cover_letter' => "Dear Hiring Team,\n\nI would love to join you.",
            'show_cover_letter' => true,
            'is_active' => true,
            'expires_at' => null,
        ];
    }

    public function withPin(string $pin): static
    {
        return $this->state(fn () => ['pin' => Pin::normalize($pin)]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function withoutCoverLetter(): static
    {
        return $this->state(fn () => ['cover_letter' => null]);
    }

    public function withHiddenCoverLetter(): static
    {
        return $this->state(fn () => ['show_cover_letter' => false]);
    }
}
