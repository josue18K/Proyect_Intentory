<?php
namespace App\Http\Controllers;
use App\Models\{Branch, License};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class LicenseController extends Controller
{
    public function index() { return view('licenses.index',['items'=>License::with(['creator', 'branch'])->latest()->paginate(15), 'branches' => Branch::where('is_active', true)->orderBy('name')->get()]); }
    public function store(Request $request) { $data = $request->validate(['branch_id' => 'required|exists:branches,id']); do {$code=strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));} while(License::where('code',$code)->exists()); $license=License::create(['code'=>$code,'created_by'=>$request->user()->id,'branch_id'=>$data['branch_id']]); $this->audit('license.created',$license,null,['code'=>$code,'branch_id'=>$data['branch_id']]); return back()->with('success','Licencia creada para la sede seleccionada: '.$code); }
}
