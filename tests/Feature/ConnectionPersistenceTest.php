<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Connection;
use Agentic\Models\Tool;
use Agentic\Persistence\Eloquent\EloquentToolRepository;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ConnectionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_and_not_stored_as_plaintext(): void
    {
        $connection = Connection::create([
            'name' => 'Orders API',
            'slug' => 'orders-api',
            'type' => 'bearer',
            'config' => ['type' => 'bearer'],
            'credentials' => ['token' => 'super-secret-token'],
        ]);

        $connection->refresh();

        $this->assertSame(
            ['token' => 'super-secret-token'],
            $connection->credentials,
        );

        $this->assertStringNotContainsString(
            'super-secret-token',
            (string) $connection->getRawOriginal('credentials'),
        );
    }

    public function test_published_tool_resolves_its_connection_reference(): void
    {
        Connection::create([
            'name' => 'Orders API',
            'slug' => 'orders-api',
            'type' => 'bearer',
            'config' => ['type' => 'bearer'],
            'credentials' => ['token' => 'super-secret-token'],
        ]);

        $tool = Tool::create([
            'name' => 'Get Order',
            'slug' => 'get-order',
            'type' => 'http',
            'driver' => 'http',
            'status' => 'published',
        ]);

        app(ToolVersionPublisher::class)->publish($tool, [
            'connection' => 'orders-api',
            'method' => 'GET',
            'url' => 'https://api.example.test/orders/{id}',
        ]);

        $definition = app(EloquentToolRepository::class)->findBySlug('get-order');

        $this->assertNotNull($definition);
        $this->assertSame('orders-api', $definition->connection);
        $this->assertSame(1, $definition->version);
    }
}
