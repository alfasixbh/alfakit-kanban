<?php

declare(strict_types=1);

return [

    'page' => [
        'title' => 'Tablero: :label',
    ],

    'validation' => [
        'unknown_column' => 'Esa columna no existe. Recarga el tablero e inténtalo de nuevo.',
        'transition' => ':title no se puede mover de :from a :to.',
        'wip_limit' => ':column está llena: admite como máximo :limit tarjetas.',
        'lane' => 'Las tarjetas no se pueden mover a otra fila.',
        'unknown_lane' => 'Esa fila ya no existe. Recarga el tablero e inténtalo de nuevo.',
        'stale' => 'El tablero ha cambiado mientras tanto. Recárgalo e inténtalo de nuevo.',
    ],

    'lanes' => [
        'none' => 'Sin asignar',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Tablero Kanban',
        'loading' => 'Cargando tablero',
        'search' => 'Buscar tarjetas',
        'search_placeholder' => 'Buscar…',
        'filters' => 'Filtros',
        'reset_filters' => 'Restablecer',
        'count' => '{1} :count tarjeta|[0,*] :count tarjetas',
        'count_filtered' => ':shown de :count tarjetas',
        'wip' => ':count de :limit',
        'wip_over' => '{1} Por encima del límite de :limit tarjeta|[0,*] Por encima del límite de :limit tarjetas',
        'wip_full' => 'Esta columna está llena',
        'collapse' => 'Contraer :column',
        'expand' => 'Expandir :column',
        'add_card' => 'Añadir una tarjeta a :column',
        'empty' => 'No hay tarjetas',
        'no_results' => 'Ninguna tarjeta coincide',
        'more_cards' => '+:count más',
        'due' => 'Vence el :date',
        'overdue' => 'Vencida',
        'progress' => ':percent completado',
        'assignees' => 'Asignada a :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Contraer la fila :lane',
        'expand_lane' => 'Expandir la fila :lane',
        'instructions' => 'Pulsa Espacio para tomar una tarjeta. Después, muévela con las flechas, suéltala con Espacio o cancela con Escape.',
        'picked_up' => ':title tomada en :column, posición :position de :count.',
        'moved' => ':title está en :column, posición :position de :count.',
        'dropped' => ':title soltada en :column, posición :position de :count.',
        'cancelled' => 'Movimiento cancelado. :title vuelve a estar en :column.',
        'not_allowed' => ':title no se puede mover a :column.',
        'move_failed' => 'No se pudo mover la tarjeta.',
        'forbidden' => 'No tienes permiso para mover esta tarjeta.',
        'load_failed' => 'No se pudo cargar el tablero.',
    ],

];
