<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="d-lg-flex min-vh-100">
    <aside class="sidebar p-3">
        <div class="d-flex align-items-center gap-2 mb-4 px-2">
            <div class="bg-warning rounded-2 px-2 py-1 fw-bold">L</div>
            <div><div class="brand-mark fw-bold small">LIUVA</div><div class="text-white-50 small">Inventario</div></div>
        </div>
        <div class="small text-uppercase text-white-50 px-2 mb-2">Principal</div>
        <nav class="nav nav-pills flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">▦ &nbsp; Dashboard</a>
            <a class="nav-link" href="{{ route('products.index') }}">□ &nbsp; Productos</a>
            <a class="nav-link" href="{{ route('movements.index') }}">↕ &nbsp; Movimientos</a>
            <a class="nav-link {{ request()->routeIs('transfers.*') ? 'active' : '' }}" href="{{ route('transfers.index') }}">⇄ &nbsp; Transferencias</a>
        </nav>
        <div class="small text-uppercase text-white-50 px-2 mt-4 mb-2">Administración</div>
        <nav class="nav nav-pills flex-column gap-1">
            <a class="nav-link" href="{{ route('categories.index') }}">◉ &nbsp; Categorías</a>
            <a class="nav-link" href="{{ route('branches.index') }}">⌂ &nbsp; Sedes</a>
            @if(auth()->user()->role === 'administrador')<a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">♙ &nbsp; Usuarios</a><a class="nav-link {{ request()->routeIs('licenses.*') ? 'active' : '' }}" href="{{ route('licenses.index') }}">▣ &nbsp; Licencias</a><a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">⌕ &nbsp; Auditoría</a>@endif
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">▣ &nbsp; Reportes</a>
            <a class="nav-link" href="#">⚙ &nbsp; Configuración</a>
        </nav>
        <div class="border-top border-secondary mt-5 pt-3 px-2 text-white-50 small">Tienda Importaciones<br>Control inteligente de stock</div>
    </aside>
    <main class="content flex-grow-1">
        <header class="bg-white border-bottom px-3 px-lg-4 py-3 d-flex justify-content-between align-items-center">
            <div><div class="text-muted small">{{ now()->isoFormat('dddd, D [de] MMMM') }}</div><h1 class="h4 mb-0 fw-bold">{{ $heading ?? 'Resumen general' }}</h1></div>
            <div class="d-flex align-items-center gap-3"><div class="text-end"><div class="fw-semibold small">{{ auth()->user()->name }}</div><div class="text-muted small">{{ ucfirst(auth()->user()->role) }}</div></div><div class="rounded-circle bg-dark text-warning fw-bold p-2">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-dark">Salir</button></form></div>
        </header>
        <div class="p-3 p-lg-4">@include('partials.form-errors') @yield('content')</div>
    </main>
</div>
</body>
</html>
