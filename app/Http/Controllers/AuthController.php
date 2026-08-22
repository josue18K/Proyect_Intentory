<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{License, User};
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function create() { return view('auth.login'); }
    public function register() { return view('auth.register'); }

    public function registerStore(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:8'], 'license_code' => ['required', 'string']]);
        $user = DB::transaction(function () use ($data) {
            $license = License::whereRaw('upper(code) = ?', [strtoupper($data['license_code'])])->lockForUpdate()->first();
            abort_unless($license && $license->isAvailable() && $license->branch_id, 422, 'El código de licencia no está disponible o no tiene una sede asignada.');
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'vendedor', 'permissions' => ['inventory.view', 'inventory.manage']]);
            $user->branches()->sync([$license->branch_id]);
            $license->update(['status' => 'activated', 'activated_by' => $user->id, 'activated_at' => now()]);
            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('success', 'Cuenta creada y licencia activada.');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (! Auth::attempt(array_merge($credentials, ['is_active' => true]), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Las credenciales no son válidas.'])->withInput();
        }
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
