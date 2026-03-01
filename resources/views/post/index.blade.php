@extends('layouts.backoffice')

@section('title', 'Kconecta - Agregar propiedad')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/page/create-property.css') }}">
@endsection

@section('heading')
    Agregar propiedad
@endsection

@section('subheading')
    Publica tu propiedad en minutos
@endsection

@section('header_actions')
    <a class="action-pill" href="{{ url('/post/my_posts') }}">Ver propiedades</a>
@endsection

@section('content')
    <section class="create-hero">
        <div class="hero-copy">
            <span class="hero-kicker">Paso 1</span>
            <h2>Selecciona el tipo de inmueble</h2>
            <p>Completa los detalles para que tu propiedad destaque y llegue a los interesados.</p>
        </div>
        <div class="hero-stat">
            <span>Opciones disponibles</span>
            <strong>{{ count($propertyTypes) }}</strong>
        </div>
    </section>

    <section class="type-grid">
        @forelse ($propertyTypes as $type)
            <a class="type-card" href="{{ url('/post/create_form/' . $type['id']) }}">
                <div class="type-media">
                    <img src="{{ asset($type['image']) }}" alt="{{ $type['label'] }}">
                </div>
                <div class="type-body">
                    <h3>{{ $type['label'] }}</h3>
                    <p>{{ $type['summary'] }}</p>
                </div>
                <span class="type-cta">Seleccionar</span>
            </a>
        @empty
            <div class="empty-state">
                <h3>Sin tipos disponibles</h3>
                <p>No hay tipos configurados para crear anuncios.</p>
            </div>
        @endforelse
    </section>
@endsection
