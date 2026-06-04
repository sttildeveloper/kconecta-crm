<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TicketApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($response = $this->ensureTicketRole($user)) {
            return $response;
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));

        if ($user->isAdmin()) {
            $tickets = Ticket::with(['user', 'property'])->latest()->paginate($perPage);
        } else {
            $tickets = Ticket::where('user_id', $user->id)
                ->with('property')
                ->latest()
                ->paginate($perPage);
        }

        return $this->paginatedResponse($tickets, 'Tickets listados correctamente.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:150',
            'description' => 'required|string',
            'priority' => 'required|string|in:low,medium,high',
            'property_id' => 'nullable|integer|exists:property,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos.', 422, $validator->errors()->toArray());
        }

        $user = $request->user();
        if ($response = $this->ensureTicketRole($user)) {
            return $response;
        }

        if ($request->property_id && !$user->isAdmin()) {
            $ownsProperty = Property::where('id', $request->property_id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$ownsProperty) {
                return $this->errorResponse('No tienes permisos sobre esta propiedad.', 403);
            }
        }

        $ticket = DB::transaction(function () use ($request, $user) {
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'property_id' => $request->property_id,
                'subject' => $request->subject,
                'description' => $request->description,
                'status' => Ticket::STATUS_OPEN,
                'priority' => $request->priority,
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->description,
            ]);

            return $ticket;
        });

        return $this->successResponse($ticket->load('messages.user'), 'Ticket creado correctamente.', 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if ($response = $this->ensureTicketRole($user)) {
            return $response;
        }

        $ticket = Ticket::with(['user', 'property', 'messages.user'])->find($id);

        if (!$ticket) {
            return $this->errorResponse('Ticket no encontrado.', 404);
        }

        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            return $this->errorResponse('No tienes permiso para ver este ticket.', 403);
        }

        return $this->successResponse($ticket, 'Detalle del ticket obtenido correctamente.');
    }

    public function reply(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos.', 422, $validator->errors()->toArray());
        }

        $user = $request->user();
        if ($response = $this->ensureTicketRole($user)) {
            return $response;
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->errorResponse('Ticket no encontrado.', 404);
        }

        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            return $this->errorResponse('No tienes permiso para responder en este ticket.', 403);
        }

        if ($ticket->status === Ticket::STATUS_CLOSED) {
            return $this->errorResponse('Este ticket esta cerrado y no admite mas respuestas.', 400);
        }

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        if ($user->isAdmin() && $ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return $this->successResponse($message->load('user'), 'Mensaje enviado correctamente.', 201);
    }

    public function close(Request $request, $id)
    {
        $user = $request->user();
        if ($response = $this->ensureTicketRole($user)) {
            return $response;
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->errorResponse('Ticket no encontrado.', 404);
        }

        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            return $this->errorResponse('No tienes permiso para cerrar este ticket.', 403);
        }

        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        return $this->successResponse($ticket, 'Ticket cerrado correctamente.');
    }

    private function successResponse($data, $message = null, $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => null,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    private function paginatedResponse(LengthAwarePaginator $paginator, $message = null, $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'next_page' => $paginator->nextPageUrl(),
                'prev_page' => $paginator->previousPageUrl(),
            ],
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    private function errorResponse($message, $status, $errors = null)
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    private function ensureTicketRole($user)
    {
        if (!$user || !$user->canManageProperties()) {
            return $this->errorResponse(
                'Solo propietarios, gestores y administradores pueden acceder al modulo de tickets.',
                Response::HTTP_FORBIDDEN,
                ['code' => 'ROLE_NOT_ALLOWED']
            );
        }

        return null;
    }
}
