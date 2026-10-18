<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests;

use AlfaSix\Alfakit\Tests\TestCase as AlfakitTestCase;
use AlfaSix\AlfakitKanban\AlfakitKanbanServiceProvider;
use Illuminate\Testing\TestResponse;
use RuntimeException;

abstract class TestCase extends AlfakitTestCase
{
    /**
     * The running test (Pest binds test closures to it).
     */
    public static function current(): static
    {
        $test = \Pest\TestSuite::getInstance()->test;

        if (! $test instanceof static) {
            throw new RuntimeException('No '.static::class.' is running.');
        }

        return $test;
    }

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), AlfakitKanbanServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }

    /**
     * The Inertia props of a panel page.
     *
     * @return array<string, mixed>
     */
    public function inertiaProps(string $url): array
    {
        $response = $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);
        $response->assertOk();

        return $response->json('props');
    }

    /**
     * Run an action as the renderer's `executeJson()` does.
     *
     * @param  array<string, mixed>  $payload
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    public function kanbanAction(string $panel, string $token, string $component, string $action, array $payload = []): TestResponse
    {
        return $this->postJson("/{$panel}/_alfakit/actions", [
            'token' => $token,
            'component' => $component,
            'action' => $action,
            ...$payload,
        ]);
    }
}
