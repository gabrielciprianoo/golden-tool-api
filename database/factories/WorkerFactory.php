<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'name' => fake()->firstName(),
            'lastname' => fake()->lastName().' '.fake()->lastName(),
            'worker_code' => 'TRB-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'area' => fake()->randomElement(['montaje/desmontaje', 'armado/desarmado']),
            'created_by' => User::first()?->id,
        ];
    }
}
