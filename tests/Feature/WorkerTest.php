<?php

use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('workers index returns workers list', function () {
    $user = User::factory()->create();

    Worker::create([
        'name' => 'Juan',
        'lastname' => 'Perez',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->get('/workers');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->has('workers', 1));
});

test('can create worker', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/workers', [
        'name' => 'Carlos',
        'lastname' => 'Lopez',
        'area' => 'armado/desarmado',
    ]);

    $response->assertRedirect('/workers');
    $this->assertDatabaseHas('workers', [
        'name' => 'Carlos',
        'lastname' => 'Lopez',
        'area' => 'armado/desarmado',
    ]);
});

test('worker creation fails with invalid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/workers', [
        'name' => '',
        'lastname' => '',
        'area' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['name', 'lastname', 'area']);
});

test('can update worker', function () {
    $user = User::factory()->create();
    $worker = Worker::create([
        'name' => 'Original',
        'lastname' => 'Name',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->put("/workers/{$worker->id}", [
        'name' => 'Updated',
        'lastname' => 'Name',
        'area' => 'armado/desarmado',
    ]);

    $response->assertRedirect('/workers');
    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'name' => 'Updated',
        'area' => 'armado/desarmado',
    ]);
});

test('can delete worker', function () {
    $user = User::factory()->create();
    $worker = Worker::create([
        'name' => 'ToDelete',
        'lastname' => 'Test',
        'worker_code' => 'TRB-0001',
        'area' => 'montaje/desmontaje',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete("/workers/{$worker->id}");

    $response->assertRedirect('/workers');
    $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
});

test('worker code is auto generated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/workers', [
        'name' => 'Test',
        'lastname' => 'Worker',
        'area' => 'montaje/desmontaje',
    ]);

    $this->assertDatabaseHas('workers', [
        'worker_code' => 'TRB-0001',
    ]);
});

test('workers search works correctly', function () {
    $user = User::factory()->create();

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

    $response = $this->actingAs($user)->get('/workers?search=Juan');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->has('workers', 1));
});
