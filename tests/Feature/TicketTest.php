<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_ticket(): void
    {
        $user = User::factory()->create([
            'user_level_id' => User::LEVEL_AGENT,
            'email_verified_at' => now(),
        ]);

        $responseWeb = $this->actingAs($user)
            ->post('/post/tickets', [
                'subject' => 'Ticket de soporte Web',
                'description' => 'Descripcion detallada de la incidencia web.',
                'priority' => 'high',
            ]);

        $responseWeb->assertSessionHasNoErrors();
        $ticketWeb = Ticket::where('subject', 'Ticket de soporte Web')->first();
        $this->assertNotNull($ticketWeb);
        $this->assertEquals($user->id, $ticketWeb->user_id);
        $this->assertEquals('high', $ticketWeb->priority);
        $this->assertEquals(Ticket::STATUS_OPEN, $ticketWeb->status);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticketWeb->id,
            'user_id' => $user->id,
            'message' => 'Descripcion detallada de la incidencia web.',
        ]);

        $responseApi = $this->actingAs($user, 'sanctum')
            ->postJson('/api/agent/tickets', [
                'subject' => 'Ticket de soporte API',
                'description' => 'Descripcion detallada de la incidencia API.',
                'priority' => 'low',
            ]);

        $responseApi->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subject', 'Ticket de soporte API');

        $ticketApi = Ticket::where('subject', 'Ticket de soporte API')->first();
        $this->assertNotNull($ticketApi);
        $this->assertEquals('low', $ticketApi->priority);
    }

    public function test_user_can_only_list_own_tickets_but_admin_lists_all(): void
    {
        $user1 = User::factory()->create(['user_level_id' => User::LEVEL_AGENT, 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['user_level_id' => User::LEVEL_AGENT, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['user_level_id' => User::LEVEL_ADMIN, 'email_verified_at' => now()]);

        Ticket::create([
            'user_id' => $user1->id,
            'subject' => 'Ticket de Usuario 1',
            'description' => 'Incidencia del usuario 1',
            'priority' => 'medium',
        ]);

        Ticket::create([
            'user_id' => $user2->id,
            'subject' => 'Ticket de Usuario 2',
            'description' => 'Incidencia del usuario 2',
            'priority' => 'medium',
        ]);

        $responseWeb = $this->actingAs($user1)->get('/post/tickets');
        $responseWeb->assertOk()
            ->assertViewHas('tickets')
            ->assertSee('Ticket de Usuario 1')
            ->assertDontSee('Ticket de Usuario 2');

        $responseApi = $this->actingAs($user1, 'sanctum')->getJson('/api/agent/tickets');
        $responseApi->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Ticket de Usuario 1')
            ->assertJsonPath('meta.current_page', 1);

        $responseAdmin = $this->actingAs($admin, 'sanctum')->getJson('/api/agent/tickets');
        $responseAdmin->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_cannot_view_someone_elses_ticket(): void
    {
        $user1 = User::factory()->create(['user_level_id' => User::LEVEL_AGENT, 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['user_level_id' => User::LEVEL_AGENT, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['user_level_id' => User::LEVEL_ADMIN, 'email_verified_at' => now()]);

        $ticket1 = Ticket::create([
            'user_id' => $user1->id,
            'subject' => 'Ticket Privado',
            'description' => 'Secreto',
            'priority' => 'low',
        ]);

        $this->actingAs($user2)->get("/post/tickets/{$ticket1->id}")->assertStatus(403);
        $this->actingAs($user2, 'sanctum')->getJson("/api/agent/tickets/{$ticket1->id}")->assertStatus(403);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/agent/tickets/{$ticket1->id}")
            ->assertOk()
            ->assertJsonPath('data.subject', 'Ticket Privado');
    }

    public function test_user_can_reply_to_ticket(): void
    {
        $user = User::factory()->create(['user_level_id' => User::LEVEL_AGENT, 'email_verified_at' => now()]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'subject' => 'Ticket de Prueba',
            'description' => 'Mensaje inicial',
            'priority' => 'medium',
        ]);

        $responseWeb = $this->actingAs($user)
            ->post("/post/tickets/{$ticket->id}/reply", [
                'message' => 'Respuesta web de prueba.',
            ]);

        $responseWeb->assertRedirect();
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Respuesta web de prueba.',
        ]);

        $responseApi = $this->actingAs($user, 'sanctum')
            ->postJson("/api/agent/tickets/{$ticket->id}/reply", [
                'message' => 'Respuesta API de prueba.',
            ]);

        $responseApi->assertStatus(201);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Respuesta API de prueba.',
        ]);
    }

    public function test_user_can_close_ticket(): void
    {
        $user = User::factory()->create(['user_level_id' => User::LEVEL_AGENT, 'email_verified_at' => now()]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'subject' => 'Ticket a cerrar',
            'description' => 'Detalle',
            'priority' => 'low',
        ]);

        $responseApi = $this->actingAs($user, 'sanctum')
            ->postJson("/api/agent/tickets/{$ticket->id}/close");

        $responseApi->assertOk();
        $ticket->refresh();
        $this->assertEquals(Ticket::STATUS_CLOSED, $ticket->status);

        $responseReply = $this->actingAs($user, 'sanctum')
            ->postJson("/api/agent/tickets/{$ticket->id}/reply", [
                'message' => 'Intento de respuesta en ticket cerrado.',
            ]);

        $responseReply->assertStatus(400);
    }

    public function test_final_client_cannot_access_ticket_module(): void
    {
        $finalClient = User::factory()->create([
            'user_level_id' => User::LEVEL_FINAL_CLIENT,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($finalClient)
            ->get('/post/tickets')
            ->assertStatus(403);

        $this->actingAs($finalClient, 'sanctum')
            ->getJson('/api/agent/tickets')
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'ROLE_NOT_ALLOWED');
    }

    public function test_unverified_property_user_can_still_access_web_ticket_module(): void
    {
        $user = User::factory()->create([
            'user_level_id' => User::LEVEL_AGENT,
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/post/tickets')
            ->assertOk();
    }
}
