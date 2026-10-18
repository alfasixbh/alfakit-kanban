<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use AlfaSix\AlfakitKanban\KanbanPlugin;
use AlfaSix\AlfakitKanban\Tests\Fixtures\Task;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TaskPolicy;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TaskStatus;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TeamBoardOverview;
use AlfaSix\AlfakitKanban\Tests\TestCase as KanbanTestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
 * The Kanban widget outside panels: a `Standalone\Widgets` component in an app page, cards
 * moved through the standalone panel's endpoints.
 */

uses(KanbanTestCase::class);

beforeEach(function (): void {
    Gate::policy(Task::class, TaskPolicy::class);

    alfakit()->getStandalonePanel()->plugin(KanbanPlugin::make());

    Route::middleware('web')->get('/team-board', fn () => Inertia::render('team-board', ['board' => TeamBoardOverview::make()]));
});

it('renders the board in an app page and moves cards through the standalone endpoints', function (): void {
    $ada = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']);
    $this->actingAs($ada);
    $task = Task::query()->create(['title' => 'Write docs', 'status' => TaskStatus::Todo, 'sort' => 1, 'assignee_id' => $ada->id]);

    $board = $this->inertiaProps('/team-board')['board'];
    $widget = collect(collect($board['content'])->firstWhere('key', 'headerWidgets')['widgets'] ?? [])->firstWhere('type', 'Kanban');

    expect($widget['component'])->toBe('kanban')
        ->and($widget['kanban']['heading'])->toBe('Team board')
        ->and($board['translations']['kanban']['instructions'])->toContain('Space');

    $this->postJson('/_alfakit/actions', [
        'token' => $board['token'],
        'component' => 'widget:'.$widget['id'],
        'action' => 'kanbanMove',
        'record' => $task->id,
        'arguments' => ['kanban' => ['column' => 'doing', 'lane' => (string) $ada->id]],
    ])->assertOk();

    expect($task->refresh()->status)->toBe(TaskStatus::Doing);
});
