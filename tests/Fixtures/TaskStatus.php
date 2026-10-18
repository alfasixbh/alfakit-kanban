<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests\Fixtures;

use AlfaSix\Alfakit\Support\Contracts\HasColor;
use AlfaSix\Alfakit\Support\Contracts\HasIcon;
use AlfaSix\Alfakit\Support\Contracts\HasLabel;
use AlfaSix\Alfakit\Support\Enums\Color;

enum TaskStatus: string implements HasColor, HasIcon, HasLabel
{
    case Todo = 'todo';
    case Doing = 'doing';
    case Review = 'review';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::Todo => 'To do',
            self::Doing => 'In progress',
            self::Review => 'In review',
            self::Done => 'Done',
        };
    }

    public function getColor(): Color
    {
        return match ($this) {
            self::Todo => Color::Gray,
            self::Doing => Color::Info,
            self::Review => Color::Warning,
            self::Done => Color::Success,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Todo => 'circle',
            self::Doing => 'loader',
            self::Review => 'eye',
            self::Done => 'circle-check',
        };
    }
}
