<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban;

use AlfaSix\Alfakit\Contracts\Plugin;
use AlfaSix\Alfakit\Panel;

/**
 * Shares the board's frontend strings with the panel. Kanban pages and widgets work
 * without it, with English labels.
 *
 * ```php
 * $panel->plugin(KanbanPlugin::make());
 * ```
 */
class KanbanPlugin implements Plugin
{
    public const ID = 'kanban';

    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        $panel->frontendTranslations(['kanban' => 'alfakit-kanban::kanban.ui']);
    }

    public function boot(Panel $panel): void {}
}
