import { WidgetApiClient } from './api';
import {
    clearStoredConversationId,
    loadStoredConversationId,
    saveStoredConversationId,
} from './conversation-storage';
import { historyMessageToBubble, type WidgetHistoryMessage } from './history';
import { playWidgetSound } from './sounds';
import { createRealtimeConnection } from './realtime-factory';
import { disconnectWidgetPusher } from './realtime-pusher';
import { assistantMessageFromEvent, typingActiveFromEvent } from './realtime-events';
import type { WidgetRealtimeConnection } from './realtime-types';
import { EMOJI_CATEGORIES, insertAtCursor } from './emoji';
import { assistantBubbleHtml } from './rich-text';
import { applyTheme, modeFromConfig, themeVarsFromConfig } from './themes';
import { isPendingMessageAck, resolveAssistantReply, type WidgetMessageAck } from './message-response';
import type { AgenticWidgetInit, WidgetConfigResponse, WidgetConversationSummary } from './types';
import './widget.css';

const ICON_CHAT = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M6.5 3h11A3.5 3.5 0 0 1 21 6.5v7A3.5 3.5 0 0 1 17.5 17h-1.86l-2.86 3.7a.95.95 0 0 1-1.56 0L8.36 17H6.5A3.5 3.5 0 0 1 3 13.5v-7A3.5 3.5 0 0 1 6.5 3Zm1.7 4.55a.85.85 0 0 0 0 1.7h7.6a.85.85 0 0 0 0-1.7H8.2Zm0 3.4a.85.85 0 0 0 0 1.7h5.1a.85.85 0 0 0 0-1.7H8.2Z"/></svg>';
const ICON_SPARK = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 9.5 9.5 2 12l7.5 2.5L12 22l2.5-7.5L22 12l-7.5-2.5Z"/></svg>';
const ICON_CLOSE = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.2 5.05 12 10.86l5.8-5.81 1.15 1.15L13.14 12l5.81 5.8-1.15 1.15L12 13.14l-5.8 5.81-1.15-1.15L10.86 12 5.05 6.2Z"/></svg>';
const ICON_SEND = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.4 20.6 21 12 3.4 3.4 3 10l11 2-11 2Z"/></svg>';
const ICON_LIST = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7.5A2.5 2.5 0 0 1 9.5 5h9A2.5 2.5 0 0 1 21 7.5v6A2.5 2.5 0 0 1 18.5 16H17l-2.4 2.6a.7.7 0 0 1-1.2-.5V16H9.5A2.5 2.5 0 0 1 7 13.5Zm-4 3A2.5 2.5 0 0 1 5.5 8H6v5.5A4 4 0 0 0 10 17.4v.6H8.7L6.4 20a.7.7 0 0 1-1.2-.5V17H5.5A2.5 2.5 0 0 1 3 14.5Z"/></svg>';
const ICON_PLUS = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>';
const ICON_THREAD = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 4h11A3.5 3.5 0 0 1 21 7.5v6A3.5 3.5 0 0 1 17.5 17h-1.7l-2.7 3.2a.85.85 0 0 1-1.4 0L8.9 17H6.5A3.5 3.5 0 0 1 3 13.5v-6A3.5 3.5 0 0 1 6.5 4Zm1.6 4.4a.75.75 0 0 0 0 1.5h7.8a.75.75 0 0 0 0-1.5Zm0 3.2a.75.75 0 0 0 0 1.5h5.1a.75.75 0 0 0 0-1.5Z"/></svg>';
const ICON_EMOJI = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-3.3 8.2a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4Zm6.6 0a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4ZM12 17.2A5.1 5.1 0 0 1 7.4 14h1.7a3.4 3.4 0 0 0 5.8 0h1.7A5.1 5.1 0 0 1 12 17.2Z"/></svg>';

const ARABIC_SCRIPT = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

function containsArabic(value: string): boolean {
    return ARABIC_SCRIPT.test(value);
}

