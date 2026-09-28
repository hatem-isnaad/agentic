export const ALL_FILTER = '__all__';

export function uniqueValues(rows: Array<Record<string, unknown>>, key: string): string[] {
    return [...new Set(rows.map((row) => String(row[key] ?? '').trim()).filter(Boolean))].sort((a, b) =>
        a.localeCompare(b),
    );
}

export function matchesSearch(row: unknown, query: string): boolean {
    const needle = query.trim().toLowerCase();
    if (needle === '') {
        return true;
    }

    return JSON.stringify(row).toLowerCase().includes(needle);
}

export function matchesFacet(row: Record<string, unknown>, key: string, value: string): boolean {
    if (value === '' || value === ALL_FILTER) {
        return true;
    }

    return String(row[key] ?? '') === value;
}

export function facetOptionsFromRows(
    rows: Array<Record<string, unknown>>,
    key: string,
    labels?: (value: string) => string,
): { value: string; label: string }[] {
    return uniqueValues(rows, key).map((value) => ({
        value,
        label: labels ? labels(value) : value,
    }));
}
