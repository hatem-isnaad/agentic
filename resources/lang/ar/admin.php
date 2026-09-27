<?php

return [
    'app_title' => 'لوحة Agentic',

    'nav' => [
        'dashboard' => 'نظرة عامة',
        'agents' => 'الوكلاء',
        'skills' => 'المهارات',
        'tools' => 'الأدوات',
        'knowledge' => 'المعرفة',
        'executions' => 'التشغيلات',
        'conversations' => 'المحادثات',
    ],

    'locale' => [
        'label' => 'اللغة',
        'en' => 'English',
        'ar' => 'العربية',
        'switch' => 'تغيير اللغة',
    ],

    'actions' => [
        'save' => 'حفظ',
        'update' => 'تحديث',
        'delete' => 'حذف',
        'edit' => 'تعديل',
        'view' => 'عرض',
        'create' => 'إنشاء',
        'cancel' => 'إلغاء',
        'index_documents' => 'فهرسة المستندات',
        'confirm_delete_agent' => 'حذف هذا الوكيل؟',
    ],

    'status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
    ],

    'fields' => [
        'name' => 'الاسم',
        'slug' => 'المعرّف (slug)',
        'description' => 'الوصف',
        'instructions' => 'التعليمات',
        'status' => 'الحالة',
        'provider' => 'مزود النموذج',
        'model' => 'النموذج',
        'skills' => 'المهارات (معرّفات مفصولة بفاصلة)',
        'tools' => 'الأدوات (معرّفات مفصولة بفاصلة)',
        'driver' => 'المحرّك',
    ],

    'empty' => [
        'agents' => 'لا يوجد وكلاء بعد.',
        'skills' => 'لا توجد مهارات بعد.',
        'tools' => 'لا توجد أدوات بعد.',
        'knowledge' => 'لا توجد مصادر معرفة بعد.',
        'executions' => 'لا توجد تشغيلات بعد.',
        'conversations' => 'لا توجد محادثات بعد.',
    ],

    'table' => [
        'name' => 'الاسم',
        'slug' => 'المعرّف',
        'status' => 'الحالة',
        'driver' => 'المحرّك',
        'agent' => 'الوكيل',
        'user' => 'المستخدم',
        'started' => 'بدء',
        'updated' => 'آخر تحديث',
        'id' => 'المعرّف',
    ],

    'dashboard' => [
        'title' => 'لوحة Agentic',
        'heading' => 'إدارة Agentic (واجهة مؤقتة)',
        'intro' => 'استبدل هذه الواجهات بلوحة التحكم في تطبيقك.',
        'stats' => [
            'agents' => 'الوكلاء',
            'skills' => 'المهارات',
            'tools' => 'الأدوات',
            'knowledge_sources' => 'مصادر المعرفة',
            'executions' => 'التشغيلات',
            'conversations' => 'المحادثات',
        ],
    ],

    'agents' => [
        'title' => 'الوكلاء',
        'create' => 'إنشاء وكيل',
        'create_heading' => 'إنشاء وكيل',
        'edit_heading' => 'تعديل :name',
    ],

    'skills' => [
        'title' => 'المهارات',
        'create' => 'إنشاء مهارة',
        'create_heading' => 'إنشاء مهارة',
        'edit_heading' => 'تعديل :name',
    ],

    'tools' => [
        'title' => 'الأدوات',
        'create' => 'إنشاء أداة',
        'create_heading' => 'إنشاء أداة',
        'edit_heading' => 'تعديل :name',
    ],

    'knowledge' => [
        'title' => 'مصادر المعرفة',
        'create' => 'إنشاء مصدر',
        'create_heading' => 'إنشاء مصدر معرفة',
        'edit_heading' => 'تعديل :name',
    ],

    'executions' => [
        'title' => 'التشغيلات',
        'show_title' => 'تشغيل',
    ],

    'conversations' => [
        'title' => 'المحادثات',
        'show_title' => 'محادثة',
    ],

    'flash' => [
        'agent_created' => 'تم إنشاء الوكيل.',
        'agent_updated' => 'تم تحديث الوكيل.',
        'agent_deleted' => 'تم حذف الوكيل.',
        'skill_created' => 'تم إنشاء المهارة.',
        'skill_updated' => 'تم تحديث المهارة.',
        'skill_deleted' => 'تم حذف المهارة.',
        'tool_created' => 'تم إنشاء الأداة.',
        'tool_updated' => 'تم تحديث الأداة.',
        'tool_deleted' => 'تم حذف الأداة.',
        'knowledge_created' => 'تم إنشاء مصدر المعرفة.',
        'knowledge_updated' => 'تم تحديث مصدر المعرفة.',
        'knowledge_deleted' => 'تم حذف مصدر المعرفة.',
        'knowledge_indexed' => 'تمت فهرسة مصدر المعرفة.',
        'locale_updated' => 'تم تحديث اللغة.',
    ],
];
