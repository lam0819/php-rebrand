<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Web\Models\PhpRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhpRelease>
 */
final class PhpReleaseFactory extends Factory
{
    protected $model = PhpRelease::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branch = $this->faker->numerify('8.#');

        return [
            'branch' => $branch,
            'version' => $branch.'.'.$this->faker->numberBetween(0, 30),
            'released_on' => $this->faker->dateTimeBetween('-1 year'),
            'tags' => [],
            'sha256' => [
                'tar.gz' => $this->faker->regexify('[0-9a-f]{64}'),
                'tar.xz' => $this->faker->regexify('[0-9a-f]{64}'),
            ],
            'source_hash' => $this->faker->regexify('[0-9a-f]{32}'),
        ];
    }

    public function security(): self
    {
        return $this->state(fn (): array => ['tags' => ['security']]);
    }
}
