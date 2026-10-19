<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Pages;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Actions\Exceptions\ActionNotResolvableException;
use AlfaSix\Alfakit\Resources\Pages\Page;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\AlfakitKanban\Concerns\InteractsWithKanban;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A resource page that shows the resource's records as a Kanban board.
 *
 * ```php
 * class TaskBoard extends KanbanPage
 * {
 *     protected static string $resource = TaskResource::class;
 *
 *     protected static ?string $statusEnum = TaskStatus::class;
 *
 *     protected static ?string $orderAttribute = 'sort';
 * }
 *
 * // TaskResource::getPages()
 * 'board' => TaskBoard::route('/board'),
 * ```
 *
 * The cards come from the resource's (tenant-scoped) query. Card actions and moves run as
 * the `kanban` component of the page.
 */
abstract class KanbanPage extends Page
{
    use InteractsWithKanban;

    /** The action context of card actions, moves and fetches. */
    public const KANBAN_COMPONENT = 'kanban';

    /** The schema the filters' searchable selects load their options from. */
    public const FILTERS_SCHEMA = 'kanbanFilters';

    public static function canAccess(): bool
    {
        return static::getResource()::canViewAny();
    }

    /**
     * The board's own name ("Tasks board"), for its sub-navigation item.
     */
    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::getBoardTitle();
    }

    public function getTitle(): string
    {
        return static::getBoardTitle();
    }

    /**
     * `$title`, else ":label board" with the resource's plural model label ("Tasks board").
     */
    public static function getBoardTitle(): string
    {
        return static::$title ?? (string) __('alfakit-kanban::kanban.page.title', ['label' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();

        return [...$this->getParentBreadcrumbs(), $resource::getUrl() => $resource::getTitleCasePluralModelLabel(), $this->getBreadcrumb()];
    }

    public function getKanbanActionComponent(): string
    {
        return self::KANBAN_COMPONENT;
    }

    public function getResourceClass(): ?string
    {
        return static::getResource();
    }

    /**
     * @return Builder<Model>|null
     */
    public function getKanbanQuery(): ?Builder
    {
        return static::getResource()::getEloquentQuery();
    }

    /**
     * A create action: a modal with `getFormSchema()` (cards land in the first column
     * unless the form picks one), or a link to the resource's create page.
     */
    protected function getHeaderActions(): array
    {
        $create = $this->makeKanbanCreateAction();

        if ($create !== null) {
            return [$create];
        }

        return static::getResource()::hasPage('create') ? [\AlfaSix\Alfakit\Actions\CreateAction::make()] : [];
    }

    /**
     * The board, followed by any `content()` components.
     */
    protected function getContentBlocks(): array
    {
        $board = [...$this->serializeKanban(), 'columnSpan' => ['default' => 'full']];
        $blocks = [['type' => 'Schema', 'key' => 'kanban', 'components' => [$board]]];

        foreach (parent::getContentBlocks() as $block) {
            $blocks[] = $block;
        }

        return $blocks;
    }

    protected function getKanbanFiltersSchemaComponent(): ?string
    {
        return self::FILTERS_SCHEMA;
    }

    /**
     * The filters form, for the options of searchable select filters.
     */
    public function getSchema(string $name): ?Schema
    {
        if ($name === self::FILTERS_SCHEMA) {
            return $this->getKanbanFiltersSchema();
        }

        return parent::getSchema($name);
    }

    /**
     * Card actions, moves and fetches resolve from the board; everything else as usual.
     */
    public function resolveAction(?string $component, string $name, bool $isBulk = false): Action
    {
        if ($component !== self::KANBAN_COMPONENT) {
            return parent::resolveAction($component, $name, $isBulk);
        }

        $path = explode(Action::PATH_SEPARATOR, $name);
        $action = $isBulk ? null : $this->getKanbanAction((string) array_shift($path));

        if ($action !== null && $path !== []) {
            $action = $action->getNestedModalAction($path);
        }

        if ($action === null) {
            throw new ActionNotResolvableException("Action [{$name}] could not be resolved on [".static::class.'] for the board.');
        }

        return $this->applyPageDatabaseTransactions($action);
    }

    protected function flushCaches(): void
    {
        parent::flushCaches();

        $this->cachedKanbanActions = null;
        $this->cachedKanbanColumns = null;
    }
}
