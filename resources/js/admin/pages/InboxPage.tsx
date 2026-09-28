import { Inbox, Send } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { adminApi, type Paginated } from '../lib/api';
import { useAdminConfig } from '../lib/config';
import { useI18n } from '../lib/i18n';
import { Button } from '../components/ui/Button';
import { EmptyState } from '../components/ui/EmptyState';
import { Skeleton } from '../components/ui/Skeleton';

type InboxRow = {
    id: string;
    agent: string;
    user_id?: string | number | null;
    preview?: string;
    last_role?: string | null;
    last_message_at?: string | null;
    handoff_status?: string;
    needs_human?: boolean;
    metadata?: { handoff?: { status?: string; by?: string } };
};

type ChatMessage = {
    id?: string;
    role?: string;
    html?: string;
    content?: string;
    created_at?: string | null;
    source?: string | null;
};

function stripHtml(html: string): string {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return (tmp.textContent || tmp.innerText || '').trim();
}

function messageText(msg: ChatMessage): string {
    if (typeof msg.content === 'string' && msg.content.trim()) {
        return msg.content;
    }
    return typeof msg.html === 'string' ? stripHtml(msg.html) : '';
}

function hasConversationHtml(msg: ChatMessage): boolean {
    return typeof msg.html === 'string' && /<figure[^>]*class="ag-attach"|<img\b|class="ag-attach-file"/i.test(msg.html);
}

function isThrottleError(err: unknown): boolean {
    return err instanceof Error && /too many attempts/i.test(err.message);
}

