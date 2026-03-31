<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 🔐 LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // 🔥 CREAR TOKEN (AQUÍ ESTÁ EL CAMBIO IMPORTANTE)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }

    // 🚪 LOGOUT
    public function logout(Request $request)
    {
        // 🔥 BORRAR TOKENS
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout exitoso',
        ]);
    }

    // 👤 USUARIO ACTUAL
    public function me(Request $request)
    {
        return response()->json(
            $request->user()->only(['id', 'name', 'email'])
        );
    }
}