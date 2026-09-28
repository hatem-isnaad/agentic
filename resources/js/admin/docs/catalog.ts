export type DocPart = 'setup' | 'build' | 'runtime' | 'platform';

export type DocCatalogEntry = {
    slug: string;
    part: DocPart;
    /** i18n key under docs.catalog.* */
    labelKey: string;
    order: number;
};

export const DOC_PART_ORDER: DocPart[] = ['setup', 'build', 'runtime', 'platform'];

export const DOC_PART_LABEL: Record<DocPart, string> = {
    setup: 'docs.part_setup',
    build: 'docs.part_build',
    runtime: 'docs.part_runtime',
    platform: 'docs.part_platform',
};

export const DOC_CATALOG: DocCatalogEntry[] = [
    { slug: 'install', part: 'setup', labelKey: 'install', order: 1 },
    { slug: 'environment', part: 'setup', labelKey: 'environment', order: 2 },
    { slug: 'ai', part: 'setup', labelKey: 'ai', order: 3 },
    { slug: 'code-tools', part: 'build', labelKey: 'code_tools', order: 4 },
    { slug: 'http-tools', part: 'build', labelKey: 'http_tools', order: 5 },
    { slug: 'mcp', part: 'build', labelKey: 'mcp', order: 6 },
    { slug: 'skills', part: 'build', labelKey: 'skills', order: 7 },
    { slug: 'agents', part: 'build', labelKey: 'agents', order: 8 },
    { slug: 'knowledge', part: 'build', labelKey: 'knowledge', order: 9 },
    { slug: 'memories', part: 'build', labelKey: 'memories', order: 10 },
    { slug: 'permissions', part: 'runtime', labelKey: 'permissions', order: 11 },
    { slug: 'approvals', part: 'runtime', labelKey: 'approvals', order: 12 },
    { slug: 'skill-routing', part: 'runtime', labelKey: 'skill_routing', order: 13 },
    { slug: 'widget', part: 'runtime', labelKey: 'widget', order: 14 },
    { slug: 'widget-embed', part: 'runtime', labelKey: 'widget_embed', order: 15 },
    { slug: 'widget-settings', part: 'runtime', labelKey: 'widget_settings', order: 16 },
    { slug: 'staff-inbox', part: 'runtime', labelKey: 'staff_inbox', order: 16.5 },
    { slug: 'workflows', part: 'runtime', labelKey: 'workflows', order: 17 },
    { slug: 'runtime-api', part: 'platform', labelKey: 'runtime_api', order: 18 },
    { slug: 'auth-admin', part: 'platform', labelKey: 'auth_admin', order: 19 },
    { slug: 'http-security', part: 'platform', labelKey: 'http_security', order: 20 },
    { slug: 'drivers-storage', part: 'platform', labelKey: 'drivers_storage', order: 21 },
    { slug: 'monitoring', part: 'platform', labelKey: 'monitoring', order: 22 },
    { slug: 'seeding', part: 'platform', labelKey: 'seeding', order: 23 },
    { slug: 'artisan', part: 'platform', labelKey: 'artisan', order: 24 },
];

export function catalogBySlug(slug: string): DocCatalogEntry | undefined {
    return DOC_CATALOG.find((c) => c.slug === slug);
}

export function sortedCatalog(): DocCatalogEntry[] {
    return [...DOC_CATALOG].sort((a, b) => a.order - b.order);
}
