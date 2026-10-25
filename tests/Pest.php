<?php

declare(strict_types=1);

use Bedrock\Commands\Tests\TestCase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Process::preventStrayProcesses();
    })
    ->in('Feature', 'Unit');
