<?php

declare(strict_types=1);

return [

    'validation' => [
        'unknown_column' => 'Cette colonne n\'existe pas. Rechargez le tableau et réessayez.',
        'transition' => ':title ne peut pas être déplacé de :from vers :to.',
        'wip_limit' => ':column est pleine : elle contient au plus :limit cartes.',
        'lane' => 'Les cartes ne peuvent pas être déplacées vers une autre ligne.',
        'unknown_lane' => 'Cette ligne n\'existe plus. Rechargez le tableau et réessayez.',
        'stale' => 'Le tableau a changé entre-temps. Rechargez-le et réessayez.',
    ],

    'lanes' => [
        'none' => 'Non attribué',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Tableau Kanban',
        'loading' => 'Chargement du tableau',
        'search' => 'Rechercher des cartes',
        'search_placeholder' => 'Rechercher…',
        'filters' => 'Filtres',
        'reset_filters' => 'Réinitialiser',
        'count' => ':count cartes',
        'count_filtered' => ':shown sur :count cartes',
        'wip' => ':count sur :limit',
        'wip_over' => 'Au-delà de la limite de :limit cartes',
        'wip_full' => 'Cette colonne est pleine',
        'collapse' => 'Replier :column',
        'expand' => 'Déplier :column',
        'add_card' => 'Ajouter une carte à :column',
        'empty' => 'Aucune carte',
        'no_results' => 'Aucune carte ne correspond',
        'more_cards' => '+:count de plus',
        'due' => 'Échéance : :date',
        'overdue' => 'En retard',
        'progress' => ':percent terminé',
        'assignees' => 'Attribué à :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Replier la ligne :lane',
        'expand_lane' => 'Déplier la ligne :lane',
        'instructions' => 'Appuyez sur Espace pour prendre une carte. Ensuite, déplacez-la avec les flèches, déposez-la avec Espace ou annulez avec Échap.',
        'picked_up' => ':title pris dans :column, position :position sur :count.',
        'moved' => ':title est dans :column, position :position sur :count.',
        'dropped' => ':title déposé dans :column, position :position sur :count.',
        'cancelled' => 'Déplacement annulé. :title est de retour dans :column.',
        'not_allowed' => ':title ne peut pas être déplacé vers :column.',
        'move_failed' => 'La carte n\'a pas pu être déplacée.',
        'forbidden' => 'Vous n\'êtes pas autorisé à déplacer cette carte.',
        'load_failed' => 'Le tableau n\'a pas pu être chargé.',
    ],

];
