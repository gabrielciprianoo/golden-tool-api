<?php

use App\Models\Asignation;
use App\Models\Tool;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWorkerForAsignations(User $user): array
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
        'quantity' => 10,
        'unassigned_quantity' => 10,
        'warranty' => 'sin garantia',
    ]);

    return compact('worker', 'tool');
}

describe('asignations index', function () {
    it('returns paginated asignations', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        Asignation::create([
            'tool_id' => $tool->id,
            'worker_id' => $worker->id,
            'assigned_quantity' => 1,
            'state' => 'nuevo',
            'date' => now()->toDateString(),
        ]);

        $this->withToken($token)
            ->getJson('/api/asignations')
            ->assertSuccessful();
    });

    it('filters by worker_id', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        Asignation::create([
            'tool_id' => $tool->id,
            'worker_id' => $worker->id,
            'assigned_quantity' => 1,
            'state' => 'nuevo',
            'date' => now()->toDateString(),
        ]);

        $this->withToken($token)
            ->getJson('/api/asignations?worker_id='.$worker->id)
            ->assertSuccessful();
    });

    it('is accessible without authentication', function () {
        $this->getJson('/api/asignations')->assertSuccessful();
    });
});

describe('asignations store', function () {
    it('creates an asignation and decrements tool unassigned_quantity', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        $this->withToken($token)
            ->postJson('/api/asignations', [
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 2,
                'state' => 'nuevo',
                'date' => now()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonStructure(['data']);

        $this->assertDatabaseHas('asignations', [
            'tool_id' => $tool->id,
            'worker_id' => $worker->id,
            'assigned_quantity' => 1,
        ]);

        expect($tool->fresh()->unassigned_quantity)->toBe(8);
    });

    it('returns 400 when insufficient stock', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        $this->withToken($token)
            ->postJson('/api/asignations', [
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 20,
                'state' => 'nuevo',
                'date' => now()->toDateString(),
            ])
            ->assertStatus(400)
            ->assertJsonFragment(['message' => 'No hay suficiente stock disponible']);
    });

    it('returns 422 when required fields are missing', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/asignations', [])
            ->assertUnprocessable();
    });

    it('validates state must be a valid option', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        $this->withToken($token)
            ->postJson('/api/asignations', [
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
                'state' => 'invalid-state',
                'date' => now()->toDateString(),
            ])
            ->assertUnprocessable();
    });
});

describe('asignations show', function () {
    it('returns a specific asignation', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        $asignation = Asignation::create([
            'tool_id' => $tool->id,
            'worker_id' => $worker->id,
            'assigned_quantity' => 1,
            'state' => 'nuevo',
            'date' => now()->toDateString(),
        ]);

        $this->withToken($token)
            ->getJson('/api/asignations/'.$asignation->id)
            ->assertSuccessful()
            ->assertJsonStructure(['data']);
    });
});

describe('asignations update', function () {
    it('updates asignation state', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        $asignation = Asignation::create([
            'tool_id' => $tool->id,
            'worker_id' => $worker->id,
            'assigned_quantity' => 1,
            'state' => 'nuevo',
            'date' => now()->toDateString(),
        ]);

        $this->withToken($token)
            ->putJson('/api/asignations/'.$asignation->id, [
                'state' => 'buen estado',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('asignations', [
            'id' => $asignation->id,
            'state' => 'buen estado',
        ]);
    });
});

describe('asignations destroy', function () {
    it('deletes asignation and returns stock to tool', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        $asignation = Asignation::create([
            'tool_id' => $tool->id,
            'worker_id' => $worker->id,
            'assigned_quantity' => 2,
            'state' => 'nuevo',
            'date' => now()->toDateString(),
        ]);

        $tool->decrement('unassigned_quantity', 2);

        $this->withToken($token)
            ->deleteJson('/api/asignations/'.$asignation->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('asignations', ['id' => $asignation->id]);
        expect($tool->fresh()->unassigned_quantity)->toBe(10);
    });
});

describe('asignations by worker', function () {
    it('returns asignations for a specific worker', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker, 'tool' => $tool] = createWorkerForAsignations($user);

        Asignation::create([
            'tool_id' => $tool->id,
            'worker_id' => $worker->id,
            'assigned_quantity' => 1,
            'state' => 'nuevo',
            'date' => now()->toDateString(),
        ]);

        $this->withToken($token)
            ->getJson('/api/asignations/worker/'.$worker->id)
            ->assertSuccessful()
            ->assertJsonStructure(['success', 'data']);
    });
});
