@extends('layouts.app', ['title' => $title, 'heading' => $title])
@section('content')
@include('partials.flash')
<div class="d-flex justify-content-between align-items-center mb-3"><p class="text-muted mb-0">Administración de {{ strtolower($title) }}.</p><a class="btn btn-dark" href="{{ $createUrl }}">+ Nuevo</a></div>
<div class="panel p-3"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr>@foreach($columns as $column)<th>{{ $column }}</th>@endforeach<th></th></tr></thead><tbody>@forelse($items as $item)<tr>@foreach($values($item) as $value)<td>{!! $value !!}</td>@endforeach<td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ $editUrl($item) }}">Editar</a><form class="d-inline" method="POST" action="{{ $deleteUrl($item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Desactivar este registro?')">Desactivar</button></form></td></tr>@empty<tr><td colspan="{{ count($columns) + 1 }}" class="text-center text-muted">No hay registros.</td></tr>@endforelse</tbody></table></div>{{ $items->links() }}</div>
@endsection
