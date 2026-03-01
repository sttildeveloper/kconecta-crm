@extends('layouts.backoffice')

@section('title', 'Kconecta - Proveedores de servicios')

@section('heading')
    Proveedores de servicios
@endsection

@section('subheading')
    Gestiona las empresas de servicios y sus datos de contacto
@endsection

@section('header_actions')
    <a class="action-primary" href="{{ url('/post/create_form/service') }}" aria-label="Agregar proveedor de servicio">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
        </svg>
        <span>Agregar proveedor de servicio</span>
    </a>
@endsection

@section('content')
    <div class="page-card">
        <div class="section-header">
            <div>
                <h2>Listado</h2>
                <p>{{ $providers->total() }} proveedores en total</p>
            </div>
        </div>

        <form class="filter-bar" method="GET" action="{{ url('/post/services') }}">
            <label class="filter-group">
                <span>Buscar</span>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre, correo o telefono">
            </label>
            <div class="filter-actions">
                <button type="submit">Filtrar</button>
                <a class="secondary" href="{{ url('/post/services') }}">Limpiar</a>
            </div>
        </form>
    </div>

    @if ($providers->count())
        <div class="catalog-grid providers-grid">
            @foreach ($providers as $provider)
                @php
                    $name = $provider['name'] ?: 'Proveedor';
                    $initial = strtoupper(substr($name, 0, 1));
                    $email = $provider['email'] ?: 'Sin correo';
                    $phone = $provider['phone'] ?: 'Sin telefono';
                    $landline = $provider['landline_phone'] ?: 'Sin telefono';
                    $address = $provider['address'] ?: 'Sin direccion';
                    $isActive = (int) ($provider['is_active'] ?? 1) === 1;
                    $toggleLabel = $isActive ? 'Desactivar' : 'Activar';
                @endphp
                <article class="entity-card provider-card {{ $isActive ? '' : 'is-inactive' }}" data-provider-id="provider-{{ $provider['id'] }}">
                    <div class="provider-card-header">
                        <div class="provider-avatar">{{ $initial }}</div>
                        <div class="provider-header-info">
                            <h3>{{ $name }}</h3>
                            <span class="provider-role">{{ $provider['level'] }}</span>
                        </div>
                        <span class="provider-status {{ $isActive ? '' : 'inactive' }}">{{ $isActive ? 'Activo' : 'Desactivado' }}</span>
                    </div>
                    <div class="provider-card-body">
                        <div class="provider-line">
                            <span class="provider-label">E-mail</span>
                            <span class="provider-value">{{ $email }}</span>
                        </div>
                        <div class="provider-line">
                            <span class="provider-label">Tel&eacute;fono</span>
                            <span class="provider-value">{{ $phone }}</span>
                        </div>
                        <div class="provider-line">
                            <span class="provider-label">Tel&eacute;fono fijo</span>
                            <span class="provider-value">{{ $landline }}</span>
                        </div>
                        <div class="provider-line provider-address">
                            <span class="provider-label">Direcci&oacute;n</span>
                            <span class="provider-value">{{ $address }}</span>
                        </div>
                    </div>
                    <div class="entity-actions icon-actions provider-actions">
                        <a class="icon-action neutral" href="{{ url('/users/' . $provider['id']) }}" title="Ver proveedor" aria-label="Ver proveedor">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3h7v7"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14L21 3"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 14v7a2 2 0 0 1-2 2h-7"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10v11a2 2 0 0 0 2 2h11"/>
                            </svg>
                        </a>
                        <a class="icon-action accent" href="{{ url('/users/edit/' . $provider['id']) }}" title="Editar proveedor" aria-label="Editar proveedor">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                            </svg>
                        </a>
                        <button type="button" class="icon-action warning" data-toggle-provider="{{ $provider['id'] }}" data-active="{{ $isActive ? '1' : '0' }}" title="{{ $toggleLabel }}" aria-label="{{ $toggleLabel }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2v10"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.3 6.3a7 7 0 1 0 11.4 0"/>
                            </svg>
                        </button>
                        <button type="button" class="icon-action danger" data-delete-provider="{{ $provider['id'] }}" title="Eliminar" aria-label="Eliminar">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h18"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6V4h8v2"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 6l-1 14H6L5 6"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 11v6M14 11v6"/>
                            </svg>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <h3>Sin proveedores</h3>
            <p>No hay proveedores de servicios registrados.</p>
            <a class="primary" href="{{ url('/post/create_form/service') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                </svg>
                <span>Agregar proveedor de servicio</span>
            </a>
        </div>
    @endif

    @if ($providers->lastPage() > 1)
        <div class="pager">
            <a class="pager-link {{ $providers->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $providers->previousPageUrl() ?? '#' }}" aria-disabled="{{ $providers->onFirstPage() ? 'true' : 'false' }}">
                Anterior
            </a>
            <span class="pager-meta">Pagina {{ $providers->currentPage() }} de {{ $providers->lastPage() }}</span>
            <a class="pager-link {{ $providers->currentPage() === $providers->lastPage() ? 'is-disabled' : '' }}" href="{{ $providers->nextPageUrl() ?? '#' }}" aria-disabled="{{ $providers->currentPage() === $providers->lastPage() ? 'true' : 'false' }}">
                Siguiente
            </a>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        (() => {
            const toggleButtons = document.querySelectorAll('[data-toggle-provider]');
            const deleteButtons = document.querySelectorAll('[data-delete-provider]');

            toggleButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const providerId = button.dataset.toggleProvider;
                    const isActive = button.dataset.active === '1';
                    const actionLabel = isActive ? 'desactivar' : 'activar';
                    if (!confirm(`Deseas ${actionLabel} este proveedor?`)) {
                        return;
                    }

                    button.disabled = true;
                    try {
                        const response = await fetch(`/users/toggle?id=${providerId}`);
                        const data = await response.json();
                        if (data.status !== 200) {
                            alert(data.message || 'No se pudo actualizar el proveedor.');
                            return;
                        }

                        const card = document.querySelector(`[data-provider-id="provider-${providerId}"]`);
                        const statusBadge = card ? card.querySelector('.provider-status') : null;
                        const isNowActive = Number(data.is_active) === 1;
                        button.dataset.active = isNowActive ? '1' : '0';
                        button.title = isNowActive ? 'Desactivar' : 'Activar';
                        button.setAttribute('aria-label', button.title);
                        if (card) {
                            card.classList.toggle('is-inactive', !isNowActive);
                        }
                        if (statusBadge) {
                            statusBadge.textContent = isNowActive ? 'Activo' : 'Desactivado';
                            statusBadge.classList.toggle('inactive', !isNowActive);
                        }
                    } catch (error) {
                        alert('Error al actualizar el proveedor.');
                    } finally {
                        button.disabled = false;
                    }
                });
            });

            deleteButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const providerId = button.dataset.deleteProvider;
                    if (!confirm('Eliminar proveedor?')) {
                        return;
                    }

                    button.disabled = true;
                    try {
                        const response = await fetch(`/user/delete?id=${providerId}`);
                        const data = await response.json();
                        if (data.status !== 200) {
                            alert(data.message || 'No se pudo eliminar el proveedor.');
                            return;
                        }

                        const card = document.querySelector(`[data-provider-id="provider-${providerId}"]`);
                        if (card) {
                            card.remove();
                        }
                    } catch (error) {
                        alert('Error al eliminar el proveedor.');
                    } finally {
                        button.disabled = false;
                    }
                });
            });
        })();
    </script>
@endsection
