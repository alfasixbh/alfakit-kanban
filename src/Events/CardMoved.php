<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Events;

use AlfaSix\AlfakitKanban\Support\KanbanMove;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a card was moved and saved. `$board` is the `KanbanPage` or
 * `KanbanWidget` class the move came from.
 */
final class CardMoved
{
    use Dispatchable;

    /**
     * @param  class-string  $board
     */
    public function __construct(
        public readonly KanbanMove $move,
        public readonly string $board,
    ) {}
}
