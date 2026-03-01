@extends('layouts.backoffice')

@section('title', 'Kconecta - Blog')

@section('heading')
    Blog
@endsection

@section('subheading')
    Gestiona los art&iacute;culos publicados
@endsection

@section('header_actions')
    <a href="{{ url('/post/blogs/create') }}">Crear art&iacute;culo</a>
    <a class="secondary" href="{{ url('/post/my_posts') }}">Ver propiedades</a>
@endsection

@section('content')
    <div class="page-card">
        <div class="section-header">
            <div>
                <h2>Listado</h2>
                <p>{{ $posts->total() }} art&iacute;culos en total</p>
            </div>
        </div>
    </div>

    <div class="catalog-grid">
        @forelse ($posts as $post)
            @php
                $imageUrl = $post['image'] ? asset($post['image']) : asset('img/image-icon-1280x960.png');
            @endphp
            <article class="entity-card" data-card-id="blog-{{ $post['id'] }}">
                <div class="entity-media">
                    <img src="{{ $imageUrl }}" alt="Articulo" loading="lazy">
                </div>
                <div class="entity-body">
                    <div class="entity-tags">
                        <span>{{ $post['category_label'] }}</span>
                        <span>{{ $post['status_label'] }}</span>
                    </div>
                    <h3>{{ $post['title'] }}</h3>
                    <div class="entity-meta">
                        @if ($post['updated_at'])
                            <span>Actualizado {{ $post['updated_at'] }}</span>
                        @endif
                    </div>
                    <p class="entity-description">{{ $post['summary'] }}</p>
                </div>
                <div class="entity-actions icon-actions">
                    <a class="icon-action neutral" href="{{ url('/blogs/' . $post['slug']) }}" target="_blank" title="Ver articulo" aria-label="Ver articulo">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3h7v7"/>
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14L21 3"/>
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 14v7a2 2 0 0 1-2 2h-7"/>
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10v11a2 2 0 0 0 2 2h11"/>
                        </svg>
                    </a>
                    @if ($isAdmin)
                        <a class="icon-action accent" href="{{ url('/post/blogs/edit/' . $post['id']) }}" title="Editar articulo" aria-label="Editar articulo">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                            </svg>
                        </a>
                    @endif
                    @if ($isAdmin)
                        <button type="button" class="icon-action danger" data-delete-blog="{{ $post['id'] }}" title="Eliminar" aria-label="Eliminar">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h18"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6V4h8v2"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 6l-1 14H6L5 6"/>
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 11v6M14 11v6"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h3>Sin art&iacute;culos aun</h3>
                <p>Publica tu primer art&iacute;culo desde el panel.</p>
                <a class="primary" href="{{ url('/post/blogs/create') }}">Crear art&iacute;culo</a>
            </div>
        @endforelse
    </div>

    @if ($posts->lastPage() > 1)
        <div class="pager">
            <a class="pager-link {{ $posts->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $posts->previousPageUrl() ?? '#' }}" aria-disabled="{{ $posts->onFirstPage() ? 'true' : 'false' }}">
                Anterior
            </a>
            <span class="pager-meta">Pagina {{ $posts->currentPage() }} de {{ $posts->lastPage() }}</span>
            <a class="pager-link {{ $posts->currentPage() === $posts->lastPage() ? 'is-disabled' : '' }}" href="{{ $posts->nextPageUrl() ?? '#' }}" aria-disabled="{{ $posts->currentPage() === $posts->lastPage() ? 'true' : 'false' }}">
                Siguiente
            </a>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        (() => {
            const deleteButtons = document.querySelectorAll('[data-delete-blog]');

            deleteButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    if (!confirm('Eliminar articulo?')) {
                        return;
                    }

                    button.disabled = true;
                    const id = button.dataset.deleteBlog;

                    try {
                        const response = await fetch(`/post/blogs/delete?id=${id}`);
                        const data = await response.json();
                        if (data.status === 200) {
                            const card = document.querySelector(`[data-card-id="blog-${id}"]`);
                            if (card) {
                                card.remove();
                            }
                        } else {
                            alert('No se pudo eliminar el articulo.');
                        }
                    } catch (error) {
                        alert('Error al eliminar el articulo.');
                    } finally {
                        button.disabled = false;
                    }
                });
            });
        })();
    </script>
@endsection
