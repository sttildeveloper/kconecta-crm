@extends('layouts.backoffice')

@section('title', 'Kconecta - Tipos de servicio')

@section('heading')
    Servicios
@endsection

@section('subheading')
    Gestiona el catalogo de tipos que consume la app movil
@endsection

@section('content')
    <div class="service-types-page">
        <div class="service-types-layout">
            <section class="page-card service-types-panel">
                <div class="section-header">
                    <div>
                        <h2>Tipos de servicio</h2>
                        <p>{{ $serviceTypes->total() }} registros en el catalogo</p>
                    </div>
                </div>

                <form class="filter-bar" method="GET" action="{{ route('admin.service-types.index') }}">
                    <label class="filter-group">
                        <span>Buscar</span>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre del tipo de servicio">
                    </label>
                    <div class="filter-actions">
                        <button type="submit">Filtrar</button>
                        <a class="secondary" href="{{ route('admin.service-types.index') }}">Limpiar</a>
                    </div>
                </form>

                <div class="service-types-table-wrap">
                    <div class="service-types-table-head" role="row">
                        <div role="columnheader">Servicio</div>
                        <div role="columnheader">Acciones</div>
                    </div>
                    <div class="service-types-list" role="list">
                        @forelse ($serviceTypes as $serviceType)
                            <article class="service-type-row" role="listitem">
                                <div class="service-type-row-main">
                                    <span>{{ $serviceType['name'] }}</span>
                                </div>
                                <div class="service-type-row-side">
                                    <div class="service-type-actions">
                                        <a class="icon-action accent" href="{{ route('admin.service-types.edit', ['id' => $serviceType['id']] + request()->query()) }}" title="Editar tipo de servicio" aria-label="Editar tipo de servicio">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9"/>
                                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.service-types.delete') }}" onsubmit="return confirm('Deseas eliminar este tipo de servicio?');">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $serviceType['id'] }}">
                                            <button type="submit" class="icon-action danger" title="Eliminar tipo de servicio" aria-label="Eliminar tipo de servicio">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h18"/>
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"/>
                                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 11v6M14 11v6"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="empty-state">
                                <h3>Sin tipos de servicio</h3>
                                <p>No hay registros en la tabla service_type con los filtros actuales.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                @if ($serviceTypes->lastPage() > 1)
                    <div class="pager">
                        <a class="pager-link {{ $serviceTypes->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $serviceTypes->previousPageUrl() ?? '#' }}" aria-disabled="{{ $serviceTypes->onFirstPage() ? 'true' : 'false' }}">
                            Anterior
                        </a>
                        <span class="pager-meta">Pagina {{ $serviceTypes->currentPage() }} de {{ $serviceTypes->lastPage() }}</span>
                        <a class="pager-link {{ $serviceTypes->currentPage() === $serviceTypes->lastPage() ? 'is-disabled' : '' }}" href="{{ $serviceTypes->nextPageUrl() ?? '#' }}" aria-disabled="{{ $serviceTypes->currentPage() === $serviceTypes->lastPage() ? 'true' : 'false' }}">
                            Siguiente
                        </a>
                    </div>
                @endif
            </section>

            <aside class="page-card service-type-form-card">
                <div class="section-header">
                    <div class="service-type-form-heading">
                        <span class="service-type-form-heading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14"/>
                            </svg>
                        </span>
                        <div>
                            <h2>{{ $editingType ? 'Editar tipo' : 'Nuevo tipo' }}</h2>
                            <p>{{ $editingType ? 'Actualiza el registro seleccionado.' : 'Agrega un nuevo tipo al catalogo.' }}</p>
                        </div>
                    </div>
                </div>

                <form class="service-type-form" method="POST" action="{{ $editingType ? route('admin.service-types.update', ['id' => $editingType->id]) : route('admin.service-types.store') }}">
                    @csrf

                    <label class="filter-group">
                        <span>Nombre</span>
                        <input type="text" name="name" value="{{ old('name', $editingType->name ?? '') }}" placeholder="Ej. Cerrajeria" maxlength="255" required>
                    </label>

                    @if ($editingType)
                        <div class="service-type-form-meta">
                            <span>ID #{{ $editingType->id }}</span>
                            <span>Creado {{ optional($editingType->created_at)->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    <div class="filter-actions service-type-form-actions">
                        <button type="submit">{{ $editingType ? 'Guardar cambios' : 'Crear tipo' }}</button>
                        @if ($editingType)
                            <a class="secondary" href="{{ route('admin.service-types.index') }}">Cancelar</a>
                        @endif
                    </div>
                </form>
            </aside>
        </div>
    </div>
@endsection
