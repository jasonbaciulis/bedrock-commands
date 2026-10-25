<?php

declare(strict_types=1);

use Bedrock\Commands\ServiceProvider;
use Bedrock\Commands\Tests\Feature\Support\ScaffoldFixture;
use Bedrock\Commands\Tests\Feature\Support\Scratch;
use Bedrock\Commands\Tests\Feature\Support\TestEntry;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    Scratch::setUpScaffoldWorkspace();
    Scratch::isolateContentTree();
});

afterEach(function (): void {
    Scratch::delete();
});

test('make creates the files and declares the set in the parent fieldset', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    expect($scaffold->fieldsetPath($fieldset))->toBeFile()
        ->and($scaffold->viewPath($view))->toBeFile();

    $declared = $scaffold->declaredSets()[$fieldset] ?? [];

    expect($declared)->not->toBeEmpty()
        ->and($declared['display'] ?? null)->toBe($name)
        ->and($declared['instructions'] ?? null)->toBe('Test instructions')
        ->and(data_get($declared, 'fields.0.import'))->toBe($fieldset);
})->with('scaffolds');

test('make without --force fails when the files already exist', function (ScaffoldFixture $scaffold): void {
    [$name] = $scaffold->create();

    $this->artisan($scaffold->command('make'), [
        'group' => $scaffold->group(),
        'name' => $name,
        '--instructions' => 'Test instructions',
    ])->assertFailed();
})->with('scaffolds');

test('make fails when the parent fieldset declares no groups', function (ScaffoldFixture $scaffold): void {
    $scaffold->writeParentFieldsetWithoutGroups();

    [$name] = ScaffoldFixture::plan();

    $this->artisan($scaffold->command('make'), [
        'group' => $scaffold->group(),
        'name' => $name,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])
        ->expectsOutputToContain('No groups found')
        ->assertFailed();
})->with('scaffolds');

test('make with an unknown group fails before it creates files', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = ScaffoldFixture::plan();

    $this->artisan($scaffold->command('make'), [
        'group' => 'does_not_exist',
        'name' => $name,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])
        ->expectsOutputToContain("Group 'does_not_exist' not found")
        ->assertFailed();

    expect($scaffold->fieldsetPath($fieldset))->not->toBeFile()
        ->and($scaffold->viewPath($view))->not->toBeFile();
})->with('scaffolds');

test('make uses a published stub instead of the bundled one', function (ScaffoldFixture $scaffold): void {
    Scratch::publishStub("{$scaffold->noun()}.antlers.html.stub", '<p>{{ name }}</p>');

    [$name, , $view] = $scaffold->create();

    expect(File::get($scaffold->viewPath($view)))->toBe("<p>{$name}</p>");
})->with('scaffolds');

test('the stubs tag publishes the bundled stubs to the path make reads', function (): void {
    $paths = collect(ServiceProvider::pathsToPublish(ServiceProvider::class, 'bedrock-commands-stubs'))
        ->mapWithKeys(fn (string $destination, string $source): array => [(string) realpath($source) => $destination])
        ->all();

    expect($paths)->toBe([(string) realpath(dirname(__DIR__, 2).'/stubs') => base_path('stubs/bedrock-commands')]);
});

test('delete removes the declaration, the files and the entry usages', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    $entry = $scaffold->createEntryUsing($fieldset);

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
    ])
        ->expectsConfirmation($scaffold->deleteConfirmation($name), 'yes')
        ->assertSuccessful();

    expect($scaffold->usedHandles(TestEntry::fresh($entry)))->not->toContain($fieldset)
        ->and($scaffold->declaredSets())->not->toHaveKey($fieldset)
        ->and($scaffold->fieldsetPath($fieldset))->not->toBeFile()
        ->and($scaffold->viewPath($view))->not->toBeFile();
})->with('scaffolds');

test('delete with --keep-files removes the declaration but keeps the files', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
        '--keep-files' => true,
    ])
        ->expectsConfirmation($scaffold->deleteConfirmation($name), 'yes')
        ->assertSuccessful();

    expect($scaffold->declaredSets())->not->toHaveKey($fieldset)
        ->and($scaffold->fieldsetPath($fieldset))->toBeFile()
        ->and($scaffold->viewPath($view))->toBeFile();
})->with('scaffolds');

test('delete with --force skips the confirmation prompt', function (ScaffoldFixture $scaffold): void {
    [, $fieldset] = $scaffold->create();

    // A shown prompt would hit the output mock without an expectation and fail the test.
    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
        '--force' => true,
    ])->assertSuccessful();

    expect($scaffold->declaredSets())->not->toHaveKey($fieldset);
})->with('scaffolds');

test('delete with an unknown group fails with an error', function (ScaffoldFixture $scaffold): void {
    $this->artisan($scaffold->command('delete'), [
        'group' => 'does_not_exist',
        $scaffold->noun() => 'whatever',
        '--force' => true,
    ])
        ->expectsOutputToContain("Group 'does_not_exist' not found")
        ->assertFailed();
})->with('scaffolds');

test('delete fails when the parent fieldset declares no groups', function (ScaffoldFixture $scaffold): void {
    $scaffold->writeParentFieldsetWithoutGroups();

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => 'whatever',
        '--force' => true,
    ])
        ->expectsOutputToContain('No groups found')
        ->assertFailed();
})->with('scaffolds');

test('delete reports an empty group instead of prompting', function (ScaffoldFixture $scaffold): void {
    $scaffold->writeParentFieldsetWithEmptyGroup();

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        '--force' => true,
    ])
        ->expectsOutputToContain($scaffold->emptyGroupNotice())
        ->assertSuccessful();
})->with('scaffolds');

test('delete fails when the files are already gone and --force is not passed', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    $scaffold->deleteFiles($fieldset, $view);

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
    ])
        ->expectsConfirmation($scaffold->deleteConfirmation($name), 'yes')
        ->expectsOutputToContain('Some files were not found to delete')
        ->assertFailed();
})->with('scaffolds');
