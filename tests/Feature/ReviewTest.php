<?php

use App\Models\Asignation;
use App\Models\Review;
use App\Models\Tool;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWorkerForReviews(User $user): array
{
    $worker = Worker::create([
        'name' => 'Juan',
        'lastname' => 'Perez',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    $tool = Tool::create([
        'name' => 'Taladro',
        'category' => 'normal',
        'price' => 500.00,
        'supplier' => 'Proveedor SA',
        'entry_date' => now()->toDateString(),
        'quantity' => 5,
        'unassigned_quantity' => 4,
    ]);

    $asignation = Asignation::create([
        'tool_id' => $tool->id,
        'worker_id' => $worker->id,
        'assigned_quantity' => 1,
        'state' => 'buen estado',
        'date' => now()->toDateString(),
    ]);

    return compact('worker', 'tool', 'asignation');
}

describe('reviews index', function () {
    it('returns list of reviews', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker] = createWorkerForReviews($user);

        Review::create([
            'worker_id' => $worker->id,
            'reviewed_by' => $user->id,
            'name' => 'Revisión mayo',
        ]);

        $this->withToken($token)
            ->getJson('/api/reviews')
            ->assertSuccessful()
            ->assertJsonStructure(['data' => [['id', 'name', 'worker_id', 'worker']]]);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson('/api/reviews')->assertUnauthorized();
    });
});

describe('reviews store', function () {
    it('creates a complete review updating tool state', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'asignation' => $asignation] = createWorkerForReviews($user);

        $this->withToken($token)
            ->postJson('/api/reviews', [
                'worker_id' => $worker->id,
                'name' => 'Revisión mensual',
                'items' => [
                    ['asignation_id' => $asignation->id, 'quantity_present' => 1, 'new_state' => 'regular'],
                ],
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'worker']]);

        $this->assertDatabaseHas('reviews', ['name' => 'Revisión mensual', 'worker_id' => $worker->id]);
        $this->assertDatabaseHas('asignations', ['id' => $asignation->id, 'state' => 'regular']);
    });

    it('records lost tool, removes asignation and decrements quantity', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool, 'asignation' => $asignation] = createWorkerForReviews($user);

        $this->withToken($token)
            ->postJson('/api/reviews', [
                'worker_id' => $worker->id,
                'name' => 'Revisión con pérdida',
                'items' => [
                    ['asignation_id' => $asignation->id, 'quantity_present' => 0],
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('lost_tools', ['tool_id' => $tool->id, 'worker_id' => $worker->id]);
        $this->assertDatabaseMissing('asignations', ['id' => $asignation->id]);
        expect($tool->fresh()->quantity)->toBe(4);
    });

    it('allows multiple reviews for the same worker', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'asignation' => $asignation] = createWorkerForReviews($user);

        $payload = fn () => [
            'worker_id' => $worker->id,
            'name' => 'Revisión '.fake()->word(),
            'items' => [['asignation_id' => $asignation->id, 'quantity_present' => 1, 'new_state' => 'regular']],
        ];

        $this->withToken($token)->postJson('/api/reviews', $payload())->assertCreated();
        $this->withToken($token)->postJson('/api/reviews', $payload())->assertCreated();

        expect(Review::where('worker_id', $worker->id)->count())->toBe(2);
    });

    it('returns 422 when required fields are missing', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/reviews', [])
            ->assertUnprocessable();
    });

    it('handles partial loss — decrements quantity and updates remaining', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForReviews($user);

        $asignation = Asignation::where('worker_id', $worker->id)->first();
        $asignation->update(['assigned_quantity' => 3]);
        $tool->update(['quantity' => 7, 'unassigned_quantity' => 4]);

        $this->withToken($token)
            ->postJson('/api/reviews', [
                'worker_id' => $worker->id,
                'name' => 'Revisión con pérdida parcial',
                'items' => [
                    ['asignation_id' => $asignation->id, 'quantity_present' => 2, 'new_state' => 'regular'],
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('lost_tools', ['tool_id' => $tool->id, 'quantity_lost' => 1]);
        $this->assertDatabaseHas('asignations', ['id' => $asignation->id, 'assigned_quantity' => 2, 'state' => 'regular']);
        expect($tool->fresh()->quantity)->toBe(6);
    });

    it('rolls back on failure leaving no partial data', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker] = createWorkerForReviews($user);

        $this->withToken($token)
            ->postJson('/api/reviews', [
                'worker_id' => $worker->id,
                'name' => 'Revisión inválida',
                'items' => [
                    ['asignation_id' => 99999, 'quantity_present' => 1, 'new_state' => 'regular'],
                ],
            ])
            ->assertUnprocessable();

        expect(Review::count())->toBe(0);
    });
});
