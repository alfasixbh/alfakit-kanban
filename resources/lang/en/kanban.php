<?php

declare(strict_types=1);

return [

    // The board page's default title; :label is the resource's plural model label.
    'page' => [
        'title' => ':label board',
    ],

    'validation' => [
        'unknown_column' => 'That column does not exist. Reload the board and try again.',
        'transition' => ':title cannot be moved from :from to :to.',
        'wip_limit' => ':column is full: it holds at most :limit cards.',
        'lane' => 'Cards cannot be moved to another row.',
        'unknown_lane' => 'That row no longer exists. Reload the board and try again.',
        'stale' => 'The board changed in the meantime. Reload it and try again.',
    ],

    'lanes' => [
        'none' => 'Unassigned',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Kanban board',
        'loading' => 'Loading board',
        'search' => 'Search cards',
        'search_placeholder' => 'Search…',
        'filters' => 'Filters',
        'reset_filters' => 'Reset',
        'count' => '{1} :count card|[0,*] :count cards',
        'count_filtered' => ':shown of :count cards',
        'wip' => ':count of :limit',
        'wip_over' => '{1} Over the limit of :limit card|[0,*] Over the limit of :limit cards',
        'wip_full' => 'This column is full',
        'collapse' => 'Collapse :column',
        'expand' => 'Expand :column',
        'add_card' => 'Add a card to :column',
        'empty' => 'No cards',
        'no_results' => 'No cards match',
        'more_cards' => '+:count more',
        'due' => 'Due :date',
        'overdue' => 'Overdue',
        'progress' => ':percent complete',
        'assignees' => 'Assigned to :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Collapse row :lane',
        'expand_lane' => 'Expand row :lane',
        'instructions' => 'Press Space to pick up a card. While it is picked up, use the arrow keys to move it, Space to drop it and Escape to cancel.',
        'picked_up' => ':title picked up in :column, position :position of :count.',
        'moved' => ':title is in :column, position :position of :count.',
        'dropped' => ':title dropped in :column, position :position of :count.',
        'cancelled' => 'Move cancelled. :title is back in :column.',
        'not_allowed' => ':title cannot be moved to :column.',
        'move_failed' => 'The card could not be moved.',
        'forbidden' => 'You are not allowed to move this card.',
        'load_failed' => 'The board could not be loaded.',
    ],

];
