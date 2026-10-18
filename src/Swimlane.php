<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban;

use AlfaSix\Alfakit\Support\Icons;
use AlfaSix\Alfakit\Support\Serializable;
use AlfaSix\Alfakit\Support\Url;
use BackedEnum;

/**
 * A row of the board: the cards whose swimlane attribute has this value (null for cards
 * without one). `Swimlane::make($user->id)->label($user->name)->avatar($user->avatar_url)`.
 */
class Swimlane
{
    /** The key of the lane of cards without a value. */
    public const NONE = '__none';

    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?string $avatar = null;

    protected bool $isCollapsed = false;

    final public function __construct(protected string|int|null $value) {}

    public static function make(string|int|BackedEnum|null $value): static
    {
        return new static($value instanceof BackedEnum ? $value->value : $value);
    }

    public function getValue(): string|int|null
    {
        return $this->value;
    }

    public function getKey(): string
    {
        return self::keyFor($this->value);
    }

    /**
     * The lane key of an attribute value.
     */
    public static function keyFor(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return is_int($value) || (is_string($value) && $value !== '') ? (string) $value : self::NONE;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? ($this->value === null ? __('alfakit-kanban::kanban.lanes.none') : (string) $this->value);
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon === null || $icon === '' ? null : Icons::resolve($icon);

        return $this;
    }

    public function avatar(?string $url): static
    {
        $this->avatar = Url::sanitize($url);

        return $this;
    }

    public function collapsed(bool $condition = true): static
    {
        $this->isCollapsed = $condition;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return Serializable::compact([
            'key' => $this->getKey(),
            'label' => $this->getLabel(),
            'icon' => $this->icon,
            'avatar' => $this->avatar,
            'collapsed' => $this->isCollapsed ?: null,
        ]);
    }
}
