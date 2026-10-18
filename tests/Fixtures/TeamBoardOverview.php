<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests\Fixtures;

use AlfaSix\Alfakit\Standalone\Widgets;

/**
 * The Kanban widget outside a panel, in one of the app's own Inertia pages.
 */
class TeamBoardOverview extends Widgets
{
    public function widgets(): array
    {
        return [TeamBoardWidget::class];
    }

    public function widgetColumns(): int|array
    {
        return 1;
    }
}
