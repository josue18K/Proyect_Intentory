@extends('layouts.app', ['title' => 'Registrar movimiento', 'heading' => 'Registrar movimiento'])
@section('content')
@include('partials.flash')
<div class="module-intro mb-4"><div><span class="eyebrow-dark">Actualizar existencias</span><h2>Registrar entrada o salida</h2><p>Busca el producto, selecciona su sede y registra la cantidad.</p></div></div>
<div class="panel movement-form-panel p-4"><form method="POST" action="{{ route('movements.store') }}">@csrf
    <div class="row g-3">
        <div class="col-12"><label class="form-label" for="movement-product-search">Buscar producto</label><div class="product-select-search"><i data-lucide="search"></i><input id="movement-product-search" type="search" class="form-control" placeholder="Escribe nombre, código interno o código de barras" data-product-select-search autocomplete="off"></div><div class="form-text" data-product-search-status>Escribe para reducir la lista de productos.</div></div>
        <div class="col-md-7"><label class="form-label" for="movement-product">Producto</label><select id="movement-product" name="product_id" class="form-select" data-product-select required><option value="">Selecciona un producto</option>@foreach($products as $product)<option value="{{ $product->id }}" data-search="{{ str($product->name.' '.$product->internal_code.' '.$product->barcode)->lower() }}" @selected(old('product_id')==$product->id)>{{ $product->name }} · {{ $product->internal_code }}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-label" for="movement-branch">Sede</label><select id="movement-branch" name="branch_id" class="form-select" required><option value="">Selecciona una sede</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id')==$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="movement-type">Operación</label><select id="movement-type" name="type" class="form-select" required><option value="entrada" @selected(old('type','entrada')==='entrada')>Entrada / reingreso</option><option value="salida" @selected(old('type')==='salida')>Salida</option></select></div>
        <div class="col-md-4"><label class="form-label" for="movement-quantity">Cantidad</label><input id="movement-quantity" name="quantity" type="number" min="1" class="form-control" value="{{ old('quantity') }}" required></div>
        <div class="col-md-4"><label class="form-label" for="movement-date">Fecha</label><input id="movement-date" name="movement_date" type="date" class="form-control" value="{{ old('movement_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required></div>
        <div class="col-12"><label class="form-label" for="movement-reason">Motivo</label><input id="movement-reason" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Compra, reingreso, venta, daño..."></div>
        <div class="col-12"><label class="form-label" for="movement-notes">Notas</label><textarea id="movement-notes" name="notes" class="form-control" placeholder="Información adicional opcional">{{ old('notes') }}</textarea></div>
    </div>
    <div class="movement-help mt-3"><i data-lucide="triangle-alert"></i><span>Las salidas no pueden superar el stock disponible. Cada operación queda registrada en auditoría.</span></div>
    <div class="movement-form-actions mt-4"><button class="btn btn-dark">Guardar movimiento</button><a href="{{ route('movements.index') }}" class="btn btn-light">Cancelar</a></div>
</form></div>
@endsection
