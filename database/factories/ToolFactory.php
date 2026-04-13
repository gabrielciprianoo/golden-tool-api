<?php

namespace Database\Factories;

use App\Models\Tool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tool>
 */
class ToolFactory extends Factory
{
    private static array $tools = [
        ['name' => 'Taladro percutor', 'category' => 'normal', 'price' => 1200.00],
        ['name' => 'Llave de impacto', 'category' => 'normal', 'price' => 950.00],
        ['name' => 'Esmeriladora angular', 'category' => 'normal', 'price' => 780.00],
        ['name' => 'Multímetro digital', 'category' => 'normal', 'price' => 450.00],
        ['name' => 'Sierra circular', 'category' => 'normal', 'price' => 1350.00],
        ['name' => 'Destornillador de impacto', 'category' => 'normal', 'price' => 620.00],
        ['name' => 'Nivel láser', 'category' => 'normal', 'price' => 890.00],
        ['name' => 'Pistola de calor', 'category' => 'normal', 'price' => 340.00],
        ['name' => 'Juego de llaves Allen', 'category' => 'refaccion', 'price' => 180.00],
        ['name' => 'Pinzas de presión', 'category' => 'refaccion', 'price' => 120.00],
        ['name' => 'Cinta métrica 5m', 'category' => 'refaccion', 'price' => 85.00],
        ['name' => 'Juego de desarmadores', 'category' => 'refaccion', 'price' => 210.00],
        ['name' => 'Martillo de bola', 'category' => 'normal', 'price' => 220.00],
        ['name' => 'Llave ajustable 12"', 'category' => 'refaccion', 'price' => 195.00],
        ['name' => 'Cortador de tubería', 'category' => 'normal', 'price' => 310.00],
    ];

    private static array $suppliers = [
        'DeWalt México',
        'Truper',
        'Stanley Tools',
        'Makita',
        'Bosch',
        'Milwaukee Tool',
    ];

    public function definition(): array
    {
        $tool = fake()->randomElement(self::$tools);
        $quantity = fake()->numberBetween(3, 20);
        $unassigned = fake()->numberBetween(1, $quantity);

        return [
            'name' => $tool['name'],
            'category' => $tool['category'],
            'price' => $tool['price'],
            'supplier' => fake()->randomElement(self::$suppliers),
            'entry_date' => fake()->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
            'quantity' => $quantity,
            'unassigned_quantity' => $unassigned,
        ];
    }
}
