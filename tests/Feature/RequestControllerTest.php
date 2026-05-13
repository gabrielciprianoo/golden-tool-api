<?php

use App\Models\Request;
use App\Models\Tool;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWorkerForRequests(User $user): array
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
        'unassigned_quantity' => 5,
        'warranty' => 'sin garantia',
    ]);

    return compact('worker', 'tool');
}

describe('requests index by worker', function () {
    it('returns requests for a specific worker', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker] = createWorkerForRequests($user);

        Request::create([
            'worker_id' => $worker->id,
            'created_by' => $user->id,
            'tool_id' => null,
            'type_request' => 'PRIMERA_VEZ',
            'details_tool' => 'Necesito herramientas',
            'state' => 'incompleta',
        ]);

        $this->withToken($token)
            ->getJson("/api/requests/worker/{$worker->id}")
            ->assertSuccessful()
            ->assertJsonStructure(['success', 'data']);
    });
});

describe('requests index created by', function () {
    it('returns all requests created by user', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker] = createWorkerForRequests($user);

        Request::create([
            'worker_id' => $worker->id,
            'created_by' => $user->id,
            'tool_id' => null,
            'type_request' => 'PRIMERA_VEZ',
            'details_tool' => 'Solicitud de prueba',
            'state' => 'incompleta',
        ]);

        $this->withToken($token)
            ->getJson('/api/requests/created-by-me')
            ->assertSuccessful()
            ->assertJsonStructure(['success', 'data']);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson('/api/requests/created-by-me')->assertUnauthorized();
    });
});

describe('requests store', function () {
    it('creates a request with both signatures sets state to pendiente_compra', function () {
        $user = User::factory()->create(['type_user' => 1]);
        ['worker' => $worker] = createWorkerForRequests($user);

        $this->postJson('/api/requests', [
            'worker_id' => $worker->id,
            'type_request' => 'PRIMERA_VEZ',
            'details_tool' => 'Necesito herramientas nuevas',
            'preferred_brand' => 'Makita',
            'signa_applicant' => 'signature_data_1',
            'signa_authorization' => 'signature_data_2',
        ])
            ->assertCreated()
            ->assertJsonStructure(['success', 'data', 'message']);

        $this->assertDatabaseHas('requests', [
            'worker_id' => $worker->id,
            'type_request' => 'PRIMERA_VEZ',
            'state' => 'pendiente_compra',
        ]);
    });

    it('creates a request with one signature sets state to incompleta', function () {
        $user = User::factory()->create(['type_user' => 1]);
        ['worker' => $worker] = createWorkerForRequests($user);

        $this->postJson('/api/requests', [
            'worker_id' => $worker->id,
            'type_request' => 'SE_ROMPIO',
            'details_tool' => 'Mi herramienta se rompió',
            'signa_applicant' => 'signature_data',
        ])
            ->assertCreated();

        $this->assertDatabaseHas('requests', [
            'worker_id' => $worker->id,
            'state' => 'incompleta',
        ]);
    });

    it('creates a request without signatures sets state to incompleta', function () {
        $user = User::factory()->create(['type_user' => 1]);
        ['worker' => $worker] = createWorkerForRequests($user);

        $this->postJson('/api/requests', [
            'worker_id' => $worker->id,
            'type_request' => 'DESGASTE',
            'details_tool' => 'Herramientas gastadas',
        ])
            ->assertCreated();

        $this->assertDatabaseHas('requests', [
            'worker_id' => $worker->id,
            'state' => 'incompleta',
        ]);
    });

    it('returns 422 when required fields are missing', function () {
        $this->postJson('/api/requests', [])
            ->assertUnprocessable();
    });

    it('validates type_request must be a valid option', function () {
        $user = User::factory()->create(['type_user' => 1]);
        ['worker' => $worker] = createWorkerForRequests($user);

        $this->postJson('/api/requests', [
            'worker_id' => $worker->id,
            'type_request' => 'INVALID_TYPE',
            'details_tool' => 'Test',
        ])
            ->assertUnprocessable();
    });
});

describe('requests update', function () {
    it('updates request state', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker] = createWorkerForRequests($user);

        $requestModel = Request::create([
            'worker_id' => $worker->id,
            'created_by' => $user->id,
            'tool_id' => null,
            'type_request' => 'PRIMERA_VEZ',
            'details_tool' => 'Solicitud inicial',
            'state' => 'incompleta',
        ]);

        $this->withToken($token)
            ->patchJson("/api/requests/{$requestModel->id}", [
                'state' => 'pendiente_compra',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('requests', [
            'id' => $requestModel->id,
            'state' => 'pendiente_compra',
        ]);
    });

    it('updates signatures and auto-changes state when both signatures present', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker] = createWorkerForRequests($user);

        $requestModel = Request::create([
            'worker_id' => $worker->id,
            'created_by' => $user->id,
            'tool_id' => null,
            'type_request' => 'PRIMERA_VEZ',
            'details_tool' => 'Solicitud incompleta',
            'signa_applicant' => 'sig1',
            'signa_authorization' => null,
            'state' => 'incompleta',
        ]);

        $this->withToken($token)
            ->patchJson("/api/requests/{$requestModel->id}", [
                'signa_authorization' => 'sig2',
            ])
            ->assertSuccessful();

        expect($requestModel->fresh()->state)->toBe('pendiente_compra');
    });

    it('returns 404 when request not found', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/requests/99999', ['state' => 'aprobada'])
            ->assertNotFound();
    });
});

describe('requests destroy', function () {
    it('deletes a request', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;
        ['worker' => $worker] = createWorkerForRequests($user);

        $requestModel = Request::create([
            'worker_id' => $worker->id,
            'created_by' => $user->id,
            'tool_id' => null,
            'type_request' => 'PRIMERA_VEZ',
            'details_tool' => 'Para eliminar',
            'state' => 'incompleta',
        ]);

        $this->withToken($token)
            ->deleteJson("/api/requests/{$requestModel->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('requests', ['id' => $requestModel->id]);
    });

    it('returns 404 when request not found', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/requests/99999')
            ->assertNotFound();
    });
});
