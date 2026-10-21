<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Concerns;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Actions\ActionGroup;
use AlfaSix\Alfakit\Actions\CreateAction;
use AlfaSix\Alfakit\Actions\DeleteAction;
use AlfaSix\Alfakit\Actions\EditAction;
use AlfaSix\Alfakit\Actions\ViewAction;
use AlfaSix\Alfakit\Http\Controllers\Concerns\PullsFlashedNotifications;
use AlfaSix\Alfakit\Notifications\Notification;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Schemas\Components\Group;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\Alfakit\Support\Authorization;
use AlfaSix\Alfakit\Support\EnumState;
use AlfaSix\Alfakit\Support\Html;
use AlfaSix\Alfakit\Tables\Filters\BaseFilter;
use AlfaSix\AlfakitKanban\Card;
use AlfaSix\AlfakitKanban\Column;
use AlfaSix\AlfakitKanban\Events\CardMoved;
use AlfaSix\AlfakitKanban\Support\KanbanMove;
use AlfaSix\AlfakitKanban\Swimlane;
use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * The board shared by `KanbanPage` and `KanbanWidget`: columns, cards, card actions, the
 * move and fetch JSON actions and the serialised board the renderers' `kanban` component
 * draws.
 *
 * Every request is authorised on the server: the records come from the (tenant-scoped)
 * board query, a move needs the record's `update` ability, a column the transition map
 * allows and room under a strict WIP limit.
 */
trait InteractsWithKanban
{
    use PullsFlashedNotifications;

    /**
     * A backed enum whose cases are the columns (labels, colours, icons and descriptions
     * from `HasLabel`, `HasColor`, `HasIcon`, `HasDescription`).
     *
     * @var class-string<UnitEnum>|null
     */
    protected static ?string $statusEnum = null;

    /** The attribute holding a card's column. */
    protected static string $statusAttribute = 'status';

    /**
     * A `BelongsTo` relationship whose related records are the columns (a `stages`
     * table); the status attribute is then its foreign key.
     */
    protected static ?string $columnRelationship = null;

    protected static string $columnRelationshipTitleAttribute = 'name';

    /** The related records' sort column; null sorts columns by key. */
    protected static ?string $columnRelationshipOrderAttribute = null;

    /** An integer column for the order of cards within a column; null to keep cards in key order. */
    protected static ?string $orderAttribute = 'sort';

    /** Card text attributes; the title defaults to the resource's record title, then `title`. */
    protected static ?string $titleAttribute = null;

    protected static ?string $descriptionAttribute = null;

    protected static ?string $dueDateAttribute = null;

    protected static ?string $progressAttribute = null;

    protected static ?string $coverImageAttribute = null;

    /** Group cards into rows by this attribute (`assignee_id`)… */
    protected static ?string $swimlaneAttribute = null;

    /** …or by this `BelongsTo` relationship (`assignee`), labelled with its title attribute. */
    protected static ?string $swimlaneRelationship = null;

    protected static string $swimlaneRelationshipTitleAttribute = 'name';

    /** @var class-string<UnitEnum>|null An enum labelling the swimlane attribute's values. */
    protected static ?string $swimlaneEnum = null;

    /** Whether dragging a card to another row changes its swimlane attribute. */
    protected static bool $canMoveBetweenSwimlanes = false;

    /** @var array<int, string> Attributes the search box looks in; empty for no search box. */
    protected static array $searchableAttributes = [];

    /** The most cards loaded per column (the header still counts them all); null for all. */
    protected static ?int $cardsPerColumn = null;

    /** Refresh the board every so often (`'10s'`); the request pauses while the tab is hidden. */
    protected static ?string $pollingInterval = null;

    /** @var array<int, string> Relationships to eager-load for the cards. */
    protected static array $with = [];

    /** The JSON actions the renderer calls. */
    public static string $moveActionName = 'kanbanMove';

    public static string $fetchActionName = 'kanbanFetch';

    /** The renderer sends its move and fetch data under this argument. */
    public static string $argumentsKey = 'kanban';

    protected ?Closure $columnsUsing = null;

    /** @var array<int, Column>|null */
    protected ?array $cachedKanbanColumns = null;

    /** @var array<string, Action>|null */
    protected ?array $cachedKanbanActions = null;

    /**
     * The action context the renderer posts card actions to (`kanban` or `widget:<id>`).
     */
    abstract public function getKanbanActionComponent(): string;

    /**
     * The records the user may see and move (tenant-scoped).
     *
     * @return Builder<Model>|null
     */
    abstract public function getKanbanQuery(): ?Builder;

    /**
     * @return class-string<resource>|null
     */
    abstract public function getResourceClass(): ?string;

    public function getKanbanHeading(): ?string
    {
        return null;
    }

    // ---------------------------------------------------------------- Columns

    /**
     * Columns from a closure: it returns `Column`s, enum cases or values.
     * `->columnsUsing(fn () => [Column::make('todo'), Column::make('done')])`.
     */
    public function columnsUsing(?Closure $callback): static
    {
        $this->columnsUsing = $callback;
        $this->cachedKanbanColumns = null;

        return $this;
    }

    /**
     * The board's columns, in order. By default the `columnsUsing()` closure, the cases of
     * `$statusEnum`, or the related records of `$columnRelationship`.
     *
     * @return array<int, Column|UnitEnum|string|int>
     */
    protected function getColumns(): array
    {
        if ($this->columnsUsing !== null) {
            $columns = ($this->columnsUsing)();

            return is_array($columns) ? array_values($columns) : [];
        }

        if (static::$statusEnum !== null && enum_exists(static::$statusEnum)) {
            return array_map(static fn (UnitEnum $case): Column => Column::fromEnum($case), static::$statusEnum::cases());
        }

        $query = $this->getColumnRelationshipQuery();

        if ($query !== null) {
            return array_values(array_map(fn (Model $related): Column => $this->makeColumnFromRelatedRecord($related), $query->get()->all()));
        }

        return [];
    }

