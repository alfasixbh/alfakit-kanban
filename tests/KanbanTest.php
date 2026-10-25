<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Tests\Fixtures\AuthTenancy\Panels;
use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use AlfaSix\Alfakit\Tests\Fixtures\Models\TenantUser;
use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use AlfaSix\AlfakitKanban\Events\CardMoved;
use AlfaSix\AlfakitKanban\KanbanPlugin;
use AlfaSix\AlfakitKanban\Swimlane;
use AlfaSix\AlfakitKanban\Tests\Fixtures\Stage;
use AlfaSix\AlfakitKanban\Tests\Fixtures\Task;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TaskBoard;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TaskPolicy;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TaskResource;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TaskStatus;
use AlfaSix\AlfakitKanban\Tests\Fixtures\TeamBoardWidget;
use AlfaSix\AlfakitKanban\Tests\TestCase as KanbanTestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

uses(KanbanTestCase::class);

function kanbanTest(): KanbanTestCase
{
    return KanbanTestCase::current();
}

function kanbanPanel(): Panel
{
    return Panels::register('boards', fn (Panel $panel): Panel => $panel
        ->plugin(KanbanPlugin::make())
        ->resources([TaskResource::class])
        ->pages([Dashboard::class])
        ->widgets([TeamBoardWidget::class]));
}

