<?php

declare(strict_types=1);

return [

    'page' => [
        'title' => 'Доска: :label',
    ],

    'validation' => [
        'unknown_column' => 'Такой колонки нет. Перезагрузите доску и попробуйте снова.',
        'transition' => 'Нельзя переместить :title из :from в :to.',
        'wip_limit' => 'Колонка :column заполнена: в ней не более :limit карточек.',
        'lane' => 'Карточки нельзя перемещать в другую строку.',
        'unknown_lane' => 'Этой строки больше нет. Перезагрузите доску и попробуйте снова.',
        'stale' => 'Доска за это время изменилась. Перезагрузите её и попробуйте снова.',
    ],

    'lanes' => [
        'none' => 'Не назначено',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Канбан-доска',
        'loading' => 'Загрузка доски',
        'search' => 'Поиск карточек',
        'search_placeholder' => 'Поиск…',
        'filters' => 'Фильтры',
        'reset_filters' => 'Сбросить',
        'count' => 'Карточек: :count',
        'count_filtered' => ':shown из :count карточек',
        'wip' => ':count из :limit',
        'wip_over' => 'Превышен лимит в :limit карточек',
        'wip_full' => 'Колонка заполнена',
        'collapse' => 'Свернуть :column',
        'expand' => 'Развернуть :column',
        'add_card' => 'Добавить карточку в :column',
        'empty' => 'Нет карточек',
        'no_results' => 'Нет подходящих карточек',
        'more_cards' => 'Ещё :count',
        'due' => 'Срок: :date',
        'overdue' => 'Просрочено',
        'progress' => 'Выполнено :percent',
        'assignees' => 'Исполнители: :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Свернуть строку :lane',
        'expand_lane' => 'Развернуть строку :lane',
        'instructions' => 'Нажмите Пробел, чтобы взять карточку. Затем перемещайте её стрелками, отпустите Пробелом или отмените клавишей Escape.',
        'picked_up' => ':title взята в колонке :column, позиция :position из :count.',
        'moved' => ':title в колонке :column, позиция :position из :count.',
        'dropped' => ':title перемещена в :column, позиция :position из :count.',
        'cancelled' => 'Перемещение отменено. :title снова в колонке :column.',
        'not_allowed' => 'Нельзя переместить :title в :column.',
        'move_failed' => 'Не удалось переместить карточку.',
        'forbidden' => 'У вас нет прав перемещать эту карточку.',
        'load_failed' => 'Не удалось загрузить доску.',
    ],

];
