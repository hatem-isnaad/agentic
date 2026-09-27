export type DocStep = { title: string; body: string; code?: string };

export type DocEnvRow = { key: string; description: string };

/** One Artisan / Composer line with plain-language what & why */
export type DocCommand = { command: string; title: string; why: string; note?: string };

export type DocChapterContent = {
    title: string;
    summary: string;
    goal: string;
    steps: DocStep[];
    /** Ordered CLI walkthrough (preferred over a single `cli` blob when present) */
    commands?: DocCommand[];
    env?: DocEnvRow[];
    configPaths?: string[];
    /** Copy-paste full install script */
    cli?: string;
    /** Example .env fragment */
    envExample?: string;
    api?: string;
    php?: string;
    ui?: { path: string; label: string };
    adminNote?: string;
};

export type DocChapterMap = Record<string, DocChapterContent>;
