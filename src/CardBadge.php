<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban;

use AlfaSix\Alfakit\Support\Enums\Color;
use AlfaSix\Alfakit\Support\EnumState;
use AlfaSix\Alfakit\Support\Html;
use AlfaSix\Alfakit\Support\Icons;
use AlfaSix\Alfakit\Support\Serializable;
use UnitEnum;

/**
 * A badge on a card: `CardBadge::make('Urgent')->color('danger')->icon('flame')`, or an
 * enum case with `CardBadge::fromEnum(Priority::High)`.
 */
class CardBadge
{
    protected ?string $color = null;

    protected ?string $icon = null;

    protected ?string $tooltip = null;

    final public function __construct(protected string $label) {}

    public static function make(string $label): static
    {
        return new static($label);
    }

    public static function fromEnum(UnitEnum $case): static
    {
        return static::make(EnumState::label($case))->color(EnumState::color($case))->icon(EnumState::icon($case));
    }

    public function color(Color|string|null $color): static
    {
        $color = $color instanceof Color ? $color->value : $color;
        $this->color = is_string($color) && $color !== '' && (preg_match('/^[a-z][a-z0-9-]{0,40}$/', $color) === 1 || Html::isSafeColor($color)) ? $color : null;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon === null || $icon === '' ? null : Icons::resolve($icon);

        return $this;
    }

    public function tooltip(?string $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return Serializable::compact([
            'label' => $this->label,
            'color' => $this->color,
            'icon' => $this->icon,
            'tooltip' => $this->tooltip,
        ]);
    }
}
