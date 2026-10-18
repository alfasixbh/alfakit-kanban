<?php

declare(strict_types=1);

return [

    'validation' => [
        'unknown_column' => '该列不存在。请重新加载看板后重试。',
        'transition' => '无法将 :title 从 :from 移动到 :to。',
        'wip_limit' => ':column 已满：最多容纳 :limit 张卡片。',
        'lane' => '卡片不能移动到其他行。',
        'unknown_lane' => '该行已不存在。请重新加载看板后重试。',
        'stale' => '看板已发生变化。请重新加载后重试。',
    ],

    'lanes' => [
        'none' => '未分配',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => '看板',
        'loading' => '正在加载看板',
        'search' => '搜索卡片',
        'search_placeholder' => '搜索…',
        'filters' => '筛选',
        'reset_filters' => '重置',
        'count' => ':count 张卡片',
        'count_filtered' => ':shown / :count 张卡片',
        'wip' => ':count / :limit',
        'wip_over' => '超出 :limit 张卡片的上限',
        'wip_full' => '此列已满',
        'collapse' => '折叠 :column',
        'expand' => '展开 :column',
        'add_card' => '向 :column 添加卡片',
        'empty' => '暂无卡片',
        'no_results' => '没有匹配的卡片',
        'more_cards' => '还有 :count 张',
        'due' => '截止 :date',
        'overdue' => '已逾期',
        'progress' => '已完成 :percent',
        'assignees' => '分配给 :names',
        'more_assignees' => '+:count',
        'collapse_lane' => '折叠行 :lane',
        'expand_lane' => '展开行 :lane',
        'instructions' => '按空格键拿起卡片。拿起后，用方向键移动，按空格键放下，或按 Esc 取消。',
        'picked_up' => '已在 :column 中拿起 :title，位置 :position / :count。',
        'moved' => ':title 位于 :column，位置 :position / :count。',
        'dropped' => '已将 :title 放入 :column，位置 :position / :count。',
        'cancelled' => '已取消移动。:title 已回到 :column。',
        'not_allowed' => '无法将 :title 移动到 :column。',
        'move_failed' => '无法移动卡片。',
        'forbidden' => '你无权移动此卡片。',
        'load_failed' => '无法加载看板。',
    ],

];
