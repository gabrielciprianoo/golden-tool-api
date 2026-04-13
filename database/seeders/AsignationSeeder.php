<?php

namespace Database\Seeders;

use App\Models\Asignation;
use App\Models\Tool;
use App\Models\Worker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsignationSeeder extends Seeder
{
    public function run(): void
    {
        $workers = Worker::all();
        $states = ['nuevo', 'buen estado', 'buen estado', 'regular', 'mal estado', 'obsoleto'];

        foreach ($workers as $worker) {
            $toolCount = fake()->numberBetween(2, 5);
            $tools = Tool::where('unassigned_quantity', '>', 0)
                ->inRandomOrder()
                ->limit($toolCount)
                ->get();

            foreach ($tools as $tool) {
                $tool->refresh();
                if ($tool->unassigned_quantity <= 0) {
                    continue;
                }

                DB::transaction(function () use ($worker, $tool, $states) {
                    $maxAssignable = min(4, $tool->unassigned_quantity);
                    $qty = fake()->numberBetween(1, $maxAssignable);
                    $date = fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d');

                    for ($i = 0; $i < $qty; $i++) {
                        Asignation::create([
                            'tool_id' => $tool->id,
                            'worker_id' => $worker->id,
                            'assigned_quantity' => 1,
                            'state' => fake()->randomElement($states),
                            'date' => $date,
                        ]);
                    }

                    $tool->decrement('unassigned_quantity', $qty);
                });
            }
        }
    }
}
