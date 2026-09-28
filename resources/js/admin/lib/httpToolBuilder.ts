export type KvRow = { id: string; key: string; value: string };

export type InputRow = {
    id: string;
    name: string;
    type: string;
    required: boolean;
    description: string;
};

export type HttpToolDraft = {
    method: string;
    url: string;
    connection: string;
    timeout: number;
    query: KvRow[];
    headers: KvRow[];
    body: KvRow[];
    inputs: InputRow[];
};

export const HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as const;
export const INPUT_TYPES = ['string', 'integer', 'number', 'boolean'] as const;

let rowSeq = 0;

export function newId(): string {
    rowSeq += 1;

    return `r${rowSeq}-${Math.random().toString(36).slice(2, 8)}`;
}

export function emptyKv(): KvRow {
    return { id: newId(), key: '', value: '' };
}

export function emptyInput(name = '', required = false): InputRow {
    return { id: newId(), name, type: 'string', required, description: '' };
}

export function emptyDraft(): HttpToolDraft {
    return {
        method: 'GET',
        url: 'https://api.example.com/v1/orders/{id}',
        connection: '',
        timeout: 20,
        query: [emptyKv()],
        headers: [emptyKv()],
        body: [emptyKv()],
        inputs: [emptyInput('id', true)],
    };
}

export function placeholdersIn(...texts: string[]): string[] {
    const found = new Set<string>();
    for (const text of texts) {
        for (const match of text.matchAll(/\{([A-Za-z][A-Za-z0-9_]*)\}/g)) {
            found.add(match[1]);
        }
    }

    return [...found];
}

export function objectToRows(value: unknown): KvRow[] {
    const flat = flattenValue(value);
    const rows = flat.map((row) => ({ id: newId(), key: row.key, value: row.value }));

    return rows.length > 0 ? rows : [emptyKv()];
}

export function rowsToObject(rows: KvRow[]): Record<string, unknown> | null {
    const out: Record<string, unknown> = {};
    let any = false;
    for (const row of rows) {
        const key = row.key.trim();
        if (key === '') continue;
        setPath(out, key, row.value);
        any = true;
    }

    return any ? out : null;
}

export function flattenValue(value: unknown, prefix = ''): { key: string; value: string }[] {
    if (value === null || value === undefined) {
        return [];
    }
    if (typeof value !== 'object') {
        return prefix ? [{ key: prefix, value: String(value) }] : [];
    }
    if (Array.isArray(value)) {
        return value.flatMap((item, index) => flattenValue(item, prefix === '' ? String(index) : `${prefix}.${index}`));
    }

    return Object.entries(value as Record<string, unknown>).flatMap(([key, nested]) =>
        flattenValue(nested, prefix === '' ? key : `${prefix}.${key}`),
    );
}

export function setPath(target: Record<string, unknown>, path: string, value: string): void {
    const parts = path.split('.').filter(Boolean);
    if (parts.length === 0) {
        return;
    }

    let current: Record<string, unknown> | unknown[] = target;
    for (let i = 0; i < parts.length - 1; i++) {
        const part = parts[i];
        const next = parts[i + 1];
        const nextIsIndex = /^\d+$/.test(next);
        const asRecord = current as Record<string, unknown>;
        if (asRecord[part] === undefined || asRecord[part] === null || typeof asRecord[part] !== 'object') {
            asRecord[part] = nextIsIndex ? [] : {};
        }
        current = asRecord[part] as Record<string, unknown> | unknown[];
    }

    const last = parts[parts.length - 1];
    (current as Record<string, unknown>)[last] = value;
}

