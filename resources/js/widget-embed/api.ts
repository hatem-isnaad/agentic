import { unwrapMessageResponse } from './message-response';
import type { WidgetConfigResponse, WidgetConversationSummary } from './types';

export type ApiClientOptions = {
    apiBase: string;
    embedToken?: string;
    bearerToken?: string;
    userId?: string;
    guestId: string;
};

export class WidgetApiClient {
    constructor(private opts: ApiClientOptions) {}

    get guestId(): string {
        return this.opts.guestId;
    }

    private headers(json = false): HeadersInit {
        const h: Record<string, string> = {
            Accept: 'application/json',
            'X-Agentic-Guest-Id': this.opts.guestId,
        };
        if (json) {
            h['Content-Type'] = 'application/json';
        }
        const bearer = this.opts.bearerToken || this.opts.embedToken;
        if (bearer) {
            h.Authorization = `Bearer ${bearer}`;
        }
        return h;
    }

    async fetchConfig(agent: string): Promise<WidgetConfigResponse> {
        const url = `${this.opts.apiBase.replace(/\/$/, '')}/config?agent=${encodeURIComponent(agent)}`;
        const res = await fetch(url, { headers: this.headers() });
        const json = await res.json();
        if (!res.ok) {
            throw new Error(json.message || `Config failed (${res.status})`);
        }

        return json as WidgetConfigResponse;
    }

    async sendMessage(
        agent: string,
        message: string,
        conversationId: string | null,
        files: File[] = [],
    ): Promise<unknown> {
        const url = `${this.opts.apiBase.replace(/\/$/, '')}/messages`;
        let res: Response;
        if (files.length > 0) {
            const body = new FormData();
            body.append('agent', agent);
            if (message) {
                body.append('message', message);
            }
            if (conversationId) {
                body.append('conversation_id', conversationId);
            }
            for (const file of files) {
                body.append('files[]', file);
            }
            res = await fetch(url, { method: 'POST', headers: this.headers(), body });
        } else {
            const body: Record<string, string> = { agent, message };
            if (conversationId) {
                body.conversation_id = conversationId;
            }
            res = await fetch(url, { method: 'POST', headers: this.headers(true), body: JSON.stringify(body) });
        }
        const json = await res.json();
        if (!res.ok) {
            throw new Error(json.message || `Send failed (${res.status})`);
        }

        return unwrapMessageResponse(json);
    }

    async requestHandoff(conversationId: string): Promise<Record<string, unknown>> {
        const res = await fetch(`${this.opts.apiBase.replace(/\/$/, '')}/conversations/${encodeURIComponent(conversationId)}/handoff`, {
            method: 'POST',
            headers: this.headers(true),
            body: JSON.stringify({}),
        });
        const json = (await res.json()) as { data?: Record<string, unknown>; message?: string };
        if (!res.ok) {
            throw new Error(json.message || `Handoff failed (${res.status})`);
        }

        return (json.data ?? json) as Record<string, unknown>;
    }

    async fetchMessages(
        conversationId: string,
        options: { limit?: number; before?: number | null; maxLimit?: number } = {},
    ): Promise<{
        messages: { id?: string; cursor?: number; role: string; html?: string; created_at?: string; source?: string | null }[];
        meta: {
            has_more: boolean;
            next_before: number | null;
            handoff?: { active?: boolean; staff_chat?: boolean; status?: string };
            realtime_tail_id?: number;
        };
    }> {
        const params = new URLSearchParams();
        if (options.limit) {
            const cap = options.maxLimit ?? 50;
            const limit = Math.max(1, Math.min(cap, options.limit));
            params.set('limit', String(limit));
        }
        if (options.before) {
            params.set('before', String(options.before));
        }
        const qs = params.toString();
        const url = `${this.opts.apiBase.replace(/\/$/, '')}/conversations/${encodeURIComponent(conversationId)}/messages${qs ? `?${qs}` : ''}`;
        const res = await fetch(url, { headers: this.headers() });
        const json = await res.json();
        if (!res.ok) {
            throw new Error(json.message || `History failed (${res.status})`);
        }

        return {
            messages: (json.data ?? []) as { id?: string; cursor?: number; role: string; html?: string }[],
            meta: (json.meta ?? { has_more: false, next_before: null }) as {
                has_more: boolean;
                next_before: number | null;
                handoff?: { active?: boolean; status?: string };
            },
        };
    }

    async createConversation(agent: string): Promise<WidgetConversationSummary> {
        const res = await fetch(`${this.opts.apiBase.replace(/\/$/, '')}/conversations`, {
            method: 'POST',
            headers: this.headers(true),
            body: JSON.stringify({ agent }),
        });
        const json = await res.json();
        if (!res.ok) {
            throw new Error((json as { message?: string }).message || `Create conversation failed (${res.status})`);
        }
        const row = (json.data ?? json) as WidgetConversationSummary;
        if (typeof row?.id !== 'string' || row.id === '') {
            throw new Error('Create conversation failed');
        }

        return row;
    }

    async fetchConversations(agent: string): Promise<WidgetConversationSummary[]> {
        const url = `${this.opts.apiBase.replace(/\/$/, '')}/conversations?agent=${encodeURIComponent(agent)}`;
        const res = await fetch(url, { headers: this.headers() });
        const json = await res.json();
        if (!res.ok) {
            throw new Error((json as { message?: string }).message || `Conversations failed (${res.status})`);
        }

        return ((json.data ?? []) as WidgetConversationSummary[]).filter(
            (row) => typeof row?.id === 'string' && row.id !== '',
        );
    }

    async fetchLatestConversation(agent: string): Promise<string | null> {
        const list = await this.fetchConversations(agent);

        return list[0]?.id ?? null;
    }

    async approve(id: string): Promise<Record<string, unknown>> {
        return this.postApproval(id, 'approve');
    }

    async reject(id: string): Promise<Record<string, unknown>> {
        return this.postApproval(id, 'reject');
    }

    private async postApproval(id: string, action: 'approve' | 'reject'): Promise<Record<string, unknown>> {
        const res = await fetch(`${this.opts.apiBase.replace(/\/$/, '')}/approvals/${encodeURIComponent(id)}/${action}`, {
            method: 'POST',
            headers: this.headers(true),
            body: JSON.stringify({}),
        });
        const json = (await res.json()) as { data?: Record<string, unknown>; message?: string };
        if (!res.ok) {
            throw new Error(json.message || `${action} failed (${res.status})`);
        }

        return (json.data ?? json) as Record<string, unknown>;
    }

    async pollRealtime(conversationId: string, sinceId: number): Promise<
        { id: number; event: string; payload: Record<string, unknown> }[]
    > {
        const url = `${this.opts.apiBase.replace(/\/$/, '')}/conversations/${encodeURIComponent(conversationId)}/realtime?since_id=${sinceId}`;
        const res = await fetch(url, { headers: this.headers() });
        const json = await res.json();
        if (!res.ok) {
            return [];
        }

        return (json.data ?? []) as { id: number; event: string; payload: Record<string, unknown> }[];
    }
}

export function ensureGuestId(storageKey = 'agentic_guest_id'): string {
    let id = localStorage.getItem(storageKey);
    if (!id) {
        id = crypto.randomUUID();
        localStorage.setItem(storageKey, id);
    }

    return id;
}
