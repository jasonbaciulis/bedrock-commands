<?php

declare(strict_types=1);

namespace Bedrock\Commands\Tests;

use Illuminate\Testing\PendingCommand;
use Bedrock\Commands\ServiceProvider;
use RuntimeException;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Freezing here rather than in a Pest beforeEach keeps $this resolvable
        // for static analysis.
        $this->freezeTime();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function artisan($command, $parameters = []): PendingCommand
    {
        $pendingCommand = parent::artisan($command, $parameters);

        // The base method returns the exit code instead of a pending command
        // once a test calls withoutMockingConsoleOutput().
        throw_unless($pendingCommand instanceof PendingCommand, RuntimeException::class, 'artisan() needs mocked console output to return a pending command.');

        return $pendingCommand;
    }
}
