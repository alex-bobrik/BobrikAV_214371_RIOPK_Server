<?php

namespace Tests\Feature\Api\Client;

use App\Models\Company;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $insurer;
    protected $reinsurer;
    protected $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->insurer = Company::factory()->create(['type' => 'insurer']);
        $this->reinsurer = Company::factory()->create(['type' => 'reinsurer']);
        
        $this->user = User::factory()->create([
            'company_id' => $this->insurer->id,
            'email_verified_at' => null,
        ]);

        $this->contract = Contract::factory()->create([
            'insurer_id' => $this->insurer->id,
            'reinsurer_id' => $this->reinsurer->id,
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_create_a_contract()
    {
        $data = [
            'type' => 'quota',
            'reinsurer_id' => $this->reinsurer->id,
            'premium' => 10000,
            'coverage' => 100000,
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'user' => [
                'id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/contracts', $data);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Договор успешно создан',
                'data' => [
                    'type' => 'quota',
                    'status' => 'pending'
                ]
            ]);

        $this->assertDatabaseHas('contracts', [
            'type' => 'quota',
            'insurer_id' => $this->insurer->id,
            'reinsurer_id' => $this->reinsurer->id,
        ]);
    }

    /** @test */
    public function it_validates_contract_creation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/contracts', [
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'type', 'reinsurer_id', 'premium', 
                'coverage', 'start_date', 'end_date'
            ]);
    }

    /** @test */
    public function it_can_show_a_contract()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/contracts/{$this->contract->id}", [
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ]
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->contract->id,
                    'reinsurer' => ['id' => $this->reinsurer->id]
                ]
            ]);
    }

    /** @test */
    public function it_can_update_a_pending_contract()
    {
        $data = [
            'type' => 'excess',
            'premium' => 15000,
            'user' => [
                'id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/contracts/{$this->contract->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Договор обновлен',
                'data' => [
                    'type' => 'excess',
                    'premium' => 15000
                ]
            ]);
    }

    /** @test */
    public function it_cannot_update_non_pending_contract()
    {
        $contract = Contract::factory()->create([
            'insurer_id' => $this->insurer->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/contracts/{$contract->id}", [
                'premium' => 20000,
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ]
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_cannot_delete_non_pending_contract()
    {
        $contract = Contract::factory()->create([
            'insurer_id' => $this->insurer->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/contracts/{$contract->id}", [
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ]
            ]);

        $response->assertStatus(403);
    }
}