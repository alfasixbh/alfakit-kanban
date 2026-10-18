<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Widgets;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Contracts\HasResource;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Widgets\Widget;
use AlfaSix\AlfakitKanban\Concerns\InteractsWithKanban;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A Kanban board on a dashboard or any page with widgets.
 *
 * ```php
 * class SupportBoard extends KanbanWidget
 * {
 *     protected static ?string $model = Ticket::class;
 *
 *     protected static ?string $statusEnum = TicketStatus::class;
 *
 *     protected ?string $heading = 'Support';
 * }
 * ```
 *
 * Card actions, moves and fetches run through the widget (`widget:<id>`).
 */
abstract class KanbanWidget extends Widget implements HasResource
{
    use InteractsWithKanban;

    /** The renderer component (`kanban` in every renderer's registry). */
    protected static ?string $component = 'kanban';

    /** @var class-string<Model>|null */
    protected static ?string $model = null;

    /** @var class-string<resource>|null */
    protected static ?string $resource = null;

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    public function getType(): string
    {
        return 'Kanban';
    }

    public function getResourceClass(): ?string
    {
        return static::$resource;
    }

    /**
     * @return class-string<Model>|null
     */
    public function getModel(): ?string
    {
        if (static::$model !== null) {
            return static::$model;
        }

        $resource = static::$resource;

        return $resource !== null ? $resource::getModel() : null;
    }

    public function getKanbanHeading(): ?string
    {
        return $this->heading;
    }

    public function getKanbanActionComponent(): string
    {
        return 'widget:'.$this->getId();
    }

    /**
     * The resource's (tenant-scoped) query, or the model's query scoped to the current
     * tenant.
     *
     * @return Builder<Model>|null
     */
    public function getKanbanQuery(): ?Builder
    {
        $resource = static::$resource;

        if ($resource !== null) {
            return $resource::getEloquentQuery();
        }

        $model = $this->getModel();

        if ($model === null) {
            return null;
        }

        $query = $model::query();
        static::scopeToCurrentTenant($query);

        return $query;
    }

    /**
     * Card actions, moves and fetches, for the action endpoint.
     */
    public function getAction(string $name): ?Action
    {
        return $this->getKanbanAction($name);
    }

    protected function getSchemaProps(): array
    {
        return ['kanban' => $this->serializeKanban()];
    }
}
