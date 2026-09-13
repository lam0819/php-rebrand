<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Web\Models\ChangelogRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangelogRelease>
 */
final class ChangelogReleaseFactory extends Factory
{
    protected $model = ChangelogRelease::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branch = $this->faker->numerify('8.#');
        $version = $branch.'.'.$this->faker->numberBetween(0, 30);
        $sections = [
            ['category' => 'Core', 'entries' => ['Fixed bug GH-'.$this->faker->numberBetween(10000, 99999).' (something).']],
            ['category' => 'Standard', 'entries' => ['Fixed a thing.', 'Fixed another thing.']],
        ];

        return [
            'version' => $version,
            'branch' => $branch,
            'released_on' => $this->faker->dateTimeBetween('-1 year'),
            'released' => true,
            'sections' => $sections,
            'entry_count' => 3,
            'source_hash' => $this->faker->regexify('[0-9a-f]{32}'),
        ];
    }

    public function unreleased(): self
    {
        return $this->state(fn (): array => ['released' => false, 'released_on' => null]);
    }
}
