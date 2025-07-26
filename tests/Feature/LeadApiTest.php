<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    protected $validToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validToken = 'test-token';
        config(['app.api_token' => $this->validToken]);
    }

    public function test_lead_submission()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'source' => 'test-campaign',
            'message' => 'Test message'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'name', 'email', 'phone', 'source', 'message'
            ]);
    }

    public function test_get_all_leads()
    {
        Lead::factory()->count(3)->create();
        // dump(Lead::all());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson('/api/leads');

        $response->assertStatus(200)
            ->assertJsonCount(3);

        // Verifikasi caching
        $this->assertTrue(Cache::has('all_leads'));
    }

    public function test_get_single_lead()
    {
        $lead = Lead::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson("/api/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $lead->id,
                'email' => $lead->email
            ]);
    }

    public function test_unauthorized_access()
    {
        $response = $this->postJson('/api/leads', []);
        $response->assertStatus(401);
    }

    public function test_validation_errors()
    {
        $this->withoutExceptionHandling();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', [
            'name' => '',
            'email' => 'invalid-email',
            'phone' => '',
            'source' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone']);
    }
}
