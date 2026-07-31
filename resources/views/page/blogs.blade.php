@extends('layouts.home')

@section('hide_announcement', true)

@section('head')
    <link rel="stylesheet" href="{{ asset('css/app/blogs.css') }}?v={{ filemtime(public_path('css/app/blogs.css')) }}">
@endsection

@section('content')
    <div class="container-main-all">
        <div class="container-all-blogs">
            @forelse ($posts as $post)
                @php
                    $imageUrl = $post['image'] ? asset($post['image']) : asset('img/image-icon-1280x960.png');
                @endphp
                <article class="property-listing-card">
                    <div class="image-gallery">
                        <a class="main-image-container" href="{{ url('/blogs/' . $post['slug']) }}" aria-label="Leer articulo">
                            <img src="{{ $imageUrl }}" alt="Imagen destacada">
                        </a>
                    </div>

                    <div class="property-details">
                        <div class="property-info-top">
                            <h2 class="property-title truncate-3-lines">
                                <a href="{{ url('/blogs/' . $post['slug']) }}">{{ $post['title'] }}</a>
                            </h2>
                        </div>
                        <p class="property-description truncate-3-lines">{{ $post['summary'] }}</p>
                        <div class="action-buttons-bottom">
                            <a href="{{ url('/blogs/' . $post['slug']) }}" class="icon-btn" aria-label="Leer articulo">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4m-8-2l8-8m0 0v5m0-5h-5"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <p>No hay articulos publicados.</p>
            @endforelse
        </div>

        @if ($posts->lastPage() > 1)
            <div style="margin-top: 1rem;">
                <a href="{{ $posts->previousPageUrl() ?? '#' }}" style="{{ $posts->onFirstPage() ? 'pointer-events:none;opacity:0.5;' : '' }}">Anterior</a>
                <span style="margin: 0 0.6rem;">Pagina {{ $posts->currentPage() }} de {{ $posts->lastPage() }}</span>
                <a href="{{ $posts->nextPageUrl() ?? '#' }}" style="{{ $posts->currentPage() === $posts->lastPage() ? 'pointer-events:none;opacity:0.5;' : '' }}">Siguiente</a>
            </div>
        @endif
    </div>
@endsection
