@extends('layouts.backoffice')

@section('title', 'Kconecta - Soporte y Tickets')

@section('heading')
    Mis Incidencias y Soporte
@endsection

@section('subheading')
    Administra y abre solicitudes de soporte técnico
@endsection

@section('header_actions')
    <a class="action-pill" href="{{ route('tickets.create') }}">Abrir nuevo ticket</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        <th>Asunto</th>
                        <th>Propiedad</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->subject }}</td>
                            <td>{{ $ticket->property ? $ticket->property->title : 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $ticket->status }}">
                                    {{ ucfirst($ticket->status) }}
                                </span>
                            </td>
                            <td>{{ ucfirst($ticket->priority) }}</td>
                            <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-primary btn-sm">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No tienes tickets abiertos en este momento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
@endsection
