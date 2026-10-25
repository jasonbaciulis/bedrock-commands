<?php

declare(strict_types=1);

use Bedrock\Commands\ServiceProvider;
use Statamic\Addons\AddonRepository;
use Statamic\Addons\Manifest;
use Statamic\Facades\Addon;
use Statamic\Statamic;

// Larastan reads command signatures from the Testbench app it boots, but Statamic
// only boots addons from vendor/composer/installed.json, which never lists the
// root package. This registers the addon the way AddonTestCase does.
resolve(Manifest::class)->manifest = [
    'bedrock/commands' => [
        'id' => 'bedrock/commands',
        'slug' => 'bedrock-commands',
        'version' => 'dev-main',
        'namespace' => 'Bedrock\Commands',
        'autoload' => 'src',
        'provider' => ServiceProvider::class,
    ],
];

// The facade cached the empty addon list while the app booted.
Addon::clearResolvedInstance(AddonRepository::class);

app()->register(ServiceProvider::class);

Statamic::runBootedCallbacks();
