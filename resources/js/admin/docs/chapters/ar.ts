import type { DocChapterMap } from './types';

/** Arabic overlays — missing fields fall back to English in useDocChapter. */
export const chaptersAr: DocChapterMap = {
    install: {
        title: '01 · تثبيت Agentic',
        summary: 'أضف الحزمة إلى Laravel، انشر الإعدادات، نفّذ migrate، اضبط مفاتيح AI، وتحقق.',
        goal: 'تثبيت يعمل من الطرفية وملف .env — واجهة الإدارة اختيارية.',
        steps: [
            {
                title: 'أمر Composer واحد',
                body:
                    'نفّذ في جذر تطبيق Laravel (وليس داخل مجلد الحزمة). الحزمة تتضمن laravel/ai كاعتماد — لا تضف laravel/ai كحزمة منفصلة.',
            },
            {
                title: 'معالج التثبيت التفاعلي',
                body: 'php artisan agentic:install يطرح أسئلة (وضع النشر، المزود، النموذج، RAG) ويكتب .env. --quick لتخطي المعالج. docs/INSTALL_WIZARD.md',
            },
            {
                title: 'نشر إعداد Agentic و Laravel AI',
                body: 'config/agentic.php رفيع — الافتراضيات من vendor. ai-config لـ config/ai.php.',
            },
            {
                title: 'جداول قاعدة البيانات',
                body: 'migrate ينشئ جداول الوكلاء والمهارات والأدوات والمعرفة والمحادثات. في الاختبارات يمكن AGENTIC_*_DRIVER=memory.',
            },
            {
                title: 'ملفات واجهة الإدارة',
                body: 'انشر JS/CSS إلى public/vendor/agentic لتعمل /agentic/admin. أعد النشر بعد ترقية الحزمة.',
            },
            {
                title: 'البيئة والتحقق',
                body: 'انسخ من vendor/hatem-isnaad/agentic/.env.example. ابدأ بـ rag-validate --offline ثم بدون --offline بعد جاهزية المفاتيح أو Ollama.',
            },
        ],
        commands: [
            {
                command: 'composer require hatem-isnaad/agentic',
                title: 'تثبيت الحزمة',
                why: 'يسجّل مزود الخدمة ويثبّت laravel/ai تلقائياً.',
                note: 'مستودع path؟ أضف repositories في composer.json ثم require hatem-isnaad/agentic:@dev',
            },
            {
                command: 'php artisan agentic:install',
                title: 'معالج التثبيت + نشر الإعداد',
                why: 'إعداد .env تفاعلي ونشر config/agentic.php. بدون معالج: --quick.',
            },
            {
                command: 'php artisan vendor:publish --tag=ai-config',
                title: 'نشر إعداد Laravel AI',
                why: 'ينشئ config/ai.php لمفاتيح OPENAI وANTHROPIC وGEMINI وOLLAMA_URL.',
            },
            {
                command: 'php artisan migrate',
                title: 'تشغيل الترحيلات',
                why: 'يخزّن الوكلاء والمهارات والأدوات والمعرفة والمحادثات في قاعدة البيانات.',
            },
            {
                command: 'php artisan vendor:publish --tag=agentic-admin-assets --force',
                title: 'نشر ملفات واجهة الإدارة',
                why: 'تشغيل React في /agentic/admin (هذه الوثائق والوكلاء والمعرفة).',
            },
            {
                command: 'php artisan agentic:rag-validate --offline',
                title: 'اختبار RAG بدون APIs',
                why: 'تضمينات حتمية للتحقق من مخزن المتجهات — مناسب لـ CI.',
            },
            {
                command: 'php artisan agentic:rag-validate',
                title: 'اختبار RAG مع تضمينات حقيقية',
                why: 'بعد ضبط AGENTIC_KNOWLEDGE_EMBEDDING والمفاتيح.',
            },
        ],
        envExample: `AGENTIC_MODE=local
AGENTIC_AI_PROVIDER=ollama
AGENTIC_AI_MODEL=qwen3:8b
OLLAMA_URL=http://localhost:11434`,
        adminNote: 'ابدأ من START_HERE.md ثم DEVELOPER_HANDBOOK.md في vendor/hatem-isnaad/agentic/docs/',
    },
    environment: {
        title: '02 · وضع النشر والبيئة',
        summary: 'ابدأ بـ AGENTIC_MODE — مفتاح واحد للمسارات والمصادقة.',
        goal: 'تجنب عشرات مفاتيح AGENTIC_* المتعارضة.',
        steps: [
            { title: 'AGENTIC_MODE', body: 'local | production | widget — راجع الفصل بالإنجليزية للتفاصيل.' },
            { title: 'ملف الإعداد', body: 'config/agentic.php رفيع يدمج إعداد الحزمة من vendor' },
            { title: 'مسح الكاش', body: 'php artisan config:clear بعد .env' },
            { title: 'الدليل', body: 'DEVELOPER_HANDBOOK.md و INSTALL_WIZARD.md' },
        ],
    },
    ai: {
        title: '03 · مزود الذكاء والنماذج',
        summary: 'مزودو Laravel AI SDK؛ الافتراضيات للوكلاء.',
        goal: 'Ollama محلياً أو مفاتيح سحابية في الإنتاج.',
        steps: [
            { title: 'الافتراضيات', body: 'AGENTIC_AI_PROVIDER و AGENTIC_AI_MODEL' },
            { title: 'قائمة النماذج', body: 'config agentic.ai.providers' },
            { title: 'لكل وكيل', body: 'provider + model على سجل الوكيل' },
        ],
    },
    'code-tools': {
        title: '04 · أدوات PHP (code)',
        summary: 'منطق الأعمال في تطبيقك — اكتشاف تلقائي.',
        goal: 'أدوات مثل CheckStock بدون تسجيل يدوي.',
        steps: [
            { title: 'المسارات', body: 'code_tools.paths و namespace في config' },
            { title: 'إنشاء الصف', body: 'php artisan agentic:make-code-tool' },
            { title: 'المزامنة', body: 'php artisan agentic:code-tools-sync' },
            { title: 'النشر', body: 'POST /tools أو /code-handlers/publish' },
        ],
    },
    'http-tools': {
        title: '05 · أدوات HTTP',
        summary: 'استدعاء REST مع JSON Schema.',
        goal: 'ربط APIs خارجية بأمان.',
        steps: [
            { title: 'التعريف', body: 'method، url، input_schema' },
            { title: 'النشر', body: 'status published' },
            { title: 'الاختبار', body: 'من صفحة الأداة أو php artisan agentic:http-tool test' },
        ],
    },
    mcp: {
        title: '06 · أدوات MCP',
        summary: 'مزامنة من خوادم laravel/mcp.',
        goal: 'استخدام أدوات MCP داخل الوكلاء.',
        steps: [{ title: 'المزامنة', body: 'php artisan agentic:mcp-sync {server}' }],
    },
    skills: {
        title: '07 · المهارات',
        summary: 'تجميع أدوات ومعرفة للوكلاء.',
        goal: 'حزم قدرات قابلة لإعادة الاستخدام.',
        steps: [{ title: 'الإنشاء', body: 'POST /skills مع tools[] و knowledge[]' }],
    },
    agents: {
        title: '08 · الوكلاء',
        summary: 'شخصية + نموذج + مهارات + صلاحيات.',
        goal: 'وكيل منشور جاهز للويدجت أو execute.',
        steps: [{ title: 'الاختبار', body: 'POST /agents/{slug}/execute' }],
    },
    knowledge: {
        title: '09 · المعرفة (RAG)',
        summary: 'تخزين متجهات، إدخال، فهرسة، بحث.',
        goal: 'إجابات مستندة إلى مستنداتك.',
        steps: [{ title: 'الإدخال', body: 'POST .../ingest' }],
    },
    memories: {
        title: '10 · الذاكرة',
        summary: 'حقائق بنطاق user/agent/conversation.',
        goal: 'ذاكرة طويلة المدى في السياق.',
        steps: [{ title: 'API', body: 'GET|POST /memories' }],
    },
    permissions: {
        title: '11 · صلاحيات الأدوات',
        summary: 'سماح/رفض قبل تشغيل المحرك.',
        goal: 'تقييد الأدوات المسموحة.',
        steps: [{ title: 'الافتراضي', body: 'AGENTIC_PERMISSION_DEFAULT' }],
    },
    approvals: {
        title: '12 · موافقة بشرية',
        summary: 'إيقاف أدوات حساسة حتى الموافقة.',
        goal: 'موافقة على الكتابة/الحذف.',
        steps: [{ title: 'الأنماط', body: 'AGENTIC_TOOL_APPROVAL_PATTERNS' }],
    },
    'skill-routing': {
        title: '13 · توجيه المهارات',
        summary: 'اختيار مهارات لكل رسالة.',
        goal: 'وكلاء متعدد المهارات بكفاءة.',
        steps: [{ title: 'التفعيل', body: 'AGENTIC_SKILL_ROUTING' }],
    },
    widget: {
        title: '14 · Widget API',
        summary: 'API محادثة للضيوف والمسجلين — من تطبيقك أو SDK التضمين.',
        goal: 'تكامل JSON تحت /api/agentic/widget بدون واجهة الإدارة.',
        steps: [
            { title: 'تفعيل API', body: 'AGENTIC_WIDGET_ENABLED=true' },
            { title: 'الهوية', body: 'X-Agentic-Guest-Id أو Sanctum' },
            { title: 'إرسال', body: 'POST /messages. مع Pusher الرد HTTP هو { pending, conversation_id } وليس HTML المساعد.' },
            {
                title: 'عرض الرد كاملاً',
                body:
                    'AGENTIC_WIDGET_STREAM=false افتراضياً. أظهر Typing ثم ارسم فقاعة واحدة عند message.created. تجاهل message.delta إلا إذا فعّلت البث كلمة بكلمة. الصور عبر رابط ملفات موقّع.',
            },
            { title: 'الفصل 15', body: 'ويدجت منبثق agentic-widget.js (مُفضّل لـ Blade/SPA)' },
        ],
        adminNote: 'vendor/hatem-isnaad/agentic/docs/WIDGET_EMBED_SDK.md',
    },
    'widget-embed': {
        title: '15 · SDK تضمين الويدجت',
        summary:
            'الصق التضمين في موقعك — JS/CSS عام؛ الـ API مقفل بسر wgt_… ونطاقات مسموحة حتى لا يعمل النسخ على مواقع أخرى.',
        goal:
            'محادثة منبثقة على نطاقاتك فقط: انشر الملفات، أنشئ رمز تضمين مع allowed_origins، احقن السر من السيرفر، فعّل require_token في الإنتاج.',
        steps: [
            {
                title: 'البناء والنشر',
                body:
                    'من جذر Laravel: npm run build:widget ثم vendor:publish --tag=agentic-widget-assets. الملفات: public/vendor/agentic/widget/agentic-widget.js و .css — لا يوجد HTML جاهز؛ الواجهة تُنشأ من JS.',
            },
            {
                title: 'تحميل JS/CSS لموقع خارجي',
                body:
                    'حمّل الملفين من /vendor/agentic/widget/ على خادم Laravel أو انسخهما إلى CDN. أعد التحميل بعد كل build. AGENTIC_WIDGET_EMBED_SCRIPT_URL لقاعدة CDN في Blade.',
                code: `curl -O https://YOUR-HOST/vendor/agentic/widget/agentic-widget.js
curl -O https://YOUR-HOST/vendor/agentic/widget/agentic-widget.css`,
            },
            {
                title: 'تضمين موقع خارجي (HTML كامل)',
                body:
                    'الصفحة على نطاقك؛ apiBase يشير إلى Laravel؛ allowed_origins يجب أن يتضمن نطاق الصفحة. فعّل CORS إذا كان API على نطاق مختلف.',
                code: `<link rel="stylesheet" href="https://API-HOST/vendor/agentic/widget/agentic-widget.css">
<script src="https://API-HOST/vendor/agentic/widget/agentic-widget.js" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    AgenticChat.init({
      agent: 'support',
      apiBase: 'https://API-HOST/api/agentic/widget',
      embedToken: 'INJECT_wgt_FROM_SERVER',
    });
  });
</script>`,
            },
            {
                title: 'سر التضمين + قفل النطاق',
                body:
                    'الإدارة → رموز التضمين أو CLI. حدّد allowed_origins (مثل https://www.example.com). wgt_… هو سر API للتضمين (ليس مفتاحاً عاماً منفصلاً). يُعرض مرة واحدة — في .env فقط.',
            },
            {
                title: 'الإنتاج',
                body:
                    'AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true — بدون Bearer wgt_… يُرجع 401. إن لم يطابق Origin القائمة: «Origin not allowed».',
            },
            {
                title: 'نسخ ولصق (Blade)',
                body: 'يُفضّل حقن الرمز من السيرفر',
                code: `<x-agentic-widget
  agent="support"
  :token="config('services.agentic.widget_embed_token')"
  theme="system"
/>`,
            },
            {
                title: 'نسخ ولصق (أي موقع)',
                body: 'حمّل CSS/JS من تطبيق Laravel؛ embedToken من إعدادات السيرفر فقط',
                code: `AgenticChat.init({
  agent: 'support',
  apiBase: '/api/agentic/widget',
  embedToken: 'FROM_SERVER_ENV',
});`,
            },
            {
                title: 'ما لا يستطيع الغريب فعله',
                body:
                    'قد ينسخ ملف JS لكن طلبات API من نطاق آخر تُرفض. wgt_… مسروق لا يعمل خارج allowed_origins. قيّد allowed_agents.',
            },
            {
                title: 'المظهر والبث',
                body:
                    'Pusher: AGENTIC_WIDGET_BROADCAST_DRIVER=pusher و queue:work. الرد فقاعة كاملة على message.created (AGENTIC_WIDGET_STREAM=false). لا ترسم message.delta كلمة بكلمة. التفاصيل: WIDGET_EMBED_SDK.md و DEVELOPER_HANDBOOK.md.',
            },
        ],
        commands: [
            {
                command: 'php artisan vendor:publish --tag=agentic-widget-assets --force',
                title: 'نشر الملفات الثابتة',
                why: 'public/vendor/agentic/widget/',
            },
            {
                command:
                    'php artisan agentic:widget-embed-token create --name=prod --agents=support --origins=https://www.example.com',
                title: 'إنشاء رمز wgt_…',
                why: 'يُعرض مرة واحدة — خزّنه في .env مع نطاقات مسموحة',
            },
        ],
        env: [
            { key: 'AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN', description: 'إلزام Bearer wgt_… أو Sanctum' },
            { key: 'AGENTIC_WIDGET_EMBED_DEFAULT_AGENT', description: 'وكيل افتراضي لـ Blade' },
        ],
        adminNote: 'المرجع: docs/WIDGET_EMBED_SDK.md · /agentic/admin/widget-embed-tokens',
        ui: { path: '/widget-embed-tokens', label: 'رموز التضمين (مطلوب للإنتاج الآمن)' },
    },
    'widget-settings': {
        title: '16 · إعدادات الويدجت لكل وكيل',
        summary: 'ترحيب، استبيان، مظهر.',
        goal: 'تخصيص UX المحادثة.',
        steps: [{ title: 'تجاوزات', body: 'PUT /widget-settings/{agent}' }],
    },
    'staff-inbox': {
        title: '16ب · صندوق موظفين خاص',
        summary: 'ابنِ المكتب في نظامك. أجنتك يوفّر JSON فقط.',
        goal: 'قائمة محادثات، استلام، رد، إعادة للوكيل — دون شاشة /agentic/admin/inbox.',
        steps: [
            { title: 'API الإدارة', body: 'GET /inbox و GET /conversations/{id}/messages و POST take/reply/release. أغلقها بـ Sanctum وبوابتك.' },
            { title: 'تحديث حي', body: 'أعد جلب القائمة كل ثوانٍ. رسائل العميل لا تُبث دائماً على Pusher. لا تبنِ بث كلمة بكلمة — AGENTIC_WIDGET_STREAM=false.' },
            { title: 'الرد', body: 'POST …/reply يخزّن رد الموظف ويبث message.created (HTML كامل مع الصور). وظائف الذكاء تتوقف بعد التحويل.' },
        ],
        adminNote: 'الدليل: docs/STAFF_INBOX.md',
        ui: { path: '/inbox', label: 'الصندوق المدمج (اختياري)' },
    },
    workflows: {
        title: '17 · سير العمل',
        summary: 'أتمتة متعددة الخطوات.',
        goal: 'تنسيق أوسع من محادثة واحدة.',
        steps: [{ title: 'التشغيل', body: 'POST /workflows/{slug}/execute' }],
    },
    'runtime-api': {
        title: '18 · Runtime API',
        summary: 'نفس CRUD تحت /api/agentic.',
        goal: 'أتمتة من الخلفية.',
        steps: [{ title: 'التفعيل', body: 'AGENTIC_API_ENABLED=true' }],
    },
    'auth-admin': {
        title: '19 · مصادقة الإدارة',
        summary: 'AGENTIC_MODE=production يفعّل Sanctum + البوابة.',
        goal: 'إدارة آمنة في الإنتاج.',
        steps: [
            { title: 'الوضع', body: 'AGENTIC_MODE=production' },
            { title: 'البوابة', body: 'AGENTIC_ADMIN_GATE + Gate::define' },
        ],
    },
    'http-security': {
        title: '20 · أمان HTTP',
        summary: 'حماية SSRF للأدوات وإدخال URLs.',
        goal: 'منع الوصول للشبكة الداخلية.',
        steps: [{ title: 'الإنتاج', body: 'AGENTIC_HTTP_ALLOW_PRIVATE_HOSTS=false' }],
    },
    'drivers-storage': {
        title: '21 · المحركات والتخزين',
        summary: 'eloquent مقابل memory للاختبارات.',
        goal: 'معرفة محرك كل مورد.',
        steps: [{ title: 'التنفيذ', body: 'AGENTIC_EXECUTION_DRIVER' }],
    },
    monitoring: {
        title: '22 · التنفيذات والمحادثات',
        summary: 'مراجعة التشغيل والسجل.',
        goal: 'تصحيح سلوك الوكيل.',
        steps: [{ title: 'التنفيذات', body: 'GET /executions' }],
    },
    seeding: {
        title: '23 · Seeders',
        summary: 'بيئات متكررة بأمر واحد.',
        goal: 'CI و staging.',
        steps: [{ title: 'مثال', body: 'Agentic3plFulfillmentSeeder' }],
    },
    artisan: {
        title: '24 · أوامر Artisan',
        summary: 'كل أوامر CLI للحزمة.',
        goal: 'مرجع سريع.',
        steps: [{ title: 'القائمة', body: 'install، make-code-tool، mcp-sync، rag-validate، prune' }],
    },
};
