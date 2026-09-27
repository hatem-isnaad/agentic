import { useMemo } from 'react';
import { useI18n } from './i18n';
import type { SelectOption } from '../components/ui/NativeSelect';

export function useStatusOptions(): SelectOption[] {
    const { t } = useI18n();
    return useMemo(
        () =>
            ['draft', 'published', 'archived'].map((value) => ({
                value,
                label: t(`status.${value}`),
            })),
        [t],
    );
}

export function statusLabel(t: (k: string) => string, status: unknown): string {
    const key = String(status ?? 'draft');
    const translated = t(`status.${key}`);
    return translated.startsWith('status.') ? key : translated;
}
