<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('login', function () {
    it('returns a token with valid credentials', function () {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['message', 'token', 'user'])
            ->assertJsonFragment(['message' => 'Login exitoso']);
    });

    it('returns 401 with wrong password', function () {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Credenciales incorrectas']);
    });

    it('returns 401 when user does not exist', function () {
        $this->postJson('/api/login', [
            'email' => 'noexiste@example.com',
            'password' => 'anypassword',
        ])
            ->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Credenciales incorrectas']);
    });

    it('validates that email and password are required', function (array $payload) {
        $this->postJson('/api/login', $payload)
            ->assertUnprocessable();
    })->with([
        'missing email' => [['password' => 'secret']],
        'missing password' => [['email' => 'user@example.com']],
        'empty body' => [[]],
    ]);

    it('validates that email has a valid format', function () {
        $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ])
            ->assertUnprocessable();
    });
});

describe('logout', function () {
    it('revokes all tokens for the authenticated user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertSuccessful()
            ->assertJsonFragment(['message' => 'Logout exitoso']);

        expect($user->tokens()->count())->toBe(0);
    });

    it('returns 401 when not authenticated', function () {
        $this->postJson('/api/logout')
            ->assertUnauthorized();
    });
});

describe('me', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertSuccessful()
            ->assertJsonFragment(['email' => $user->email]);
    });

    it('returns 401 when not authenticated', function () {
        $this->getJson('/api/me')
            ->assertUnauthorized();
    });
});
