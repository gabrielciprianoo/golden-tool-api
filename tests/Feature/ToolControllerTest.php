<?php

use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('tools index', function () {
    it('returns all tools', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        Tool::create([
            'name' => 'Taladro',
            'category' => 'normal',
            'price' => 500.00,
            'supplier' => 'Proveedor SA',
            'entry_date' => now()->toDateString(),
            'quantity' => 5,
            'unassigned_quantity' => 5,
            'warranty' => 'sin garantia',
        ]);

        $this->withToken($token)
            ->getJson('/api/tools')
            ->assertSuccessful()
            ->assertJsonStructure(['*' => ['id', 'name', 'category', 'price', 'quantity']]);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson('/api/tools')->assertUnauthorized();
    });
});

describe('tools store', function () {
    it('creates a tool with valid data', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/tool', [
                'name' => 'Destornillador',
                'category' => 'normal',
                'price' => 150.00,
                'supplier' => 'Herramientas ABC',
                'entry_date' => now()->toDateString(),
                'quantity' => 10,
                'warranty' => 'sin garantia',
            ])
            ->assertCreated()
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('tools', [
            'name' => 'Destornillador',
            'quantity' => 10,
            'unassigned_quantity' => 10,
        ]);
    });

    it('sets unassigned_quantity equal to quantity on creation', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/tool', [
                'name' => 'Martillo',
                'category' => 'normal',
                'price' => 200.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 5,
                'warranty' => 'con garantia',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('tools', [
            'name' => 'Martillo',
            'quantity' => 5,
            'unassigned_quantity' => 5,
        ]);
    });

    it('returns 422 when required fields are missing', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/tool', [])
            ->assertUnprocessable();
    });

    it('validates warranty must be con garantia or sin garantia', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/tool', [
                'name' => 'Sierra',
                'category' => 'normal',
                'price' => 300.00,
                'supplier' => 'Proveedor SA',
                'entry_date' => now()->toDateString(),
                'quantity' => 3,
                'warranty' => 'invalid-warranty',
            ])
            ->assertUnprocessable();
    });
});

describe('tools show', function () {
    it('returns a specific tool', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

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

        $this->withToken($token)
            ->getJson("/api/tool/{$tool->id}")
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'Taladro']);
    });

    it('returns 404 when tool not found', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/tool/99999')
            ->assertNotFound();
    });
});

describe('tools update', function () {
    it('updates a tool with valid data', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

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

        $this->withToken($token)
            ->putJson("/api/tool/{$tool->id}", [
                'name' => 'Taladro Actualizado',
                'price' => 600.00,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('tools', [
            'id' => $tool->id,
            'name' => 'Taladro Actualizado',
            'price' => 600.00,
        ]);
    });

    it('increments unassigned_quantity when quantity is increased', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $tool = Tool::create([
            'name' => 'Taladro',
            'category' => 'normal',
            'price' => 500.00,
            'supplier' => 'Proveedor SA',
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

    it('returns 404 when tool not found', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/tool/99999', ['name' => 'Test'])
            ->assertNotFound();
    });
});

describe('tools destroy', function () {
    it('deletes a tool', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

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

        $this->withToken($token)
            ->deleteJson("/api/tool/{$tool->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('tools', ['id' => $tool->id]);
    });

    it('returns 404 when tool not found', function () {
        $user = User::factory()->create(['type_user' => 1]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/tool/99999')
            ->assertNotFound();
    });
});
