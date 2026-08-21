<?php
namespace App\Http\Controllers;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class LicenseController extends Controller
{
    public function index() { return view('licenses.index',['items'=>License::with('creator')->latest()->paginate(15)]); }
    public function store(Request $request) { do {$code=strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));} while(License::where('code',$code)->exists()); $license=License::create(['code'=>$code,'created_by'=>$request->user()->id]); $this->audit('license.created',$license,null,['code'=>$code]); return back()->with('success','Licencia creada: '.$code); }
}
