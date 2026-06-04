@extends('layouts.backoffice')

@section('title', 'Kconecta - Detalle de Ticket')

@section('heading')
    Ticket #{{ $ticket->id }}: {{ $ticket->subject }}
@endsection

@section('subheading')
    Creado por {{ $ticket->user->first_name }} {{ $ticket->user->last_name }} • Estado: {{ ucfirst($ticket->status) }} • Prioridad: {{ ucfirst($ticket->priority) }}
@endsection

@section('header_actions')
    <a class="action-pill" href="{{ route('tickets.index') }}">Volver al listado</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="ticket-messages mt-4">
                <h4>Conversación</h4>
                @foreach ($ticket->messages as $msg)
                    <div class="message-card p-3 my-3 border rounded {{ $msg->user_id === Auth::id() ? 'bg-light text-right' : 'bg-white' }}">
                        <strong>{{ $msg->user->first_name }} {{ $msg->user->last_name }}</strong> 
                        <small class="text-muted">• {{ $msg->created_at->format('d/m/Y H:i') }}</small>
                        <p class="mt-2">{{ $msg->message }}</p>
                    </div>
                @endforeach
            </div>

            @if ($ticket->status !== \App\Models\Ticket::STATUS_CLOSED)
                <hr>
                <div class="reply-section mt-4">
                    <h4>Responder</h4>
                    <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <textarea name="message" class="form-control" rows="4" placeholder="Escribe tu respuesta aquí..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Enviar mensaje</button>
                    </form>

                    <form action="{{ route('tickets.close', $ticket->id) }}" method="POST" class="d-inline mt-3">
                        @csrf
                        <button type="submit" class="btn btn-danger mt-3">Cerrar Ticket</button>
                    </form>
                </div>
            @else
                <div class="alert alert-info mt-4">Este ticket está cerrado. No se admiten más respuestas.</div>
            @endif
        </div>
    </div>
@endsection
