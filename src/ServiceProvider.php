<?php

declare(strict_types=1);

namespace Bedrock\Commands;

use Statamic\Providers\AddonServiceProvider;

/**
 * Statamic autoloads the commands in Console/Commands and merges
 * config/bedrock-commands.php, so this provider only publishes the stubs.
 */
final class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {
        $this->publishes([
            __DIR__.'/../stubs' => base_path('stubs/bedrock-commands'),
        ], 'bedrock-commands-stubs');
    }
}
