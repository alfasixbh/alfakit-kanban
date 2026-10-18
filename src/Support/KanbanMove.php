<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Support;

use AlfaSix\AlfakitKanban\Column;
use Illuminate\Database\Eloquent\Model;

/**
 * A card move the user asked for, validated: the record, the column it leaves (null when
 * its status matched no column) and the one it enters, its lanes, and where it lands.
 */
final class KanbanMove
{
    /**
     * @param  string|null  $fromLane  Lane keys (`Swimlane::NONE` for no value); null on boards without swimlanes.
     * @param  int  $position  The card's position in the target column, from 0.
     */
    public function __construct(
        public readonly Model $record,
        public readonly ?Column $from,
        public readonly Column $to,
        public readonly ?string $fromLane,
        public readonly ?string $toLane,
        public readonly mixed $toLaneValue,
        public readonly int $position,
    ) {}

    public function changesColumn(): bool
    {
        return $this->from === null || $this->from->getKey() !== $this->to->getKey();
    }

    public function changesLane(): bool
    {
        return $this->toLane !== null && $this->fromLane !== $this->toLane;
    }
}
