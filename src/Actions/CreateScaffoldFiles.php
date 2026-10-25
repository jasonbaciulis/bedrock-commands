<?php

declare(strict_types=1);

namespace Bedrock\Commands\Actions;

use Bedrock\Commands\Enums\ScaffoldType;
use Bedrock\Commands\Support\ScaffoldName;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Statamic\Facades\Fieldset;
use Statamic\Facades\YAML;

/**
 * Create the fieldset and view files for a new scaffolded set from stubs.
 */
final readonly class CreateScaffoldFiles
{
    public function __construct(private Filesystem $files) {}

    /**
     * @throws RuntimeException When a target file exists and $force is false
     */
    public function handle(ScaffoldType $type, ScaffoldName $name, bool $force): void
    {
        $this->assertWritable($type, $name, $force);

        Fieldset::make($name->fieldset)
            ->setContents(YAML::parse($this->stub("fieldset_{$type->noun()}.yaml.stub", $name->display)))
            ->saveQuietly();

        $this->files->put(
            $type->viewPathFor($name->view),
            $this->stub("{$type->noun()}.antlers.html.stub", $name->display)
        );
    }

    private function stub(string $stub, string $name): string
    {
        $contents = $this->files->get($this->stubPath($stub));

        return Str::replace('{{ name }}', $name, $contents);
    }

    private function stubPath(string $stub): string
    {
        $publishedPath = base_path("stubs/bedrock-commands/{$stub}");

        return $this->files->exists($publishedPath) ? $publishedPath : dirname(__DIR__, 2)."/stubs/{$stub}";
    }

    private function assertWritable(ScaffoldType $type, ScaffoldName $name, bool $force): void
    {
        if ($force) {
            return;
        }

        $paths = [
            Fieldset::directory()."/{$name->fieldset}.yaml",
            $type->viewPathFor($name->view),
        ];

        foreach ($paths as $path) {
            throw_if($this->files->exists($path), RuntimeException::class, "File exists: {$path} (use --force to overwrite)");
        }
    }
}
