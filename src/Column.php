<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban;

use AlfaSix\Alfakit\Support\Concerns\EvaluatesClosures;
use AlfaSix\Alfakit\Support\Enums\Color;
use AlfaSix\Alfakit\Support\EnumState;
use AlfaSix\Alfakit\Support\Html;
use AlfaSix\Alfakit\Support\Icons;
use AlfaSix\Alfakit\Support\Labels;
use AlfaSix\Alfakit\Support\Serializable;
use BackedEnum;
use Closure;
use UnitEnum;

/**
 * One column of a Kanban board: the value of the status attribute its cards have, and how
 * the column looks.
 *
 * ```php
 * Column::make('review')
 *     ->label('In review')
 *     ->color('warning')
 *     ->icon('eye')
 *     ->description('Waiting for a second pair of eyes')
 *     ->wipLimit(3, strict: true)
 *     ->collapsible();
 * ```
 *
 * Every option also takes a closure.
 */
class Column
{
    use EvaluatesClosures;

    protected string|Closure|null $label = null;

    protected Color|string|Closure|null $color = null;

    protected string|Closure|null $icon = null;

    protected string|Closure|null $description = null;

    protected int|Closure|null $wipLimit = null;

    protected bool|Closure $isWipLimitStrict = false;

    protected bool|Closure $isCollapsible = false;

    protected bool|Closure $isCollapsed = false;

    protected bool|Closure $canCreate = true;

    protected string|Closure|null $emptyStateLabel = null;

    /**
     * @param  string|int  $value  The status attribute's value for this column (an enum's
     *                             backing value, a related record's key…).
     */
    final public function __construct(protected string|int $value) {}

    public static function make(string|int|BackedEnum $value): static
    {
        return new static($value instanceof BackedEnum ? $value->value : $value);
    }

    /**
     * A column for an enum case, with the case's label, colour, icon and description
     * (`HasLabel`, `HasColor`, `HasIcon`, `HasDescription`).
     */
    public static function fromEnum(UnitEnum $case): static
    {
        return static::make(EnumState::key($case))
            ->label(EnumState::label($case))
            ->color(EnumState::color($case))
            ->icon(EnumState::icon($case))
            ->description(EnumState::description($case));
    }

    protected function getEvaluationIdentifier(): string
    {
        return 'column';
    }

    /** The value written to the status attribute. */
    public function getValue(): string|int
    {
        return $this->value;
    }

    /** The column's key on the wire. */
    public function getKey(): string
    {
        return (string) $this->value;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        $label = $this->evaluate($this->label);

        return is_string($label) && $label !== '' ? $label : Labels::fromName((string) $this->value, false);
    }

    /**
     * A theme colour (`primary`, `success`, `warning`, `danger`, `info`, `gray`), a panel
     * colour name or a plain CSS colour (`#f59e0b`).
     */
    public function color(Color|string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        $color = $this->evaluate($this->color);

        if ($color instanceof Color) {
            return $color->value;
        }

        return is_string($color) && $color !== '' && (preg_match('/^[a-z][a-z0-9-]{0,40}$/', $color) === 1 || Html::isSafeColor($color)) ? $color : null;
    }

    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        $icon = $this->evaluate($this->icon);

        return is_string($icon) && $icon !== '' ? Icons::resolve($icon) : null;
    }

    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        $description = $this->evaluate($this->description);

        return is_string($description) && $description !== '' ? $description : null;
    }

    /**
     * The most cards the column should hold. Over the limit the column is highlighted; with
     * `strict` the server refuses moves (and quick creates) into a full column.
     */
    public function wipLimit(int|Closure|null $limit, bool|Closure $strict = false): static
    {
        $this->wipLimit = $limit;
        $this->isWipLimitStrict = $strict;

        return $this;
    }

    public function getWipLimit(): ?int
    {
        $limit = $this->evaluate($this->wipLimit);

        return is_int($limit) && $limit > 0 ? $limit : null;
    }

    public function isWipLimitStrict(): bool
    {
        return $this->getWipLimit() !== null && (bool) $this->evaluate($this->isWipLimitStrict);
    }

    /**
     * The user can collapse the column to a narrow strip.
     */
    public function collapsible(bool|Closure $condition = true): static
    {
        $this->isCollapsible = $condition;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return (bool) $this->evaluate($this->isCollapsible) || $this->isCollapsed();
    }

    /**
     * The column starts collapsed (and is collapsible).
     */
    public function collapsed(bool|Closure $condition = true): static
    {
        $this->isCollapsed = $condition;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return (bool) $this->evaluate($this->isCollapsed);
    }

    /**
     * Whether the column offers the board's quick-create button.
     */
    public function creatable(bool|Closure $condition = true): static
    {
        $this->canCreate = $condition;

        return $this;
    }

    public function isCreatable(): bool
    {
        return (bool) $this->evaluate($this->canCreate);
    }

    /**
     * The text of an empty column (defaults to "No cards").
     */
    public function emptyStateLabel(string|Closure|null $label): static
    {
        $this->emptyStateLabel = $label;

        return $this;
    }

    public function getEmptyStateLabel(): ?string
    {
        $label = $this->evaluate($this->emptyStateLabel);

        return is_string($label) && $label !== '' ? $label : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        $limit = $this->getWipLimit();

        return Serializable::compact([
            'key' => $this->getKey(),
            'label' => $this->getLabel(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'description' => $this->getDescription(),
            'wipLimit' => $limit,
            'wipStrict' => $this->isWipLimitStrict() ?: null,
            'collapsible' => $this->isCollapsible() ?: null,
            'collapsed' => $this->isCollapsed() ?: null,
            'emptyState' => $this->getEmptyStateLabel(),
        ]);
    }
}
