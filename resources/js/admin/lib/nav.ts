export type NavItem = { to: string; labelKey: string; segment: string | null };

export type NavGroup = { labelKey: string; items: NavItem[] };

/** Minimal nav for non-technical users — wizard + essentials only. */
export const simpleNavGroups: NavGroup[] = [
    {
        labelKey: 'nav.group_simple',
        items: [
            { to: '/', labelKey: 'nav.dashboard', segment: null },
            { to: '/setup', labelKey: 'nav.setup_wizard', segment: 'setup' },
            { to: '/docs', labelKey: 'nav.docs', segment: 'docs' },
            { to: '/agents', labelKey: 'nav.agents', segment: 'agents' },
            { to: '/conversations', labelKey: 'nav.conversations', segment: 'conversations' },
            { to: '/knowledge-sources', labelKey: 'nav.knowledge', segment: 'knowledge-sources' },
            { to: '/custom-code-tools', labelKey: 'nav.custom_code_tools', segment: 'custom-code-tools' },
            { to: '/connections', labelKey: 'nav.connections', segment: 'connections' },
            { to: '/channel-accounts', labelKey: 'nav.channel_accounts', segment: 'channel-accounts' },
            { to: '/widget-embed-tokens', labelKey: 'nav.widget_embed_tokens', segment: 'widget-embed-tokens' },
            { to: '/widget-settings', labelKey: 'nav.widget_settings', segment: 'widget-settings' },
            { to: '/settings', labelKey: 'nav.settings', segment: 'settings' },
        ],
    },
];

export const adminNavGroups: NavGroup[] = [
    {
        labelKey: 'nav.group_start',
        items: [
            { to: '/', labelKey: 'nav.dashboard', segment: null },
            { to: '/setup', labelKey: 'nav.setup_wizard', segment: 'setup' },
            { to: '/docs', labelKey: 'nav.docs', segment: 'docs' },
            { to: '/docs/json-builder', labelKey: 'nav.json_builder', segment: 'json-builder' },
            { to: '/settings', labelKey: 'nav.settings', segment: 'settings' },
        ],
    },
    {
        labelKey: 'nav.group_build',
        items: [
            { to: '/agents', labelKey: 'nav.agents', segment: 'agents' },
            { to: '/skills', labelKey: 'nav.skills', segment: 'skills' },
            { to: '/custom-code-tools', labelKey: 'nav.custom_code_tools', segment: 'custom-code-tools' },
            { to: '/connections', labelKey: 'nav.connections', segment: 'connections' },
            { to: '/tools', labelKey: 'nav.tools', segment: 'tools' },
            { to: '/channel-accounts', labelKey: 'nav.channel_accounts', segment: 'channel-accounts' },
            { to: '/mcp-servers', labelKey: 'nav.mcp', segment: 'mcp-servers' },
            { to: '/knowledge-sources', labelKey: 'nav.knowledge', segment: 'knowledge-sources' },
            { to: '/memories', labelKey: 'nav.memories', segment: 'memories' },
            { to: '/workflows', labelKey: 'nav.workflows', segment: 'workflows' },
        ],
    },
    {
        labelKey: 'nav.group_run',
        items: [
            { to: '/widget-embed-tokens', labelKey: 'nav.widget_embed_tokens', segment: 'widget-embed-tokens' },
            { to: '/widget-settings', labelKey: 'nav.widget_settings', segment: 'widget-settings' },
            { to: '/workflow-runs', labelKey: 'nav.workflow_runs', segment: 'workflow-runs' },
            { to: '/executions', labelKey: 'nav.executions', segment: 'executions' },
            { to: '/conversations', labelKey: 'nav.conversations', segment: 'conversations' },
            { to: '/evaluations', labelKey: 'nav.evaluations', segment: 'evaluations' },
        ],
    },
];

export function navGroupsForMode(expertMode: boolean): NavGroup[] {
    return expertMode ? adminNavGroups : simpleNavGroups;
}

/** NavLink `end` — avoid /docs staying active on /docs/json-builder. */
export function navItemUsesEndMatch(to: string): boolean {
    return to === '/' || to === '/docs';
}

/** @deprecated use adminNavGroups — flat list for breadcrumbs */
export const adminNav = adminNavGroups.flatMap((g) => g.items);