    /**
     * The columns as `Column`s, in order, without duplicates.
     *
     * @return array<int, Column>
     */
    public function getKanbanColumns(): array
    {
        if ($this->cachedKanbanColumns !== null) {
            return $this->cachedKanbanColumns;
        }

        $columns = [];

        foreach ($this->getColumns() as $column) {
            $column = match (true) {
                $column instanceof Column => $column,
                $column instanceof UnitEnum => Column::fromEnum($column),
                default => Column::make($column),
            };

            $columns[$column->getKey()] ??= $column;
        }

        return $this->cachedKanbanColumns = array_values($columns);
    }

    public function findKanbanColumn(string $key): ?Column
    {
        foreach ($this->getKanbanColumns() as $column) {
            if ($column->getKey() === $key) {
                return $column;
            }
        }

        return null;
    }

    /**
     * A column for a related record of `$columnRelationship`. Override for colours, icons
     * or WIP limits stored on the related table.
     */
    protected function makeColumnFromRelatedRecord(Model $related): Column
    {
        $key = $related->getKey();
        $title = $related->getAttribute(static::$columnRelationshipTitleAttribute);

        return Column::make(is_int($key) ? $key : (string) $key)->label(is_scalar($title) ? (string) $title : null);
    }

    /**
     * The related records of `$columnRelationship`, scoped to the current tenant when they
     * belong to one.
     *
     * @return Builder<Model>|null
     */
    protected function getColumnRelationshipQuery(): ?Builder
    {
        $relationship = $this->getColumnRelationship();

        if ($relationship === null) {
            return null;
        }

        $query = $relationship->getRelated()->newQuery();
        static::scopeToCurrentTenant($query);

        $order = static::$columnRelationshipOrderAttribute;

        return $query->orderBy($query->getModel()->qualifyColumn($order ?? $query->getModel()->getKeyName()));
    }

    /**
     * @return BelongsTo<Model, Model>|null
     */
    protected function getColumnRelationship(): ?BelongsTo
    {
        return $this->getBelongsTo(static::$columnRelationship);
    }

    /**
     * @return BelongsTo<Model, Model>|null
     */
    protected function getBelongsTo(?string $name): ?BelongsTo
    {
        $model = $this->getKanbanModelInstance();

        if ($name === null || $model === null || ! method_exists($model, $name)) {
            return null;
        }

        $relationship = $model->{$name}();

        return $relationship instanceof BelongsTo ? $relationship : null;
    }

    /**
     * The attribute holding a card's column (the foreign key with `$columnRelationship`).
     */
    public function getStatusAttribute(): string
    {
        return $this->getColumnRelationship()?->getForeignKeyName() ?? static::$statusAttribute;
    }

    public function getOrderAttribute(): ?string
    {
        return static::$orderAttribute;
    }

    /**
     * The key of the column a record is in (it may match no column).
     */
    public function getRecordColumnKey(Model $record): ?string
    {
        $value = $record->getAttribute($this->getStatusAttribute());

        return match (true) {
            $value instanceof UnitEnum => (string) EnumState::key($value),
            is_int($value), is_string($value) => (string) $value,
            default => null,
        };
    }

    // ---------------------------------------------------------------- Transitions

    /**
     * The columns each column's cards may move to, by column value; columns missing from
     * the map allow every move. Null (the default) allows every move.
     *
     * ```php
     * return [
     *     'todo' => ['doing'],
     *     'doing' => ['todo', 'review'],
     *     'review' => ['doing', 'done'],
     * ];
     * ```
     *
     * @return array<string|int, array<int, string|int|BackedEnum>>|null
     */
    protected function getTransitions(): ?array
    {
        return null;
    }

    /**
     * Whether cards may move from one column to another (`$record` is null while the board
     * is drawn and the moved record when a move is saved). Override for rules that depend
     * on the record.
     */
    public function canMoveTo(Column $from, Column $to, ?Model $record = null): bool
    {
        if ($from->getKey() === $to->getKey()) {
            return true;
        }

        $transitions = $this->getTransitions();

        if ($transitions === null || ! array_key_exists($from->getKey(), $transitions)) {
            return true;
        }

        $allowed = array_map(static fn (mixed $value): string => $value instanceof BackedEnum ? (string) $value->value : (is_scalar($value) ? (string) $value : ''), $transitions[$from->getKey()]);

        return in_array($to->getKey(), $allowed, true);
    }

    /**
     * The allowed moves for the renderer, `{from: [to…]}`, or null when every move is.
     *
     * @return array<string, array<int, string>>|null
     */
    protected function serializeTransitions(): ?array
    {
        $columns = $this->getKanbanColumns();
        $map = [];
        $restricted = false;

        foreach ($columns as $from) {
            $map[$from->getKey()] = [];

            foreach ($columns as $to) {
                if ($this->canMoveTo($from, $to)) {
                    $map[$from->getKey()][] = $to->getKey();
                } else {
                    $restricted = true;
                }
            }
        }

        return $restricted ? $map : null;
    }

    // ---------------------------------------------------------------- Authorisation

    /**
     * Whether the user may move cards on this board at all.
     */
    public function canMoveCards(): bool
    {
        return true;
    }

    /**
     * Whether the user may move this card: the resource's (or the model policy's) `update`.
     */
    public function canMoveCard(Model $record): bool
    {
        $resource = $this->getResourceClass();

        return $resource !== null ? $resource::can('update', $record) : Authorization::allows('update', $record);
    }

    // ---------------------------------------------------------------- Cards

    protected function getKanbanModelInstance(): ?Model
    {
        return $this->getKanbanQuery()?->getModel();
    }

