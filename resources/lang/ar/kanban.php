<?php

declare(strict_types=1);

return [

    'validation' => [
        'unknown_column' => 'هذا العمود غير موجود. أعد تحميل اللوحة وحاول مرة أخرى.',
        'transition' => 'لا يمكن نقل :title من :from إلى :to.',
        'wip_limit' => 'العمود :column ممتلئ: يتسع لـ :limit بطاقات على الأكثر.',
        'lane' => 'لا يمكن نقل البطاقات إلى صف آخر.',
        'unknown_lane' => 'هذا الصف لم يعد موجودًا. أعد تحميل اللوحة وحاول مرة أخرى.',
        'stale' => 'تغيّرت اللوحة في هذه الأثناء. أعد تحميلها وحاول مرة أخرى.',
    ],

    'lanes' => [
        'none' => 'غير مُسند',
    ],

    // Shared with the renderers as `kanban.*` (see KanbanPlugin::register()).
    'ui' => [
        'label' => 'لوحة كانبان',
        'loading' => 'جارٍ تحميل اللوحة',
        'search' => 'البحث في البطاقات',
        'search_placeholder' => 'بحث…',
        'filters' => 'عوامل التصفية',
        'reset_filters' => 'إعادة تعيين',
        'count' => 'عدد البطاقات: :count',
        'count_filtered' => ':shown من :count بطاقة',
        'wip' => ':count من :limit',
        'wip_over' => 'تجاوز الحد البالغ :limit بطاقات',
        'wip_full' => 'هذا العمود ممتلئ',
        'collapse' => 'طي :column',
        'expand' => 'توسيع :column',
        'add_card' => 'إضافة بطاقة إلى :column',
        'empty' => 'لا توجد بطاقات',
        'no_results' => 'لا توجد بطاقات مطابقة',
        'more_cards' => '+:count أخرى',
        'due' => 'تاريخ الاستحقاق :date',
        'overdue' => 'متأخرة',
        'progress' => 'اكتمل :percent',
        'assignees' => 'مُسندة إلى :names',
        'more_assignees' => '+:count',
        'collapse_lane' => 'طي الصف :lane',
        'expand_lane' => 'توسيع الصف :lane',
        'instructions' => 'اضغط مفتاح المسافة لالتقاط بطاقة. ثم حرّكها بمفاتيح الأسهم، وأفلتها بمفتاح المسافة أو ألغِ بمفتاح Escape.',
        'picked_up' => 'تم التقاط :title في :column، الموضع :position من :count.',
        'moved' => ':title في :column، الموضع :position من :count.',
        'dropped' => 'تم إفلات :title في :column، الموضع :position من :count.',
        'cancelled' => 'أُلغي النقل. عادت :title إلى :column.',
        'not_allowed' => 'لا يمكن نقل :title إلى :column.',
        'move_failed' => 'تعذّر نقل البطاقة.',
        'forbidden' => 'غير مسموح لك بنقل هذه البطاقة.',
        'load_failed' => 'تعذّر تحميل اللوحة.',
    ],

];
