<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests\Fixtures;

use AlfaSix\AlfakitKanban\Widgets\KanbanWidget;

class TeamBoardWidget extends KanbanWidget
{
    protected static bool $isLazy = false;

    protected static ?string $model = Task::class;

    protected static ?string $statusEnum = TaskStatus::class;

    protected static ?string $swimlaneRelationship = 'assignee';

    protected static bool $canMoveBetweenSwimlanes = true;

    protected ?string $heading = 'Team board';
}
