@extends('layouts.app', ['title' => 'Control de stock', 'heading' => 'Control de stock'])
@section('content')
@include('partials.flash')
<div class="module-intro mb-4">
    <div><span class="eyebrow-dark">Entradas y salidas</span><h2>Control de stock</h2><p>Busca un producto y consulta rápidamente su historial de existencias.</p></div>
    <div class="module-actions"><span class="record-count"><strong>{{ $items->total() }}</strong><small>movimientos</small></span><a class="btn btn-dark" href="{{ route('movements.create') }}"><i data-lucide="circle-plus"></i> Registrar movimiento</a></div>
</div>
<form class="panel stock-search-panel mb-3" role="search">
    <div class="stock-search-box"><i data-lucide="search"></i><input name="search" type="search" value="{{ request('search') }}" placeholder="Buscar por nombre, código interno o código de barras" aria-label="Buscar producto" autofocus><button class="btn btn-dark" type="submit">Buscar producto</button>@if(request('search'))<a class="btn btn-light" href="{{ route('movements.index') }}">Limpiar</a>@endif</div>
</form>
<div class="panel p-3">
    <div class="data-panel-head"><div><h3>{{ request('search') ? 'Resultados para “'.request('search').'”' : 'Historial reciente' }}</h3><p>Todos los cambios de cantidad quedan registrados con fecha y usuario.</p></div><span class="data-status"><i></i>Control activo</span></div>
    <div class="table-responsive" tabindex="0"><table class="table align-middle"><thead><tr><th>Fecha</th><th>Producto</th><th>Sede</th><th>Operación</th><th>Cantidad</th><th>Stock resultante</th><th>Usuario</th></tr></thead><tbody>
    @forelse($items as $item)<tr><td>{{ ($item->movement_date ?? $item->created_at)->format('d/m/Y') }}<div class="text-muted small">{{ $item->created_at->format('H:i') }}</div></td><td><div class="product-cell"><span class="product-avatar">{{ strtoupper(substr($item->product->name,0,1)) }}</span><div><strong>{{ $item->product->name }}</strong><small>{{ $item->product->internal_code }}</small></div></div></td><td>{{ $item->branch->name }}</td><td><span class="badge {{ $item->type === 'entrada' ? 'badge-normal' : 'badge-low' }}">{{ $item->type === 'entrada' ? 'Entrada' : 'Salida' }}</span></td><td><strong>{{ $item->type === 'entrada' ? '+' : '-' }}{{ $item->quantity }}</strong></td><td><strong class="stock-number {{ $item->stock_after===0?'is-empty':'' }}">{{ $item->stock_after }}</strong> @if($item->stock_after===0)<span class="badge badge-empty">Sin stock</span>@endif</td><td>{{ $item->user->name }}</td></tr>
    @empty<tr><td colspan="7"><div class="empty-state"><i data-lucide="{{ request('search') ? 'search-x' : 'inbox' }}"></i><strong>{{ request('search') ? 'No encontramos movimientos de ese producto' : 'Control de stock reiniciado' }}</strong><span>{{ request('search') ? 'Prueba escribiendo otro nombre o código.' : 'Registra una entrada para comenzar el nuevo historial.' }}</span><a href="{{ route('movements.create') }}" class="btn btn-dark btn-sm">Registrar primera entrada</a></div></td></tr>@endforelse
    </tbody></table></div>{{ $items->links() }}
</div>
@endsection
