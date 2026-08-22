<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
class UserController extends Controller
{
    public function index() { return view('users.index', ['items'=>User::with('branches')->latest()->paginate(15), 'branches' => \App\Models\Branch::where('is_active', true)->orderBy('name')->get(), 'permissions' => ['inventory.view' => 'Ver inventario', 'inventory.manage' => 'Registrar movimientos', 'reports.view' => 'Ver reportes']]); }
    public function update(Request $request, User $user) { $data=$request->validate(['role'=>'required|in:administrador,vendedor','permissions'=>'array','permissions.*'=>'in:inventory.view,inventory.manage,reports.view','branch_ids'=>'array','branch_ids.*'=>'exists:branches,id']); $old=$user->only('role','permissions'); $user->update(['role'=>$data['role'], 'permissions'=>$data['role'] === 'administrador' ? [] : ($data['permissions'] ?? [])]); if (array_key_exists('branch_ids', $data) || $data['role'] === 'administrador') $user->branches()->sync($data['role'] === 'administrador' ? [] : ($data['branch_ids'] ?? [])); $this->audit('user.permissions.updated',$user,$old,$user->only('role','permissions')); return back()->with('success','Permisos y sedes actualizados.'); }
    public function toggle(Request $request, User $user) { abort_if($user->is($request->user()),422,'No puedes desactivar tu propio usuario.'); $old=$user->only('is_active'); $user->update(['is_active'=>!$user->is_active]); $this->audit('user.status.updated',$user,$old,$user->only('is_active')); return back()->with('success','Estado actualizado.'); }
    public function destroy(Request $request, User $user) { abort_if($user->is($request->user()), 422, 'No puedes eliminar tu propio usuario.'); $request->validate(['password' => ['required', 'current_password']]); $old = $user->only('is_active'); $user->update(['is_active' => false]); $user->branches()->detach(); $this->audit('user.deleted', $user, $old, ['is_active' => false]); return back()->with('success', 'Usuario eliminado y conservado en el historial.'); }
}
