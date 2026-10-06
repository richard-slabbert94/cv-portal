<?php

namespace Database\Factories;

use App\Models\CvVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvVersion>
 */
class CvVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'content' => [...CvVersion::blankContent(), 'name' => fake()->name(), 'headline' => fake()->jobTitle()],
        ];
    }

    /** The real master CV from database/data, so tests exercise a full, realistic record. */
    public function master(): static
    {
        return $this->state(fn () => [
            'name' => 'Master',
            'content' => json_decode(file_get_contents(database_path('data/master-cv.json')), true, flags: JSON_THROW_ON_ERROR),
        ]);
    }
}
