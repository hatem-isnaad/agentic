import { ListChecks, Plus, Play } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input } from '../components/ui/Input';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { HelpCallout } from '../components/ui/HelpCallout';
import { Table, THead, Th, Td, Tr, ErrorBanner } from '../components/ui/DataTable';
import { EmptyState } from '../components/ui/EmptyState';
import { TableSkeleton } from '../components/ui/Skeleton';

type EvalSetRow = {
    id: number;
    name: string;
    slug: string;
    agent_slug: string;
    description?: string | null;
    cases_count?: number;
};

type EvalSetDetail = EvalSetRow & {
    cases?: { id: number; question: string; expect_contains?: string[] }[];
    runs?: { id: number; passed: number; failed: number; total: number; status: string; created_at?: string }[];
};

export function EvalSetsPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<EvalSetRow[]>([]);
    const [detail, setDetail] = useState<EvalSetDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [show, setShow] = useState(false);
    const [name, setName] = useState('');
    const [agentSlug, setAgentSlug] = useState('');
    const [question, setQuestion] = useState('');
    const [expect, setExpect] = useState('');
    const [running, setRunning] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await adminApi.get<Paginated<EvalSetRow>>(boot, '/eval-sets?per_page=100');
            setRows(res.data);
        } catch {
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [boot]);

    useEffect(() => {
        void load();
    }, [load]);

    const open = async (slug: string) => {
        const res = await adminApi.get<{ data: EvalSetDetail }>(boot, `/eval-sets/${slug}`);
        setDetail(res.data);
    };

    const create = async () => {
        setError(null);
        try {
            await adminApi.post(boot, '/eval-sets', { name: name.trim(), agent_slug: agentSlug.trim() });
            setShow(false);
            setName('');
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    };

    const addCase = async () => {
        if (!detail) {
            return;
        }
        setError(null);
        try {
            await adminApi.post(boot, `/eval-sets/${detail.slug}/cases`, {
                question: question.trim(),
                expect_contains: expect
                    .split(',')
                    .map((part) => part.trim())
                    .filter(Boolean),
            });
            setQuestion('');
            setExpect('');
            await open(detail.slug);
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    };

    const run = async (slug: string) => {
        setRunning(true);
        setError(null);
        try {
            await adminApi.post(boot, `/eval-sets/${slug}/run`, {});
            await open(slug);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Run failed');
        } finally {
            setRunning(false);
        }
    };

    return (
        <PageContainer>
            <PageHeader
                title={t('eval_sets.title')}
                description={t('eval_sets.intro')}
                actions={
                    <Button type="button" onClick={() => setShow((v) => !v)}>
                        <Plus className="h-4 w-4" />
                        {t('eval_sets.create')}
                    </Button>
                }
            />
            <HelpCallout>{t('eval_sets.hint')}</HelpCallout>
            {error && <ErrorBanner message={error} />}

            {show && (
                <Card className="mb-6">
                    <CardBody className="space-y-4">
                        <FormField label={t('fields.name')} required>
                            <Input value={name} onChange={(e) => setName(e.target.value)} />
                        </FormField>
                        <FormField label={t('evaluations.agent')} required>
                            <Input value={agentSlug} onChange={(e) => setAgentSlug(e.target.value)} placeholder="support" />
                        </FormField>
                        <Button type="button" onClick={() => void create()}>
                            {t('actions.save')}
                        </Button>
                    </CardBody>
                </Card>
            )}

            <Card className="mb-6">
                <CardBody>
                    <Table>
                        <THead>
                            <Th>{t('table.name')}</Th>
                            <Th>{t('evaluations.agent')}</Th>
                            <Th>{t('eval_sets.cases')}</Th>
                            <Th>{t('table.actions')}</Th>
                        </THead>
                        <tbody>
                            {loading && <TableSkeleton cols={4} rows={3} />}
                            {!loading && rows.length === 0 && (
                                <tr>
                                    <td colSpan={4}>
                                        <EmptyState icon={ListChecks} title={t('eval_sets.empty')} description={t('eval_sets.hint')} />
                                    </td>
                                </tr>
                            )}
                            {!loading &&
                                rows.map((row) => (
                                    <Tr key={row.id}>
                                        <Td>
                                            <button type="button" className="font-medium text-brand-700 hover:underline" onClick={() => void open(row.slug)}>
                                                {row.name}
                                            </button>
                                        </Td>
                                        <Td>{row.agent_slug}</Td>
                                        <Td>{row.cases_count ?? 0}</Td>
                                        <Td>
                                            <Button type="button" variant="secondary" disabled={running} onClick={() => void run(row.slug)}>
                                                <Play className="h-4 w-4" />
                                                {t('eval_sets.run')}
                                            </Button>
                                        </Td>
                                    </Tr>
                                ))}
                        </tbody>
                    </Table>
                </CardBody>
            </Card>

            {detail && (
                <Card>
                    <CardBody className="space-y-4">
                        <h2 className="text-sm font-semibold text-slate-900">{detail.name}</h2>
                        <FormField label={t('eval_sets.question')}>
                            <Input value={question} onChange={(e) => setQuestion(e.target.value)} />
                        </FormField>
                        <FormField label={t('eval_sets.expect')}>
                            <Input value={expect} onChange={(e) => setExpect(e.target.value)} placeholder="order, confirmed" />
                        </FormField>
                        <Button type="button" onClick={() => void addCase()}>
                            {t('eval_sets.add_case')}
                        </Button>
                        <ul className="space-y-2 text-sm">
                            {(detail.cases ?? []).map((row) => (
                                <li key={row.id} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p className="font-medium text-slate-900">{row.question}</p>
                                    <p className="text-slate-500">{(row.expect_contains ?? []).join(', ') || '—'}</p>
                                </li>
                            ))}
                        </ul>
                        {(detail.runs ?? []).length > 0 && (
                            <p className="text-sm text-slate-600">
                                {t('eval_sets.last_run')}: {detail.runs?.[0]?.passed}/{detail.runs?.[0]?.total} ({detail.runs?.[0]?.status})
                            </p>
                        )}
                    </CardBody>
                </Card>
            )}
        </PageContainer>
    );
}
