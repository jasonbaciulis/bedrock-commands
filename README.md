# Bedrock Commands

A Statamic addon with CLI commands that create, rename and delete the page builder blocks and article sets of the [Bedrock](https://github.com/jasonbaciulis/bedrock) starter kit. Each command keeps the fieldset, the Antlers view, the parent YAML definition and the entry content in sync.

## Requirements

- PHP 8.4+
- Statamic 6.34+
- The Bedrock fieldsets `resources/fieldsets/blocks.yaml` and `resources/fieldsets/article.yaml`

## Installation

The Bedrock starter kit installs this addon for you. To add it to an existing Bedrock project:

```bash
composer require --dev jasonbaciulis/bedrock-commands
```

## Blocks

Blocks are like LEGO bricks that give you maximum flexibility when you build pages. Blocks are sets of the Blocks field, which is based on the [Replicator Fieldtype](https://statamic.dev/fieldtypes/replicator).

### Make

```bash
php please make:bedrock-block
```

- Adds a set to the Blocks field in `resources/fieldsets/blocks.yaml`.
- Creates a fieldset for the block in `resources/fieldsets/{file_name}.yaml`.
- Creates a view with default markup in `resources/views/blocks/{file-name}.antlers.html`.

### Delete

```bash
php please delete:bedrock-block
```

Removes a set from the Blocks field, removes it from all entries and deletes its fieldset and view.

### Rename

```bash
php please rename:bedrock-block
```

Renames a block, its fieldset and its view, updates all entries that use it and optionally moves it to another group.

## Sets

Sets give you a flexible content editing experience for entries, e.g. blog posts. Sets live in the Article field, which is based on the [Bard Fieldtype](https://statamic.dev/fieldtypes/bard).

### Make

```bash
php please make:bedrock-set
```

- Adds a set to the Article field in `resources/fieldsets/article.yaml`.
- Creates a fieldset for the set in `resources/fieldsets/{file_name}.yaml`.
- Creates a view with default markup in `resources/views/sets/{file-name}.antlers.html`.

### Delete

```bash
php please delete:bedrock-set
```

Removes a set from the Article field, removes it from all entries and deletes its fieldset and view.

### Rename

```bash
php please rename:bedrock-set
```

Renames a set, its fieldset and its view, updates all entries that use it and optionally moves it to another group.

## Arguments and options

Each command asks for the values that you do not give. `<group>` must be an existing group handle from `blocks.yaml` or `article.yaml`.

| Command | Arguments | Options |
| --- | --- | --- |
| `make:bedrock-block`, `make:bedrock-set` | `<group> "<Display Name>"` | `--instructions="..."` sets the editor instructions. `--force` overwrites existing files. |
| `delete:bedrock-block`, `delete:bedrock-set` | `<group> <fieldset_handle>` | `--keep-files` removes the set from the parent fieldset but keeps its fieldset and view. `--force` skips the confirmation and ignores missing files. |
| `rename:bedrock-block`, `rename:bedrock-set` | `<group> <current_handle> "<New Display Name>"` | `--force` skips the confirmation and overwrites existing files. |

### Scripts and AI agents

A prompt hangs a non-interactive shell. Always give all arguments, and give `--force` to the delete and rename commands:

```bash
php please make:bedrock-block hero "Hero Simple" --instructions="A simple hero with a title and text."
php please delete:bedrock-block hero hero_simple --force
php please rename:bedrock-block hero hero_simple "Hero Centered" --force
```

Tell your AI agents to always use these commands for blocks and sets, and never to create or remove the files by hand. Bedrock's `.ai/guidelines/bedrock.md` already does this for Laravel Boost.

## Configuration

The commands write the views to `resources/views/blocks` and `resources/views/sets`. To change these paths, publish the config:

```bash
php artisan vendor:publish --tag=bedrock-commands-config
```

Then edit `blocks_views_path` and `sets_views_path` in `config/bedrock-commands.php`.

### Stubs

The make commands create the fieldset and the view from stubs. To change the default markup or fields, publish the stubs:

```bash
php artisan vendor:publish --tag=bedrock-commands-stubs
```

Then edit the files in `stubs/bedrock-commands`. The commands use a published stub when it exists, and the bundled stub when it does not. `{{ name }}` is replaced with the display name.

| Stub | Creates |
| --- | --- |
| `fieldset_block.yaml.stub` | The fieldset of a new block |
| `block.antlers.html.stub` | The view of a new block |
| `fieldset_set.yaml.stub` | The fieldset of a new set |
| `set.antlers.html.stub` | The view of a new set |

## Development

```bash
composer install
composer test
```

`composer test` runs Pint, Rector, type coverage, PHPStan and the Pest suite with 100% code coverage. The tests use the fixtures in `tests/__fixtures__`, not the project's fieldsets and content.

## License

Bedrock Commands is open-source software licensed under the [MIT license](LICENSE.md).