function stripPreviewMarkup(value: string): string {
    return value
        .replace(/\*\*(.*?)\*\*/g, '$1')
        .replace(/__(.*?)__/g, '$1')
        .replace(/`+/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function visibleBubbleText(text: string, html: string | null): string {
    if (!html) {
        return text;
    }

    const tmp = document.createElement('div');
    tmp.innerHTML = html;

    return tmp.textContent ?? text;
}

function applyAssistantDirection(el: HTMLElement, text: string, html: string | null): void {
    if (!containsArabic(visibleBubbleText(text, html))) {
        return;
    }

    el.dir = 'rtl';
    el.style.direction = 'rtl';
}

function agentDisplayName(cfg: WidgetConfigResponse): string {
    const agent = cfg.data.agent;
    if (typeof agent === 'object' && agent !== null && 'name' in agent) {
        return String((agent as { name?: string }).name ?? (agent as { slug?: string }).slug ?? 'Assistant');
    }

    return String(agent);
}

export class WidgetUi {
    private launcherEl!: HTMLButtonElement;
    private panel!: HTMLElement;
    private messagesEl: HTMLElement;
    private loadMoreEl: HTMLElement;
    private input: HTMLInputElement;
    private open = false;
    private conversationId: string | null;
    private api: WidgetApiClient;
    private config: AgenticWidgetInit;
    private agentLabel = 'Assistant';
    private realtime: WidgetRealtimeConnection | null = null;
    private pollMs = 3000;
    private realtimeDriver = 'null';
    private channelPrefix = 'agentic-widget';
    private pusherConfig: { key?: string | null; cluster?: string | null } = {};
    private realtimeSinceId = 0;
    private replyMode: 'sync' | 'async' = 'sync';
    private typingEl: HTMLElement | null = null;
    private historyPageSize = 20;
    private historyMaxPageSize = 50;
    private configReady = false;
    private historyStatusEl: HTMLElement | null = null;
    private historyLoading = false;
    private hasMoreHistory = false;
    private nextBefore: number | null = null;
    private welcomeText: string | null = null;
    private realtimeDot: HTMLElement | null = null;
    private seenMessageIds = new Set<string>();
    private realtimeBoundConversationId: string | null = null;
    private sending = false;
    private awaitingAssistantReply = false;
    private pusherSubscribed = false;
    private reconcileInFlight = false;
    /** Only accept assistant messages newer than the tail cursor captured when the user sent. */
    private pendingAssistantAfterCursor: number | null = null;
    private root: HTMLElement | null = null;
    private mounted = false;
    private inboxEl: HTMLElement | null = null;
    private inboxListEl: HTMLElement | null = null;
    private inboxBackdropEl: HTMLElement | null = null;
    private inboxOpen = false;
    private conversations: WidgetConversationSummary[] = [];
    private resumeAfterHours = 24;
    private historyObserver: IntersectionObserver | null = null;
    private emojiBtn!: HTMLButtonElement;
    private emojiPop!: HTMLElement;
    private emojiGrid!: HTMLElement;
    private emojiOpen = false;

    constructor(config: AgenticWidgetInit, api: WidgetApiClient) {
        this.config = config;
        this.api = api;
        this.conversationId =
            config.conversationId ?? loadStoredConversationId(config.agent, api.guestId) ?? null;
        this.build();
        this.mounted = true;
    }

    private build(): void {
        this.root = document.createElement('div');
        this.root.className = 'ag-widget-root';
        this.root.style.zIndex = String(this.config.zIndex ?? 2147483000);
        document.body.appendChild(this.root);

        const pos = this.config.position ?? 'bottom-right';
        this.root.dataset.position = pos;

        this.launcherEl = document.createElement('button');
        this.launcherEl.type = 'button';
        this.launcherEl.className = 'ag-launcher';
        this.launcherEl.setAttribute('aria-label', 'Open chat');
        this.launcherEl.innerHTML = ICON_CHAT;
        this.launcherEl.addEventListener('click', () => this.toggle());

        this.panel = document.createElement('div');
        this.panel.className = 'ag-panel';
        this.panel.hidden = true;

        const header = document.createElement('header');
        header.className = 'ag-header';
        header.innerHTML = `
            <div class="ag-title-wrap">
                <div class="ag-agent-avatar">${ICON_SPARK}</div>
                <div class="ag-title-copy">
                    <strong class="ag-title">Assistant</strong>
                    <span class="ag-subtitle">
                        <span class="ag-realtime-dot" data-state="disconnected" title="Realtime"></span>
                        <span class="ag-status-text">Online</span>
                    </span>
                </div>
            </div>
            <div class="ag-header-actions">
                <button type="button" class="ag-icon-btn ag-inbox-toggle" aria-label="Conversations">${ICON_LIST}</button>
                <button type="button" class="ag-icon-btn ag-close" aria-label="Close">${ICON_CLOSE}</button>
            </div>
        `;
        this.realtimeDot = header.querySelector('.ag-realtime-dot');
        header.querySelector('.ag-close')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.setPanelOpen(false);
        });
        header.querySelector('.ag-inbox-toggle')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            void this.toggleInbox();
        });

        this.messagesEl = document.createElement('div');
        this.messagesEl.className = 'ag-messages';
        this.messagesEl.setAttribute('aria-live', 'polite');

        this.loadMoreEl = document.createElement('div');
        this.loadMoreEl.className = 'ag-load-more';
        this.loadMoreEl.hidden = true;
        this.loadMoreEl.setAttribute('aria-hidden', 'true');
        this.loadMoreEl.innerHTML = '<span class="ag-dots"><span></span><span></span><span></span></span>';
        this.messagesEl.appendChild(this.loadMoreEl);
        this.messagesEl.addEventListener('scroll', () => this.onMessagesScroll(), { passive: true });

        const form = document.createElement('form');
        form.className = 'ag-composer';
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = 'Loading…';
        this.input.disabled = true;
        this.input.required = true;
        this.emojiBtn = document.createElement('button');
        this.emojiBtn.type = 'button';
        this.emojiBtn.className = 'ag-emoji-btn';
        this.emojiBtn.setAttribute('aria-label', 'Add emoji');
        this.emojiBtn.setAttribute('aria-expanded', 'false');
        this.emojiBtn.innerHTML = ICON_EMOJI;
        this.emojiBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.setEmojiOpen(!this.emojiOpen);
        });
        this.emojiPop = this.buildEmojiPicker();
        const send = document.createElement('button');
        send.type = 'submit';
        send.className = 'ag-send';
        send.setAttribute('aria-label', 'Send');
        send.innerHTML = ICON_SEND;
        form.append(this.input, this.emojiBtn, send, this.emojiPop);
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.setEmojiOpen(false);
            void this.onSend();
        });
        document.addEventListener('click', (e) => {
            if (!this.emojiOpen) {
                return;
            }
            const target = e.target as Node | null;
            if (this.emojiPop.contains(target) || this.emojiBtn.contains(target)) {
                return;
            }
            this.setEmojiOpen(false);
        });

        this.inboxBackdropEl = document.createElement('div');
        this.inboxBackdropEl.className = 'ag-inbox-backdrop';
        this.inboxBackdropEl.hidden = true;
        this.inboxBackdropEl.addEventListener('click', () => this.showInbox(false));

        this.inboxEl = document.createElement('aside');
        this.inboxEl.className = 'ag-inbox';
        this.inboxEl.hidden = true;
        this.inboxEl.setAttribute('role', 'dialog');
        this.inboxEl.setAttribute('aria-label', 'Conversations');
        this.inboxEl.innerHTML = `
            <div class="ag-inbox-head">
                <div class="ag-inbox-head-copy">
                    <strong>Conversations</strong>
                    <span>Recent chats</span>
                </div>
                <button type="button" class="ag-icon-btn ag-inbox-close" aria-label="Close conversations">${ICON_CLOSE}</button>
            </div>
            <button type="button" class="ag-inbox-new">${ICON_PLUS}<span>New conversation</span></button>
            <div class="ag-inbox-list"></div>
        `;
        this.inboxListEl = this.inboxEl.querySelector('.ag-inbox-list');
        this.inboxEl.querySelector('.ag-inbox-close')?.addEventListener('click', () => this.showInbox(false));
        this.inboxEl.querySelector('.ag-inbox-new')?.addEventListener('click', () => {
            void this.startNewConversation();
        });

        this.panel.append(header, this.messagesEl, form, this.inboxBackdropEl, this.inboxEl);
        this.root.append(this.launcherEl, this.panel);
        this.bindHistoryObserver();
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') {
                return;
            }
            if (this.emojiOpen) {
                e.preventDefault();
                this.setEmojiOpen(false);
                return;
            }
            if (this.inboxOpen) {
                e.preventDefault();
                this.showInbox(false);
            }
        });

        applyTheme(this.root, this.config.theme ?? 'system');
        if (this.config.open) {
            this.setPanelOpen(true);
        }
    }

    async bootstrap(): Promise<boolean> {
        try {
            const cfg = await this.api.fetchConfig(this.config.agent);

            const embed = cfg.data.embed ?? {};
            if (embed.auth_required && !this.config.userId && !this.config.bearerToken) {
                throw new Error('This embed token requires userId in AgenticChat.init.');
            }

            this.agentLabel = agentDisplayName(cfg);

            const themeCfg = cfg.data.theme ?? {};
            const mode = this.config.theme ?? modeFromConfig('system', themeCfg as Record<string, unknown>);
            applyTheme(this.root!, mode, themeVarsFromConfig(themeCfg as Record<string, unknown>));

            const rt = cfg.data.realtime ?? {};
            this.realtimeDriver = rt.driver ?? 'null';
            if (this.realtimeDriver !== 'pusher' && this.realtimeDot) {
                this.realtimeDot.hidden = true;
            }
            this.channelPrefix = rt.channel_prefix ?? 'agentic-widget';
            if (rt.interval_ms) {
                this.pollMs = rt.interval_ms;
            }
            this.pusherConfig = rt.pusher ?? {};
            this.replyMode = cfg.data.reply?.mode === 'async' ? 'async' : 'sync';
            this.historyMaxPageSize = cfg.data.history?.max_page_size ?? 50;
            this.historyPageSize = Math.min(
                cfg.data.history?.page_size ?? 20,
                this.historyMaxPageSize,
            );
            this.resumeAfterHours = Math.max(1, cfg.data.conversation?.resume_after_hours ?? 24);

            const welcome = cfg.data.welcome ?? cfg.data.intake?.welcome_message;
            this.welcomeText = typeof welcome === 'string' && welcome.trim() ? welcome : null;

            this.configReady = true;

            if (this.panelOpenPendingHistory) {
                this.panelOpenPendingHistory = false;
            }
            if (this.open) {
                await this.onPanelOpened();
            }

            const title = this.root?.querySelector('.ag-title');
            if (title) {
                title.textContent = this.agentLabel;
            }
            const status = this.root?.querySelector('.ag-status-text');
            if (status) {
                status.textContent = this.realtimeDriver === 'pusher' ? 'Live support' : 'Online';
            }

            this.input.placeholder = 'Type a message…';
            this.input.disabled = false;

            return true;
        } catch (err) {
            console.error('[AgenticChat]', err);
            if (this.input) {
                this.input.placeholder = 'Chat unavailable';
                this.input.disabled = true;
            }

            return false;
        }
    }

    private async refreshConversationList(): Promise<WidgetConversationSummary[]> {
        this.conversations = await this.api.fetchConversations(this.config.agent);
        this.renderInbox();

        return this.conversations;
    }

    private conversationActivityMs(row: WidgetConversationSummary): number {
        const raw = row.last_message_at ?? row.updated_at ?? row.created_at;
        const ms = raw ? Date.parse(raw) : Number.NaN;

        return Number.isFinite(ms) ? ms : 0;
    }

    private isFreshConversation(row: WidgetConversationSummary): boolean {
        const ageMs = Date.now() - this.conversationActivityMs(row);

        return ageMs >= 0 && ageMs < this.resumeAfterHours * 60 * 60 * 1000;
    }

    private async resolveConversationForSession(): Promise<void> {
        let list: WidgetConversationSummary[] = [];
        try {
            list = await this.refreshConversationList();
        } catch {
            this.conversationId = loadStoredConversationId(this.config.agent, this.api.guestId);
            return;
        }

        const stored = loadStoredConversationId(this.config.agent, this.api.guestId);
        const storedRow = stored ? list.find((row) => row.id === stored) : undefined;
        if (storedRow) {
            this.conversationId = storedRow.id;

            return;
        }

        const latest = list[0];
        if (latest && this.isFreshConversation(latest)) {
            this.conversationId = latest.id;
            this.persistConversationId();

            return;
        }

        this.conversationId = null;
        clearStoredConversationId(this.config.agent, this.api.guestId);
    }

    private panelOpenPendingHistory = false;

    private async onPanelOpened(): Promise<void> {
        if (!this.configReady) {
            this.panelOpenPendingHistory = true;

            return;
        }
        if (this.historyLoading) {
            return;
        }

        this.historyLoading = true;
        this.setHistoryStatus('Loading messages…');
        try {
            await this.resolveConversationForSession();

            if (!this.conversationId) {
                this.clearHistoryStatus();
                this.clearChatBubbles();
                this.seenMessageIds.clear();
                this.showWelcomeIfEmpty();

                return;
            }

            await this.loadHistoryWithRecovery();
            this.startRealtime();
        } catch (err) {
            this.clearHistoryStatus();
            console.error('[AgenticChat] history', err);
            this.showWelcomeIfEmpty();
        } finally {
            this.historyLoading = false;
            this.clearHistoryStatus();
        }
    }

    private async toggleInbox(): Promise<void> {
        if (this.inboxOpen) {
            this.showInbox(false);

            return;
        }
        this.showInbox(true);
        try {
            await this.refreshConversationList();
        } catch (err) {
            console.error('[AgenticChat] conversations', err);
        }
    }

    private showInbox(open: boolean): void {
        if (open) {
            this.setEmojiOpen(false);
        }
        this.inboxOpen = open;
        this.panel?.classList.toggle('ag-inbox-open', open);
        if (this.inboxEl) {
            this.inboxEl.hidden = !open;
            this.inboxEl.classList.toggle('is-open', open);
            this.inboxEl.setAttribute('aria-hidden', open ? 'false' : 'true');
        }
        if (this.inboxBackdropEl) {
            this.inboxBackdropEl.hidden = !open;
            this.inboxBackdropEl.classList.toggle('is-open', open);
        }
        const toggle = this.root?.querySelector('.ag-inbox-toggle') as HTMLElement | null;
        if (toggle) {
            toggle.classList.toggle('is-active', open);
            toggle.setAttribute('aria-pressed', open ? 'true' : 'false');
        }
    }

    private buildEmojiPicker(): HTMLElement {
        const pop = document.createElement('div');
        pop.className = 'ag-emoji-pop';
        pop.hidden = true;
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Emoji');

        const tabs = document.createElement('div');
        tabs.className = 'ag-emoji-tabs';
        this.emojiGrid = document.createElement('div');
        this.emojiGrid.className = 'ag-emoji-grid';

        EMOJI_CATEGORIES.forEach((category, index) => {
            const tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'ag-emoji-tab';
            tab.textContent = category.icon;
            tab.setAttribute('aria-label', category.label);
            if (index === 0) {
                tab.classList.add('is-active');
            }
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                tabs.querySelectorAll('.ag-emoji-tab').forEach((el) => el.classList.remove('is-active'));
                tab.classList.add('is-active');
                this.renderEmojiGrid(category.emojis);
            });
            tabs.appendChild(tab);
        });

        pop.append(tabs, this.emojiGrid);
        this.renderEmojiGrid(EMOJI_CATEGORIES[0]?.emojis ?? []);

        return pop;
    }

    private renderEmojiGrid(emojis: string[]): void {
        this.emojiGrid.replaceChildren();
        for (const emoji of emojis) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ag-emoji-item';
            btn.textContent = emoji;
            btn.setAttribute('aria-label', emoji);
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this.input.disabled) {
                    return;
                }
                insertAtCursor(this.input, emoji);
            });
            this.emojiGrid.appendChild(btn);
        }
    }

    private setEmojiOpen(open: boolean): void {
        this.emojiOpen = open;
        if (this.emojiPop) {
            this.emojiPop.hidden = !open;
        }
        if (this.emojiBtn) {
            this.emojiBtn.classList.toggle('is-open', open);
            this.emojiBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    private renderInbox(): void {
        if (!this.inboxListEl) {
            return;
        }
        this.inboxListEl.replaceChildren();
        if (this.conversations.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ag-inbox-empty';
            empty.textContent = 'No previous conversations yet.';
            this.inboxListEl.appendChild(empty);

            return;
        }

        for (const row of this.conversations) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ag-inbox-item';
            if (row.id === this.conversationId) {
                btn.classList.add('is-active');
            }
            const preview = stripPreviewMarkup(row.preview?.trim() || 'New conversation');
            const avatar = document.createElement('span');
            avatar.className = 'ag-inbox-avatar';
            avatar.innerHTML = ICON_THREAD;
            const copy = document.createElement('span');
            copy.className = 'ag-inbox-copy';
            const title = document.createElement('strong');
            title.textContent = preview;
            if (containsArabic(preview)) {
                title.dir = 'rtl';
                title.style.direction = 'rtl';
            }
            const meta = document.createElement('span');
            meta.textContent = this.formatConversationTime(row);
            copy.append(title, meta);
            btn.append(avatar, copy);
            btn.addEventListener('click', () => {
                void this.openConversation(row.id);
            });
            this.inboxListEl.appendChild(btn);
        }
    }

    private formatConversationTime(row: WidgetConversationSummary): string {
        const ms = this.conversationActivityMs(row);
        if (!ms) {
            return 'Earlier';
        }
        const delta = Date.now() - ms;
        if (delta < 60_000) {
            return 'Just now';
        }
        if (delta < 3_600_000) {
            return `${Math.floor(delta / 60_000)} min ago`;
        }
        if (delta < 86_400_000) {
            return `${Math.floor(delta / 3_600_000)} h ago`;
        }

        return new Date(ms).toLocaleDateString();
    }

    private async startNewConversation(): Promise<void> {
        this.realtime?.stop();
        this.realtimeBoundConversationId = null;
        this.conversationId = null;
        this.seenMessageIds.clear();
        this.hasMoreHistory = false;
        this.nextBefore = null;
        this.syncHistorySentinel(false);
        this.awaitingAssistantReply = false;
        this.pendingAssistantAfterCursor = null;
        clearStoredConversationId(this.config.agent, this.api.guestId);
        this.clearChatBubbles();
        this.showWelcomeIfEmpty();
        this.showInbox(false);
        this.input.disabled = true;
        try {
            const row = await this.api.createConversation(this.config.agent);
            this.conversationId = row.id;
            this.persistConversationId();
            void this.refreshConversationList().catch(() => undefined);
        } catch (err) {
            console.error('[AgenticChat] new conversation', err);
        } finally {
            if (this.configReady) {
                this.input.disabled = false;
                this.input.focus();
            }
        }
    }

    private async openConversation(id: string): Promise<void> {
        this.conversationId = id;
        this.persistConversationId();
        this.showInbox(false);
        this.seenMessageIds.clear();
        this.setHistoryStatus('Loading messages…');
        try {
            await this.loadHistoryPage(null);
            this.startRealtime();
        } catch (err) {
            console.error('[AgenticChat] open conversation', err);
        } finally {
            this.clearHistoryStatus();
        }
    }

    private async loadHistoryWithRecovery(): Promise<void> {
        if (!this.conversationId) {
            return;
        }

        try {
            await this.loadHistoryPage(null);
        } catch {
            clearStoredConversationId(this.config.agent, this.api.guestId);
            this.conversationId = null;
            await this.resolveConversationForSession();
            if (!this.conversationId) {
                throw new Error('No conversation history');
            }
            await this.loadHistoryPage(null);
        }
    }

    private setHistoryStatus(text: string): void {
        this.clearHistoryStatus();
        this.historyStatusEl = document.createElement('div');
        this.historyStatusEl.className = 'ag-bubble ag-assistant ag-history-status';
        this.historyStatusEl.textContent = text;
        this.messagesEl.appendChild(this.historyStatusEl);
    }

    private clearHistoryStatus(): void {
        this.historyStatusEl?.remove();
        this.historyStatusEl = null;
    }

    private showWelcomeIfEmpty(): void {
        if (this.conversationId) {
            return;
        }
        if (this.welcomeText && this.countBubbles() === 0) {
            this.addBubble('assistant', this.welcomeText, null);
        }
    }

    private countBubbles(): number {
        return this.messagesEl.querySelectorAll('.ag-bubble').length;
    }

    private clearChatBubbles(): void {
        this.messagesEl.querySelectorAll('.ag-row, .ag-bubble').forEach((el) => el.remove());
    }

    private async loadHistoryPage(before: number | null): Promise<void> {
        if (!this.conversationId) {
            return;
        }

        const isInitial = before === null;
        this.syncHistorySentinel(true);

        const { messages, meta } = await this.api.fetchMessages(this.conversationId, {
            limit: this.historyPageSize,
            maxLimit: this.historyMaxPageSize,
            before,
        });

        if (isInitial) {
            this.clearChatBubbles();
            this.seenMessageIds.clear();
            if (messages.length === 0) {
                this.showWelcomeIfEmpty();
            } else {
                for (const msg of messages) {
                    this.registerHistoryMessage(msg);
                    this.insertHistoryMessage(msg, false);
                }
                this.scrollMessagesToBottom();
            }
        } else {
            const prevHeight = this.messagesEl.scrollHeight;
            for (const msg of messages) {
                this.registerHistoryMessage(msg);
                const bubble = historyMessageToBubble(msg);
                if (!bubble.text && !bubble.html) {
                    continue;
                }
                const el = this.createBubbleElement(bubble.role, bubble.text, bubble.html);
                const anchor = this.loadMoreEl.nextElementSibling;
                if (anchor) {
                    this.messagesEl.insertBefore(el, anchor);
                } else {
                    this.messagesEl.appendChild(el);
                }
            }
            const delta = this.messagesEl.scrollHeight - prevHeight;
            this.messagesEl.scrollTop += delta;
        }

        this.hasMoreHistory = meta.has_more;
        this.nextBefore = meta.next_before;
        this.syncHistorySentinel(false);
    }

    private bindHistoryObserver(): void {
        this.historyObserver = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                void this.loadOlderMessages();
            }
        }, {
            root: this.messagesEl,
            rootMargin: '72px 0px 0px 0px',
            threshold: 0,
        });
    }

    private syncHistorySentinel(loading: boolean): void {
        this.loadMoreEl.hidden = !this.hasMoreHistory;
        this.loadMoreEl.classList.toggle('is-loading', loading && this.hasMoreHistory);
        if (!this.historyObserver) {
            return;
        }
        if (this.hasMoreHistory) {
            this.historyObserver.observe(this.loadMoreEl);
        } else {
            this.historyObserver.unobserve(this.loadMoreEl);
        }
    }

    private onMessagesScroll(): void {
        if (this.messagesEl.scrollTop > 48) {
            return;
        }
        void this.loadOlderMessages();
    }

    private async loadOlderMessages(): Promise<void> {
        if (!this.hasMoreHistory || this.nextBefore === null || this.historyLoading) {
            return;
        }

        this.historyLoading = true;
        this.syncHistorySentinel(true);
        try {
            await this.loadHistoryPage(this.nextBefore);
        } finally {
            this.historyLoading = false;
            this.syncHistorySentinel(false);
        }
    }

    private insertHistoryMessage(msg: WidgetHistoryMessage, prepend: boolean): void {
        const bubble = historyMessageToBubble(msg);
        if (!bubble.text && !bubble.html) {
            return;
        }

        const el = this.createBubbleElement(bubble.role, bubble.text, bubble.html);
        if (prepend) {
            const anchor = this.loadMoreEl.nextElementSibling;
            if (anchor) {
                this.messagesEl.insertBefore(el, anchor);
            } else {
                this.messagesEl.appendChild(el);
            }
        } else {
            this.messagesEl.appendChild(el);
        }
    }

    private createBubbleElement(role: 'user' | 'assistant', text: string, html: string | null): HTMLElement {
        const row = document.createElement('div');
        row.className = `ag-row ag-row-${role}`;
        const el = document.createElement('div');
        el.className = `ag-bubble ag-${role}`;
        if (role === 'assistant') {
            const rich = assistantBubbleHtml(html, text);
            if (rich) {
                el.classList.add('ag-rich');
                el.innerHTML = rich;
            } else {
                el.textContent = text;
            }
            applyAssistantDirection(el, text, html);
            const avatar = document.createElement('div');
            avatar.className = 'ag-mini-avatar';
            avatar.innerHTML = ICON_SPARK;
            row.append(avatar, el);
        } else {
            el.textContent = text;
            row.append(el);
        }

        return row;
    }

    private scrollMessagesToBottom(): void {
        this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
    }

    private startRealtime(): void {
        if (!this.conversationId) {
            return;
        }
        if (this.realtimeBoundConversationId === this.conversationId && this.realtime) {
            return;
        }
        this.realtime?.stop();
        this.realtimeBoundConversationId = this.conversationId;

        if (this.realtimeDriver === 'pusher' && this.realtimeDot) {
            this.realtimeDot.dataset.state = 'connecting';
            this.realtimeDot.title = `Pusher · ${this.channelPrefix}.${this.conversationId}`;
        }

        const connection = createRealtimeConnection(
            this.realtimeDriver,
            this.api,
            this.conversationId,
            this.pollMs,
            this.channelPrefix,
            this.pusherConfig,
            (ev) => this.handleRealtimeEvent(ev),
            this.realtimeSinceId,
            {
                debug: this.config.debug === true,
                onPusherState: (state) => {
                    if (this.realtimeDriver === 'pusher') {
                        if (state === 'connected') {
                            this.pusherSubscribed = true;
                        } else if (state === 'disconnected') {
                            this.pusherSubscribed = false;
                        }
                    }
                    if (!this.realtimeDot || this.realtimeDriver !== 'pusher') {
                        return;
                    }
                    const mapped =
                        state === 'connected' ? 'connected' : state === 'disconnected' ? 'disconnected' : 'connecting';
                    this.realtimeDot.dataset.state = mapped;
                },
                onPusherSubscribed: () => {
                    if (this.awaitingAssistantReply) {
                        void this.reconcileLatestAssistantMessage();
                    }
                },
            },
        );

        this.realtime = connection;
        connection?.start();
    }

    private handleRealtimeEvent(ev: {
        id: number;
        event: string;
        payload: Record<string, unknown>;
    }): void {
        if (ev.id > 0) {
            this.realtimeSinceId = Math.max(this.realtimeSinceId, ev.id);
        }

        const typing = typingActiveFromEvent(ev);
        if (typing !== null) {
            this.setTyping(typing);

            return;
        }

        const reply = assistantMessageFromEvent(ev);
        if (!reply) {
            return;
        }

        const msg = ev.payload.message;
        const mid =
            msg && typeof msg === 'object' && typeof (msg as { id?: string }).id === 'string'
                ? (msg as { id: string }).id
                : null;

        this.showAssistantIfNew(mid, reply.text, reply.html);
    }

    private trackMessageId(id: string | undefined): void {
        if (id) {
            this.seenMessageIds.add(`id:${id}`);
        }
    }

    private registerHistoryMessage(msg: WidgetHistoryMessage): void {
        this.trackMessageId(msg.id);
        if (msg.role !== 'assistant') {
            return;
        }
        const bubble = historyMessageToBubble(msg);
        if (!bubble.text && !bubble.html) {
            return;
        }
        const primary = this.assistantDedupeKey(msg.id ?? null, bubble.text, bubble.html);
        const textOnly = this.assistantDedupeKey(null, bubble.text, bubble.html);
        this.seenMessageIds.add(primary);
        this.seenMessageIds.add(textOnly);
    }

    private assistantDedupeKey(id: string | null, text: string, html: string | null): string {
        if (id) {
            return `id:${id}`;
        }

        const tmp = document.createElement('div');
        if (html) {
            tmp.innerHTML = html;
        }

        const normalized = (html ? tmp.textContent : text)?.trim() ?? '';

        return `txt:${normalized.slice(0, 400)}`;
    }

    private showAssistantIfNew(id: string | null, text: string, html: string | null): boolean {
        const primary = this.assistantDedupeKey(id, text, html);
        if (this.seenMessageIds.has(primary)) {
            return false;
        }

        const textOnly = this.assistantDedupeKey(null, text, html);
        if (id && this.seenMessageIds.has(textOnly)) {
            return false;
        }

        this.seenMessageIds.add(primary);
        this.seenMessageIds.add(textOnly);
        this.setTyping(false);
        this.awaitingAssistantReply = false;
        this.pendingAssistantAfterCursor = null;
        this.addBubble('assistant', text, html);
        playWidgetSound('receive', this.config.sounds ?? true);

        return true;
    }

    /** Catches replies that were broadcast before the WebSocket subscription finished. */
    private async reconcileLatestAssistantMessage(): Promise<void> {
        if (!this.conversationId || !this.awaitingAssistantReply || this.reconcileInFlight) {
            return;
        }

        this.reconcileInFlight = true;
        try {
            const { messages } = await this.api.fetchMessages(this.conversationId, {
                limit: 8,
                maxLimit: this.historyMaxPageSize,
            });
            const minCursor = this.pendingAssistantAfterCursor ?? 0;
            for (let i = messages.length - 1; i >= 0; i--) {
                const msg = messages[i];
                if (msg.role !== 'assistant') {
                    continue;
                }
                if (typeof msg.cursor === 'number' && msg.cursor <= minCursor) {
                    continue;
                }
                const bubble = historyMessageToBubble(msg);
                if (!bubble.text && !bubble.html) {
                    continue;
                }
                if (this.showAssistantIfNew(msg.id ?? null, bubble.text, bubble.html)) {
                    return;
                }
            }
        } catch {
            /* ignore */
        } finally {
            this.reconcileInFlight = false;
        }
    }

    private async syncRealtimeCursor(): Promise<void> {
        if (!this.conversationId) {
            return;
        }
        try {
            const events = await this.api.pollRealtime(this.conversationId, this.realtimeSinceId);
            for (const ev of events) {
                this.realtimeSinceId = Math.max(this.realtimeSinceId, ev.id);
            }
        } catch {
            /* ignore */
        }
    }

    private setPanelOpen(open: boolean): void {
        this.open = open;
        if (!this.panel) {
            return;
        }
        this.panel.hidden = !open;
        this.root?.classList.toggle('ag-is-open', open);
        this.launcherEl.classList.toggle('is-open', open);
        this.launcherEl.innerHTML = open ? ICON_CLOSE : ICON_CHAT;
        this.launcherEl.setAttribute('aria-label', open ? 'Close chat' : 'Open chat');
        if (!open) {
            this.panelOpenPendingHistory = false;
            this.showInbox(false);
            this.setEmojiOpen(false);
            this.input?.blur();

            return;
        }
        this.input.focus();
        void this.onPanelOpened();
    }

    private toggle(): void {
        this.setPanelOpen(!this.open);
    }

    private setTyping(active: boolean): void {
        if (!active) {
            this.typingEl?.remove();
            this.typingEl = null;

            return;
        }

        if (this.typingEl) {
            return;
        }

        this.typingEl = this.createBubbleElement('assistant', '', null);
        const bubble = this.typingEl.querySelector('.ag-bubble');
        if (bubble) {
            bubble.classList.add('ag-typing');
            bubble.innerHTML = '<span>Assistant is typing</span><span class="ag-dots" aria-hidden="true"><span></span><span></span><span></span></span>';
        }
        this.typingEl.setAttribute('aria-live', 'polite');
        this.messagesEl.appendChild(this.typingEl);
        this.scrollMessagesToBottom();
    }

    private addBubble(role: 'user' | 'assistant', text: string, html: string | null): void {
        this.messagesEl.appendChild(this.createBubbleElement(role, text, html));
        this.scrollMessagesToBottom();
    }

    private persistConversationId(): void {
        if (this.conversationId) {
            saveStoredConversationId(this.config.agent, this.api.guestId, this.conversationId);
        }
    }

    private async captureConversationTailCursor(): Promise<number> {
        if (!this.conversationId) {
            return 0;
        }

        try {
            const { messages } = await this.api.fetchMessages(this.conversationId, {
                limit: 1,
                maxLimit: this.historyMaxPageSize,
            });
            const last = messages[messages.length - 1];

            return typeof last?.cursor === 'number' ? last.cursor : 0;
        } catch {
            return 0;
        }
    }

    private async onSend(): Promise<void> {
        const text = this.input.value.trim();
        if (!text || this.sending || !this.configReady) {
            return;
        }
        this.sending = true;
        this.input.value = '';
        this.input.disabled = true;
        this.pendingAssistantAfterCursor = await this.captureConversationTailCursor();
        this.addBubble('user', text);
        playWidgetSound('send', this.config.sounds ?? true);
        this.setTyping(true);
        this.awaitingAssistantReply = this.replyMode === 'async' || this.realtimeDriver === 'pusher';
        if (this.conversationId) {
            this.startRealtime();
        }
        try {
            const data = (await this.api.sendMessage(
                this.config.agent,
                text,
                this.conversationId,
            )) as WidgetMessageAck;

            if (typeof data.conversation_id === 'string') {
                this.conversationId = data.conversation_id;
                this.persistConversationId();
                void this.refreshConversationList().catch(() => undefined);
            }

            if (this.conversationId) {
                this.startRealtime();
            }

            if (isPendingMessageAck(data)) {
                this.awaitingAssistantReply = true;

                return;
            }

            if (this.realtimeDriver === 'polling') {
                await this.syncRealtimeCursor();
            }

            if (this.realtimeDriver === 'pusher') {
                if (data.success === false) {
                    this.setTyping(false);
                    this.awaitingAssistantReply = false;
                    this.pendingAssistantAfterCursor = null;
                    this.addBubble('assistant', String(data.error ?? 'Something went wrong.'), null);

                    return;
                }

                /* Assistant text is delivered on the Pusher channel (or reconcile), not the HTTP body. */
                return;
            }

            this.setTyping(false);
            this.awaitingAssistantReply = false;
            const reply = resolveAssistantReply(data);
            if (reply) {
                const msgId =
                    data.message && typeof data.message.id === 'string' ? data.message.id : null;
                this.showAssistantIfNew(msgId, reply.text, reply.html);
            }
        } catch (err) {
            this.setTyping(false);
            this.awaitingAssistantReply = false;
            this.addBubble('assistant', err instanceof Error ? err.message : 'Error', null);
        } finally {
            this.sending = false;
            this.input.disabled = false;
            this.input.focus();
        }
    }

    destroy(): void {
        this.setTyping(false);
        this.realtime?.stop();
        this.realtimeBoundConversationId = null;
        if (this.pusherConfig.key && this.pusherConfig.cluster) {
            disconnectWidgetPusher(this.pusherConfig.key, this.pusherConfig.cluster);
        }
        this.root?.remove();
        this.root = null;
        this.mounted = false;
    }
}
