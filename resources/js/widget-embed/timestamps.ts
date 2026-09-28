const EN = 'en-US';

export function parseSentAt(value: string | Date | null | undefined): Date | null {
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }
    if (!value) {
        return null;
    }
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function dayKey(value: string | Date | null | undefined): string | null {
    const date = parseSentAt(value);
    if (!date) {
        return null;
    }

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

/** Clock under each message, always English: 2:34 PM */
export function formatMessageTimeEn(value: string | Date | null | undefined): string {
    const date = parseSentAt(value);
    if (!date) {
        return '';
    }

    return new Intl.DateTimeFormat(EN, { hour: 'numeric', minute: '2-digit' }).format(date);
}

/** Day chip between groups, always English: Today / Yesterday / Monday, September 28, 2026 */
export function formatDayLabelEn(value: string | Date | null | undefined, now = new Date()): string {
    const date = parseSentAt(value);
    if (!date) {
        return '';
    }

    const today = dayKey(now);
    const yesterday = dayKey(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1));
    const key = dayKey(date);

    if (key === today) {
        return 'Today';
    }
    if (key === yesterday) {
        return 'Yesterday';
    }

    return new Intl.DateTimeFormat(EN, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
}
