<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lunar\Models\Channel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Channel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => Str::title($name),
            'handle' => Str::slug($name),
            'url' => fake()->url(),
            'default' => false,
        ];
    }

    /**
     * Mark the channel as default.
     */
    public function defaultChannel(): static
    {
        return $this->state(fn () => [
            'default' => true,
        ]);
    }
}
