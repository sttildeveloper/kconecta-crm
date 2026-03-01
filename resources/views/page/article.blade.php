@extends('layouts.page')

@section('nav_option')
    <a href="{{ url('/') }}">
        <span>Ir al inicio</span>
    </a>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/page/article.css') }}">
@endsection

@section('content')
    @php
        $shareUrl = rawurlencode(url('/blogs/' . $article->slug));
        $shareText = rawurlencode($article->title);
        $shareWhatsApp = rawurlencode($article->title . ' ' . url('/blogs/' . $article->slug));
        $coverImage = $article->featured_image ? asset($article->featured_image) : asset('img/image-icon-1280x960.png');
        $instagramUrl = 'https://www.instagram.com/vendoyo.es/';
    @endphp
    <div class="article-shell">
        <article class="article-card">
            <header class="article-hero">
                <div class="article-meta">
                    <span class="article-chip">Blog Kconecta</span>
                    @if ($article->updated_at)
                        <span class="article-date">Actualizado {{ $article->updated_at->format('d/m/Y') }}</span>
                    @endif
                </div>
                <h1>{{ $article->title }}</h1>
                <p class="article-summary">{{ $article->summary }}</p>
                <div class="article-share">
                    <span>Compartir:</span>
                    <a class="share-btn fb" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Compartir en Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2C6.486 2 2 6.486 2 12c0 4.991 3.657 9.128 8.438 9.879v-6.987H7.897V12h2.541V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.242 0-1.63.771-1.63 1.562V12h2.773l-.443 2.892h-2.33v6.987C18.343 21.128 22 16.991 22 12c0-5.514-4.486-10-10-10z"/>
                        </svg>
                    </a>
                    <a class="share-btn ig" href="{{ $instagramUrl }}" target="_blank" rel="noopener" aria-label="Seguir en Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 3c-2.206 0-4 1.794-4 4v10c0 2.206 1.794 4 4 4h10c2.206 0 4-1.794 4-4V7c0-2.206-1.794-4-4-4H7zm10 2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h10zm-5 2.5A4.5 4.5 0 1 0 16.5 12 4.505 4.505 0 0 0 12 7.5zm0 2A2.5 2.5 0 1 1 9.5 12 2.503 2.503 0 0 1 12 9.5zm5.25-2.75a1.25 1.25 0 1 0 1.25 1.25 1.252 1.252 0 0 0-1.25-1.25z"/>
                        </svg>
                    </a>
                    <a class="share-btn x" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" aria-label="Compartir en X">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18.9 2H22l-7.4 8.5L23 22h-6.7l-5.2-6.3L5.7 22H2.6l8-9.2L1 2h6.8l4.7 5.7L18.9 2zm-1.3 2-4.2 4.9 6.6 8.1h1.7l-7.1-8.8L19 4h-1.4zm-9.1 0H4.8l6.5 8.1L6 20h1.6l4.8-5.6L15.5 18h3.4L8.5 4z"/>
                        </svg>
                    </a>
                    <a class="share-btn wa" href="https://api.whatsapp.com/send?text={{ $shareWhatsApp }}" target="_blank" rel="noopener" aria-label="Compartir en WhatsApp">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2a10 10 0 0 0-8.68 15.03L2 22l5.12-1.34A10 10 0 1 0 12 2zm0 2a8 8 0 0 1 0 16 7.9 7.9 0 0 1-4.05-1.1l-.3-.18-2.99.78.8-2.91-.2-.3A8 8 0 0 1 12 4zm4.35 9.86c-.24-.12-1.4-.69-1.62-.77-.22-.08-.38-.12-.54.12-.16.24-.62.77-.77.93-.14.16-.28.18-.52.06a6.53 6.53 0 0 1-1.92-1.18 7.2 7.2 0 0 1-1.33-1.65c-.14-.24 0-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.48-.4-.42-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.57 4.1 3.6.57.24 1.01.38 1.36.48.57.18 1.09.16 1.5.1.46-.06 1.4-.58 1.6-1.14.2-.56.2-1.04.14-1.14-.06-.1-.2-.16-.44-.28z"/>
                        </svg>
                    </a>
                </div>
            </header>
            <div class="article-media">
                <img src="{{ $coverImage }}" alt="Articulo">
            </div>
            <div class="article-content">
                {!! $article->content !!}
            </div>
        </article>
    </div>
@endsection

@section('js')
@endsection
