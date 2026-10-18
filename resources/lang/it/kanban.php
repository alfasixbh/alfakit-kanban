<?php

declare(strict_types=1);

return [

    'validation' => [
        'unknown_column' => 'Questa colonna non esiste. Ricarica la bacheca e riprova.',
        'transition' => ':title non può essere spostato da :from a :to.',
        'wip_limit' => ':column è piena: contiene al massimo :limit schede.',
        'lane' => 'Le schede non possono essere spostate in un\'altra riga.',
        'unknown_lane' => 'Questa riga non esiste più. Ricarica la bacheca e riprova.',
        'stale' => 'La bacheca è cambiata nel frattempo. Ricaricala e riprova.',
    ],

    'lanes' => [
        'none' => 'Non assegnato',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Bacheca Kanban',
        'loading' => 'Caricamento della bacheca',
        'search' => 'Cerca schede',
        'search_placeholder' => 'Cerca…',
        'filters' => 'Filtri',
        'reset_filters' => 'Reimposta',
        'count' => ':count schede',
        'count_filtered' => ':shown di :count schede',
        'wip' => ':count di :limit',
        'wip_over' => 'Oltre il limite di :limit schede',
        'wip_full' => 'Questa colonna è piena',
        'collapse' => 'Comprimi :column',
        'expand' => 'Espandi :column',
        'add_card' => 'Aggiungi una scheda a :column',
        'empty' => 'Nessuna scheda',
        'no_results' => 'Nessuna scheda corrispondente',
        'more_cards' => '+:count altre',
        'due' => 'Scadenza :date',
        'overdue' => 'In ritardo',
        'progress' => ':percent completato',
        'assignees' => 'Assegnata a :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Comprimi la riga :lane',
        'expand_lane' => 'Espandi la riga :lane',
        'instructions' => 'Premi Spazio per prendere una scheda. Poi spostala con le frecce, rilasciala con Spazio o annulla con Esc.',
        'picked_up' => ':title preso in :column, posizione :position di :count.',
        'moved' => ':title è in :column, posizione :position di :count.',
        'dropped' => ':title rilasciato in :column, posizione :position di :count.',
        'cancelled' => 'Spostamento annullato. :title è di nuovo in :column.',
        'not_allowed' => ':title non può essere spostato in :column.',
        'move_failed' => 'Impossibile spostare la scheda.',
        'forbidden' => 'Non hai il permesso di spostare questa scheda.',
        'load_failed' => 'Impossibile caricare la bacheca.',
    ],

];
