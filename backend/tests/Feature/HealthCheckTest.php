<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test that the health check endpoint returns 200 with standard response structure.
     */
    public function test_health_check_returns_successful_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'environment',
                    'timestamp',
                    'database',
                    'version',
                ],
            ]);

        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'healthy',
                'database' => 'connected',
                'version' => config('app.version'),
            ],
        ]);
    }

    /**
     * Test that health endpoint does not expose sensitive database or stack trace details.
     */
    public function test_health_check_does_not_leak_sensitive_details(): void
    {
        $response = $this->getJson('/api/v1/health');

        $content = $response->getContent();

        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('root', strtolower($content));
        $this->assertStringNotContainsString('sqlstate', strtolower($content));
        $this->assertStringNotContainsString('trace', strtolower($content));
    }
}
