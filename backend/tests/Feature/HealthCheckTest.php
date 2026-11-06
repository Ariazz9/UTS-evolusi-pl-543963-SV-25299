<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_200_when_services_are_healthy(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => [
                        'status' => 'up',
                    ],
                    'cache' => [
                        'status' => 'up',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'database' => ['status', 'message'],
                    'cache'    => ['status', 'message'],
                ],
            ]);
    }

    public function test_api_health_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ]);
    }

    public function test_health_endpoint_returns_503_when_database_fails(): void
    {
        // Simulasikan kegagalan database
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \PDOException('Simulated database connection refused'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'checks' => [
                    'database' => [
                        'status' => 'down',
                    ],
                ],
            ]);
    }
}

