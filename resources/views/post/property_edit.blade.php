@extends('layouts.backoffice')

@section('title', 'Kconecta - Editar propiedad')

@section('heading')
    Editar propiedad
@endsection

@section('subheading')
    Actualiza la informaci&oacute;n principal del anuncio
@endsection

@section('header_actions')
    <a class="secondary" href="{{ url('/post/my_posts') }}">Volver a propiedades</a>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/libraries/bulma.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app/forms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page/property-form.css') }}">
@endsection

@section('content')
    <form action="{{ url('/post/update') }}" method="post" autocomplete="off">
        @csrf
        <div class="container-title-page">
            <h2>Editar anuncio &raquo; <span>{{ $property->title ?: 'Propiedad' }}</span></h2>
        </div>

        <div class="container-main">
            <input type="hidden" name="id" value="{{ $property->id }}">

            <div class="container-row-form box">
                <div class="div-col-1">
                    <label>
                        <span class="title-label">Referencia</span>
                        <input type="text" class="input" value="{{ $property->reference }}" disabled>
                    </label>
                    <label>
                        <span class="title-label">Tipo</span>
                        <input type="text" class="input" value="{{ $typeLabel }}" disabled>
                    </label>
                </div>
                <div class="div-col-1">
                    <label>
                        <span class="title-label">Categoria</span>
                        <input type="text" class="input" value="{{ $categoryLabel }}" disabled>
                    </label>
                    <label>
                        <span class="title-label">Estado</span>
                        <div class="select">
                            <select name="state_id">
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}" {{ (int) $property->state_id === (int) $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </label>
                </div>
                <label class="label-col-100">
                    <span class="title-label">Direcci&oacute;n</span>
                    <input type="text" class="input" value="{{ $address?->address }}{{ $address?->city ? ', ' . $address->city : '' }}" disabled>
                </label>
            </div>

            <div class="container-row-form box">
                <label class="label-col-100">
                    <span class="title-label">Titulo *</span>
                    <input type="text" class="input" name="title" value="{{ old('title', $property->title) }}" required>
                </label>
                <div class="div-col-1">
                    <label>
                        <span class="title-label">Precio de venta</span>
                        <input type="text" class="input" name="sale_price" value="{{ old('sale_price', $property->sale_price) }}">
                    </label>
                    <label>
                        <span class="title-label">Precio de alquiler</span>
                        <input type="text" class="input" name="rental_price" value="{{ old('rental_price', $property->rental_price) }}">
                    </label>
                </div>
            </div>

            <div class="container-row-form-col-1 box">
                <div class="div-col-1">
                    <label>
                        <span class="title-label">Descripci&oacute;n</span>
                        <textarea class="textarea" name="description" rows="6">{{ old('description', $property->description) }}</textarea>
                    </label>
                </div>
            </div>

            <div class="box">
                <button class="button container-button-save" type="submit">Guardar cambios</button>
            </div>
        </div>
    </form>
@endsection
