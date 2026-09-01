@extends('layouts.app', ['title'=>'Auditoría','heading'=>'Historial y auditoría'])
@section('content')
<div class="module-intro mb-3"><div><span class="eyebrow-dark">Trazabilidad del sistema</span><h2>Actividad reciente</h2><p>Consulta quién realizó cada acción y revisa los cambios con nombres comprensibles.</p></div></div>
<form class="panel audit-filter mb-3"><div class="d-flex gap-2"><div class="position-relative flex-grow-1"><i data-lucide="search"></i><input name="action" value="{{ request('action') }}" class="form-control" placeholder="Buscar por acción: producto, usuario, movimiento..."></div><button class="btn btn-dark">Buscar</button>@if(request('action'))<a href="{{ route('audit.index') }}" class="btn btn-light">Limpiar</a>@endif</div></form>
<div class="audit-timeline">
@forelse($items as $item)
<article class="panel audit-card">
    <div class="audit-icon"><i data-lucide="{{ str($item->action)->contains('created') ? 'circle-plus' : (str($item->action)->contains('deleted') ? 'archive' : 'pencil') }}"></i></div>
    <div class="audit-main"><div class="audit-heading"><div><strong>{{ $item->actionLabel() }}</strong><span>{{ $item->subjectLabel() }}</span></div><time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->format('d/m/Y') }}<small>{{ $item->created_at->format('H:i') }}</small></time></div>
        <div class="audit-user"><span class="avatar-mini">{{ strtoupper(substr($item->user?->name ?? 'S', 0, 1)) }}</span><span><b>{{ $item->user?->name ?? 'Sistema' }}</b><small>{{ $item->user?->email ?? 'Acción automática' }}</small></span></div>
        @php($changes = $item->readableChanges())
        @if(count($changes))<details class="audit-changes"><summary>Ver {{ count($changes) }} {{ count($changes) === 1 ? 'cambio' : 'cambios' }} <i data-lucide="chevron-down"></i></summary><div class="audit-change-grid">@foreach($changes as $change)<div><span>{{ $change['label'] }}</span>@if($change['old'] !== 'Sin dato')<del>{{ $change['old'] }}</del>@endif<strong>{{ $change['new'] }}</strong></div>@endforeach</div></details>@endif
    </div>
</article>
@empty<div class="panel empty-state">No encontramos registros con ese filtro.</div>@endforelse
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
