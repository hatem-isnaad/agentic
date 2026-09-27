import { Navigate, useParams } from 'react-router-dom';
import { catalogBySlug } from '../../docs/catalog';
import { useDocChapter } from '../../docs/useDocChapter';
import { DocChapterView } from '../../components/docs/DocChapterView';
import { HostSetupChecklist } from '../../components/docs/HostSetupChecklist';
import { useAdminConfig } from '../../lib/config';

export function DocChapterPage() {
    const { chapterSlug = '' } = useParams();
    const boot = useAdminConfig();
    const meta = catalogBySlug(chapterSlug);
    const chapter = useDocChapter(chapterSlug);

    if (!meta || !chapter) {
        return <Navigate to="/docs" replace />;
    }

    return (
        <div className="space-y-8">
            {chapterSlug === 'install' ? <HostSetupChecklist /> : null}
            <DocChapterView chapter={chapter} adminApiPrefix={boot.apiPrefix} />
        </div>
    );
}