function kanbanUser(string $email = 'editor@example.com', string $name = 'Editor'): User
{
    return User::query()->create(['name' => $name, 'email' => $email, 'password' => 'secret']);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function kanbanTask(string $title, TaskStatus $status = TaskStatus::Todo, ?int $sort = null, array $attributes = []): Task
{
    return Task::query()->create(['title' => $title, 'status' => $status, 'sort' => $sort, ...$attributes]);
}

/**
 * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}
 */
function kanbanPage(string $url = '/boards/tasks'): array
{
    $props = kanbanTest()->inertiaProps($url);
    $block = collect($props['page']['content'])->firstWhere('key', 'kanban');

    return [$props['page']['token'], $block['components'][0], $props['page']];
}

/**
 * @param  array<string, mixed>  $board
 * @return array<string, mixed>
 */
function kanbanColumn(array $board, string $key): array
{
    return collect($board['columns'])->firstWhere('key', $key);
}

/**
 * @param  array<string, mixed>  $board
 * @return array<int, string>
 */
function kanbanTitles(array $board, string $column): array
{
    return collect(kanbanColumn($board, $column)['cards'])->pluck('title')->all();
}

/**
 * @param  array<string, mixed>  $move
 * @return Illuminate\Testing\TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function kanbanMove(string $token, Task $task, array $move, string $component = 'kanban', string $panel = 'boards'): Illuminate\Testing\TestResponse
{
    return kanbanTest()->kanbanAction($panel, $token, $component, 'kanbanMove', ['record' => $task->id, 'arguments' => ['kanban' => $move]]);
}

beforeEach(function (): void {
    Gate::policy(Task::class, TaskPolicy::class);
    TaskBoard::$moves = [];
});

it('registers on a panel and shares its frontend strings', function (): void {
    $panel = kanbanPanel();

    expect($panel->getPlugin('kanban'))->toBeInstanceOf(KanbanPlugin::class)
        ->and($panel->getFrontendTranslations()['kanban']['instructions'])->toContain('Space');
});

it('renders the board with enum columns, ordered cards and their content', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    kanbanTask('Second', sort: 2, attributes: ['description' => 'Two', 'priority' => 'high', 'due_on' => '2020-01-01', 'progress' => 40]);
    kanbanTask('First', sort: 1);
    kanbanTask('Shipping', TaskStatus::Done, 1);

    [, $board, $page] = kanbanPage();

    expect($board['type'])->toBe('kanban')
        ->and($board['actionComponent'])->toBe('kanban')
        ->and($board['movable'])->toBeTrue()
        ->and($board['orderable'])->toBeTrue()
        ->and($board['searchable'])->toBeTrue()
        ->and($board['pollingInterval'])->toBe('30s')
        ->and(collect($board['columns'])->pluck('key')->all())->toBe(['todo', 'doing', 'review', 'done'])
        ->and(kanbanColumn($board, 'doing'))->toMatchArray(['label' => 'In progress', 'color' => 'info', 'icon' => 'loader', 'wipLimit' => 2, 'wipStrict' => true, 'count' => 0, 'creatable' => true])
        ->and(kanbanColumn($board, 'done'))->toMatchArray(['collapsible' => true, 'description' => 'Shipped', 'count' => 1])
        ->and(kanbanTitles($board, 'todo'))->toBe(['First', 'Second'])
        ->and($board['transitions']['done'])->toBe(['review', 'done'])
        ->and($board['transitions']['todo'])->toBe(['todo', 'doing', 'review', 'done'])
        ->and($board['cardAction']['name'])->toBe('view')
        ->and($board['createAction']['name'])->toBe('create')
        ->and($board['filters']['component'])->toBe('kanbanFilters')
        ->and($board['filters']['state'])->toBe(['priority' => ['value' => null]])
        ->and($page['headerActions'][0]['name'])->toBe('create');

    $card = kanbanColumn($board, 'todo')['cards'][1];

    expect($card)->toMatchArray(['title' => 'Second', 'description' => 'Two', 'overdue' => true, 'progress' => 40])
        ->and($card['dueDate'])->toStartWith('2020-01-01')
        ->and($card['badges'])->toBe([['label' => 'high', 'color' => 'danger']])
        ->and($card['schema'][0]['state'])->toBe('high')
        ->and(collect($card['actions'][0]['actions'])->pluck('name')->all())->toBe(['view', 'edit', 'delete'])
        ->and($card)->not->toHaveKey('movable');
});

it('shows a rich text description as its text', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    kanbanTask('Rich', sort: 1, attributes: ['description' => '<p>Hello <strong>team</strong>.</p><p>Second&nbsp;line &amp; more</p>']);

    [, $board] = kanbanPage();

    // Before: the card read "<p>Hello <strong>team</strong>.</p>…".
    expect(kanbanColumn($board, 'todo')['cards'][0]['description'])->toBe('Hello team. Second line & more');
});

it('titles the board page after the board, not the resource list', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());

    [, , $page] = kanbanPage();

    expect($page['title'])->toBe('Tasks board')
        ->and(TaskBoard::getNavigationLabel())->toBe('Tasks board')
        ->and($page['heading'])->toBe('Tasks board')
        ->and(array_column($page['breadcrumbs'], 'label'))->toBe(['Tasks', 'Tasks board']);

    app()->setLocale('fr');

    expect(TaskBoard::getBoardTitle())->toBe('Tableau : Tasks');

    app()->setLocale('en');
});

it('moves a card to another column and position in one request', function (): void {
    Event::fake([CardMoved::class]);
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    $task = kanbanTask('Write docs', sort: 1);
    $a = kanbanTask('Alpha', TaskStatus::Review, 1);
    $b = kanbanTask('Beta', TaskStatus::Review, 2);
    [$token] = kanbanPage();

    $response = kanbanMove($token, $task, ['column' => 'review', 'after' => $a->id])->assertOk();

    expect($response->json('result.ok'))->toBeTrue()
        ->and($response->json('result.card.title'))->toBe('Write docs')
        ->and($response->json('result.counts'))->toBe(['todo' => 0, 'review' => 3])
        ->and($task->refresh()->status)->toBe(TaskStatus::Review)
        ->and(Task::query()->where('status', 'review')->orderBy('sort')->pluck('title')->all())->toBe(['Alpha', 'Write docs', 'Beta'])
        ->and($b->refresh()->sort)->toBe(3)
        ->and(TaskBoard::$moves)->toHaveCount(1)
        ->and(TaskBoard::$moves[0]->from?->getKey())->toBe('todo')
        ->and(TaskBoard::$moves[0]->to->getKey())->toBe('review')
        ->and(TaskBoard::$moves[0]->position)->toBe(1);

    Event::assertDispatched(CardMoved::class, fn (CardMoved $event): bool => $event->move->record->is($task) && $event->board === TaskBoard::class);
});

it('reorders cards within a column', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    $a = kanbanTask('A', sort: 1);
    $b = kanbanTask('B', sort: 2);
    $c = kanbanTask('C', sort: 3);
    [$token] = kanbanPage();

    kanbanMove($token, $c, ['column' => 'todo', 'before' => $a->id])->assertOk();

    expect(Task::query()->orderBy('sort')->pluck('title')->all())->toBe(['C', 'A', 'B']);

    kanbanMove($token, $c, ['column' => 'todo', 'position' => 'end'])->assertOk();

    expect(Task::query()->orderBy('sort')->pluck('title')->all())->toBe(['A', 'B', 'C'])
        ->and($b->refresh()->sort)->toBe(2);
});

it('refuses moves the server does not allow', function (array $move, string $message, TaskStatus $status = TaskStatus::Todo): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    $task = kanbanTask('Card', $status, 1);
    kanbanTask('Busy 1', TaskStatus::Doing, 1);
    kanbanTask('Busy 2', TaskStatus::Doing, 2);
    [$token] = kanbanPage();

    kanbanMove($token, $task, $move)
        ->assertUnprocessable()
        ->assertJsonPath('errors.kanban.0', $message);

    expect($task->refresh()->status)->toBe($status);
})->with([
    'an unknown column' => [['column' => 'archived'], 'That column does not exist. Reload the board and try again.'],
    'no column' => [[], 'That column does not exist. Reload the board and try again.'],
    'a full strict column' => [['column' => 'doing'], 'In progress is full: it holds at most 2 cards.'],
    'a forbidden transition' => [['column' => 'todo'], 'Card cannot be moved from Done to To do.', TaskStatus::Done],
    'a stale neighbour' => [['column' => 'review', 'after' => 999], 'The board changed in the meantime. Reload it and try again.'],
]);

it('lets a soft WIP limit be exceeded', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    kanbanTask('Waiting', TaskStatus::Review, 1);
    $task = kanbanTask('Card', sort: 1);
    [$token] = kanbanPage();

    kanbanMove($token, $task, ['column' => 'review'])->assertOk();

    [, $board] = kanbanPage();

    expect(kanbanColumn($board, 'review'))->toMatchArray(['count' => 2, 'wipLimit' => 1])
        ->and(kanbanColumn($board, 'review'))->not->toHaveKey('wipStrict');
});

it('authorises every move with the update policy', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    $locked = kanbanTask('Locked', sort: 1);
    [$token, $board] = kanbanPage();

    expect(kanbanColumn($board, 'todo')['cards'][0]['movable'])->toBeFalse();

    kanbanMove($token, $locked, ['column' => 'doing'])->assertForbidden();
    kanbanTest()->kanbanAction('boards', $token, 'kanban', 'kanbanMove', ['record' => 999, 'arguments' => ['kanban' => ['column' => 'doing']]])->assertNotFound();
    kanbanTest()->kanbanAction('boards', $token, 'kanban', 'kanbanMove', ['arguments' => ['kanban' => ['column' => 'doing']]])->assertNotFound();

    expect($locked->refresh()->status)->toBe(TaskStatus::Todo);
});

it('hides moving and creating from users the policy denies', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser('viewer@example.com'));
    $task = kanbanTask('Card', sort: 1);
    [$token, $board] = kanbanPage();

    expect(kanbanColumn($board, 'todo')['cards'][0]['movable'])->toBeFalse()
        ->and($board)->not->toHaveKey('createAction')
        ->and(kanbanColumn($board, 'todo'))->not->toHaveKey('creatable')
        ->and(collect(kanbanColumn($board, 'todo')['cards'][0]['actions'][0]['actions'])->pluck('name')->all())->toBe(['view']);

    kanbanMove($token, $task, ['column' => 'doing'])->assertForbidden();

    expect($task->refresh()->status)->toBe(TaskStatus::Todo);
});

it('searches and filters the board through the fetch action', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    kanbanTask('Invoice customer', sort: 1, attributes: ['priority' => 'high']);
    kanbanTask('Fix login', sort: 2, attributes: ['priority' => 'low', 'description' => 'invoice link']);
    kanbanTask('100% done', TaskStatus::Done, 1);
    [$token] = kanbanPage();

    $fetch = fn (array $state) => kanbanTest()->kanbanAction('boards', $token, 'kanban', 'kanbanFetch', ['arguments' => ['kanban' => $state]])->assertOk()->json('result.board');

    $board = $fetch(['search' => 'invoice']);

    expect(kanbanTitles($board, 'todo'))->toBe(['Invoice customer', 'Fix login'])
        ->and($board['search'])->toBe('invoice')
        ->and(kanbanColumn($board, 'done')['cards'])->toBe([]);

    $board = $fetch(['search' => 'invoice', 'filters' => ['priority' => ['value' => 'low']]]);

    expect(kanbanTitles($board, 'todo'))->toBe(['Fix login'])
        ->and(kanbanColumn($board, 'todo'))->toMatchArray(['count' => 2, 'total' => 1])
        ->and($board['filters']['state']['priority']['value'])->toBe('low');

    // LIKE wildcards are literal; nested arrays in the filter state are dropped.
    expect(kanbanTitles($fetch(['search' => '100%']), 'done'))->toBe(['100% done'])
        ->and(kanbanTitles($fetch(['search' => '%']), 'todo'))->toBe([])
        ->and(kanbanTitles($fetch(['filters' => ['priority' => ['value' => ['nested' => ['x']]]]]), 'todo'))->toHaveCount(2);
});

it('opens card actions for the card record', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    $task = kanbanTask('Card', sort: 1);
    $locked = kanbanTask('Locked', sort: 2);
    [$token] = kanbanPage();

    $modal = kanbanTest()->getJson('/boards/_alfakit/actions/modal?'.http_build_query([
        'token' => $token, 'component' => 'kanban', 'action' => 'edit', 'record' => $task->id,
    ]))->assertOk()->json();

    expect($modal['form']['state']['title'])->toBe('Card');

    kanbanTest()->postJson('/boards/_alfakit/actions', ['token' => $token, 'component' => 'kanban', 'action' => 'delete', 'record' => $locked->id])->assertForbidden();
    kanbanTest()->postJson('/boards/_alfakit/actions', ['token' => $token, 'component' => 'kanban', 'action' => 'edit', 'record' => 999])->assertNotFound();
});

it('quick-creates a card in the clicked column, at its end', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    kanbanTask('Waiting', TaskStatus::Review, 4);
    [$token] = kanbanPage();

    $modal = kanbanTest()->getJson('/boards/_alfakit/actions/modal?'.http_build_query([
        'token' => $token, 'component' => 'kanban', 'action' => 'create', 'arguments' => ['column' => 'review'],
    ]))->assertOk()->json();

    expect($modal['form']['state']['status'])->toBe('review');

    kanbanTest()->post('/boards/_alfakit/actions', [
        'token' => $token, 'component' => 'kanban', 'action' => 'create',
        'arguments' => ['column' => 'review'], 'data' => ['title' => 'New card'],
    ], ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);

    $created = Task::query()->where('title', 'New card')->firstOrFail();

    expect($created->status)->toBe(TaskStatus::Review)
        ->and($created->sort)->toBe(5);
});

it('refuses to quick-create in a full strict column', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    kanbanTask('Busy 1', TaskStatus::Doing, 1);
    kanbanTask('Busy 2', TaskStatus::Doing, 2);
    [$token, $board] = kanbanPage();

    expect(kanbanColumn($board, 'doing'))->not->toHaveKey('creatable');

    kanbanTest()->post('/boards/_alfakit/actions', [
        'token' => $token, 'component' => 'kanban', 'action' => 'create',
        'arguments' => ['column' => 'doing'], 'data' => ['title' => 'One too many'],
    ], ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);

    expect(Task::query()->where('title', 'One too many')->exists())->toBeFalse();
});

it('builds columns from a relationship and limits the cards per column', function (): void {
    kanbanPanel();
    kanbanTest()->actingAs(kanbanUser());
    $later = Stage::query()->create(['name' => 'Later', 'position' => 2, 'wip' => 3]);
    $now = Stage::query()->create(['name' => 'Now', 'position' => 1]);

    foreach (['One', 'Two', 'Three'] as $index => $title) {
        kanbanTask($title, sort: $index + 1, attributes: ['stage_id' => $now->id]);
    }

    $moving = kanbanTask('Moving', sort: 1, attributes: ['stage_id' => $later->id]);
    [$token, $board] = kanbanPage('/boards/tasks/stages');

    expect(collect($board['columns'])->pluck('label')->all())->toBe(['Now', 'Later'])
        ->and(kanbanColumn($board, (string) $now->id))->toMatchArray(['count' => 3, 'total' => 3])
        ->and(kanbanTitles($board, (string) $now->id))->toBe(['One', 'Two'])
        ->and(kanbanColumn($board, (string) $later->id)['wipLimit'])->toBe(3);

    kanbanMove($token, $moving, ['column' => (string) $now->id, 'position' => 'start'])->assertOk();

    expect($moving->refresh()->stage_id)->toBe($now->id)
        ->and(Task::query()->where('stage_id', $now->id)->orderBy('sort')->pluck('title')->all())->toBe(['Moving', 'One', 'Two', 'Three']);
});

it('groups a widget into swimlanes and moves cards between them', function (): void {
    kanbanPanel();
    $ada = kanbanUser('ada@example.com', 'Ada');
    $bob = kanbanUser('bob@example.com', 'Bob');
    kanbanTest()->actingAs($ada);
    $task = kanbanTask('Shared', sort: 1, attributes: ['assignee_id' => $ada->id]);
    kanbanTask('Bob task', TaskStatus::Doing, 1, ['assignee_id' => $bob->id]);
    kanbanTask('Nobody', sort: 2);

    $props = kanbanTest()->inertiaProps('/boards');
    $widget = collect(collect($props['page']['content'])->firstWhere('key', 'widgets')['widgets'])->firstWhere('type', 'Kanban');
    $board = $widget['kanban'];

    expect($widget['component'])->toBe('kanban')
        ->and($board['heading'])->toBe('Team board')
        ->and($board['actionComponent'])->toBe('widget:'.$widget['id'])
        ->and($board['laneMovable'])->toBeTrue()
        ->and($board['lanes'])->toBe([
            ['key' => (string) $ada->id, 'label' => 'Ada'],
            ['key' => (string) $bob->id, 'label' => 'Bob'],
            ['key' => Swimlane::NONE, 'label' => 'Unassigned'],
        ])
        ->and(collect(kanbanColumn($board, 'todo')['cards'])->pluck('lane')->all())->toBe([(string) $ada->id, Swimlane::NONE])
        // The widget has no form: no quick create and no view/edit modal.
        ->and($board)->not->toHaveKey('createAction');

    $component = 'widget:'.$widget['id'];

    kanbanMove($props['page']['token'], $task, ['column' => 'doing', 'lane' => (string) $bob->id], $component)->assertOk();

    expect($task->refresh()->assignee_id)->toBe($bob->id)
        ->and($task->status)->toBe(TaskStatus::Doing);

    kanbanMove($props['page']['token'], $task, ['column' => 'doing', 'lane' => '999'], $component)
        ->assertUnprocessable()
        ->assertJsonPath('errors.kanban.0', 'That row no longer exists. Reload the board and try again.');

    kanbanMove($props['page']['token'], $task, ['column' => 'doing', 'lane' => Swimlane::NONE], $component)->assertOk();

    expect($task->refresh()->assignee_id)->toBeNull();
});

it('scopes the board, its moves and its columns to the current tenant', function (): void {
    Panels::register('boardteam', fn (Panel $panel): Panel => $panel
        ->tenant(Team::class, slugAttribute: 'slug')
        ->plugin(KanbanPlugin::make())
        ->resources([TaskResource::class]));

    $user = TenantUser::query()->create(['name' => 'T', 'email' => 't@example.com', 'password' => 'secret']);
    $mine = Team::query()->create(['name' => 'Mine', 'slug' => 'mine']);
    $other = Team::query()->create(['name' => 'Other', 'slug' => 'other']);
    $user->teams()->attach($mine);
    kanbanTest()->actingAs($user);

    $ours = kanbanTask('Ours', sort: 1, attributes: ['team_id' => $mine->id]);
    $theirs = kanbanTask('Theirs', sort: 1, attributes: ['team_id' => $other->id]);
    $ourStage = Stage::query()->create(['name' => 'Our stage', 'team_id' => $mine->id]);
    $theirStage = Stage::query()->create(['name' => 'Their stage', 'team_id' => $other->id]);

    [$token, $board] = kanbanPage('/boardteam/mine/tasks');

    expect(kanbanTitles($board, 'todo'))->toBe(['Ours']);

    kanbanMove($token, $theirs, ['column' => 'doing'], panel: 'boardteam/mine')->assertNotFound();
    kanbanMove($token, $ours, ['column' => 'todo', 'after' => $theirs->id], panel: 'boardteam/mine')->assertUnprocessable();

    [$token, $stages] = kanbanPage('/boardteam/mine/tasks/stages');

    expect(collect($stages['columns'])->pluck('label')->all())->toBe(['Our stage']);

    kanbanMove($token, $ours, ['column' => (string) $theirStage->id], panel: 'boardteam/mine')->assertUnprocessable();
    kanbanMove($token, $ours, ['column' => (string) $ourStage->id], panel: 'boardteam/mine')->assertOk();

    expect($theirs->refresh()->status)->toBe(TaskStatus::Todo)
        ->and($ours->refresh()->stage_id)->toBe($ourStage->id);
});

it('takes columns from a closure, enum cases or plain values', function (): void {
    kanbanPanel();
    $widget = TeamBoardWidget::make()->columnsUsing(fn (): array => [
        AlfaSix\AlfakitKanban\Column::make('backlog')->label('Backlog')->wipLimit(0),
        TaskStatus::Done,
        'archived',
        'backlog',
    ]);

    $columns = $widget->getKanbanColumns();

    expect(array_map(fn ($column) => $column->getKey(), $columns))->toBe(['backlog', 'done', 'archived'])
        ->and($columns[0]->getWipLimit())->toBeNull()
        ->and($columns[1]->getLabel())->toBe('Done')
        ->and($columns[1]->getColor())->toBe('success')
        ->and($columns[2]->getLabel())->toBe('Archived')
        ->and(AlfaSix\AlfakitKanban\Column::make('x')->color('javascript:alert(1)')->getColor())->toBeNull()
        ->and(AlfaSix\AlfakitKanban\Column::make('x')->color('#f59e0b')->getColor())->toBe('#f59e0b');
});

it('marks a date-only due date overdue only after that day', function (): void {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-10-25 15:00:00'));

    expect(AlfaSix\AlfakitKanban\Card::make(new Task)->dueDate('2026-10-25')->isOverdue())->toBeFalse()
        ->and(AlfaSix\AlfakitKanban\Card::make(new Task)->dueDate('2026-10-24')->isOverdue())->toBeTrue()
        ->and(AlfaSix\AlfakitKanban\Card::make(new Task)->dueDate('2026-10-25 09:00:00')->isOverdue())->toBeTrue()
        ->and(AlfaSix\AlfakitKanban\Card::make(new Task)->dueDate('2026-10-30')->overdue()->isOverdue())->toBeTrue()
        ->and(AlfaSix\AlfakitKanban\Card::make(new Task)->isOverdue())->toBeFalse();
});
