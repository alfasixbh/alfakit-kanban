<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests\Fixtures;

use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Schemas\Schema;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('title')->required()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TaskBoard::route('/'),
            'stages' => StageBoard::route('/stages'),
        ];
    }
}
