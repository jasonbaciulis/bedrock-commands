<?php

declare(strict_types=1);

use Bedrock\Commands\Tests\Feature\Support\ScaffoldFixture;

dataset('scaffolds', [
    'block' => [ScaffoldFixture::Block],
    'set' => [ScaffoldFixture::ArticleSet],
]);
