<?php

use App\Models\Asignation;
use App\Models\Request as AppRequest;
use App\Models\Tool;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Comprehensive Application Flow', function () {

    describe('Step 1: Authentication', function () {
        it('can authenticate using token directly', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/tools')
                ->assertSuccessful();
        });

        it('unauthenticated user cannot access protected routes', function () {
            $this->getJson('/api/asignations')->assertSuccessful();
        });
    });

    describe('Step 2: Tool Management', function () {
        it('admin can create a tool', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/tool', [
                    'name' => 'Taladro Percutor',
                    'category' => 'normal',
                    'price' => 750.00,
                    'supplier' => 'Herramientas Max',
                    'entry_date' => now()->toDateString(),
                    'quantity' => 10,
                    'warranty' => 'con garantia',
                ])
                ->assertCreated()
                ->assertJsonStructure(['message', 'data']);

            $this->assertDatabaseHas('tools', [
                'name' => 'Taladro Percutor',
                'quantity' => 10,
                'unassigned_quantity' => 10,
            ]);
        });

        it('tool inventory is tracked correctly', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $tool = Tool::create([
                'name' => 'Sierra Circular',
                'category' => 'normal',
                'price' => 500.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 5,
                'warranty' => 'sin garantia',
            ]);

            $this->assertDatabaseHas('tools', ['name' => 'Sierra Circular', 'quantity' => 5]);
        });

        it('tool quantity can be updated', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $tool = Tool::create([
                'name' => 'Llave Stilson',
                'category' => 'normal',
                'price' => 200.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 3,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->putJson("/api/tool/{$tool->id}", ['quantity' => 15])
                ->assertSuccessful();

            expect($tool->fresh()->unassigned_quantity)->toBe(13);
        });
    });

    describe('Step 3: Worker Management', function () {
        it('admin can create a worker directly', function () {
            $user = User::factory()->create(['type_user' => 1]);

            $worker = Worker::create([
                'name' => 'Pedro',
                'lastname' => 'García',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->assertDatabaseHas('workers', [
                'name' => 'Pedro',
                'lastname' => 'García',
                'area' => 'montaje/desmontaje',
            ]);
        });

        it('worker gets auto-generated code', function () {
            $user = User::factory()->create(['type_user' => 1]);

            $worker = Worker::create([
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
    });

    describe('Step 4: Request Management', function () {
        it('worker can request tools (incomplete state)', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Carlos',
                'lastname' => 'Martínez',
                'worker_code' => 'TRB-0001',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/requests', [
                    'worker_id' => $worker->id,
                    'type_request' => 'PRIMERA_VEZ',
                    'details_tool' => 'Necesito herramientas básicas',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('requests', [
                'worker_id' => $worker->id,
                'state' => 'incompleta',
            ]);
        });

        it('request with both signatures becomes pendiente_compra', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Luis',
                'lastname' => 'Hernández',
                'worker_code' => 'TRB-0002',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $this->withToken($token)
                ->postJson('/api/requests', [
                    'worker_id' => $worker->id,
                    'type_request' => 'SE_ROMPIO',
                    'details_tool' => 'Mi herramienta se rompió',
                    'signa_applicant' => 'base64_signature_1',
                    'signa_authorization' => 'base64_signature_2',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('requests', [
                'worker_id' => $worker->id,
                'state' => 'pendiente_compra',
            ]);
        });

        it('request state can be updated', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Miguel',
                'lastname' => 'Torres',
                'worker_code' => 'TRB-0003',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $request = AppRequest::create([
                'worker_id' => $worker->id,
                'created_by' => $user->id,
                'type_request' => 'DESGASTE',
                'details_tool' => 'Herramientas gastadas',
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

        it('requests can be listed by worker', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Rosa',
                'lastname' => 'Flores',
                'worker_code' => 'TRB-0004',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            AppRequest::create([
                'worker_id' => $worker->id,
                'created_by' => $user->id,
                'type_request' => 'PRIMERA_VEZ',
                'details_tool' => 'Primera solicitud',
                'state' => 'incompleta',
            ]);

            $this->withToken($token)
                ->getJson("/api/requests/worker/{$worker->id}")
                ->assertSuccessful()
                ->assertJsonStructure(['success', 'data']);
        });
    });

    describe('Step 5: Asignation Management', function () {
        it('tool can be assigned to worker', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Jorge',
                'lastname' => 'Ruiz',
                'worker_code' => 'TRB-0005',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Destornillador Set',
                'category' => 'normal',
                'price' => 300.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 10,
                'warranty' => 'sin garantia',
            ]);

            $this->withToken($token)
                ->postJson('/api/asignations', [
                    'tool_id' => $tool->id,
                    'worker_id' => $worker->id,
                    'assigned_quantity' => 2,
                    'state' => 'nuevo',
                    'date' => now()->toDateString(),
                ])
                ->assertCreated();

            $this->assertDatabaseHas('asignations', [
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 1,
            ]);

            expect($tool->fresh()->unassigned_quantity)->toBe(8);
        });

        it('asignation fails with insufficient stock', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Sara',
                'lastname' => 'Vega',
                'worker_code' => 'TRB-0006',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Martillo',
                'category' => 'normal',
                'price' => 150.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 3,
                'unassigned_quantity' => 3,
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

        it('asignation state can be updated', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Mario',
                'lastname' => 'Lima',
                'worker_code' => 'TRB-0007',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Cinta Métrica',
                'category' => 'normal',
                'price' => 50.00,
                'supplier' => 'Proveedor SA',
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

        it('asignation can be deleted and stock is returned', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Elena',
                'lastname' => 'Reyes',
                'worker_code' => 'TRB-0008',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Pala',
                'category' => 'normal',
                'price' => 180.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 3,
                'warranty' => 'sin garantia',
            ]);

            $asignation = Asignation::create([
                'tool_id' => $tool->id,
                'worker_id' => $worker->id,
                'assigned_quantity' => 2,
                'state' => 'nuevo',
                'date' => now()->toDateString(),
            ]);

            $tool->decrement('unassigned_quantity', 2);

            $this->withToken($token)
                ->deleteJson("/api/asignations/{$asignation->id}")
                ->assertSuccessful();

            $this->assertDatabaseMissing('asignations', ['id' => $asignation->id]);
            expect($tool->fresh()->unassigned_quantity)->toBe(3);
        });

        it('asignations can be filtered by worker', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Pablo',
                'lastname' => 'Núñez',
                'worker_code' => 'TRB-0009',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Cincel',
                'category' => 'normal',
                'price' => 100.00,
                'supplier' => 'Proveedor SA',
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
    });

    describe('Step 6: Review Management', function () {
        it('complete review updates tool state', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Carmen',
                'lastname' => 'Ortiz',
                'worker_code' => 'TRB-0010',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Nivel',
                'category' => 'normal',
                'price' => 250.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 4,
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

        it('review with lost tool removes asignation', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Raúl',
                'lastname' => 'Castro',
                'worker_code' => 'TRB-0011',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Pinza',
                'category' => 'normal',
                'price' => 80.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'unassigned_quantity' => 4,
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
            expect($tool->fresh()->quantity)->toBe(4);
        });

        it('partial loss decrements quantity correctly', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $worker = Worker::create([
                'name' => 'Isabel',
                'lastname' => 'Mendoza',
                'worker_code' => 'TRB-0012',
                'area' => 'montaje/desmontaje',
                'created_by' => $user->id,
            ]);

            $tool = Tool::create([
                'name' => 'Set Destornilladores',
                'category' => 'normal',
                'price' => 350.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'unassigned_quantity' => 8,
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

            expect($tool->fresh()->quantity)->toBe(8);
        });
    });

    describe('Edge Cases & Error Handling', function () {
        it('validates required fields on tool creation', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->postJson('/api/tool', [])
                ->assertUnprocessable();
        });

        it('validates warranty format', function () {
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

        it('returns 404 for non-existent resources', function () {
            $user = User::factory()->create(['type_user' => 1]);
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/tool/99999')
                ->assertNotFound();

            $this->withToken($token)
                ->patchJson('/api/requests/99999', ['state' => 'test'])
                ->assertNotFound();
        });
    });
});
