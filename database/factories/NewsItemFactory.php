<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Web\Models\NewsItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsItem>
 */
final class NewsItemFactory extends Factory
{
    protected $model = NewsItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-3 years');

        return [
            'entry_id' => $date->format('Y-m-d').'-'.$this->faker->numberBetween(1, 4),
            'title' => 'PHP '.$this->faker->numerify('8.#.##').' Released!',
            'category' => 'releases',
            'label' => 'New PHP release',
            'terms' => [
                ['term' => 'releases', 'label' => 'New PHP release'],
                ['term' => 'frontpage', 'label' => 'PHP.net frontpage news'],
            ],
            'body_html' => '<p>'.$this->faker->paragraph().'</p>',
            'link' => 'https://www.php.net/index.php',
            'via' => null,
            'published_at' => $date,
            'source_hash' => $this->faker->regexify('[0-9a-f]{32}'),
        ];
    }

    public function security(): self
    {
        return $this->state(fn (): array => [
            'category' => 'security',
            'label' => 'Security advisory',
            'terms' => [['term' => 'security', 'label' => 'Security advisory']],
        ]);
    }
}
