<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests\Fixtures;

use AlfaSix\Alfakit\Forms\Components\Select;
use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\Alfakit\Infolists\Components\TextEntry;
use AlfaSix\Alfakit\Tables\Filters\SelectFilter;
use AlfaSix\AlfakitKanban\Card;
use AlfaSix\AlfakitKanban\CardBadge;
use AlfaSix\AlfakitKanban\Column;
use AlfaSix\AlfakitKanban\Pages\KanbanPage;
use AlfaSix\AlfakitKanban\Support\KanbanMove;
use Illuminate\Database\Eloquent\Model;

class TaskBoard extends KanbanPage
{
    protected static string $resource = TaskResource::class;

    protected static ?string $statusEnum = TaskStatus::class;

    protected static ?string $descriptionAttribute = 'description';

    protected static ?string $dueDateAttribute = 'due_on';

    protected static ?string $progressAttribute = 'progress';

    /** @var array<int, string> */
    protected static array $searchableAttributes = ['title', 'description'];

    protected static ?string $pollingInterval = '30s';

    /** @var array<int, KanbanMove> */
    public static array $moves = [];

    protected function getColumns(): array
    {
        return array_map(static fn (Column $column): Column => match ($column->getKey()) {
            'doing' => $column->wipLimit(2, strict: true),
            'review' => $column->wipLimit(1),
            'done' => $column->collapsible()->description('Shipped'),
            default => $column,
        }, parent::getColumns());
    }

    protected function getTransitions(): ?array
    {
        return [
            'done' => [TaskStatus::Review],
        ];
    }

    public function getCard(Model $record): Card
    {
        $priority = $record->getAttribute('priority');

        return parent::getCard($record)->badges(is_string($priority) ? [CardBadge::make($priority)->color('danger')] : []);
    }

    public function getCardSchema(): array
    {
        return [TextEntry::make('priority')->label('Priority')];
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')->required(),
            Select::make('status')->options(TaskStatus::class),
        ];
    }

    protected function getKanbanFilters(): array
    {
        return [SelectFilter::make('priority')->options(['high' => 'High', 'low' => 'Low'])];
    }

    protected function afterCardMoved(KanbanMove $move): void
    {
        static::$moves[] = $move;
    }
}
