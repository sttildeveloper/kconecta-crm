@extends('layouts.backoffice')

@section('title', 'Kconecta - Usuarios')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/app/users.css') }}">
@endsection

@section('heading')
    Usuarios
@endsection

@section('subheading')
    Gestiona los usuarios registrados
@endsection

@section('content')
    <div class="users-list-page">
        @if (session('status'))
            <div class="page-card provider-import-feedback success-feedback">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="page-card provider-import-feedback error-feedback">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="page-card">
            <div class="section-header">
                <div>
                    <h2>Listado</h2>
                    <p>{{ $users->total() }} usuarios en total</p>
                </div>
                @if ($isAdmin ?? false)
                    <div class="provider-import-trigger">
                        <span>Altas masivas de proveedores</span>
                        <a href="#provider-import-card" class="secondary">Importar proveedores</a>
                    </div>
                @endif
            </div>

            <form class="filter-bar" method="GET" action="{{ url('/users') }}">
                <label class="filter-group">
                    <span>Buscar</span>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre, correo o usuario">
                </label>
                <label class="filter-group">
                    <span>Nivel</span>
                    <select name="level">
                        <option value="all">Todos</option>
                        @foreach ($levelOptions as $level)
                            <option value="{{ $level->value }}" {{ ($filters['level'] ?? '') === (string) $level->value ? 'selected' : '' }}>
                                {{ $level->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-group">
                    <span>Desde</span>
                    <input type="date" name="ds" value="{{ $filters['ds'] ?? '' }}">
                </label>
                <label class="filter-group">
                    <span>Hasta</span>
                    <input type="date" name="de" value="{{ $filters['de'] ?? '' }}">
                </label>
                <div class="filter-actions">
                    <button type="submit">Filtrar</button>
                    <a class="secondary" href="{{ url('/users') }}">Limpiar</a>
                </div>
            </form>
        </div>

        @if ($isAdmin ?? false)
            @php
                $importSummary = $providerImportPreview['summary'] ?? null;
                $importReport = collect($providerImportPreview['report'] ?? []);
                $readyRows = $importSummary ? (($importSummary['created'] ?? 0) + ($importSummary['updated'] ?? 0)) : 0;
            @endphp
            <div class="page-card provider-import-card" id="provider-import-card">
                <div class="section-header">
                    <div>
                        <h2>Importar Proveedores</h2>
                        <p>Sube un CSV, revisa duplicados y confirma solo cuando el resumen sea correcto.</p>
                    </div>
                </div>

                <form class="provider-import-form" method="POST" action="{{ url('/users/providers/import/preview') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="level" value="{{ $filters['level'] ?? \App\Models\User::LEVEL_SERVICE_PROVIDER }}">
                    <label class="filter-group provider-import-file">
                        <span>Archivo CSV</span>
                        <input id="providers_csv" type="file" name="providers_csv" accept=".csv,text/csv">
                        <span class="provider-import-file-ui" aria-hidden="true">
                            <span class="provider-import-file-button">Seleccionar archivo</span>
                            <span class="provider-import-file-name" data-provider-file-name>Ningún archivo seleccionado</span>
                        </span>
                    </label>
                    <div class="filter-actions">
                        <button type="submit">Analizar CSV</button>
                    </div>
                </form>

                @error('providers_csv')
                    <p class="provider-import-error">{{ $message }}</p>
                @enderror

                @if ($importSummary)
                    <div class="provider-import-preview">
                        <div class="provider-import-preview-head">
                            <div>
                                <h3>Resumen previo</h3>
                                <p>
                                    Archivo: <strong>{{ $providerImportPreview['original_name'] ?? 'CSV temporal' }}</strong>
                                    · Analizado {{ $providerImportPreview['uploaded_at'] ?? '' }}
                                </p>
                            </div>
                            <div class="provider-import-risk">
                                <strong>{{ $readyRows }}</strong>
                                <span>listos para importar</span>
                            </div>
                        </div>

                        <div class="provider-import-stats">
                            <article>
                                <strong>{{ $importSummary['rows'] ?? 0 }}</strong>
                                <span>filas leidas</span>
                            </article>
                            <article>
                                <strong>{{ $readyRows }}</strong>
                                <span>altas posibles</span>
                            </article>
                            <article>
                                <strong>{{ $importSummary['conflicts'] ?? 0 }}</strong>
                                <span>duplicados/conflictos</span>
                            </article>
                            <article>
                                <strong>{{ $importSummary['skipped'] ?? 0 }}</strong>
                                <span>saltadas</span>
                            </article>
                            <article>
                                <strong>{{ $importSummary['errors'] ?? 0 }}</strong>
                                <span>errores</span>
                            </article>
                        </div>

                        <div class="provider-import-table-wrap">
                            <table class="provider-import-table">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Direccion</th>
                                        <th>E-mail</th>
                                        <th>Telefono fijo</th>
                                        <th>Whatsapp</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($importReport as $row)
                                        <tr class="import-row-{{ $row['resultado'] }}">
                                            <td>{{ $row['empresa'] ?: 'Null' }}</td>
                                            <td>{{ $row['direccion'] ?? 'Null' }}</td>
                                            <td>{{ $row['email'] ?? 'Null' }}</td>
                                            <td>{{ $row['telefono_fijo'] ?? 'Null' }}</td>
                                            <td>{{ $row['whatsapp'] ?? 'Null' }}</td>
                                            <td>{{ $row['observaciones'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="provider-import-actions">
                            <form method="POST" action="{{ url('/users/providers/import/cancel') }}">
                                @csrf
                                <button type="submit" class="secondary danger-soft">Cancelar</button>
                            </form>
                            <form method="POST" action="{{ url('/users/providers/import/commit') }}">
                                @csrf
                                <button type="submit" {{ $readyRows === 0 ? 'disabled' : '' }}>Proceder</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="users-table-wrap">
            <div class="users-table-head" role="row">
                <div role="columnheader">Usuario</div>
                <div role="columnheader">Contacto</div>
                <div role="columnheader">Direccion</div>
                <div class="header-metrics-grid" role="presentation" aria-label="Metricas">
                    <div role="columnheader" aria-label="Vistas" title="Vistas">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </div>
                    <div role="columnheader" aria-label="Contacto" title="Contacto">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 11.5a8 8 0 0 1-11.72 7.05L4 20l1.55-4.02A8 8 0 1 1 20 11.5Z"/>
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.7" d="M9.5 10.25h.01M12 10.25h.01M14.5 10.25h.01"/>
                        </svg>
                    </div>
                    <div role="columnheader" aria-label="Tickets" title="Tickets">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4V7Z"/>
                            <path fill="none" stroke="currentColor" stroke-dasharray="2.5 2.5" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 7v10"/>
                        </svg>
                    </div>
                    <div role="columnheader" aria-label="Valoraciones" title="Valoraciones">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="m12 17.27 4.15 2.51-1.1-4.72 3.67-3.18-4.83-.41L12 7l-1.89 4.48-4.83.41 3.67 3.18-1.1 4.72z"/>
                        </svg>
                    </div>
                </div>
                <div role="columnheader">Acciones</div>
            </div>
            <div class="users-table-list" role="list">
                @forelse ($users as $userItem)
                    @php
                        $photoUrl = $userItem['photo'] ? asset('img/photo_profile/' . $userItem['photo']) : asset('img/default-avatar-profile-icon.webp');
                        $addressText = trim($userItem['address'] . ($userItem['city'] ? ', ' . $userItem['city'] : '') . ($userItem['province'] ? ', ' . $userItem['province'] : ''));
                        $isActive = (int) $userItem['is_active'] === 1;
                        $toggleLabel = $isActive ? 'Desactivar' : 'Activar';
                    @endphp
                    <article class="user-row {{ $isActive ? '' : 'is-inactive' }}" data-card-id="user-{{ $userItem['id'] }}" role="listitem">
                        <div class="user-row-user">
                            <div class="user-avatar">
                                <img src="{{ $photoUrl }}" alt="Usuario">
                            </div>
                            <div class="user-row-user-copy">
                                <strong>{{ $userItem['name'] }}</strong>
                                <span>{{ $userItem['level'] }}</span>
                                <div class="user-row-badges">
                                    <span class="user-badge">#{{ $userItem['id'] }}</span>
                                    <span class="user-status {{ $isActive ? '' : 'inactive' }}">{{ $userItem['status_label'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="user-row-cell">
                            <strong>Correo</strong>
                            <span>{{ $userItem['email'] ?: 'Sin correo' }}</span>
                            <strong>Telefono</strong>
                            <span>{{ $userItem['phone'] ?: 'Sin telefono' }}</span>
                            <strong>Usuario</strong>
                            <span>{{ $userItem['user_name'] ?: 'Sin usuario' }}</span>
                        </div>
                        <div class="user-row-cell">
                            <strong>Direccion</strong>
                            <span>{{ $addressText !== '' ? $addressText : 'Sin direccion' }}</span>
                            <strong>Registro</strong>
                            <span>{{ $userItem['created_at'] ? 'Registrado ' . $userItem['created_at'] : 'Sin fecha' }}</span>
                        </div>
                        <div class="user-row-cell user-row-metrics user-row-metrics-grid">
                            @if (! empty($userItem['provider_metrics']))
                                <div class="metric-value-cell">
                                    <span class="metric-label">Vistas</span>
                                    <span class="metric-val">{{ $userItem['provider_metrics']['profile_visits'] ?? 0 }}</span>
                                </div>
                                <div class="metric-value-cell">
                                    <span class="metric-label">Contacto</span>
                                    <span class="metric-val">{{ $userItem['provider_metrics']['contact_clicks'] ?? 0 }}</span>
                                </div>
                                <div class="metric-value-cell">
                                    <span class="metric-label">Tickets</span>
                                    <span class="metric-val">{{ $userItem['provider_metrics']['service_tickets'] ?? 0 }}</span>
                                </div>
                                <div class="metric-value-cell font-bold">
                                    <span class="metric-label">Valoraciones</span>
                                    <span class="metric-val">
                                        {{ number_format((float) ($userItem['provider_metrics']['ratings_average'] ?? 0), 1) }}
                                        <span class="metric-count">({{ $userItem['provider_metrics']['ratings_received'] ?? 0 }})</span>
                                    </span>
                                </div>
                            @else
                                <div class="user-row-empty-metrics">No aplica</div>
                            @endif
                        </div>
                        <div class="user-row-actions">
                            <div class="user-row-action-item">
                                <a class="icon-action neutral" href="{{ url('/users/' . $userItem['id']) }}" title="Ver usuario" aria-label="Ver usuario">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3h7v7"/>
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14L21 3"/>
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 14v7a2 2 0 0 1-2 2h-7"/>
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10v11a2 2 0 0 0 2 2h11"/>
                                    </svg>
                                </a>
                            </div>
                            <div class="user-row-action-item">
                                <a class="icon-action accent" href="{{ url('/users/edit/' . $userItem['id']) }}" title="Editar usuario" aria-label="Editar usuario">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9"/>
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                    </svg>
                                </a>
                            </div>
                            <div class="user-row-action-item">
                                <button type="button" class="icon-action warning" data-toggle-user="{{ $userItem['id'] }}" data-active="{{ $isActive ? '1' : '0' }}" title="{{ $toggleLabel }}" aria-label="{{ $toggleLabel }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2v10"/>
                                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.3 6.3a7 7 0 1 0 11.4 0"/>
                                    </svg>
                                </button>
                            </div>
                            @if (($isAdmin ?? false) && (int) ($userItem['user_level_id'] ?? 0) === \App\Models\User::LEVEL_SERVICE_PROVIDER)
                                <div class="user-row-action-item">
                                    <button type="button" class="icon-action danger" data-delete-user="{{ $userItem['id'] }}" title="Eliminar usuario" aria-label="Eliminar usuario">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h18"/>
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"/>
                                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 11v6M14 11v6"/>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <h3>Sin usuarios</h3>
                        <p>No hay usuarios registrados actualmente.</p>
                    </div>
                @endforelse
            </div>
        </div>

        @if ($users->lastPage() > 1)
            <div class="pager">
                <a class="pager-link {{ $users->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $users->previousPageUrl() ?? '#' }}" aria-disabled="{{ $users->onFirstPage() ? 'true' : 'false' }}">
                    Anterior
                </a>
                <span class="pager-meta">Pagina {{ $users->currentPage() }} de {{ $users->lastPage() }}</span>
                <a class="pager-link {{ $users->currentPage() === $users->lastPage() ? 'is-disabled' : '' }}" href="{{ $users->nextPageUrl() ?? '#' }}" aria-disabled="{{ $users->currentPage() === $users->lastPage() ? 'true' : 'false' }}">
                    Siguiente
                </a>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const buttons = document.querySelectorAll('[data-toggle-user]');
            const deleteButtons = document.querySelectorAll('[data-delete-user]');
            const providerCsvInput = document.getElementById('providers_csv');
            const providerFileName = document.querySelector('[data-provider-file-name]');

            if (providerCsvInput && providerFileName) {
                providerCsvInput.addEventListener('change', () => {
                    const selectedFile = providerCsvInput.files && providerCsvInput.files[0]
                        ? providerCsvInput.files[0].name
                        : 'Ningún archivo seleccionado';
                    providerFileName.textContent = selectedFile;
                });
            }

            buttons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const userId = button.dataset.toggleUser;
                    const isActive = button.dataset.active === '1';
                    const actionLabel = isActive ? 'desactivar' : 'activar';
                    if (!confirm(`Deseas ${actionLabel} este usuario?`)) {
                        return;
                    }

                    button.disabled = true;
                    try {
                        const response = await fetch(`/users/toggle?id=${userId}`);
                        const data = await response.json();
                        if (data.status !== 200) {
                            alert(data.message || 'No se pudo actualizar el usuario.');
                            return;
                        }

                        const card = document.querySelector(`[data-card-id="user-${userId}"]`);
                        const statusBadge = card ? card.querySelector('.user-status') : null;
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
                        alert('Error al actualizar el usuario.');
                    } finally {
                        button.disabled = false;
                    }
                });
            });

            deleteButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const userId = button.dataset.deleteUser;
                    if (!confirm('Se eliminara este proveedor, su ficha de servicios y toda su multimedia asociada. Deseas continuar?')) {
                        return;
                    }

                    button.disabled = true;
                    try {
                        const response = await fetch(`/user/delete?id=${userId}`);
                        const data = await response.json();
                        if (data.status !== 200) {
                            alert(data.message || 'No se pudo eliminar el proveedor.');
                            return;
                        }

                        const card = document.querySelector(`[data-card-id="user-${userId}"]`);
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
