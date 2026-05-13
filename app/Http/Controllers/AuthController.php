<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'type_user']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout exitoso',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->only(['id', 'name', 'email', 'type_user'])
        );
    }
}
