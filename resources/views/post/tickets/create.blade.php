@extends('layouts.backoffice')

@section('title', 'Kconecta - Abrir Ticket')

@section('heading')
    Abrir nuevo ticket de soporte
@endsection

@section('subheading')
    Cuéntanos tu incidencia y la resolveremos lo antes posible
@endsection

@section('header_actions')
    <a class="action-pill" href="{{ route('tickets.index') }}">Volver al listado</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('tickets.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="subject">Asunto</label>
                    <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject') }}" required max="150">
                </div>

                <div class="form-group mt-3">
                    <label for="property_id">Propiedad/Caso asociada (Opcional)</label>
                    <select name="property_id" id="property_id" class="form-control">
                        <option value="">Ninguna propiedad en particular</option>
                        @foreach ($properties as $prop)
                            <option value="{{ $prop->id }}" {{ old('property_id') == $prop->id ? 'selected' : '' }}>
                                {{ $prop->title }} (Ref: {{ $prop->reference }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mt-3">
                    <label for="priority">Prioridad</label>
                    <select name="priority" id="priority" class="form-control" required>
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>

                <div class="form-group mt-3">
                    <label for="description">Descripción del problema</label>
                    <textarea name="description" id="description" class="form-control" rows="6" required>{{ old('description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success mt-4">Enviar solicitud</button>
            </form>
        </div>
    </div>
@endsection
