<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban;

use AlfaSix\Alfakit\Support\Enums\Color;
use AlfaSix\Alfakit\Support\Html;
use AlfaSix\Alfakit\Support\Url;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use UnitEnum;

/**
 * What a card shows for its record. Return one from `getCard()`:
 *
 * ```php
 * public function getCard(Model $record): Card
 * {
 *     return parent::getCard($record)
 *         ->badges([CardBadge::fromEnum($record->priority)])
 *         ->assignees($record->users->map(fn (User $user) => CardAssignee::make($user->name)->avatar($user->avatar_url))->all())
 *         ->dueDate($record->due_at)
 *         ->progress($record->progress);
 * }
 * ```
 *
 * Text is shown as plain text; image URLs are sanitised.
 */
class Card
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $color = null;

    /** @var array<int, CardBadge> */
    protected array $badges = [];

    /** @var array<int, CardAssignee> */
    protected array $assignees = [];

    protected ?CarbonImmutable $dueDate = null;

    protected ?bool $isOverdue = null;

    protected ?float $progress = null;

    protected ?string $coverImage = null;

    protected ?string $coverImageAlt = null;

    final public function __construct(protected Model $record) {}

    public static function make(Model $record): static
    {
        return new static($record);
    }

    public function getRecord(): Model
    {
        return $this->record;
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title !== null && $this->title !== '' ? $this->title : (string) $this->record->getKey();
    }

    public function description(?string $description): static
    {
        $this->description = $description === null || trim($description) === '' ? null : $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * An accent colour on the card's edge (a theme colour, panel colour or CSS colour).
     */
    public function color(Color|string|null $color): static
    {
        $color = $color instanceof Color ? $color->value : $color;
        $this->color = is_string($color) && $color !== '' && (preg_match('/^[a-z][a-z0-9-]{0,40}$/', $color) === 1 || Html::isSafeColor($color)) ? $color : null;

        return $this;
    }

    /**
     * @param  array<int, CardBadge|UnitEnum|string>  $badges  Strings become plain badges,
     *                                                         enum cases use their label,
     *                                                         colour and icon.
     */
    public function badges(array $badges): static
    {
        $this->badges = [];

        foreach ($badges as $badge) {
            $badge = match (true) {
                $badge instanceof CardBadge => $badge,
                $badge instanceof UnitEnum => CardBadge::fromEnum($badge),
                is_string($badge) && $badge !== '' => CardBadge::make($badge),
                default => null,
            };

            if ($badge !== null) {
                $this->badges[] = $badge;
            }
        }

        return $this;
    }

    /**
     * @param  array<int, CardAssignee|string>  $assignees  Strings are names.
     */
    public function assignees(array $assignees): static
    {
        $this->assignees = [];

        foreach ($assignees as $assignee) {
            if (is_string($assignee) && $assignee !== '') {
                $assignee = CardAssignee::make($assignee);
            }

            if ($assignee instanceof CardAssignee) {
                $this->assignees[] = $assignee;
            }
        }

        return $this;
    }

    /**
     * The due date; a past date marks the card overdue unless `overdue(false)`. A date at
     * midnight (no time) is due until the end of that day.
     */
    public function dueDate(DateTimeInterface|string|null $date): static
    {
        if (is_string($date)) {
            try {
                $date = $date === '' ? null : CarbonImmutable::parse($date);
            } catch (Throwable) {
                $date = null;
            }
        }

        $this->dueDate = $date === null ? null : CarbonImmutable::instance($date);

        return $this;
    }

    public function getDueDate(): ?CarbonImmutable
    {
        return $this->dueDate;
    }

    public function overdue(?bool $condition = true): static
    {
        $this->isOverdue = $condition;

        return $this;
    }

    public function isOverdue(): bool
    {
        if ($this->isOverdue !== null || $this->dueDate === null) {
            return (bool) $this->isOverdue;
        }

        // A date without a time (a `date` column, a DatePicker) is due until the end of that day.
        return $this->dueDate->isStartOfDay()
            ? $this->dueDate->endOfDay()->isPast()
            : $this->dueDate->isPast();
    }

    /**
     * Progress from 0 to 100 (clamped), shown as a bar.
     */
    public function progress(int|float|null $percent): static
    {
        $this->progress = $percent === null ? null : max(0.0, min(100.0, (float) $percent));

        return $this;
    }

    public function coverImage(?string $url, ?string $alt = null): static
    {
        $this->coverImage = Url::sanitize($url);
        $this->coverImageAlt = $alt;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return array_filter([
            'title' => $this->getTitle(),
            'description' => $this->description,
            'color' => $this->color,
            'badges' => array_map(static fn (CardBadge $badge): array => $badge->toSchema(), $this->badges) ?: null,
            'assignees' => array_map(static fn (CardAssignee $assignee): array => $assignee->toSchema(), $this->assignees) ?: null,
            'dueDate' => $this->dueDate?->toIso8601String(),
            'overdue' => $this->dueDate !== null && $this->isOverdue() ? true : null,
            'progress' => $this->progress,
            'coverImage' => $this->coverImage,
            'coverImageAlt' => $this->coverImage !== null ? $this->coverImageAlt : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
