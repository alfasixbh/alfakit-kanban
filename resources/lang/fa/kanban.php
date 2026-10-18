<?php

declare(strict_types=1);

return [

    'validation' => [
        'unknown_column' => 'این ستون وجود ندارد. تابلو را دوباره بارگذاری کنید و دوباره تلاش کنید.',
        'transition' => ':title را نمی‌توان از :from به :to منتقل کرد.',
        'wip_limit' => ':column پر است: حداکثر :limit کارت جا می‌گیرد.',
        'lane' => 'کارت‌ها را نمی‌توان به ردیف دیگری منتقل کرد.',
        'unknown_lane' => 'این ردیف دیگر وجود ندارد. تابلو را دوباره بارگذاری کنید و دوباره تلاش کنید.',
        'stale' => 'تابلو در این فاصله تغییر کرده است. آن را دوباره بارگذاری کنید و دوباره تلاش کنید.',
    ],

    'lanes' => [
        'none' => 'تخصیص‌نیافته',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'تابلوی کانبان',
        'loading' => 'در حال بارگذاری تابلو',
        'search' => 'جستجوی کارت‌ها',
        'search_placeholder' => 'جستجو…',
        'filters' => 'فیلترها',
        'reset_filters' => 'بازنشانی',
        'count' => ':count کارت',
        'count_filtered' => ':shown از :count کارت',
        'wip' => ':count از :limit',
        'wip_over' => 'بیش از سقف :limit کارت',
        'wip_full' => 'این ستون پر است',
        'collapse' => 'جمع کردن :column',
        'expand' => 'باز کردن :column',
        'add_card' => 'افزودن کارت به :column',
        'empty' => 'کارتی نیست',
        'no_results' => 'کارتی مطابقت ندارد',
        'more_cards' => '+:count مورد دیگر',
        'due' => 'سررسید :date',
        'overdue' => 'دیرکرد',
        'progress' => ':percent انجام شده',
        'assignees' => 'واگذار شده به :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'جمع کردن ردیف :lane',
        'expand_lane' => 'باز کردن ردیف :lane',
        'instructions' => 'برای برداشتن کارت کلید فاصله را بزنید. سپس آن را با کلیدهای جهت‌نما جابه‌جا کنید، با فاصله رها کنید یا با Escape لغو کنید.',
        'picked_up' => ':title در :column برداشته شد، جایگاه :position از :count.',
        'moved' => ':title در :column است، جایگاه :position از :count.',
        'dropped' => ':title در :column رها شد، جایگاه :position از :count.',
        'cancelled' => 'جابه‌جایی لغو شد. :title به :column برگشت.',
        'not_allowed' => ':title را نمی‌توان به :column منتقل کرد.',
        'move_failed' => 'کارت جابه‌جا نشد.',
        'forbidden' => 'اجازهٔ جابه‌جایی این کارت را ندارید.',
        'load_failed' => 'تابلو بارگذاری نشد.',
    ],

];
