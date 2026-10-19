<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    public function test_unauthenticated_request_cannot_access_material_endpoints(): void
    {
        $response = $this->getJson('/api/materials');
        $response->assertStatus(401);

        $createResponse = $this->postJson('/api/materials', [
            'name' => 'Semen Gresik 50kg',
            'category' => 'Semen & Mortar',
            'unit' => 'Sak',
            'price' => 65000,
            'stock' => 50,
        ]);
        $createResponse->assertStatus(401);
    }

    public function test_can_list_all_materials(): void
    {
        Material::factory()->count(3)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/materials');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'name', 'category', 'unit', 'price', 'stock', 'description', 'created_at', 'updated_at']
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_material_successfully_returns_201(): void
    {
        $payload = [
            'name' => 'Besi Hollow Galvanis 4x4',
            'category' => 'Besi & Baja',
            'unit' => 'Batang',
            'price' => 95000,
            'stock' => 80,
            'description' => 'Besi hollow galvanis tahan karat tebal 1.2mm panjang 6 meter.',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/materials', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Besi Hollow Galvanis 4x4')
            ->assertJsonPath('data.unit', 'Batang')
            ->assertJsonPath('data.stock', 80);

        $this->assertDatabaseHas('materials', [
            'name' => 'Besi Hollow Galvanis 4x4',
            'unit' => 'Batang',
        ]);
    }

    public function test_create_material_validation_fails_with_422(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/materials', [
                // missing name, category
                'price' => -500, // invalid price
                'stock' => -1,   // invalid stock
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category', 'price', 'stock']);
    }

    public function test_can_show_single_material(): void
    {
        $material = Material::factory()->create([
            'name' => 'Cat Genteng Nippon Paint 4kg',
            'category' => 'Cat & Pelapis',
            'unit' => 'Kaleng',
            'price' => 175000,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/materials/' . $material->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Cat Genteng Nippon Paint 4kg');
    }

    public function test_show_non_existent_material_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/materials/99999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Material not found',
            ]);
    }

    public function test_can_update_material(): void
    {
        $material = Material::factory()->create([
            'name' => 'Pasir Pasang 1 Pick-up',
            'price' => 300000,
            'stock' => 10,
        ]);

        $updatePayload = [
            'name' => 'Pasir Pasang Super 1 Pick-up',
            'price' => 320000,
            'stock' => 15,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/materials/' . $material->id, $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Pasir Pasang Super 1 Pick-up')
            ->assertJsonPath('data.stock', 15);

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Pasir Pasang Super 1 Pick-up',
        ]);
    }

    public function test_can_delete_material(): void
    {
        $material = Material::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/materials/' . $material->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Material deleted successfully',
            ]);

        $this->assertDatabaseMissing('materials', [
            'id' => $material->id,
        ]);
    }
}

