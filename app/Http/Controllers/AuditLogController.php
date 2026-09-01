<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use Illuminate\Http\Request;
class AuditLogController extends Controller
{
    public function index(Request $request) { $items=AuditLog::with(['user','auditable'])->when($request->action,fn($q,$v)=>$q->where('action','like','%'.$v.'%'))->latest()->paginate(20)->withQueryString(); return view('audit.index',compact('items')); }
}