    /**
     * What a card shows. By default the title, description, due date, progress and cover
     * image attributes.
     */
    public function getCard(Model $record): Card
    {
        $card = Card::make($record)->title($this->getCardTitle($record));

        foreach ([
            'description' => static::$descriptionAttribute,
            'dueDate' => static::$dueDateAttribute,
            'progress' => static::$progressAttribute,
            'coverImage' => static::$coverImageAttribute,
        ] as $method => $attribute) {
            if ($attribute === null) {
                continue;
            }

            $value = $record->getAttribute($attribute);

            match ($method) {
                'description' => $card->description(is_scalar($value) ? static::plainCardText((string) $value) : null),
                'dueDate' => $card->dueDate($value instanceof DateTimeInterface || is_string($value) ? $value : null),
                'progress' => $card->progress(is_numeric($value) ? (float) $value : null),
                'coverImage' => $card->coverImage(is_string($value) ? $value : null),
            };
        }

        return $card;
    }

    /**
     * A description from a rich editor (`<p>Hello</p>`) shows as its text, never as markup.
     */
    protected static function plainCardText(string $value): string
    {
        if (! str_contains($value, '<')) {
            return $value;
        }

        // Keep words of separate blocks apart once the tags are gone.
        $spaced = (string) preg_replace('/<\/(p|div|li|h[1-6]|blockquote|pre)>|<br\s*\/?>/i', '$0 ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', Html::toText(new HtmlString($spaced))));
    }

    /**
     * The card's title: `$titleAttribute`, else the resource's record title, else `title`.
     */
    public function getCardTitle(Model $record): string
    {
        $title = static::$titleAttribute !== null ? $record->getAttribute(static::$titleAttribute) : null;
        $resource = $this->getResourceClass();

        if ($title === null && $resource !== null && $resource::hasRecordTitle()) {
            $title = $resource::getRecordTitle($record);
        }

        $title ??= $record->getAttribute('title');

        return is_scalar($title) && (string) $title !== '' ? (string) $title : (string) $record->getKey();
    }

    /**
     * Infolist entries shown on every card, filled from its record
     * (`[TextEntry::make('customer.name')->icon('user')]`).
     *
     * @return array<\AlfaSix\Alfakit\Schemas\Components\Component>
     */
    public function getCardSchema(): array
    {
        return [];
    }

    /**
     * Fields of the default create, edit and view modals. Without fields, view and edit
     * open the resource's pages when it has them, and there is no quick create.
     *
     * @return array<\AlfaSix\Alfakit\Schemas\Components\Component>
     */
    public function getFormSchema(): array
    {
        return [];
    }

    protected function hasViewAction(): bool
    {
        return true;
    }

    protected function hasEditAction(): bool
    {
        return true;
    }

    protected function hasDeleteAction(): bool
    {
        return true;
    }

    /**
     * The "+" button of every column, opening the create modal with the column filled in.
     */
    protected function hasQuickCreate(): bool
    {
        return true;
    }

    /**
     * The card's menu. It receives the card's record (`$record`). By default view, edit and
     * delete in a dropdown.
     *
     * @return array<Action|ActionGroup>
     */
    protected function getCardActions(): array
    {
        $resource = $this->getResourceClass();
        $hasForm = $this->getFormSchema() !== [];
        $actions = [];

        if ($this->hasViewAction() && ($hasForm || ($resource !== null && $resource::hasPage('view')))) {
            $actions[] = ViewAction::make()->schema($hasForm ? fn (): array => $this->getFormSchema() : null);
        }

        if ($this->hasEditAction() && ($hasForm || ($resource !== null && $resource::hasPage('edit')))) {
            $actions[] = EditAction::make()->schema($hasForm ? fn (): array => $this->getFormSchema() : null);
        }

        if ($this->hasDeleteAction()) {
            $actions[] = DeleteAction::make();
        }

        return $actions === [] ? [] : [ActionGroup::make($actions)];
    }

    /**
     * The name of the card action that clicking a card (or Enter) opens: by default `view`,
     * else `edit`; null for cards that open nothing.
     */
    protected function getCardClickActionName(): ?string
    {
        $map = $this->getKanbanActionMap();

        foreach (['view', 'edit'] as $name) {
            if (isset($map[$name])) {
                return $name;
            }
        }

        return null;
    }

    /**
     * The quick-create action (also the page's header create action). Its modal opens with
     * the column (and lane) the user clicked filled in; a new card goes to the end of its
     * column.
     */
    public function makeKanbanCreateAction(string $name = 'create'): ?CreateAction
    {
        $model = $this->getKanbanModelInstance();

        if ($model === null || $this->getFormSchema() === []) {
            return null;
        }

        return CreateAction::make($name)
            ->model($model::class)
            ->schema(fn (): array => $this->getFormSchema())
            ->mountUsing(function (?Schema $form, array $arguments): void {
                $form?->fill();

                if ($form === null) {
                    return;
                }

                $form->fill([...$form->getRawState(), ...$this->getQuickCreateFormData($arguments)]);
            })
            ->mutateDataUsing(fn (array $data, array $arguments, CreateAction $action): array => $this->prepareCreatedCardData($data, $arguments, $action));
    }

    /**
     * The form values a column's create modal opens with: the status attribute (and the
     * swimlane attribute) of the column and lane clicked.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function getQuickCreateFormData(array $arguments): array
    {
        $data = [];
        $column = is_scalar($arguments['column'] ?? null) ? $this->findKanbanColumn((string) $arguments['column']) : null;

        if ($column !== null) {
            $data[$this->getStatusAttribute()] = $column->getValue();
        }

        $lane = $arguments['lane'] ?? null;
        $laneAttribute = $this->getSwimlaneAttribute();

        if ($laneAttribute !== null && is_string($lane) && $lane !== '' && ($resolved = $this->resolveLaneValue($lane)) !== false) {
            $data[$laneAttribute] = $resolved;
        }

        return $data;
    }

    /**
     * The new card's data: the clicked column when the form has no status field, checked
     * against the columns and a strict WIP limit, and an order after the column's cards.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function prepareCreatedCardData(array $data, array $arguments, CreateAction $action): array
    {
        $status = $this->getStatusAttribute();

        // The clicked column and lane fill in what the form leaves empty.
        foreach ($this->getQuickCreateFormData($arguments) as $attribute => $value) {
            if (($data[$attribute] ?? null) === null) {
                $data[$attribute] = $value;
            }
        }

        $value = $data[$status] ?? null;
        $value = $value instanceof UnitEnum ? EnumState::key($value) : $value;
        $column = is_int($value) || is_string($value) ? $this->findKanbanColumn((string) $value) : null;

        if ($column === null) {
            $column = $this->getKanbanColumns()[0] ?? null;

            if ($column === null) {
                return $data;
            }

            $data[$status] = $column->getValue();
        }

        if ($column->isWipLimitStrict() && $this->countColumnCards($column) >= (int) $column->getWipLimit()) {
            Notification::make()->danger()->title($this->wipLimitMessage($column))->send();
            $action->halt();
        }

        $order = $this->getOrderAttribute();

        if ($order !== null && ! isset($data[$order])) {
            $query = $this->getKanbanQuery();
            $data[$order] = $query === null ? 1 : ((int) $query->where($query->getModel()->qualifyColumn($status), $column->getValue())->max($query->getModel()->qualifyColumn($order))) + 1;
        }

        return $data;
    }

    // ---------------------------------------------------------------- Swimlanes

    /**
     * An explicit list of rows, in order; null (the default) builds the rows from the
     * cards' swimlane values.
     *
     * @return array<int, Swimlane>|null
     */
    public function getSwimlanes(): ?array
    {
        return null;
    }

    public function hasSwimlanes(): bool
    {
        return $this->getSwimlaneAttribute() !== null;
    }

    /**
     * The attribute that groups cards into rows (the foreign key with `$swimlaneRelationship`).
     */
    public function getSwimlaneAttribute(): ?string
    {
        return $this->getBelongsTo(static::$swimlaneRelationship)?->getForeignKeyName() ?? static::$swimlaneAttribute;
    }

    public function canMoveBetweenSwimlanes(): bool
    {
        return static::$canMoveBetweenSwimlanes;
    }

    public function getRecordLaneKey(Model $record): ?string
    {
        $attribute = $this->getSwimlaneAttribute();

        return $attribute === null ? null : Swimlane::keyFor($record->getAttribute($attribute));
    }

    /**
     * The row a record's swimlane value is drawn as (for rows built from the cards).
     */
    protected function makeSwimlaneForRecord(Model $record): Swimlane
    {
        $attribute = (string) $this->getSwimlaneAttribute();
        $value = $record->getAttribute($attribute);

        if ($value instanceof UnitEnum) {
            return Swimlane::make(EnumState::key($value))->label(EnumState::label($value))->icon(EnumState::icon($value));
        }

        $lane = Swimlane::make(is_int($value) || is_string($value) ? $value : null);

        if ($value === null || $value === '') {
            return $lane;
        }

        if (static::$swimlaneRelationship !== null) {
            $related = $record->getRelationValue(static::$swimlaneRelationship);
            $title = $related instanceof Model ? $related->getAttribute(static::$swimlaneRelationshipTitleAttribute) : null;

            return $lane->label(is_scalar($title) ? (string) $title : null);
        }

        if (static::$swimlaneEnum !== null && ($case = EnumState::resolve($value, static::$swimlaneEnum)) !== null) {
            return $lane->label(EnumState::label($case))->icon(EnumState::icon($case));
        }

        return $lane;
    }

    /**
     * The swimlane attribute value of a lane key posted by the renderer, or false when the
     * lane is unknown (or belongs to another tenant).
     */
    protected function resolveLaneValue(string $key): mixed
    {
        $explicit = $this->getSwimlanes();

        if ($explicit !== null) {
            foreach ($explicit as $lane) {
                if ($lane->getKey() === $key) {
                    return $lane->getValue();
                }
            }

            return false;
        }

        if ($key === Swimlane::NONE) {
            return null;
        }

        $relationship = $this->getBelongsTo(static::$swimlaneRelationship);

        if ($relationship !== null) {
            $query = $relationship->getRelated()->newQuery();
            static::scopeToCurrentTenant($query);
            $related = $query->where($relationship->getQualifiedOwnerKeyName(), $key)->first();

            return $related instanceof Model ? $related->getAttribute($relationship->getOwnerKeyName()) : false;
        }

        if (static::$swimlaneEnum !== null) {
            $case = EnumState::resolve($key, static::$swimlaneEnum);

            return $case === null ? false : EnumState::key($case);
        }

        // A plain attribute: only values cards on the board already have.
        $query = $this->getKanbanQuery();
        $attribute = (string) $this->getSwimlaneAttribute();

        if ($query === null) {
            return false;
        }

        $record = $query->where($query->getModel()->qualifyColumn($attribute), $key)->first();

        return $record instanceof Model ? $record->getAttribute($attribute) : false;
    }

    // ---------------------------------------------------------------- Search and filters

    public function isKanbanSearchable(): bool
    {
        return static::$searchableAttributes !== [];
    }

    /**
     * Narrow the cards to a search term: by default a `LIKE` on `$searchableAttributes`.
     *
     * @param  Builder<Model>  $query
     */
    protected function applyKanbanSearch(Builder $query, string $search): void
    {
        // `!` escapes LIKE wildcards on every database AlfaKit supports; PostgreSQL
        // compares case-insensitively (and as text) like the other drivers do.
        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
        $model = $query->getModel();
        $grammar = $query->getQuery()->getGrammar();
        $pgsql = $query->getConnection()->getDriverName() === 'pgsql';

        $query->where(static function (Builder $query) use ($term, $model, $grammar, $pgsql): void {
            foreach (static::$searchableAttributes as $attribute) {
                $column = $grammar->wrap($model->qualifyColumn($attribute));
                $query->orWhereRaw(($pgsql ? "cast({$column} as text) ilike" : "{$column} like")." ? escape '!'", [$term]);
            }
        });
    }

    /**
     * Table filters shown above the board (`SelectFilter`, `TernaryFilter`, `Filter`…).
     * Searchable select filters load their options on kanban pages; on widgets give them
     * `preload()` or static options.
     *
     * @return array<BaseFilter>
     */
    protected function getKanbanFilters(): array
    {
        return [];
    }

    /**
     * @return array<string, BaseFilter>
     */
    protected function getKanbanFilterMap(): array
    {
        $filters = [];

        foreach ($this->getKanbanFilters() as $filter) {
            if (! $filter->isHidden()) {
                $filters[$filter->getName()] = $filter;
            }
        }

        return $filters;
    }

    /**
     * The filters form, each filter's fields under its name (`{status: {value: …}}`).
     *
     * @param  array<string, mixed>|null  $state
     */
    public function getKanbanFiltersSchema(?array $state = null): ?Schema
    {
        $filters = $this->getKanbanFilterMap();

        if ($filters === []) {
            return null;
        }

        $schema = Schema::make()->components(array_values(array_map(
            static fn (BaseFilter $filter): Group => Group::make()
                ->key('filter:'.$filter->getName())
                ->statePath($filter->getName())
                ->schema($filter->getForm())
                ->columnSpan($filter->getColumnSpan()),
            $filters,
        )));

        $model = $this->getKanbanModelInstance();

        if ($model !== null) {
            $schema->model($model::class);
        }

        return $schema->fill($this->normalizeFilterState($state));
    }

    /**
     * The posted filter state, reduced to each filter's scalar values (defaults for filters
     * it leaves out).
     *
     * @param  array<mixed>|null  $state
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeFilterState(?array $state): array
    {
        $normalized = [];

        foreach ($this->getKanbanFilterMap() as $name => $filter) {
            $values = $state === null ? ($filter->getDefaultState() ?? []) : ($state[$name] ?? []);
            $normalized[$name] = [];

            foreach (is_array($values) ? $values : [] as $field => $value) {
                if (! is_string($field)) {
                    continue;
                }

                if (is_array($value)) {
                    $value = array_values(array_filter($value, static fn (mixed $item): bool => is_scalar($item)));
                } elseif (! is_scalar($value) && $value !== null) {
                    continue;
                }

                $normalized[$name][$field] = $value;
            }
        }

        return $normalized;
    }

    // ---------------------------------------------------------------- Queries

    /**
     * The board query narrowed by a search term and filter state.
     *
     * @param  array<string, array<string, mixed>>  $filters  Normalised filter state.
     * @return Builder<Model>|null
     */
    protected function getFilteredKanbanQuery(string $search, array $filters): ?Builder
    {
        $query = $this->getKanbanQuery();

        if ($query === null) {
            return null;
        }

        if ($search !== '' && $this->isKanbanSearchable()) {
            $this->applyKanbanSearch($query, $search);
        }

        foreach ($this->getKanbanFilterMap() as $name => $filter) {
            $filter->state($filters[$name] ?? [])->apply($query);
        }

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function orderKanbanCards(Builder $query): Builder
    {
        $model = $query->getModel();
        $order = $this->getOrderAttribute();

        if ($order !== null) {
            $query->orderBy($model->qualifyColumn($order));
        }

        return $query->orderBy($model->getQualifiedKeyName());
    }

    /**
     * Cards per column key.
     *
     * @param  Builder<Model>|null  $query
     * @return array<string, int>
     */
    protected function countCardsByColumn(?Builder $query): array
    {
        if ($query === null) {
            return [];
        }

        $status = $query->getModel()->qualifyColumn($this->getStatusAttribute());
        $counts = [];

        foreach ($query->reorder()->toBase()->select($status.' as kanban_column')->selectRaw('count(*) as kanban_count')->groupBy($status)->get() as $row) {
            $key = $row->kanban_column ?? null;

            if (is_int($key) || is_string($key)) {
                $counts[(string) $key] = (int) ($row->kanban_count ?? 0);
            }
        }

        return $counts;
    }

    public function countColumnCards(Column $column): int
    {
        $query = $this->getKanbanQuery();

        return $query === null ? 0 : $query->where($query->getModel()->qualifyColumn($this->getStatusAttribute()), $column->getValue())->count();
    }

    protected function wipLimitMessage(Column $column): string
    {
        return __('alfakit-kanban::kanban.validation.wip_limit', ['column' => $column->getLabel(), 'limit' => $column->getWipLimit()]);
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected static function scopeToCurrentTenant(Builder $query): void
    {
        $panel = alfakit()->getCurrentPanel();
        $tenant = $panel?->getTenant();

        if ($panel !== null && $tenant !== null && method_exists($query->getModel(), $panel->getTenantOwnershipRelationshipName())) {
            Resource::scopeEloquentQueryToTenant($query, $tenant);
        }
    }

    public function getKanbanPollingInterval(): ?string
    {
        return static::$pollingInterval;
    }

    // ---------------------------------------------------------------- Actions

    /**
     * Card actions, the quick-create action and the move and fetch actions, by name.
     *
     * @return array<string, Action>
     */
    protected function getKanbanActionMap(): array
    {
        if ($this->cachedKanbanActions !== null) {
            return $this->cachedKanbanActions;
        }

        $map = [];

        foreach ($this->getPreparedCardActions() as $action) {
            foreach ($action instanceof ActionGroup ? $action->getFlatActions() : [$action] as $flat) {
                $map[$flat->getName()] = $flat;
            }
        }

        if ($this->hasQuickCreate() && ($create = $this->makeKanbanCreateAction()) !== null) {
            $map[$create->getName()] ??= $create->context($this);
        }

        $map[static::$moveActionName] = $this->makeMoveAction();
        $map[static::$fetchActionName] = $this->makeFetchAction();

        return $this->cachedKanbanActions = $map;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getPreparedCardActions(): array
    {
        $actions = $this->getCardActions();

        foreach ($actions as $action) {
            $action->context($this);

            foreach ($action instanceof ActionGroup ? $action->getFlatActions() : [$action] as $flat) {
                $flat->recordTitle(fn (?Model $record): ?string => $record === null ? null : $this->getCardTitle($record));
            }
        }

        return $actions;
    }

    protected function makeMoveAction(): Action
    {
        return Action::make(static::$moveActionName)
            ->context($this)
            ->authorize(fn (?Model $record): bool => $record !== null && $this->canMoveCards() && $this->canMoveCard($record))
            ->action(function (array $arguments, ?Model $record): array {
                abort_if($record === null, 404);

                $raw = $arguments[static::$argumentsKey] ?? null;
                $move = $this->resolveMove($record, is_array($raw) ? $raw : []);

                $this->beforeCardMoved($move);
                DB::transaction(fn () => $this->saveCardMove($move));
                $this->afterCardMoved($move);

                CardMoved::dispatch($move, static::class);

                $query = $this->getKanbanQuery();
                $fresh = $query === null ? null : $this->eagerLoadCards($query)->whereKey($record->getKey())->first();
                $counts = $this->countCardsByColumn($this->getKanbanQuery());
                $keys = array_values(array_unique(array_filter([$move->from?->getKey(), $move->to->getKey()])));

                return [
                    'ok' => true,
                    'card' => $fresh instanceof Model ? $this->serializeCards(new Collection([$fresh]))[0] ?? null : null,
                    'counts' => array_combine($keys, array_map(static fn (string $key): int => $counts[$key] ?? 0, $keys)),
                    'notifications' => $this->pullFlashedNotifications(request()),
                ];
            });
    }

    protected function makeFetchAction(): Action
    {
        return Action::make(static::$fetchActionName)
            ->context($this)
            ->action(function (array $arguments): array {
                $raw = $arguments[static::$argumentsKey] ?? null;
                $raw = is_array($raw) ? $raw : [];
                $search = is_string($raw['search'] ?? null) ? mb_substr(trim($raw['search']), 0, 200) : '';
                $filters = is_array($raw['filters'] ?? null) ? $raw['filters'] : null;

                return ['board' => $this->serializeKanban($search, $filters)];
            });
    }

    /**
     * Check a posted move (`{column, lane, after, before}`) and work out where the card
     * lands.
     *
     * @param  array<mixed>  $raw
     *
     * @throws ValidationException
     */
    protected function resolveMove(Model $record, array $raw): KanbanMove
    {
        $columnKey = $raw['column'] ?? null;
        $to = is_int($columnKey) || is_string($columnKey) ? $this->findKanbanColumn((string) $columnKey) : null;

        if ($to === null) {
            $this->failMove('unknown_column');
        }

        $fromKey = $this->getRecordColumnKey($record);
        $from = $fromKey === null ? null : $this->findKanbanColumn($fromKey);
        $title = $this->getCardTitle($record);

        if ($from !== null && ! $this->canMoveTo($from, $to, $record)) {
            $this->failMove('transition', ['title' => $title, 'from' => $from->getLabel(), 'to' => $to->getLabel()]);
        }

        if (($from === null || $from->getKey() !== $to->getKey()) && $to->isWipLimitStrict() && $this->countColumnCards($to) >= (int) $to->getWipLimit()) {
            throw ValidationException::withMessages([static::$argumentsKey => $this->wipLimitMessage($to)]);
        }

        // Lanes.
        $fromLane = $this->getRecordLaneKey($record);
        $toLane = $fromLane;
        $laneValue = $fromLane === null ? null : $record->getAttribute((string) $this->getSwimlaneAttribute());
        $postedLane = $raw['lane'] ?? null;

        if ($fromLane !== null && is_string($postedLane) && $postedLane !== '' && $postedLane !== $fromLane) {
            if (! $this->canMoveBetweenSwimlanes()) {
                $this->failMove('lane');
            }

            $laneValue = $this->resolveLaneValue($postedLane);

            if ($laneValue === false) {
                $this->failMove('unknown_lane');
            }

            $toLane = $postedLane;
        }

        $laneValue = $laneValue instanceof UnitEnum ? EnumState::key($laneValue) : $laneValue;

        // Position among the column's cards (all of them, not only the filtered ones).
        $position = 0;
        $order = $this->getOrderAttribute();

        if ($order !== null) {
            $keys = $this->getColumnCardKeys($to, $record);
            $after = $this->postedKey($raw['after'] ?? null);
            $before = $this->postedKey($raw['before'] ?? null);

            if ($after !== null) {
                $index = array_search($after, $keys, true);
                $index === false ? $this->failMove('stale') : $position = $index + 1;
            } elseif ($before !== null) {
                $index = array_search($before, $keys, true);
                $index === false ? $this->failMove('stale') : $position = $index;
            } else {
                $position = ($raw['position'] ?? null) === 'start' ? 0 : count($keys);
            }
        }

        return new KanbanMove($record, $from, $to, $fromLane, $toLane, $laneValue, $position);
    }

    /**
     * Keys of a column's cards in order, without `$except`.
     *
     * @return array<int, string>
     */
    protected function getColumnCardKeys(Column $column, ?Model $except = null): array
    {
        $query = $this->getKanbanQuery();

        if ($query === null) {
            return [];
        }

        $model = $query->getModel();
        $query->where($model->qualifyColumn($this->getStatusAttribute()), $column->getValue());

        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        $base = $this->orderKanbanCards($query)->toBase();
        $base->columns = null;

        return array_values(array_map(static fn (mixed $key): string => is_scalar($key) ? (string) $key : '', $base->pluck($model->getQualifiedKeyName())->all()));
    }

    protected function postedKey(mixed $value): ?string
    {
        return is_int($value) || (is_string($value) && $value !== '') ? (string) $value : null;
    }

    /**
     * Write a validated move: the status (and swimlane) attribute and the column's order.
     * Runs in a transaction; override to move cards through your own service.
     */
    protected function saveCardMove(KanbanMove $move): void
    {
        $record = $move->record;
        $record->setAttribute($this->getStatusAttribute(), $move->to->getValue());

        if ($move->changesLane()) {
            $record->setAttribute((string) $this->getSwimlaneAttribute(), $move->toLaneValue);
        }

        $order = $this->getOrderAttribute();

        if ($order === null) {
            $record->save();

            return;
        }

        $keys = $this->getColumnCardKeys($move->to, $record);
        array_splice($keys, $move->position, 0, [(string) $record->getKey()]);

        $record->setAttribute($order, $move->position + 1);
        $record->save();

        // The other cards of the column keep their order, renumbered around the moved one.
        $model = $record->newInstance();
        $current = [];
        $query = $this->getKanbanQuery();

        if ($query !== null) {
            $base = $query->whereIn($model->getQualifiedKeyName(), $keys)->toBase();
            $base->columns = null;

            foreach ($base->pluck($model->qualifyColumn($order), $model->getQualifiedKeyName())->all() as $key => $value) {
                $current[(string) $key] = $value;
            }
        }

        foreach ($keys as $index => $key) {
            if ($key === (string) $record->getKey()) {
                continue;
            }

            $value = $current[$key] ?? null;

            if (! is_numeric($value) || (int) $value !== $index + 1) {
                $model->newQueryWithoutScopes()->whereKey($key)->toBase()->update([$order => $index + 1]);
            }
        }
    }

    /**
     * Runs before a validated move is saved; throw a `ValidationException` to refuse it
     * (its message is shown and the card returns).
     */
    protected function beforeCardMoved(KanbanMove $move): void {}

    /**
     * Runs after a move was saved, before the `CardMoved` event. Send a notification here
     * if you want one.
     */
    protected function afterCardMoved(KanbanMove $move): void {}

    /**
     * @param  array<string, mixed>  $replacements
     *
     * @throws ValidationException
     */
    protected function failMove(string $reason, array $replacements = []): never
    {
        throw ValidationException::withMessages([static::$argumentsKey => __('alfakit-kanban::kanban.validation.'.$reason, $replacements)]);
    }

    /**
     * A card action, the quick-create action or the move and fetch actions, for the
     * action endpoint. Card actions and moves get the card's record (posted as `record`,
     * found within the board query).
     */
    public function getKanbanAction(string $name): ?Action
    {
        $action = $this->getKanbanActionMap()[$name] ?? null;

        if ($action === null) {
            return null;
        }

        // Fetching and creating need no record. The create modal is opened with the
        // column (and lane) clicked, which `mountUsing()` fills in.
        if ($action instanceof CreateAction) {
            $arguments = request()->input('arguments');
            $arguments = is_array($arguments) ? array_filter(['column' => $arguments['column'] ?? null, 'lane' => $arguments['lane'] ?? null], is_scalar(...)) : [];

            return $action->arguments([...$action->getArguments(), ...$arguments]);
        }

        if ($name === static::$fetchActionName) {
            return $action;
        }

        $key = request()->input('record');

        if ((is_string($key) || is_int($key)) && $key !== '') {
            $record = $this->resolveKanbanRecord($key);

            abort_if($record === null, 404);

            $action->record($record);
        } elseif ($name === static::$moveActionName) {
            abort(404);
        }

        return $action;
    }

    public function resolveKanbanRecord(int|string $key): ?Model
    {
        $query = $this->getKanbanQuery();

        return $query?->whereKey($key)->first();
    }

    // ---------------------------------------------------------------- Serialisation

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function eagerLoadCards(Builder $query): Builder
    {
        $with = static::$with;

        if (static::$swimlaneRelationship !== null) {
            $with[] = static::$swimlaneRelationship;
        }

        return $with === [] ? $query : $query->with(array_values(array_unique($with)));
    }

    /**
     * The board for the renderer's `kanban` component.
     *
     * @param  array<mixed>|null  $filters  Posted filter state; null for the defaults.
     * @return array<string, mixed>
     */
    public function serializeKanban(string $search = '', ?array $filters = null): array
    {
        $columns = $this->getKanbanColumns();
        $filterState = $this->normalizeFilterState($filters);
        $query = $this->getFilteredKanbanQuery($search, $filterState);
        $status = $this->getStatusAttribute();
        $limit = static::$cardsPerColumn !== null && static::$cardsPerColumn > 0 ? static::$cardsPerColumn : null;

        $wipCounts = $this->countCardsByColumn($this->getKanbanQuery());
        $isFiltered = $search !== '' || array_filter($filterState, static fn (array $state): bool => array_filter($state, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [] && $value !== false) !== []) !== [];
        $totals = $isFiltered ? $this->countCardsByColumn($query === null ? null : clone $query) : $wipCounts;

        /** @var array<string, Collection<int, Model>> $records */
        $records = [];

        if ($query !== null && $columns !== []) {
            $model = $query->getModel();
            $this->eagerLoadCards($query);

            if ($limit !== null) {
                foreach ($columns as $column) {
                    $records[$column->getKey()] = $this->orderKanbanCards((clone $query)->where($model->qualifyColumn($status), $column->getValue()))->limit($limit)->get();
                }
            } else {
                $values = array_map(static fn (Column $column): string|int => $column->getValue(), $columns);

                foreach ($this->orderKanbanCards($query->whereIn($model->qualifyColumn($status), $values))->get() as $record) {
                    $key = $this->getRecordColumnKey($record);

                    if ($key !== null) {
                        ($records[$key] ??= new Collection)->push($record);
                    }
                }
            }
        }

        $create = $this->hasQuickCreate() ? ($this->getKanbanActionMap()['create'] ?? null) : null;
        $create = $create instanceof CreateAction && $create->isVisible() ? $create : null;
        $clickName = $this->getCardClickActionName();
        $click = $clickName === null ? null : $this->getKanbanActionMap()[$clickName];
        $lanes = [];
        $clickSchema = null;
        $serializedColumns = [];

        foreach ($columns as $column) {
            $cards = $records[$column->getKey()] ?? new Collection;
            $limitWip = $column->getWipLimit();

            foreach ($this->hasSwimlanes() ? $cards : [] as $record) {
                $lanes[(string) $this->getRecordLaneKey($record)] ??= $this->makeSwimlaneForRecord($record);
            }

            $serializedColumns[] = [
                ...$column->toSchema(),
                'count' => $wipCounts[$column->getKey()] ?? 0,
                'total' => $totals[$column->getKey()] ?? 0,
                'creatable' => $create !== null && $column->isCreatable() && ! ($column->isWipLimitStrict() && ($wipCounts[$column->getKey()] ?? 0) >= (int) $limitWip) ? true : null,
                'cards' => $this->serializeCards($cards, $click, $clickSchema),
            ];
        }

        return array_filter([
            'type' => 'kanban',
            'key' => 'kanban',
            'actionComponent' => $this->getKanbanActionComponent(),
            'heading' => $this->getKanbanHeading(),
            'columns' => array_map(static fn (array $column): array => array_filter($column, static fn (mixed $value): bool => $value !== null), $serializedColumns),
            'lanes' => $this->hasSwimlanes() ? $this->serializeLanes($lanes) : null,
            'movable' => $this->canMoveCards(),
            'orderable' => $this->getOrderAttribute() !== null,
            'laneMovable' => $this->hasSwimlanes() && $this->canMoveBetweenSwimlanes() ? true : null,
            'transitions' => $this->serializeTransitions(),
            'moveAction' => static::$moveActionName,
            'fetchAction' => static::$fetchActionName,
            'argumentsKey' => static::$argumentsKey,
            'cardAction' => $clickSchema,
            'createAction' => $create?->toSchema(),
            'searchable' => $this->isKanbanSearchable() ? true : null,
            'search' => $search !== '' ? $search : null,
            'filters' => $this->serializeKanbanFilters($filters),
            'pollingInterval' => $this->getKanbanPollingInterval(),
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, Swimlane>  $derived
     * @return array<int, array<string, mixed>>
     */
    protected function serializeLanes(array $derived): array
    {
        $explicit = $this->getSwimlanes();
        $lanes = [];

        foreach ($explicit ?? [] as $lane) {
            $lanes[$lane->getKey()] = $lane;
        }

        if ($explicit === null) {
            uasort($derived, static function (Swimlane $a, Swimlane $b): int {
                if (($a->getKey() === Swimlane::NONE) !== ($b->getKey() === Swimlane::NONE)) {
                    return $a->getKey() === Swimlane::NONE ? 1 : -1;
                }

                return strnatcasecmp($a->getLabel(), $b->getLabel());
            });
        }

        foreach ($derived as $key => $lane) {
            $lanes[$key] ??= $lane;
        }

        return array_values(array_map(static fn (Swimlane $lane): array => $lane->toSchema(), $lanes));
    }

    /**
     * @param  array<mixed>|null  $state
     * @return array<string, mixed>|null
     */
    protected function serializeKanbanFilters(?array $state): ?array
    {
        $schema = $this->getKanbanFiltersSchema($state);

        if ($schema === null) {
            return null;
        }

        return [
            ...$schema->toPayload(),
            'component' => $this->getKanbanFiltersSchemaComponent(),
        ];
    }

    /**
     * The schema name the filters' searchable selects load options from (pages only).
     */
    protected function getKanbanFiltersSchemaComponent(): ?string
    {
        return null;
    }

    /**
     * @param  Collection<int, Model>  $records
     * @param  array<string, mixed>|null  $clickSchema  Receives the click action's schema (prepared with the first card it is visible for).
     * @return array<int, array<string, mixed>>
     */
    protected function serializeCards(Collection $records, ?Action $click = null, ?array &$clickSchema = null): array
    {
        $actions = $this->getPreparedCardActions();
        $hasSchema = $this->getCardSchema() !== [];
        $movable = $this->canMoveCards();
        $hasLanes = $this->hasSwimlanes();
        $cards = [];

        foreach ($records as $record) {
            $schemas = [];

            foreach ($actions as $action) {
                $action->record($record);

                if ($action->isVisible()) {
                    $schemas[] = $action->toSchema();
                }
            }

            $clickable = null;
            $url = null;

            if ($click !== null) {
                $click->record($record);
                $clickable = $click->isVisible() && ! $click->isDisabled();
                $url = $clickable && $click->isLink() ? \AlfaSix\Alfakit\Support\Url::sanitize($click->getUrl()) : null;

                if ($clickable && $clickSchema === null) {
                    $clickSchema = $click->toSchema();
                }
            }

            $cards[] = array_filter([
                'key' => (string) $record->getKey(),
                ...$this->getCard($record)->toSchema(),
                'lane' => $hasLanes ? $this->getRecordLaneKey($record) : null,
                'movable' => $movable && $this->canMoveCard($record) ? null : false,
                'clickable' => $clickable === false ? false : null,
                'url' => $url,
                'schema' => $hasSchema ? Schema::make()->record($record)->components($this->getCardSchema())->toSchema() : null,
                'actions' => $schemas ?: null,
            ], static fn (mixed $value): bool => $value !== null);
        }

        foreach ($actions as $action) {
            $action->record(null);
        }

        $click?->record(null);

        return $cards;
    }
}
