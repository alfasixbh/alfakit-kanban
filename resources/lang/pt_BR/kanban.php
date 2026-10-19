<?php

declare(strict_types=1);

return [

    'page' => [
        'title' => 'Quadro: :label',
    ],

    'validation' => [
        'unknown_column' => 'Essa coluna não existe. Recarregue o quadro e tente novamente.',
        'transition' => ':title não pode ser movido de :from para :to.',
        'wip_limit' => ':column está cheia: comporta no máximo :limit cartões.',
        'lane' => 'Os cartões não podem ser movidos para outra linha.',
        'unknown_lane' => 'Essa linha não existe mais. Recarregue o quadro e tente novamente.',
        'stale' => 'O quadro mudou nesse meio tempo. Recarregue-o e tente novamente.',
    ],

    'lanes' => [
        'none' => 'Não atribuído',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Quadro Kanban',
        'loading' => 'Carregando quadro',
        'search' => 'Pesquisar cartões',
        'search_placeholder' => 'Pesquisar…',
        'filters' => 'Filtros',
        'reset_filters' => 'Redefinir',
        'count' => ':count cartões',
        'count_filtered' => ':shown de :count cartões',
        'wip' => ':count de :limit',
        'wip_over' => 'Acima do limite de :limit cartões',
        'wip_full' => 'Esta coluna está cheia',
        'collapse' => 'Recolher :column',
        'expand' => 'Expandir :column',
        'add_card' => 'Adicionar um cartão a :column',
        'empty' => 'Nenhum cartão',
        'no_results' => 'Nenhum cartão corresponde',
        'more_cards' => '+:count mais',
        'due' => 'Vence em :date',
        'overdue' => 'Atrasado',
        'progress' => ':percent concluído',
        'assignees' => 'Atribuído a :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'Recolher a linha :lane',
        'expand_lane' => 'Expandir a linha :lane',
        'instructions' => 'Pressione Espaço para pegar um cartão. Depois, mova-o com as setas, solte-o com Espaço ou cancele com Esc.',
        'picked_up' => ':title pego em :column, posição :position de :count.',
        'moved' => ':title está em :column, posição :position de :count.',
        'dropped' => ':title solto em :column, posição :position de :count.',
        'cancelled' => 'Movimento cancelado. :title voltou para :column.',
        'not_allowed' => ':title não pode ser movido para :column.',
        'move_failed' => 'Não foi possível mover o cartão.',
        'forbidden' => 'Você não tem permissão para mover este cartão.',
        'load_failed' => 'Não foi possível carregar o quadro.',
    ],

];
