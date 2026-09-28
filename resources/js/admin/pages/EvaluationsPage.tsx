import { Plus, Star } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';
import { TableSkeleton } from '../components/ui/Skeleton';
import { HelpCallout } from '../components/ui/HelpCallout';
import { DataTable, Table, THead, Th, Td, Tr, ErrorBanner } from '../components/ui/DataTable';
import { EmptyState } from '../components/ui/EmptyState';
import { ListFilters } from '../components/ui/ListFilters';
import { ALL_FILTER, facetOptionsFromRows, matchesFacet, matchesSearch } from '../lib/listFilters';

type Row = {
    id: number;
    score: number;
    label: string | null;
    notes: string | null;
    agent_slug: string | null;
    conversation_id: string | null;
    execution_id: string | null;
    created_at?: string;
};

export function EvaluationsPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<Row[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [show, setShow] = useState(false);
    const [score, setScore] = useState('5');
    const [agentSlug, setAgentSlug] = useState('');
    const [conversationId, setConversationId] = useState('');
    const [executionId, setExecutionId] = useState('');
    const [label, setLabel] = useState('');
    const [notes, setNotes] = useState('');
    const [q, setQ] = useState('');
    const [agentFilter, setAgentFilter] = useState(ALL_FILTER);
    const [scoreFilter, setScoreFilter] = useState(ALL_FILTER);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await adminApi.get<Paginated<Row>>(boot, '/evaluations?per_page=100');
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

    const visible = useMemo(
        () =>
            rows.filter(
                (row) =>
                    matchesSearch(row, q) &&
                    matchesFacet(row as unknown as Record<string, unknown>, 'agent_slug', agentFilter) &&
                    matchesFacet({ score: String(row.score) }, 'score', scoreFilter),
            ),
        [agentFilter, q, rows, scoreFilter],
    );

    const create = async () => {
        setError(null);
        try {
            await adminApi.post(boot, '/evaluations', {
                score: Number(score),
                agent_slug: agentSlug.trim() || null,
                conversation_id: conversationId.trim() || null,
                execution_id: executionId.trim() || null,
                label: label.trim() || null,
                notes: notes.trim() || null,
            });
            setShow(false);
            setNotes('');
            setLabel('');
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed');
        }
    };

    return (
        <PageContainer>
            <PageHeader
                title={t('evaluations.title')}
                description={t('evaluations.subtitle')}
                actions={
                    <Button type="button" onClick={() => setShow((v) => !v)}>
                        <Plus className="h-4 w-4" />
                        {t('evaluations.create')}
                    </Button>
                }
            />
            <HelpCallout>{t('evaluations.hint')}</HelpCallout>
            {error && <ErrorBanner message={error} />}

            {show && (
                <Card className="mb-6">
                    <CardBody className="space-y-4">
                        <FormField label={t('evaluations.score')} required>
                            <NativeSelect
                                value={score}
                                onValueChange={setScore}
                                options={['1', '2', '3', '4', '5'].map((value) => ({ value, label: value }))}
                            />
                        </FormField>
                        <FormField label={t('evaluations.agent')}>
                            <Input value={agentSlug} onChange={(e) => setAgentSlug(e.target.value)} placeholder="support" />
                        </FormField>
                        <FormField label={t('evaluations.conversation')}>
                            <Input value={conversationId} onChange={(e) => setConversationId(e.target.value)} />
                        </FormField>
                        <FormField label={t('evaluations.execution')}>
                            <Input value={executionId} onChange={(e) => setExecutionId(e.target.value)} />
                        </FormField>
                        <FormField label={t('evaluations.label')}>
                            <Input value={label} onChange={(e) => setLabel(e.target.value)} />
                        </FormField>
                        <FormField label={t('evaluations.notes')}>
                            <Textarea value={notes} onChange={(e) => setNotes(e.target.value)} rows={3} />
                        </FormField>
                        <Button type="button" onClick={() => void create()}>
                            {t('actions.save')}
                        </Button>
                    </CardBody>
                </Card>
            )}

            <DataTable
                toolbar={
                    <ListFilters
                        search={q}
                        onSearch={setQ}
                        searchPlaceholder={t('filters.search')}
                        filters={[
                            {
                                id: 'agent',
                                label: t('table.agent'),
                                value: agentFilter,
                                onChange: setAgentFilter,
                                options: facetOptionsFromRows(rows as unknown as Record<string, unknown>[], 'agent_slug'),
                            },
                            {
                                id: 'score',
                                label: t('filters.score'),
                                value: scoreFilter,
                                onChange: setScoreFilter,
                                options: ['1', '2', '3', '4', '5'].map((value) => ({ value, label: value })),
                            },
                        ]}
                        resultCount={visible.length}
                        totalCount={rows.length}
                        countLabel={t('filters.showing', { shown: String(visible.length), total: String(rows.length) })}
                        allLabel={t('filters.all')}
                        clearLabel={t('filters.clear')}
                    />
                }
            >
                <Table>
                    <THead>
                        <Th>{t('evaluations.score')}</Th>
                        <Th>{t('evaluations.agent')}</Th>
                        <Th>{t('evaluations.conversation')}</Th>
                        <Th>{t('evaluations.label')}</Th>
                        <Th>{t('evaluations.notes')}</Th>
                    </THead>
                    <tbody>
                        {loading && <TableSkeleton cols={5} rows={4} />}
                        {!loading && visible.length === 0 && (
                            <tr>
                                <td colSpan={5}>
                                    <EmptyState
                                        icon={Star}
                                        title={rows.length === 0 ? t('evaluations.empty') : t('filters.empty')}
                                        description={rows.length === 0 ? t('evaluations.hint') : t('filters.empty_hint')}
                                    />
                                </td>
                            </tr>
                        )}
                        {!loading &&
                            visible.map((row) => (
                                <Tr key={row.id}>
                                    <Td className="font-semibold tabular-nums">{row.score}</Td>
                                    <Td>{row.agent_slug || '—'}</Td>
                                    <Td>{row.conversation_id || '—'}</Td>
                                    <Td>{row.label || '—'}</Td>
                                    <Td className="max-w-xs truncate text-slate-500">{row.notes || '—'}</Td>
                                </Tr>
                            ))}
                    </tbody>
                </Table>
            </DataTable>
        </PageContainer>
    );
}
