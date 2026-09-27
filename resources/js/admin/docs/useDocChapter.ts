import { useI18n } from '../lib/i18n';
import { chaptersAr } from './chapters/ar';
import { chaptersEn } from './chapters/en';
import type { DocChapterContent } from './chapters/types';

function mergeChapter(base: DocChapterContent, overlay?: DocChapterContent): DocChapterContent {
    if (!overlay) {
        return base;
    }

    return {
        ...base,
        ...overlay,
        steps: overlay.steps?.length ? overlay.steps : base.steps,
        commands: overlay.commands?.length ? overlay.commands : base.commands,
        env: overlay.env?.length ? overlay.env : base.env,
    };
}

export function getDocChapter(locale: string, slug: string): DocChapterContent | undefined {
    const en = chaptersEn[slug];
    if (!en) {
        return undefined;
    }

    if (locale === 'ar') {
        return mergeChapter(en, chaptersAr[slug]);
    }

    return en;
}

export function useDocChapter(slug: string): DocChapterContent | undefined {
    const { locale } = useI18n();
    return getDocChapter(locale, slug);
}
