<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $this->ensureTicketRole($user);

        if ($user->isAdmin()) {
            $tickets = Ticket::with(['user', 'property'])->latest()->paginate(15);
        } else {
            $tickets = Ticket::where('user_id', $user->id)
                ->with('property')
                ->latest()
                ->paginate(15);
        }

        return view('post.tickets.index', compact('tickets'));
    }

    public function create()
    {
        $user = Auth::user();
        $this->ensureTicketRole($user);

        if ($user->isAdmin()) {
            $properties = Property::select('id', 'title', 'reference')->get();
        } else {
            $properties = Property::where('user_id', $user->id)
                ->select('id', 'title', 'reference')
                ->get();
        }

        return view('post.tickets.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:150',
            'description' => 'required|string',
            'priority' => 'required|string|in:low,medium,high',
            'property_id' => 'nullable|integer|exists:property,id',
        ]);

        $user = Auth::user();
        $this->ensureTicketRole($user);

        if ($request->property_id && !$user->isAdmin()) {
            $ownsProperty = Property::where('id', $request->property_id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$ownsProperty) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['property_id' => 'No tienes permisos sobre esta propiedad.']);
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

        return redirect()->action([self::class, 'show'], ['id' => $ticket->id])
            ->with('success', 'Ticket creado correctamente.');
    }

    public function show($id)
    {
        $user = Auth::user();
        $this->ensureTicketRole($user);
        $ticket = Ticket::with(['user', 'property', 'messages.user'])->findOrFail($id);

        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver este ticket.');
        }

        return view('post.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $this->ensureTicketRole($user);
        $ticket = Ticket::findOrFail($id);

        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            abort(403, 'No tienes permiso para responder en este ticket.');
        }

        if ($ticket->status === Ticket::STATUS_CLOSED) {
            return redirect()->back()->withErrors(['message' => 'Este ticket esta cerrado y no admite mas respuestas.']);
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        if ($user->isAdmin() && $ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return redirect()->back()->with('success', 'Mensaje enviado correctamente.');
    }

    public function close($id)
    {
        $user = Auth::user();
        $this->ensureTicketRole($user);
        $ticket = Ticket::findOrFail($id);

        if (!$user->isAdmin() && $ticket->user_id !== $user->id) {
            abort(403, 'No tienes permiso para cerrar este ticket.');
        }

        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        return redirect()->back()->with('success', 'Ticket cerrado correctamente.');
    }

    private function ensureTicketRole($user): void
    {
        if (!$user || !$user->canManageProperties()) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para acceder al modulo de tickets.');
        }
    }
}
