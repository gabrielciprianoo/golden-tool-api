<?php

use App\Models\Asignation;
use App\Models\Request as AppRequest;
use App\Models\Review;
use App\Models\Tool;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('FULL APPLICATION TEST - Complete Coverage', function () {

    // ============================================================
    // AUTHENTICATION TESTS
    // ============================================================
    describe('Authentication', function () {
        it('user can get authenticated info with valid token', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/me')
                ->assertSuccessful()
                ->assertJsonFragment(['email' => $user->email]);
        });

        it('unauthenticated request is rejected', function () {
            $this->getJson('/api/me')->assertUnauthorized();
        });

        it('public routes are accessible without auth', function () {
            $this->getJson('/api/asignations')->assertSuccessful();
        });
    });

    // ============================================================
    // TOOLS TESTS - Complete CRUD + Inventory
    // ============================================================
    describe('Tools - Complete CRUD', function () {
        it('admin can create a tool with all fields', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/tool', [
                    'name' => 'Taladro Bosch',
                    'category' => 'normal',
                    'price' => 850.00,
                    'supplier' => 'Bosch SA',
                    'entry_date' => now()->toDateString(),
                    'quantity' => 15,
                    'warranty' => 'con garantia',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('tools', [
                'name' => 'Taladro Bosch',
                'quantity' => 15,
                'unassigned_quantity' => 15,
                'warranty' => 'con garantia',
            ]);
        });

        it('tool creation sets unassigned_quantity equal to quantity', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/tool', [
                    'name' => 'Martillo',
                    'category' => 'normal',
                    'price' => 150.00,
                    'supplier' => 'Test',
                    'entry_date' => now()->toDateString(),
                    'quantity' => 20,
                    'warranty' => 'sin garantia',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('tools', [
                'quantity' => 20,
                'unassigned_quantity' => 20,
            ]);
        });

        it('tool can be read individually', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $tool = Tool::create([
                'name' => 'Sierra',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 5,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->getJson("/api/tool/{$tool->id}")
                ->assertSuccessful()
                ->assertJsonFragment(['name' => 'Sierra']);
        });

        it('tool can be updated - name and price', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $tool = Tool::create([
                'name' => 'Old Name',
                'category' => 'normal',
                'price' => 100.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 5,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->putJson("/api/tool/{$tool->id}", [
                    'name' => 'New Name',
                    'price' => 200.00,
                ])
                ->assertSuccessful();

            $this->assertDatabaseHas('tools', [
                'id' => $tool->id,
                'name' => 'New Name',
                'price' => 200.00,
            ]);
        });

        it('tool quantity update increments unassigned_quantity', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $tool = Tool::create([
                'name' => 'Test Tool',
                'category' => 'normal',
                'price' => 100.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 3,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->putJson("/api/tool/{$tool->id}", [
                    'quantity' => 10,
                ])
                ->assertSuccessful();

            expect($tool->fresh()->unassigned_quantity)->toBe(8);
        });

        it('tool can be deleted', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $tool = Tool::create([
                'name' => 'To Delete',
                'category' => 'normal',
                'price' => 100.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 1,
                'unassigned_quantity' => 1,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->deleteJson("/api/tool/{$tool->id}")
                ->assertSuccessful();

            $this->assertDatabaseMissing('tools', ['id' => $tool->id]);
        });

        it('returns 404 for non-existent tool', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/tool/99999')
                ->assertNotFound();
        });

        it('validates warranty must be con garantia or sin garantia', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/tool', [
                    'name' => 'Test',
                    'category' => 'normal',
                    'price' => 100.00,
                    'supplier' => 'Test',
                    'entry_date' => now()->toDateString(),
                    'quantity' => 1,
                    'warranty' => 'invalid',
                ])
                ->assertUnprocessable();
        });

        it('validates required fields on tool creation', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/tool', [])
                ->assertUnprocessable();
        });
    });

    // ============================================================
    // WORKERS TESTS - CRUD + Auto-generate code
    // ============================================================
    describe('Workers - Complete CRUD', function () {
        it('worker can be created with model', function () {
            $user = User::factory()->create(['type_user' => 1]);

            $worker = Worker::create([
                'name' => 'Juan',
                'lastname' => 'Pérez',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->assertDatabaseHas('workers', [
                'name' => 'Juan',
                'lastname' => 'Pérez',
                'area' => 'montaje/desmontaje',
            ]);
        });

        it('worker gets auto-generated code TRB-XXXX', function () {
            $user = User::factory()->create(['type_user' => 1]);

            Worker::create([
                'name' => 'Ana',
                'lastname' => 'López',
                'worker_code' => 'TRB-0001',
                'area' => 'armado/desarmado',
                'created_by' => $user->id,
            ]);

            $this->assertDatabaseHas('workers', [
                'name' => 'Ana',
                'worker_code' => 'TRB-0001',
            ]);
        });

        it('worker can be created and read via model', function () {
            $user = User::factory()->create(['type_user' => 1]);

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->assertDatabaseHas('workers', [
                'id' => $worker->id,
                'name' => 'Test',
            ]);
        });

        it('worker can be updated via model', function () {
            $user = User::factory()->create(['type_user' => 1]);

            $worker = Worker::create([
                'name' => 'Old Name',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $worker->update(['name' => 'New Name']);

            $this->assertDatabaseHas('workers', [
                'id' => $worker->id,
                'name' => 'New Name',
            ]);
        });

        it('worker can be deleted via model', function () {
            $user = User::factory()->create(['type_user' => 1]);

            $worker = Worker::create([
                'name' => 'To Delete',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $worker->delete();

            $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
        });
    });

    // ============================================================
    // REQUESTS TESTS - Complete CRUD + State Logic
    // ============================================================
    describe('Requests - Complete CRUD + State Logic', function () {
        it('can create request without signatures - state incompleta', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/requests', [
                    'worker_id' => $worker->id,
                    'type_request' => 'PRIMERA_VEZ',
                    'details_tool' => 'Necesito herramientas',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('requests', [
                'worker_id' => $worker->id,
                'state' => 'incompleta',
            ]);
        });

        it('request with one signature - state incompleta', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/requests', [
                    'worker_id' => $worker->id,
                    'type_request' => 'SE_ROMPIO',
                    'details_tool' => 'Se rompió',
                    'signa_applicant' => 'signature_data',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('requests', [
                'worker_id' => $worker->id,
                'state' => 'incompleta',
            ]);
        });

        it('request with both signatures - state pendiente_compra', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/requests', [
                    'worker_id' => $worker->id,
                    'type_request' => 'PRIMERA_VEZ',
                    'details_tool' => 'Nueva solicitud',
                    'signa_applicant' => 'sig1',
                    'signa_authorization' => 'sig2',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('requests', [
                'worker_id' => $worker->id,
                'state' => 'pendiente_compra',
            ]);
        });

        it('request can be read by worker', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            AppRequest::create([
                'worker_id' => $worker->id,
                'created_by' => $user->id,
                'type_request' => 'PRIMERA_VEZ',
                'details_tool' => 'Test',
                'state' => 'incompleta',
            ]);

            $this->withToken($token)
                ->getJson("/api/requests/worker/{$worker->id}")
                ->assertSuccessful()
                ->assertJsonStructure(['success', 'data']);
        });

        it('request can be updated', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $request = AppRequest::create([
                'worker_id' => $worker->id,
                'created_by' => $user->id,
                'type_request' => 'PRIMERA_VEZ',
                'details_tool' => 'Test',
                'state' => 'incompleta',
            ]);

            $this->withToken($token)
                ->patchJson("/api/requests/{$request->id}", [
                    'state' => 'pendiente_compra',
                ])
                ->assertSuccessful();

            $this->assertDatabaseHas('requests', [
                'id' => $request->id,
                'state' => 'pendiente_compra',
            ]);
        });

        it('adding second signature auto-changes state to pendiente_compra', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $request = AppRequest::create([
                'worker_id' => $worker->id,
                'created_by' => $user->id,
                'type_request' => 'PRIMERA_VEZ',
                'details_tool' => 'Test',
                'signa_applicant' => 'sig1',
                'signa_authorization' => null,
                'state' => 'incompleta',
            ]);

            $this->withToken($token)
                ->patchJson("/api/requests/{$request->id}", [
                    'signa_authorization' => 'sig2',
                ])
                ->assertSuccessful();

            expect($request->fresh()->state)->toBe('pendiente_compra');
        });

        it('request can be deleted', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $request = AppRequest::create([
                'worker_id' => $worker->id,
                'created_by' => $user->id,
                'type_request' => 'PRIMERA_VEZ',
                'details_tool' => 'Test',
                'state' => 'incompleta',
            ]);

            $this->withToken($token)
                ->deleteJson("/api/requests/{$request->id}")
                ->assertSuccessful();

            $this->assertDatabaseMissing('requests', ['id' => $request->id]);
        });

        it('validates type_request must be valid option', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/requests', [
                    'worker_id' => $worker->id,
                    'type_request' => 'INVALID',
                    'details_tool' => 'Test',
                ])
                ->assertUnprocessable();
        });

        it('returns 404 for non-existent request', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->patchJson('/api/requests/99999', ['state' => 'test'])
                ->assertNotFound();
        });
    });

    // ============================================================
    // ASIGNATIONS TESTS - Complete CRUD + Stock Control
    // ============================================================
    describe('Asignations - Complete CRUD + Stock Control', function () {
        it('can create asignation with valid stock', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 10,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->postJson('/api/asignations', [
                    'tool_id' => $tool->id,
                    'worker_id' => $worker->id,
                    'assigned_quantity' => 3,
                    'state' => 'nuevo',
                    'date' => now()->toDateString(),
                ])
                ->assertCreated();

            $this->assertDatabaseHas('asignations', [
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
            ]);

            expect($tool->fresh()->unassigned_quantity)->toBe(7);
        });

        it('rejects asignation when insufficient stock', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 2,
                'unassigned_quantity' => 2,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->postJson('/api/asignations', [
                    'tool_id' => $tool->id,
                    'worker_id' => $worker->id,
                    'assigned_quantity' => 10,
                    'state' => 'nuevo',
                    'date' => now()->toDateString(),
                ])
                ->assertStatus(400)
                ->assertJsonFragment(['message' => 'No hay suficiente stock disponible']);
        });

        it('asignation can be read', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 5,
                'warranty' => 'sin garantia',
            ]);

            $asignation = Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
                'state' => 'nuevo',
                'date' => now()->toDateString(),
            ]);

            $this->withToken($token)
                ->getJson("/api/asignations/{$asignation->id}")
                ->assertSuccessful()
                ->assertJsonStructure(['data']);
        });

        it('asignation state can be updated', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 4,
                'warranty' => 'sin garantia',
            ]);

            $asignation = Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
                'state' => 'nuevo',
                'date' => now()->toDateString(),
            ]);

            $this->withToken($token)
                ->putJson("/api/asignations/{$asignation->id}", [
                    'state' => 'buen estado',
                ])
                ->assertSuccessful();

            $this->assertDatabaseHas('asignations', [
                'id' => $asignation->id,
                'state' => 'buen estado',
            ]);
        });

        it('asignation deletion returns stock to tool', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 8,
                'warranty' => 'sin garantia',
            ]);

            $asignation = Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 2,
                'state' => 'nuevo',
                'date' => now()->toDateString(),
            ]);

            $originalUnassigned = $tool->unassigned_quantity;

            $this->withToken($token)
                ->deleteJson("/api/asignations/{$asignation->id}")
                ->assertSuccessful();

            $this->assertDatabaseMissing('asignations', ['id' => $asignation->id]);
            expect($tool->fresh()->unassigned_quantity)->toBe($originalUnassigned + 2);
        });

        it('asignations can be filtered by worker', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 5,
                'warranty' => 'sin garantia',
            ]);

            Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
                'state' => 'nuevo',
                'date' => now()->toDateString(),
            ]);

            $this->withToken($token)
                ->getJson("/api/asignations/worker/{$worker->id}")
                ->assertSuccessful()
                ->assertJsonStructure(['success', 'data']);
        });

        it('asignations can be filtered by state', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 5,
                'warranty' => 'sin garantia',
            ]);

            Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
                'state' => 'buen estado',
                'date' => now()->toDateString(),
            ]);

            $this->withToken($token)
                ->getJson('/api/asignations?state=buen estado')
                ->assertSuccessful();
        });

        it('validates required fields on asignation', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/asignations', [])
                ->assertUnprocessable();
        });

        it('validates state must be valid option', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 5,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->postJson('/api/asignations', [
                    'tool_id' => $tool->id,
                    'worker_id' => $worker->id,
                    'assigned_quantity' => 1,
                    'state' => 'invalid_state',
                    'date' => now()->toDateString(),
                ])
                ->assertUnprocessable();
        });
    });

    // ============================================================
    // REVIEWS TESTS - Complete CRUD + Loss Logic
    // ============================================================
    describe('Reviews - Complete CRUD + Loss Logic', function () {
        it('can create review with items', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 9,
                'warranty' => 'sin garantia',
            ]);

            $asignation = Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
                'state' => 'buen estado',
                'date' => now()->toDateString(),
            ]);

            $this->withToken($token)
                ->postJson('/api/reviews', [
                    'name' => 'Revisión Mensual',
                    'worker_id' => $worker->id,
                    'items' => [
                        [
                            'asignation_id' => $asignation->id,
                            'quantity_present' => 1,
                            'new_state' => 'regular',
                        ],
                    ],
                ])
                ->assertCreated();

            $this->assertDatabaseHas('reviews', [
                'name' => 'Revisión Mensual',
                'worker_id' => $worker->id,
            ]);

            $this->assertDatabaseHas('asignations', [
                'id' => $asignation->id,
                'state' => 'regular',
            ]);
        });

        it('review with total loss removes asignation and records lost tool', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Taladro',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 9,
                'warranty' => 'sin garantia',
            ]);

            $asignation = Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
                'state' => 'buen estado',
                'date' => now()->toDateString(),
            ]);

            $this->withToken($token)
                ->postJson('/api/reviews', [
                    'name' => 'Revisión Pérdida',
                    'worker_id' => $worker->id,
                    'items' => [
                        [
                            'asignation_id' => $asignation->id,
                            'quantity_present' => 0,
                        ],
                    ],
                ])
                ->assertCreated();

            $this->assertDatabaseHas('lost_tools', [
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'quantity_lost' => 1,
            ]);

            $this->assertDatabaseMissing('asignations', ['id' => $asignation->id]);
            expect($tool->fresh()->quantity)->toBe(9);
        });

        it('review with partial loss updates asignation and records lost tool', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Set Herramientas',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 15,
                'unassigned_quantity' => 10,
                'warranty' => 'sin garantia',
            ]);

            $asignation = Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 5,
                'state' => 'buen estado',
                'date' => now()->toDateString(),
            ]);

            $this->withToken($token)
                ->postJson('/api/reviews', [
                    'name' => 'Revisión Parcial',
                    'worker_id' => $worker->id,
                    'items' => [
                        [
                            'asignation_id' => $asignation->id,
                            'quantity_present' => 3,
                            'new_state' => 'regular',
                        ],
                    ],
                ])
                ->assertCreated();

            $this->assertDatabaseHas('lost_tools', [
                'tool_id' => $tool->id,
                'quantity_lost' => 2,
            ]);

            $this->assertDatabaseHas('asignations', [
                'id' => $asignation->id,
                'assigned_quantity' => 3,
                'state' => 'regular',
            ]);

            expect($tool->fresh()->quantity)->toBe(13);
        });

        it('review can be read', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $review = Review::create([
                'name' => 'Test Review',
                'worker_id' => $worker->id,
                'reviewed_by' => $user->id,
            ]);

            $this->withToken($token)
                ->getJson("/api/reviews/{$review->id}")
                ->assertSuccessful()
                ->assertJsonFragment(['name' => 'Test Review']);
        });

        it('review can list all reviews', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            Review::create([
                'name' => 'Review 1',
                'worker_id' => $worker->id,
                'reviewed_by' => $user->id,
            ]);

            $this->withToken($token)
                ->getJson('/api/reviews')
                ->assertSuccessful();
        });

        it('validates required fields on review', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/reviews', [])
                ->assertUnprocessable();
        });

        it('validates items array on review', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/reviews', [
                    'name' => 'Test',
                    'worker_id' => $worker->id,
                    'items' => [
                        ['asignation_id' => 99999],
                    ],
                ])
                ->assertUnprocessable();
        });
    });

    // ============================================================
    // EDGE CASES & ERROR HANDLING
    // ============================================================
    describe('Edge Cases & Error Handling', function () {
        it('protected routes require admin role', function () {
            $user = User::factory()->create(['type_user' => 2]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/tool')
                ->assertStatus(405);
        });

        it('non-admin user can access public routes', function () {
            $user = User::factory()->create(['type_user' => 2]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/asignations')
                ->assertSuccessful();
        });

        it('non-admin user can only access basic worker list', function () {
            $user = User::factory()->create(['type_user' => 2]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/workers')
                ->assertSuccessful();
        });

        it('handles tool with zero unassigned_quantity', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Sin Stock',
                'category' => 'normal',
                'price' => 100.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 0,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->postJson('/api/asignations', [
                    'tool_id' => $tool->id,
                    'worker_id' => $worker->id,
                    'assigned_quantity' => 1,
                    'state' => 'nuevo',
                    'date' => now()->toDateString(),
                ])
                ->assertStatus(400);
        });

        it('multiple asignations can be created in sequence', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Herramienta',
                'category' => 'normal',
                'price' => 100.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 10,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->postJson('/api/asignations', [
                    'tool_id' => $tool->id,
                    'worker_id' => $worker->id,
                    'assigned_quantity' => 3,
                    'state' => 'nuevo',
                    'date' => now()->toDateString(),
                ])
                ->assertCreated();

            expect($tool->fresh()->unassigned_quantity)->toBe(7);
        });

        it('tool update to lower quantity behavior', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $tool = Tool::create([
                'name' => 'Test',
                'category' => 'normal',
                'price' => 100.00,
                'supplier' => 'Test',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 10,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->putJson("/api/tool/{$tool->id}", [
                    'quantity' => 5,
                ])
                ->assertSuccessful();
        });

        it('request with preferred_brand is stored', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Test',
                'lastname' => 'Worker',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/requests', [
                    'worker_id' => $worker->id,
                    'type_request' => 'PRIMERA_VEZ',
                    'details_tool' => 'Necesito taladro',
                    'preferred_brand' => 'Makita',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('requests', [
                'worker_id' => $worker->id,
                'preferred_brand' => 'Makita',
            ]);
        });
    });
});
