<?php

namespace Tests\Feature\Client;

use App\Models\Company;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimControllerTest extends TestCase
{
    // use RefreshDatabase;

    protected $user;
    protected $contract;
    protected $insurer;
    protected $reinsurer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->insurer = Company::factory()->create();
        $this->reinsurer = Company::factory()->create();

        $this->user = User::factory()->create([
            'company_id' => $this->insurer->id,
            'email_verified_at' => null,
        ]);

        $this->contract = Contract::factory()->create([
            'insurer_id' => $this->insurer->id,
            'reinsurer_id' => $this->reinsurer->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_stores_a_valid_claim()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/claims', [
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ],
                'contract_id' => $this->contract->id,
                'amount' => 1000.50,
                'description' => 'Test claim description',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Убыток зарегистрирован',
            ]);

        $this->assertDatabaseHas('claims', [
            'contract_id' => $this->contract->id,
            'amount' => 1000.50,
            'description' => 'Test claim description',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/claims', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user', 'contract_id', 'amount', 'description'
            ]);
    }

    /** @test */
    public function it_validates_user_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/claims', [
                'user' => 'invalid',
                'contract_id' => $this->contract->id,
                'amount' => 1000.50,
                'description' => 'Test claim description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user']);
    }

    /** @test */
    public function it_validates_contract_belongs_to_user_company()
    {
        $otherCompany = Company::factory()->create();
        $otherContract = Contract::factory()->create([
            'insurer_id' => $otherCompany->id,
            'reinsurer_id' => $this->reinsurer->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/claims', [
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ],
                'contract_id' => $otherContract->id,
                'amount' => 1000.50,
                'description' => 'Test claim description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contract_id']);
    }

    /** @test */
    public function it_validates_amount_is_positive()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/claims', [
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ],
                'contract_id' => $this->contract->id,
                'amount' => -100,
                'description' => 'Test claim description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_validates_description_max_length()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/claims', [
                'user' => [
                    'id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                ],
                'contract_id' => $this->contract->id,
                'amount' => 1000.50,
                'description' => str_repeat('a', 2001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }
}