export function parseInputSchema(schema: unknown): InputRow[] {
    if (!schema || typeof schema !== 'object') {
        return [emptyInput()];
    }

    const record = schema as Record<string, unknown>;
    const properties = (record.properties && typeof record.properties === 'object' ? record.properties : record) as Record<string, unknown>;
    const requiredList = Array.isArray(record.required) ? record.required.map(String) : [];
    const rows: InputRow[] = [];

    for (const [name, raw] of Object.entries(properties)) {
        if (name === 'type' && typeof raw === 'string') {
            continue;
        }
        if (name === 'properties' || name === 'required') {
            continue;
        }
        const field = raw && typeof raw === 'object' ? (raw as Record<string, unknown>) : {};
        rows.push({
            id: newId(),
            name,
            type: String(field.type ?? 'string'),
            required: requiredList.includes(name) || field.required === true,
            description: String(field.description ?? ''),
        });
    }

    return rows.length > 0 ? rows : [emptyInput()];
}

export function draftFromDefinition(definition: Record<string, unknown>, config: Record<string, unknown> = {}): HttpToolDraft {
    const merged = { ...config, ...definition };
    const method = String(merged.method ?? 'GET').toUpperCase();
    const url = String(merged.url ?? '');
    const query = objectToRows(merged.query);
    const headers = objectToRows(merged.headers);
    const body = typeof merged.body === 'string'
        ? [{ id: newId(), key: '', value: merged.body }]
        : objectToRows(merged.body);
    const inputs = parseInputSchema(merged.input_schema);
    const draft: HttpToolDraft = {
        method: HTTP_METHODS.includes(method as (typeof HTTP_METHODS)[number]) ? method : 'GET',
        url: url || emptyDraft().url,
        connection: String(merged.connection ?? ''),
        timeout: Number(merged.timeout ?? 20) || 20,
        query,
        headers,
        body,
        inputs,
    };

    return syncInputs(draft);
}

export function syncInputs(draft: HttpToolDraft): HttpToolDraft {
    const urlParams = placeholdersIn(draft.url);
    const other = placeholdersIn(
        ...draft.query.map((row) => row.value),
        ...draft.headers.map((row) => row.value),
        ...draft.body.map((row) => row.value),
    );
    const names = [...new Set([...urlParams, ...other])];
    const existing = new Map(draft.inputs.filter((row) => row.name.trim() !== '').map((row) => [row.name.trim(), row]));
    const next: InputRow[] = names.map((name) => {
        const current = existing.get(name);
        if (current) {
            return urlParams.includes(name) ? { ...current, required: true } : current;
        }

        return emptyInput(name, urlParams.includes(name));
    });

    for (const row of draft.inputs) {
        const name = row.name.trim();
        if (name !== '' && !names.includes(name)) {
            next.push(row);
        }
    }

    return { ...draft, inputs: next.length > 0 ? next : [emptyInput()] };
}

export function buildInputSchema(inputs: InputRow[]): Record<string, unknown> | null {
    const properties: Record<string, unknown> = {};
    const required: string[] = [];
    for (const row of inputs) {
        const name = row.name.trim();
        if (name === '') continue;
        properties[name] = {
            type: row.type || 'string',
            ...(row.description.trim() !== '' ? { description: row.description.trim() } : {}),
        };
        if (row.required) {
            required.push(name);
        }
    }
    if (Object.keys(properties).length === 0) {
        return null;
    }

    return {
        type: 'object',
        properties,
        ...(required.length > 0 ? { required } : {}),
    };
}

export function serializeDraft(draft: HttpToolDraft): Record<string, unknown> {
    const query = rowsToObject(draft.query);
    const headers = rowsToObject(draft.headers);
    const bodyRows = draft.body.filter((row) => row.key.trim() !== '' || row.value.trim() !== '');
    let body: unknown = null;
    if (bodyRows.length === 1 && bodyRows[0].key.trim() === '') {
        body = bodyRows[0].value;
    } else {
        body = rowsToObject(draft.body);
    }

    const schema = buildInputSchema(draft.inputs);

    return Object.fromEntries(
        Object.entries({
            method: draft.method,
            url: draft.url.trim(),
            connection: draft.connection.trim() || undefined,
            timeout: draft.timeout,
            query: query ?? undefined,
            headers: headers ?? undefined,
            body: body ?? undefined,
            input_schema: schema ?? undefined,
        }).filter(([, value]) => value !== undefined),
    );
}
