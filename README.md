# AlfaKit Kanban

<p><picture><source media="(prefers-color-scheme: dark)" srcset="../../../art/alfasix/alfa-six-micro-white.svg"><img src="../../../art/alfasix/alfa-six-micro-navy.svg" alt="" width="24" height="18" align="top"></picture> <sub>by <a href="https://alfasixbh.com">Alfa Six</a></sub></p>

Kanban boards for any Eloquent model in [AlfaKit](https://alfakit.dev) panels: a resource board page and a dashboard widget, with columns from an enum, a relationship or a closure, rich cards, drag and drop with mouse, touch and keyboard, WIP limits, transitions, swimlanes, search and filters. Every move is one authorised, validated and tenant-scoped request.

## Installation

```bash
composer require alfasix/alfakit-kanban
```

The board ships with `@alfakit/react`, `@alfakit/vue` and `@alfakit/svelte`; nothing to install on the frontend.

## Register

```php
use AlfaSix\AlfakitKanban\KanbanPlugin;

$panel->plugin(KanbanPlugin::make());
```

## Usage

```php
use AlfaSix\AlfakitKanban\Pages\KanbanPage;

class TaskBoard extends KanbanPage
{
    protected static string $resource = TaskResource::class;

    protected static ?string $statusEnum = TaskStatus::class;

    protected static ?string $orderAttribute = 'sort';

    public function getFormSchema(): array
    {
        return [TextInput::make('title')->required()];
    }
}

// TaskResource::getPages()
'board' => TaskBoard::route('/board'),
```

Cards move by dragging or with the keyboard (Space to pick up, arrows to move, Space to drop, Escape to cancel). The server checks the `update` policy, the target column, `getTransitions()`, strict WIP limits and the tenant on every move, and dispatches `CardMoved` afterwards.

See the [documentation](https://alfakit.dev/docs/plugins/kanban) for every option.