function formatTime(value?: string | null): string {
    if (!value) {
        return '';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export function InboxPage() {
    const boot = useAdminConfig();
    const { t } = useI18n();
    const [rows, setRows] = useState<InboxRow[]>([]);
    const [loading, setLoading] = useState(true);
    const [activeId, setActiveId] = useState<string | null>(null);
    const [thread, setThread] = useState<InboxRow | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [draft, setDraft] = useState('');
    const [busy, setBusy] = useState(false);
    const scroller = useRef<HTMLDivElement | null>(null);
    const stickToBottom = useRef(true);
    const messagesFingerprint = useRef('');

    const loadList = useCallback(async (silent = false) => {
        if (!silent) {
            setLoading(true);
        }
        try {
            const res = await adminApi.get<Paginated<InboxRow>>(boot, '/inbox?per_page=100');
            setRows(res.data);
            setActiveId((current) => current ?? res.data[0]?.id ?? null);
        } catch (err) {
            if (isThrottleError(err)) {
                throw err;
            }
            setRows([]);
        } finally {
            if (!silent) {
                setLoading(false);
            }
        }
    }, [boot]);

    const loadThreadMeta = useCallback(async (id: string) => {
        const conv = await adminApi.get<{ data: InboxRow }>(boot, `/conversations/${id}`);
        const status = conv.data.metadata?.handoff?.status ?? 'none';
        setThread({
            ...conv.data,
            id,
            handoff_status: status,
            needs_human: status === 'requested' || status === 'taken',
        });
    }, [boot]);

    const loadMessages = useCallback(async (id: string, silent = false, withMeta = true) => {
        try {
            if (withMeta) {
                await loadThreadMeta(id);
            }
            const msgs = await adminApi.get<{ data: ChatMessage[] }>(boot, `/conversations/${id}/messages?limit=80`);
            const fingerprint = JSON.stringify(msgs.data);
            if (fingerprint !== messagesFingerprint.current) {
                messagesFingerprint.current = fingerprint;
                setMessages(msgs.data);
            }
        } catch (err) {
            if (isThrottleError(err)) {
                throw err;
            }
            if (!silent) {
                setThread(null);
                setMessages([]);
            }
        }
    }, [boot, loadThreadMeta]);

    useEffect(() => {
        void loadList();
    }, [loadList]);

    useEffect(() => {
        if (!activeId) {
            setThread(null);
            setMessages([]);
            return;
        }
        void loadMessages(activeId);
    }, [activeId, loadMessages]);

    useEffect(() => {
        let timer: number | undefined;
        let delayMs = 8000;

        const schedule = (ms: number) => {
            timer = window.setTimeout(() => void tick(), ms);
        };

        const tick = async () => {
            try {
                await loadList(true);
                if (activeId) {
                    await loadMessages(activeId, true, false);
                }
                delayMs = 8000;
            } catch (err) {
                if (isThrottleError(err)) {
                    delayMs = Math.min(delayMs * 2, 60_000);
                }
            }
            schedule(delayMs);
        };

        schedule(delayMs);

        return () => {
            if (timer !== undefined) {
                window.clearTimeout(timer);
            }
        };
    }, [activeId, loadList, loadMessages]);

    useEffect(() => {
        stickToBottom.current = true;
        messagesFingerprint.current = '';
    }, [activeId]);

    useEffect(() => {
        const el = scroller.current;
        if (el && stickToBottom.current) {
            el.scrollTop = el.scrollHeight;
        }
    }, [messages.length, activeId]);

    const status = thread?.handoff_status ?? thread?.metadata?.handoff?.status ?? 'none';
    const waiting = status === 'requested';

    const take = async () => {
        if (!activeId) {
            return;
        }
        setBusy(true);
        try {
            await adminApi.post(boot, `/conversations/${activeId}/take`, {});
            await Promise.all([loadMessages(activeId, true), loadList(true)]);
        } finally {
            setBusy(false);
        }
    };

    const release = async () => {
        if (!activeId) {
            return;
        }
        setBusy(true);
        try {
            await adminApi.post(boot, `/conversations/${activeId}/release`, {});
            await Promise.all([loadMessages(activeId, true), loadList(true)]);
        } finally {
            setBusy(false);
        }
    };

    const send = async () => {
        const text = draft.trim();
        if (!activeId || !text || busy) {
            return;
        }
        setBusy(true);
        setDraft('');
        const optimistic: ChatMessage = {
            id: `local-${Date.now()}`,
            role: 'assistant',
            content: text,
            source: 'staff',
            created_at: new Date().toISOString(),
        };
        setMessages((current) => [...current, optimistic]);
        try {
            await adminApi.post(boot, `/conversations/${activeId}/reply`, { message: text });
            await Promise.all([loadMessages(activeId, true), loadList(true)]);
        } catch {
            setDraft(text);
            setMessages((current) => current.filter((msg) => msg.id !== optimistic.id));
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="ag-inbox-app">
            <aside className="ag-inbox-threads">
                <div className="ag-inbox-threads__head">
                    <h1>{t('inbox.title')}</h1>
                    <p>{t('inbox.live')}</p>
                </div>
                <div className="ag-inbox-threads__list">
                    {loading ? (
                        <div className="space-y-2 p-3">
                            <Skeleton className="h-14 w-full" />
                            <Skeleton className="h-14 w-full" />
                            <Skeleton className="h-14 w-full" />
                        </div>
                    ) : rows.length === 0 ? (
                        <div className="p-6">
                            <EmptyState icon={Inbox} title={t('inbox.empty')} description={t('inbox.empty_hint')} />
                        </div>
                    ) : (
                        rows.map((row) => {
                            const open = row.id === activeId;
                            const badge = row.handoff_status ?? row.metadata?.handoff?.status ?? 'none';
                            return (
                                <button
                                    key={row.id}
                                    type="button"
                                    className={`ag-inbox-thread${open ? ' is-open' : ''}${row.needs_human ? ' is-hot' : ''}`}
                                    onClick={() => setActiveId(row.id)}
                                >
                                    <span className="ag-inbox-thread__avatar">{row.agent.slice(0, 1).toUpperCase()}</span>
                                    <span className="ag-inbox-thread__copy">
                                        <span className="ag-inbox-thread__top">
                                            <strong>{row.agent}</strong>
                                            <time>{formatTime(row.last_message_at)}</time>
                                        </span>
                                        <span className="ag-inbox-thread__preview">{row.preview || t('inbox.no_preview')}</span>
                                        {badge !== 'none' && <span className="ag-inbox-thread__badge">{badge}</span>}
                                    </span>
                                </button>
                            );
                        })
                    )}
                </div>
            </aside>

            <section className="ag-inbox-chat">
                {!activeId || !thread ? (
                    <div className="flex h-full items-center justify-center p-8">
                        <EmptyState icon={Inbox} title={t('inbox.pick')} description={t('inbox.pick_hint')} />
                    </div>
                ) : (
                    <>
                        <header className="ag-inbox-chat__head">
                            <div>
                                <strong>{thread.agent}</strong>
                                <p>
                                    {thread.user_id ? `${t('fields.user_id')} ${thread.user_id}` : t('inbox.guest')}
                                    {status !== 'none' ? ` · ${status}` : ''}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button type="button" variant="secondary" disabled={busy} onClick={() => void take()}>
                                    {t('inbox.take')}
                                </Button>
                                <Button type="button" variant="ghost" disabled={busy} onClick={() => void release()}>
                                    {t('inbox.release')}
                                </Button>
                            </div>
                        </header>
                        {waiting && <div className="ag-inbox-chat__alert">{t('inbox.waiting_customer')}</div>}
                        <div
                            ref={scroller}
                            className="ag-inbox-chat__scroller"
                            onScroll={(event) => {
                                const el = event.currentTarget;
                                stickToBottom.current = el.scrollHeight - el.scrollTop - el.clientHeight < 72;
                            }}
                        >
                            {messages.length === 0 ? (
                                <p className="py-16 text-center text-sm text-slate-500">{t('empty.messages')}</p>
                            ) : (
                                messages.map((msg, index) => {
                                    const mine = msg.role === 'assistant';
                                    const text = messageText(msg);
                                    const richHtml = hasConversationHtml(msg);
                                    if (!text && !richHtml) {
                                        return null;
                                    }
                                    return (
                                        <div key={msg.id ?? `${index}-${text.slice(0, 12)}`} className={`ag-inbox-bubble-row ${mine ? 'is-mine' : 'is-theirs'}`}>
                                            <div className={`ag-inbox-bubble ${mine ? 'is-mine' : 'is-theirs'}`}>
                                                {richHtml && typeof msg.html === 'string' ? (
                                                    <div className="ag-inbox-bubble__html" dangerouslySetInnerHTML={{ __html: msg.html }} />
                                                ) : (
                                                    <p>{text}</p>
                                                )}
                                                <time>{formatTime(msg.created_at)}</time>
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                        <form
                            className="ag-inbox-composer"
                            onSubmit={(event) => {
                                event.preventDefault();
                                void send();
                            }}
                        >
                            <textarea
                                value={draft}
                                rows={1}
                                placeholder={t('inbox.placeholder')}
                                onChange={(event) => setDraft(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter' && !event.shiftKey) {
                                        event.preventDefault();
                                        void send();
                                    }
                                }}
                            />
                            <Button type="submit" disabled={busy || !draft.trim()}>
                                <Send className="h-4 w-4" />
                                {t('inbox.send')}
                            </Button>
                        </form>
                    </>
                )}
            </section>
        </div>
    );
}
