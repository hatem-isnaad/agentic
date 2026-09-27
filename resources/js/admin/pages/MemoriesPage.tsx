import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { adminApi } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { FormField } from '../components/ui/FormField';
import { Input, Textarea } from '../components/ui/Input';
import { NativeSelect } from '../components/ui/NativeSelect';
import { PageContainer } from '../components/ui/PageContainer';
import { PageHeader } from '../components/ui/PageHeader';

type MemoryRow = {
    id: number;
    scope: string;
    scope_key: string;
    key: string;
    content: string;
    agent_slug?: string | null;
    importance?: number;
};

const SCOPES = ['user', 'agent', 'conversation', 'tenant'].map((v) => ({ value: v, label: v }));

export function MemoriesPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [scope, setScope] = useState('user');
    const [scopeKey, setScopeKey] = useState('');
    const [agentSlug, setAgentSlug] = useState('');
    const [rows, setRows] = useState<MemoryRow[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const [newKey, setNewKey] = useState('');
    const [newContent, setNewContent] = useState('');
    const [importance, setImportance] = useState('5');

    const load = async () => {
        if (!scopeKey.trim()) {
            setError(t('memories.scope_key_required'));
            return;
        }
        setBusy(true);
        setError(null);
        try {
            const q = new URLSearchParams({
                scope,
                scope_key: scopeKey.trim(),
                limit: '50',
            });
            if (agentSlug.trim()) q.set('agent_slug', agentSlug.trim());
            const res = await adminApi.get<{ data: MemoryRow[] }>(boot, `/memories?${q}`);
            setRows(res.data);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Load failed');
            setRows([]);
        } finally {
            setBusy(false);
        }
    };

    const create = async () => {
        if (!scopeKey.trim() || !newKey.trim() || !newContent.trim()) {
            setError(t('memories.create_required'));
            return;
        }
        setBusy(true);
        setError(null);
        try {
            await adminApi.post(boot, '/memories', {
                scope,
                scope_key: scopeKey.trim(),
                key: newKey.trim(),
                content: newContent,
                agent_slug: agentSlug.trim() || null,
                importance: parseInt(importance, 10) || 5,
            });
            setNewKey('');
            setNewContent('');
            await load();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Create failed');
        } finally {
            setBusy(false);
        }
    };

    const remove = async (id: number) => {
        if (!window.confirm(t('actions.confirm_delete'))) return;
        setBusy(true);
        try {
            await adminApi.delete(boot, `/memories/${id}`);
            setRows((prev) => prev.filter((r) => r.id !== id));
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Delete failed');
        } finally {
            setBusy(false);
        }
    };

    return (
        <PageContainer>
            <PageHeader title={t('memories.title')} description={t('memories.intro')} />

            {error && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>
            )}

            <Card className="mb-6">
                <CardBody className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <FormField label={t('memories.fields.scope')}>
                        <NativeSelect value={scope} onValueChange={setScope} options={SCOPES} />
                    </FormField>
                    <FormField label={t('memories.fields.scope_key')}>
                        <Input value={scopeKey} onChange={(e) => setScopeKey(e.target.value)} placeholder="user-42" />
                    </FormField>
                    <FormField label={t('memories.fields.agent_slug')}>
                        <Input value={agentSlug} onChange={(e) => setAgentSlug(e.target.value)} placeholder="support" />
                    </FormField>
                    <div className="flex items-end">
                        <Button type="button" disabled={busy} onClick={load}>{t('memories.load_action')}</Button>
                    </div>
                </CardBody>
            </Card>

            <Card className="mb-6">
                <CardBody className="space-y-4">
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">{t('memories.create_title')}</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <FormField label={t('memories.fields.key')}>
                            <Input value={newKey} onChange={(e) => setNewKey(e.target.value)} />
                        </FormField>
                        <FormField label={t('memories.fields.importance')}>
                            <Input value={importance} onChange={(e) => setImportance(e.target.value)} type="number" min={1} max={10} />
                        </FormField>
                    </div>
                    <FormField label={t('memories.fields.content')}>
                        <Textarea value={newContent} onChange={(e) => setNewContent(e.target.value)} rows={3} />
                    </FormField>
                    <Button type="button" disabled={busy} onClick={create}>{t('memories.create_action')}</Button>
                </CardBody>
            </Card>

            <Card>
                <CardBody className="p-0 overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-slate-100 bg-slate-50/80 text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th className="px-4 py-3">ID</th>
                                <th className="px-4 py-3">{t('memories.fields.key')}</th>
                                <th className="px-4 py-3">{t('memories.fields.content')}</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-4 py-8 text-center text-slate-500">{t('empty.memories')}</td>
                                </tr>
                            ) : (
                                rows.map((row) => (
                                    <tr key={row.id}>
                                        <td className="px-4 py-3 font-mono text-xs">{row.id}</td>
                                        <td className="px-4 py-3 font-semibold">{row.key}</td>
                                        <td className="px-4 py-3 max-w-md truncate text-slate-600">{row.content}</td>
                                        <td className="px-4 py-3 text-end">
                                            <Button type="button" variant="danger" className="inline-flex gap-1" onClick={() => remove(row.id)}>
                                                <Trash2 className="h-3.5 w-3.5" />
                                                {t('actions.delete')}
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </CardBody>
            </Card>
        </PageContainer>
    );
}
