<?php

namespace Database\Factories;

use App\Models\Asignation;
use App\Models\Tool;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asignation>
 */
class AsignationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tool_id' => Tool::factory(),
            'worker_id' => Worker::factory(),
            'assigned_quantity' => 1,
            'state' => fake()->randomElement(['nuevo', 'buen estado', 'regular', 'mal estado', 'obsoleto']),
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
