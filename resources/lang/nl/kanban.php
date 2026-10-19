<?php

declare(strict_types=1);

return [

    'page' => [
        'title' => 'Bord: :label',
    ],

    'validation' => [
        'unknown_column' => 'Die kolom bestaat niet. Laad het bord opnieuw en probeer het nog eens.',
        'transition' => ':title kan niet van :from naar :to worden verplaatst.',
        'wip_limit' => ':column is vol: er passen maximaal :limit kaarten in.',
        'lane' => 'Kaarten kunnen niet naar een andere rij worden verplaatst.',
        'unknown_lane' => 'Die rij bestaat niet meer. Laad het bord opnieuw en probeer het nog eens.',
        'stale' => 'Het bord is intussen gewijzigd. Laad het opnieuw en probeer het nog eens.',
    ],

    'lanes' => [
        'none' => 'Niet toegewezen',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Kanbanbord',
        'loading' => 'Bord laden',
        'search' => 'Kaarten zoeken',
        'search_placeholder' => 'Zoeken…',
        'filters' => 'Filters',
        'reset_filters' => 'Herstellen',
        'count' => ':count kaarten',
        'count_filtered' => ':shown van :count kaarten',
        'wip' => ':count van :limit',
        'wip_over' => 'Boven de limiet van :limit kaarten',
        'wip_full' => 'Deze kolom is vol',
        'collapse' => ':column inklappen',
        'expand' => ':column uitklappen',
        'add_card' => 'Kaart toevoegen aan :column',
        'empty' => 'Geen kaarten',
        'no_results' => 'Geen overeenkomende kaarten',
        'more_cards' => '+:count meer',
        'due' => 'Vervalt op :date',
        'overdue' => 'Achterstallig',
        'progress' => ':percent voltooid',
        'assignees' => 'Toegewezen aan :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Rij :lane inklappen',
        'expand_lane' => 'Rij :lane uitklappen',
        'instructions' => 'Druk op Spatie om een kaart op te pakken. Verplaats hem daarna met de pijltjestoetsen, zet hem neer met Spatie of annuleer met Escape.',
        'picked_up' => ':title opgepakt in :column, positie :position van :count.',
        'moved' => ':title staat in :column, positie :position van :count.',
        'dropped' => ':title neergezet in :column, positie :position van :count.',
        'cancelled' => 'Verplaatsen geannuleerd. :title staat weer in :column.',
        'not_allowed' => ':title kan niet naar :column worden verplaatst.',
        'move_failed' => 'De kaart kon niet worden verplaatst.',
        'forbidden' => 'Je mag deze kaart niet verplaatsen.',
        'load_failed' => 'Het bord kon niet worden geladen.',
    ],

];
