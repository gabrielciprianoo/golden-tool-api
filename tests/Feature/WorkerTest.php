<?php

use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('workers index returns workers list', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    Worker::create([
        'name' => 'Juan',
        'lastname' => 'Perez',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    $this->withToken($token)
        ->getJson('/api/workers')
        ->assertSuccessful()
        ->assertJsonCount(1);
});

test('can create worker', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/workers', [
            'name' => 'Carlos',
            'lastname' => 'Lopez',
            'area' => 'armado/desarmado',
        ])
        ->assertCreated()
        ->assertJsonStructure(['id', 'name', 'lastname', 'area', 'worker_code']);

    $this->assertDatabaseHas('workers', [
        'name' => 'Carlos',
        'lastname' => 'Lopez',
        'area' => 'armado/desarmado',
    ]);
});

test('worker creation fails with invalid data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/workers', [
            'name' => '',
            'lastname' => '',
            'area' => 'invalid',
        ])
        ->assertUnprocessable();
});

test('can update worker', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $worker = Worker::create([
        'name' => 'Original',
        'lastname' => 'Name',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    $this->withToken($token)
        ->putJson("/api/workers/{$worker->id}", [
            'name' => 'Updated',
            'lastname' => 'Name',
            'area' => 'armado/desarmado',
        ])
        ->assertSuccessful()
        ->assertJsonPath('name', 'Updated');

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'name' => 'Updated',
        'area' => 'armado/desarmado',
    ]);
});

test('can delete worker', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $worker = Worker::create([
        'name' => 'ToDelete',
        'lastname' => 'Test',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    $this->withToken($token)
        ->deleteJson("/api/workers/{$worker->id}")
        ->assertSuccessful();

    $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
});

test('worker code is auto generated', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/workers', [
            'name' => 'Test',
            'lastname' => 'Worker',
            'area' => 'montaje/desmontaje',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('workers', [
        'worker_code' => 'TRB-0001',
    ]);
});

test('workers returns all workers', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    Worker::create([
        'name' => 'Juan',
        'lastname' => 'Perez',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    Worker::create([
        'name' => 'Maria',
        'lastname' => 'Garcia',
        'worker_code' => 'TRB-0002',
        'area' => 'armado/desarmado',
        'created_by' => $user->id,
    ]);

    $this->withToken($token)
        ->getJson('/api/workers')
        ->assertSuccessful()
        ->assertJsonCount(2);
});
