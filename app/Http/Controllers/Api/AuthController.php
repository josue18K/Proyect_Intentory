<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Las credenciales no son válidas.'], 401);
        }

        $token = $user->createToken('app-inventory')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => ['active' => true, 'token' => $token, 'user' => $this->userData($user->load('branches'))],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.', 'data' => null]);
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Usuario autenticado.', 'data' => $this->userData($request->user()->load('branches'))]);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'active' => (bool) $user->is_active,
            'branches' => $user->branches->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name, 'slug' => $branch->slug])->values(),
        ];
    }
}
