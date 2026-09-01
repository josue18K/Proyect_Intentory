@extends('layouts.app', ['title' => 'Listas de stock', 'heading' => 'Listas especiales de stock'])

@section('content')
<div class="module-intro mb-3"><div><span class="eyebrow-dark">Conteo listo para compartir</span><h2>Químicos y compras rápidas</h2><p>Consulta unidades, docenas completas y sobrantes. Comparte cada lista directamente por WhatsApp.</p></div></div>

<form class="panel p-3 mb-3 special-stock-filter">
    <label class="form-label" for="stock-list-branch">Sede</label>
    <div class="d-flex gap-2"><select class="form-select" id="stock-list-branch" name="branch_id"><option value="">Todas las sedes permitidas</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</select><button class="btn btn-dark">Actualizar</button></div>
</form>

<div class="special-stock-tabs" role="tablist" aria-label="Tipos de lista">
    @foreach($groups as $index => $group)<button type="button" class="{{ $index === 0 ? 'active' : '' }}" data-stock-tab="{{ $group['key'] }}"><i data-lucide="{{ $group['icon'] }}"></i><span>{{ $group['title'] }}</span><b>{{ $group['products']->count() }}</b></button>@endforeach
</div>

@foreach($groups as $index => $group)
<section class="special-stock-group {{ $index === 0 ? 'active' : '' }}" data-stock-panel="{{ $group['key'] }}">
    <div class="special-stock-summary panel">
        <div><span class="section-kicker">{{ $selectedBranch?->name ?? 'Todas las sedes' }}</span><h2>{{ $group['title'] }}</h2><p>{{ number_format($group['total_units']) }} unidades registradas en {{ $group['products']->count() }} productos.</p></div>
        <button type="button" class="btn btn-success" data-share-stock data-share-text="{{ $group['message'] }}"><i data-lucide="send"></i> Compartir por WhatsApp</button>
    </div>
    <div class="special-stock-list panel">
        <div class="special-stock-head"><span>Producto</span><span>Unidades</span><span>Equivalencia</span></div>
        @forelse($group['products'] as $product)
        <article class="special-stock-row">
            <div><strong>{{ $product['name'] }}</strong><small>{{ $product['internal_code'] }}</small></div>
            <span class="special-stock-quantity {{ $product['quantity'] === 0 ? 'empty' : '' }}">{{ $product['quantity'] }}</span>
            <div class="special-stock-dozens"><strong>{{ $product['full_dozens'] }} doc.</strong>@if($product['remainder'])<span>+ {{ $product['remainder'] }} unid.</span>@endif<small>≈ {{ number_format($product['approx_dozens'], 2) }} docenas</small></div>
        </article>
        @empty<div class="empty-state">No hay productos clasificados en esta lista.</div>@endforelse
    </div>
</section>
@endforeach
@endsection
