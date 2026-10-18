<?php

declare(strict_types=1);

return [

    'validation' => [
        'unknown_column' => 'Bu sütun yok. Panoyu yeniden yükleyip tekrar deneyin.',
        'transition' => ':title, :from sütunundan :to sütununa taşınamaz.',
        'wip_limit' => ':column dolu: en fazla :limit kart alabilir.',
        'lane' => 'Kartlar başka bir satıra taşınamaz.',
        'unknown_lane' => 'Bu satır artık yok. Panoyu yeniden yükleyip tekrar deneyin.',
        'stale' => 'Pano bu arada değişti. Yeniden yükleyip tekrar deneyin.',
    ],

    'lanes' => [
        'none' => 'Atanmamış',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'Kanban panosu',
        'loading' => 'Pano yükleniyor',
        'search' => 'Kartlarda ara',
        'search_placeholder' => 'Ara…',
        'filters' => 'Filtreler',
        'reset_filters' => 'Sıfırla',
        'count' => ':count kart',
        'count_filtered' => ':shown / :count kart',
        'wip' => ':count / :limit',
        'wip_over' => ':limit kart sınırının üzerinde',
        'wip_full' => 'Bu sütun dolu',
        'collapse' => ':column daralt',
        'expand' => ':column genişlet',
        'add_card' => ':column sütununa kart ekle',
        'empty' => 'Kart yok',
        'no_results' => 'Eşleşen kart yok',
        'more_cards' => '+:count daha',
        'due' => 'Son tarih :date',
        'overdue' => 'Gecikmiş',
        'progress' => ':percent tamamlandı',
        'assignees' => ':names kişisine atandı',
        'more_assignees' => '+:count',
        'collapse_lane' => ':lane satırını daralt',
        'expand_lane' => ':lane satırını genişlet',
        'instructions' => 'Bir kartı almak için Boşluk tuşuna basın. Ardından ok tuşlarıyla taşıyın, Boşluk ile bırakın veya Escape ile iptal edin.',
        'picked_up' => ':title, :column sütununda alındı, konum :position / :count.',
        'moved' => ':title, :column sütununda, konum :position / :count.',
        'dropped' => ':title, :column sütununa bırakıldı, konum :position / :count.',
        'cancelled' => 'Taşıma iptal edildi. :title yeniden :column sütununda.',
        'not_allowed' => ':title, :column sütununa taşınamaz.',
        'move_failed' => 'Kart taşınamadı.',
        'forbidden' => 'Bu kartı taşıma izniniz yok.',
        'load_failed' => 'Pano yüklenemedi.',
    ],

];
