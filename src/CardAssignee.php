<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban;

use AlfaSix\Alfakit\Support\Serializable;
use AlfaSix\Alfakit\Support\Url;

/**
 * A person on a card, shown as an avatar (their image, else their initials):
 * `CardAssignee::make($user->name)->avatar($user->avatar_url)`.
 */
class CardAssignee
{
    protected ?string $avatar = null;

    final public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function avatar(?string $url): static
    {
        $this->avatar = Url::sanitize($url);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return Serializable::compact([
            'name' => $this->name,
            'avatar' => $this->avatar,
        ]);
    }
}
