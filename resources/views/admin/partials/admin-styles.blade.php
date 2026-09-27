<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --ag-bg: #f4f6f9;
        --ag-surface: #ffffff;
        --ag-sidebar: #0f172a;
        --ag-sidebar-text: #cbd5e1;
        --ag-sidebar-active: #ffffff;
        --ag-primary: #2563eb;
        --ag-primary-hover: #1d4ed8;
        --ag-border: #e2e8f0;
        --ag-text: #0f172a;
        --ag-muted: #64748b;
        --ag-success-bg: #ecfdf5;
        --ag-success-text: #047857;
        --ag-danger: #dc2626;
        --ag-radius: 10px;
        --ag-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }

    *, *::before, *::after { box-sizing: border-box; }

    body.agentic-admin {
        margin: 0;
        font-family: 'Cairo', system-ui, sans-serif;
        font-size: 15px;
        line-height: 1.55;
        color: var(--ag-text);
        background: var(--ag-bg);
    }

    .ag-shell {
        display: flex;
        min-height: 100vh;
    }

    .ag-sidebar {
        width: 260px;
        flex-shrink: 0;
        background: var(--ag-sidebar);
        color: var(--ag-sidebar-text);
        display: flex;
        flex-direction: column;
        padding: 1.25rem 0;
    }

    .ag-brand {
        padding: 0 1.25rem 1.25rem;
        font-weight: 700;
        font-size: 1.15rem;
        color: var(--ag-sidebar-active);
        letter-spacing: 0.02em;
    }

    .ag-nav {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        padding: 0 0.65rem;
    }

    .ag-nav a {
        display: block;
        padding: 0.55rem 0.85rem;
        border-radius: 8px;
        color: var(--ag-sidebar-text);
        text-decoration: none;
        font-weight: 500;
        transition: background 0.15s, color 0.15s;
    }

    .ag-nav a:hover,
    .ag-nav a.is-active {
        background: rgba(255, 255, 255, 0.08);
        color: var(--ag-sidebar-active);
    }

    .ag-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .ag-topbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1rem;
        padding: 0.85rem 1.75rem;
        background: var(--ag-surface);
        border-bottom: 1px solid var(--ag-border);
    }

    .ag-locale select {
        font-family: inherit;
        font-size: 0.9rem;
        padding: 0.4rem 0.65rem;
        border-radius: 8px;
        border: 1px solid var(--ag-border);
        background: var(--ag-surface);
    }

    .ag-content {
        padding: 1.5rem 1.75rem 2.5rem;
        max-width: 1120px;
        width: 100%;
    }

    .page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .page-header h1 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .page-header .lead {
        margin: 0.35rem 0 0;
        color: var(--ag-muted);
        font-size: 0.95rem;
    }

    .card {
        background: var(--ag-surface);
        border: 1px solid var(--ag-border);
        border-radius: var(--ag-radius);
        box-shadow: var(--ag-shadow);
    }

    .card-body { padding: 1.25rem; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: var(--ag-surface);
        border: 1px solid var(--ag-border);
        border-radius: var(--ag-radius);
        padding: 1rem 1.1rem;
        box-shadow: var(--ag-shadow);
    }

    .stat-card .label {
        font-size: 0.8rem;
        color: var(--ag-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .stat-card .value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-top: 0.25rem;
        color: var(--ag-primary);
    }

    table.ag-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.92rem;
    }

    .ag-table th,
    .ag-table td {
        padding: 0.65rem 0.85rem;
        text-align: start;
        border-bottom: 1px solid var(--ag-border);
    }

    .ag-table th {
        font-weight: 600;
        color: var(--ag-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background: #f8fafc;
    }

    .ag-table tbody tr:hover { background: #f8fafc; }

    .ag-table .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: inherit;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
    }

    .btn-primary {
        background: var(--ag-primary);
        color: #fff;
    }

    .btn-primary:hover { background: var(--ag-primary-hover); color: #fff; }

    .btn-ghost {
        background: transparent;
        color: var(--ag-primary);
        border: 1px solid var(--ag-border);
    }

    .btn-ghost:hover { background: #f1f5f9; }

    .btn-danger {
        background: #fef2f2;
        color: var(--ag-danger);
        border: 1px solid #fecaca;
    }

    .btn-danger:hover { background: #fee2e2; }

    .btn-link {
        background: none;
        border: none;
        padding: 0;
        color: var(--ag-primary);
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        font-size: inherit;
    }

    .btn-link:hover { text-decoration: underline; }

    .badge {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #e2e8f0;
        color: #334155;
    }

    .badge-published { background: var(--ag-success-bg); color: var(--ag-success-text); }

    .form-stack label {
        display: block;
        margin-top: 1rem;
        font-weight: 600;
        font-size: 0.88rem;
    }

    .form-stack label:first-child { margin-top: 0; }

    .form-stack input,
    .form-stack textarea,
    .form-stack select {
        width: 100%;
        max-width: 36rem;
        margin-top: 0.35rem;
        padding: 0.55rem 0.75rem;
        font-family: inherit;
        font-size: 0.95rem;
        border: 1px solid var(--ag-border);
        border-radius: 8px;
        background: #fff;
    }

    .form-stack textarea { min-height: 6rem; resize: vertical; }

    .form-stack textarea.code { font-family: ui-monospace, monospace; font-size: 0.85rem; min-height: 12rem; }

    .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .alert {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-weight: 500;
    }

    .alert-success {
        background: var(--ag-success-bg);
        color: var(--ag-success-text);
        border: 1px solid #a7f3d0;
    }

    .alert-error {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .alert-error ul { margin: 0.5rem 0 0; padding-inline-start: 1.25rem; }

    .detail-grid {
        display: grid;
        gap: 0.75rem;
    }

    .detail-row {
        display: grid;
        grid-template-columns: minmax(140px, 200px) 1fr;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--ag-border);
    }

    .detail-row dt {
        margin: 0;
        font-weight: 600;
        color: var(--ag-muted);
        font-size: 0.88rem;
    }

    .detail-row dd {
        margin: 0;
        word-break: break-word;
    }

    pre.ag-code {
        background: #0f172a;
        color: #e2e8f0;
        padding: 1rem;
        border-radius: 8px;
        overflow: auto;
        font-size: 0.8rem;
        line-height: 1.45;
        max-height: 420px;
    }

    .message-list { display: flex; flex-direction: column; gap: 0.75rem; }

    .message-item {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        border: 1px solid var(--ag-border);
        background: #f8fafc;
    }

    .message-item .role {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--ag-muted);
        margin-bottom: 0.35rem;
    }

    .toolbar { margin-bottom: 1rem; }

    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }

    @media (max-width: 900px) {
        .ag-shell { flex-direction: column; }
        .ag-sidebar { width: 100%; }
        .ag-nav { flex-direction: row; flex-wrap: wrap; }
        .detail-row { grid-template-columns: 1fr; }
    }
</style>
