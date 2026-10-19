<?php

declare(strict_types=1);

return [

    'page' => [
        'title' => 'Board: :label',
    ],

    'validation' => [
        'unknown_column' => 'Diese Spalte existiert nicht. Laden Sie das Board neu und versuchen Sie es erneut.',
        'transition' => ':title kann nicht von :from nach :to verschoben werden.',
        'wip_limit' => ':column ist voll: Sie fasst höchstens :limit Karten.',
        'lane' => 'Karten können nicht in eine andere Zeile verschoben werden.',
        'unknown_lane' => 'Diese Zeile existiert nicht mehr. Laden Sie das Board neu und versuchen Sie es erneut.',
        'stale' => 'Das Board wurde zwischenzeitlich geändert. Laden Sie es neu und versuchen Sie es erneut.',
    ],

    'lanes' => [
        'none' => 'Nicht zugewiesen',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Kanban-Board',
        'loading' => 'Board wird geladen',
        'search' => 'Karten durchsuchen',
        'search_placeholder' => 'Suchen…',
        'filters' => 'Filter',
        'reset_filters' => 'Zurücksetzen',
        'count' => ':count Karten',
        'count_filtered' => ':shown von :count Karten',
        'wip' => ':count von :limit',
        'wip_over' => 'Über dem Limit von :limit Karten',
        'wip_full' => 'Diese Spalte ist voll',
        'collapse' => ':column einklappen',
        'expand' => ':column ausklappen',
        'add_card' => 'Karte zu :column hinzufügen',
        'empty' => 'Keine Karten',
        'no_results' => 'Keine passenden Karten',
        'more_cards' => '+:count weitere',
        'due' => 'Fällig am :date',
        'overdue' => 'Überfällig',
        'progress' => ':percent erledigt',
        'assignees' => 'Zugewiesen an :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Zeile :lane einklappen',
        'expand_lane' => 'Zeile :lane ausklappen',
        'instructions' => 'Drücken Sie die Leertaste, um eine Karte aufzunehmen. Bewegen Sie sie dann mit den Pfeiltasten, legen Sie sie mit der Leertaste ab oder brechen Sie mit Escape ab.',
        'picked_up' => ':title in :column aufgenommen, Position :position von :count.',
        'moved' => ':title ist in :column, Position :position von :count.',
        'dropped' => ':title in :column abgelegt, Position :position von :count.',
        'cancelled' => 'Verschieben abgebrochen. :title ist wieder in :column.',
        'not_allowed' => ':title kann nicht nach :column verschoben werden.',
        'move_failed' => 'Die Karte konnte nicht verschoben werden.',
        'forbidden' => 'Sie dürfen diese Karte nicht verschieben.',
        'load_failed' => 'Das Board konnte nicht geladen werden.',
    ],

];
