<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Viewers (e-mail containing "viewer") may look but not change anything; nobody may move
 * or delete a task titled "Locked".
 */
class TaskPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Task $task): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return ! self::isViewer($user);
    }

    public function update(?Authenticatable $user, Task $task): bool
    {
        return ! self::isViewer($user) && $task->title !== 'Locked';
    }

    public function delete(?Authenticatable $user, Task $task): bool
    {
        return ! self::isViewer($user) && $task->title !== 'Locked';
    }

    private static function isViewer(?Authenticatable $user): bool
    {
        return str_contains((string) $user?->getAttribute('email'), 'viewer');
    }
}
