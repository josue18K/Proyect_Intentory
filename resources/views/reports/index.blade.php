@extends('layouts.app',['title'=>'Reportes','heading'=>'Reportes de inventario'])
@section('content')
@include('partials.flash')
<div class="module-intro mb-4"><div><span class="eyebrow-dark">Reporte configurable</span><h2>Inventario para compartir</h2><p>Elige una tienda y genera el reporte completo o solamente de productos específicos.</p></div></div>
<form class="panel p-3 mb-3" id="report-filter-form">
    <div class="row g-3 align-items-end">
        <div class="col-lg-5"><label class="form-label" for="report-branch">Tienda</label><select id="report-branch" name="branch_id" class="form-select" required onchange="this.form.querySelectorAll('[name=\'product_ids[]\']').forEach(el=>el.checked=false);this.form.submit()"><option value="">Seleccionar sede</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id')==$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
        <div class="col-lg-3"><label class="form-label" for="report-type">Movimiento</label><select id="report-type" name="type" class="form-select"><option value="">Entradas y salidas</option><option value="entrada" @selected(request('type')==='entrada')>Entradas</option><option value="salida" @selected(request('type')==='salida')>Salidas</option></select></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="report-from">Desde</label><input id="report-from" type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="report-to">Hasta</label><input id="report-to" type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
    </div>
    @if($selectedBranch)
    <div class="report-product-picker mt-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><div><h3 class="h6 mb-1">Productos incluidos</h3><p class="small text-muted mb-0">Sin selección se incluyen todos. Marca únicamente los que necesitas para un reporte específico.</p></div><div class="d-flex flex-wrap gap-2"><button class="btn btn-sm btn-outline-dark" type="button" data-report-default>Productos esenciales</button><button class="btn btn-sm btn-light" type="button" data-report-clear>Limpiar selección</button></div></div>
        <input class="form-control mb-2" type="search" placeholder="Buscar producto dentro de esta tienda" data-report-search>
         <div class="report-product-grid" data-report-products>@foreach($availableProducts as $product)<label class="report-product-option" data-product-name="{{ str($product->name)->lower() }}"><input class="form-check-input" type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id,$selectedProductIds))><span><strong>{{ $product->name }}</strong><small>{{ $product->category?->name }} · S/ {{ number_format($product->sale_price,2) }}</small></span></label>@endforeach</div>
    </div>
    @endif
    <div class="d-flex justify-content-end mt-3"><button class="btn btn-dark">Actualizar reporte</button></div>
</form>
@if($selectedBranch)
<div class="d-flex gap-2 flex-wrap mb-3"><a class="btn btn-outline-dark" href="{{ route('reports.csv',request()->query()) }}">Descargar CSV</a><a class="btn btn-dark" href="{{ route('reports.pdf',request()->query()) }}">Descargar PDF</a><button class="btn btn-success" data-whatsapp-url="{{ 'https://wa.me/?text='.rawurlencode('Reporte de inventario '.$selectedBranch->name.': '.$shareUrl) }}">Enviar PDF por WhatsApp</button><span class="badge badge-normal align-self-center">{{ $inventory->count() }} productos incluidos</span></div>
<div class="row g-3"><div class="col-lg-8"><div class="panel p-3"><h2 class="h6">Existencias</h2><div class="table-responsive reports-scroll"><table class="table"><thead><tr><th>Categoría</th><th>Producto</th><th>Cantidad</th><th>Precio</th></tr></thead><tbody>@foreach($inventory->sortBy(fn($row)=>$row->product->name) as $row)<tr><td>{{ $row->product->category?->name }}</td><td>{{ $row->product->name }}</td><td>{{ $row->quantity }}</td><td>S/ {{ number_format($row->product->sale_price,2) }}</td></tr>@endforeach</tbody></table></div></div></div><div class="col-lg-4"><div class="panel p-3"><h2 class="h6">Mayor rotación</h2><p class="small text-muted">Unidades vendidas en el periodo.</p><div class="table-responsive"><table class="table"><thead><tr><th>Producto</th><th>Unidades</th></tr></thead><tbody>@foreach($products->take(15) as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->rotation_units??0 }}</td></tr>@endforeach</tbody></table></div></div></div></div>
@else<div class="alert alert-warning">Selecciona una sede para configurar y generar el reporte.</div>@endif
@endsection
