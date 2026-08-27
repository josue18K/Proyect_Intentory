@extends('layouts.app',['title'=>'Productos','heading'=>'Productos por tienda'])
@section('content')
@include('partials.flash')
<div class="module-intro mb-4">
    <div><span class="eyebrow-dark">Catálogo por sede</span><h2>Productos</h2><p>Administra productos, precios y stock desde un solo lugar.</p></div>
    <div class="module-actions"><span class="record-count"><strong>{{ $items->total() }}</strong><small>registros</small></span>@if(auth()->user()->role==='administrador')<a class="btn btn-dark" href="{{ route('products.create') }}">+ Nuevo producto</a>@endif</div>
</div>
<form class="panel p-3 mb-3 row g-2">
    <div class="col-lg-4"><label class="form-label" for="product-search">Buscar</label><input id="product-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nombre, código o código de barras"></div>
    <div class="col-lg-3 col-md-6"><label class="form-label" for="branch-filter">Tienda</label><select id="branch-filter" name="branch_id" class="form-select"><option value="">Todas las tiendas</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id')==$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
    <div class="col-lg-3 col-md-6"><label class="form-label" for="category-filter">Categoría</label><select id="category-filter" name="category_id" class="form-select"><option value="">Todas las categorías</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id')==$category->id)>{{ $category->name }}</option>@endforeach</select></div>
    <div class="col-lg-2"><label class="form-label" for="sort-filter">Orden</label><select id="sort-filter" name="sort" class="form-select"><option value="az" @selected(request('sort','az')==='az')>Nombre A - Z</option><option value="za" @selected(request('sort')==='za')>Nombre Z - A</option></select></div>
    <div class="col-12 d-flex gap-2 justify-content-end"><a class="btn btn-light" href="{{ route('products.index') }}">Limpiar</a><button class="btn btn-dark">Aplicar filtros</button></div>
</form>
<div class="panel p-3"><div class="table-responsive" tabindex="0"><table class="table"><thead><tr><th>Sede</th><th>Código</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Cantidad</th><th></th></tr></thead><tbody>
@forelse($items as $item) @php($stock=$item->inventories->firstWhere('branch_id',$item->branch_id)?->quantity??0)<tr><td><span class="badge badge-normal">{{ $item->branch?->name??'Sin sede' }}</span></td><td>{{ $item->internal_code }}<div class="small text-muted">{{ $item->barcode }}</div></td><td><strong>{{ $item->name }}</strong><div><a class="small" href="{{ route('products.history',$item) }}">Ver historial</a></div></td><td>{{ $item->category->name }}</td><td>S/ {{ number_format($item->sale_price,2) }}</td><td><strong>{{ $stock }}</strong> @if($stock<=$item->minimum_stock)<span class="badge badge-low">Bajo</span>@endif</td><td class="actions-cell">@if(auth()->user()->role==='administrador')<a class="btn btn-sm btn-outline-dark" href="{{ route('products.edit',$item) }}">Editar producto y stock</a><form class="d-inline" method="POST" action="{{ route('products.destroy',$item) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Desactivar producto?')">Eliminar</button></form>@endif</td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-5">No hay productos para estos filtros.</td></tr>@endforelse
</tbody></table></div>{{ $items->links() }}</div>
@endsection
