<?php

declare(strict_types=1);

return [

    'validation' => [
        'unknown_column' => 'その列は存在しません。ボードを再読み込みしてもう一度お試しください。',
        'transition' => ':title を :from から :to に移動することはできません。',
        'wip_limit' => ':column はいっぱいです（最大 :limit 枚）。',
        'lane' => 'カードを別の行に移動することはできません。',
        'unknown_lane' => 'その行はもう存在しません。ボードを再読み込みしてもう一度お試しください。',
        'stale' => 'その間にボードが変更されました。再読み込みしてもう一度お試しください。',
    ],

    'lanes' => [
        'none' => '未割り当て',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'かんばんボード',
        'loading' => 'ボードを読み込み中',
        'search' => 'カードを検索',
        'search_placeholder' => '検索…',
        'filters' => 'フィルター',
        'reset_filters' => 'リセット',
        'count' => ':count 枚のカード',
        'count_filtered' => ':count 枚中 :shown 枚',
        'wip' => ':limit 枚中 :count 枚',
        'wip_over' => '上限 :limit 枚を超えています',
        'wip_full' => 'この列はいっぱいです',
        'collapse' => ':column を折りたたむ',
        'expand' => ':column を展開',
        'add_card' => ':column にカードを追加',
        'empty' => 'カードはありません',
        'no_results' => '一致するカードはありません',
        'more_cards' => 'ほか :count 枚',
        'due' => '期限 :date',
        'overdue' => '期限切れ',
        'progress' => ':percent 完了',
        'assignees' => '担当: :names',
        'more_assignees' => '+:count',
        'collapse_lane' => '行 :lane を折りたたむ',
        'expand_lane' => '行 :lane を展開',
        'instructions' => 'スペースキーでカードを持ち上げます。持ち上げたら矢印キーで移動し、スペースキーで置くか、Escape キーでキャンセルします。',
        'picked_up' => ':title を :column で持ち上げました（:count 枚中 :position 番目）。',
        'moved' => ':title は :column にあります（:count 枚中 :position 番目）。',
        'dropped' => ':title を :column に置きました（:count 枚中 :position 番目）。',
        'cancelled' => '移動をキャンセルしました。:title は :column に戻りました。',
        'not_allowed' => ':title は :column に移動できません。',
        'move_failed' => 'カードを移動できませんでした。',
        'forbidden' => 'このカードを移動する権限がありません。',
        'load_failed' => 'ボードを読み込めませんでした。',
    ],

];
