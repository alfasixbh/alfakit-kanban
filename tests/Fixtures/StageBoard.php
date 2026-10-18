<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests\Fixtures;

use AlfaSix\AlfakitKanban\Column;
use AlfaSix\AlfakitKanban\Pages\KanbanPage;
use Illuminate\Database\Eloquent\Model;

class StageBoard extends KanbanPage
{
    protected static string $resource = TaskResource::class;

    protected static ?string $columnRelationship = 'stage';

    protected static ?string $columnRelationshipOrderAttribute = 'position';

    protected static ?int $cardsPerColumn = 2;

    protected function makeColumnFromRelatedRecord(Model $related): Column
    {
        $wip = $related->getAttribute('wip');

        return parent::makeColumnFromRelatedRecord($related)->wipLimit(is_int($wip) ? $wip : null, strict: true);
    }
}
