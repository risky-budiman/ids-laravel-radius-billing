<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerMobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\User::create([
            'id' => 1,
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);

        // Create a dummy customer
        $this->customer = Customer::create([
            'customer_code' => 'CUST_API_001',
            'name' => 'API Customer Test',
            'username' => 'apicustomer',
            'password' => 'secret123', // plaintext fallback compatibility
            'email' => 'api@customer.com',
            'phone' => '0899999999',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function test_customer_can_login_via_api()
    {
        $response = $this->postJson('/api/v1/customer/login', [
            'username' => 'apicustomer',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'customer' => [
                'id', 'customer_code', 'name', 'username', 'email', 'phone', 'status', 'is_active'
            ]
        ]);
    }

    /** @test */
    public function test_customer_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/customer/login', [
            'username' => 'apicustomer',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function test_authenticated_customer_can_access_profile()
    {
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/customer/profile');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'customer' => [
                'id', 'customer_code', 'name', 'username', 'email', 'phone', 'address', 'status', 'is_active', 'type'
            ]
        ]);
    }

    /** @test */
    public function test_authenticated_customer_can_list_and_show_invoices()
    {
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-API-TEST',
            'amount' => 100000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
        ]);

        $headers = ['Authorization' => 'Bearer ' . $token];

        // List
        $response = $this->withHeaders($headers)->getJson('/api/v1/customer/invoices');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'invoices');

        // Show
        $response = $this->withHeaders($headers)->getJson("/api/v1/customer/invoices/{$invoice->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'invoice',
            'active_gateways'
        ]);
    }

    /** @test */
    public function test_authenticated_customer_can_manage_tickets()
    {
        $token = $this->customer->createToken('test-token')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        // 1. Create Ticket
        $response = $this->withHeaders($headers)->postJson('/api/v1/customer/tickets', [
            'subject' => 'Connection Drop',
            'description' => 'My internet is dropping every 5 mins.',
        ]);

        $response->assertStatus(201);
        $ticketId = $response->json('ticket.id');

        // 2. List Tickets
        $response = $this->withHeaders($headers)->getJson('/api/v1/customer/tickets');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tickets');

        // 3. Show Ticket
        $response = $this->withHeaders($headers)->getJson("/api/v1/customer/tickets/{$ticketId}");
        $response->assertStatus(200);
        $response->assertJsonStructure(['ticket' => ['replies', 'assignee']]);

        // 4. Reply to Ticket
        $response = $this->withHeaders($headers)->postJson("/api/v1/customer/tickets/{$ticketId}/reply", [
            'message' => 'Still dropping, please check.',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'reply']);
    }
}
