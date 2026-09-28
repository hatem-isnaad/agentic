import { WidgetApiClient } from './api';
import {
    clearStoredConversationId,
    loadStoredConversationId,
    saveStoredConversationId,
} from './conversation-storage';
import { historyMessageToBubble, type WidgetHistoryMessage } from './history';
import { dayKey, formatDayLabelEn, formatMessageTimeEn } from './timestamps';
import { playWidgetSound } from './sounds';
import { createRealtimeConnection } from './realtime-factory';
import { disconnectWidgetPusher } from './realtime-pusher';
import { assistantMessageFromEvent, streamDeltaFromEvent, typingActiveFromEvent } from './realtime-events';
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
const ICON_ATTACH = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.2 12.8 14 7a2.8 2.8 0 1 1 4 4l-7.3 7.3a4.2 4.2 0 0 1-6-6l7.1-7.1 1.2 1.2-7.1 7.1a2.5 2.5 0 0 0 3.5 3.5L18 8.6a1.1 1.1 0 1 0-1.6-1.6l-5.8 5.8 1.2 1.2 5.8-5.8a2.8 2.8 0 0 1 4 4L9.4 19.1a4.2 4.2 0 1 1-6-6l7.3-7.3 1.2 1.2-7.3 7.3a2.5 2.5 0 0 0 3.6 3.5l7.1-7.1 1.2 1.2-7.1 7.1a4.2 4.2 0 0 1-6-6Z"/></svg>';

const MESSAGE_SKELETON_HTML = `
<div class="ag-skel-row">
    <div class="ag-skel-avatar"></div>
    <div class="ag-skel-bubble">
        <div class="ag-skel-line ag-skel-w-72"></div>
        <div class="ag-skel-line ag-skel-w-44"></div>
    </div>
</div>
<div class="ag-skel-row ag-skel-end">
    <div class="ag-skel-bubble">
        <div class="ag-skel-line ag-skel-w-56"></div>
    </div>
</div>
<div class="ag-skel-row">
    <div class="ag-skel-avatar"></div>
    <div class="ag-skel-bubble">
        <div class="ag-skel-line ag-skel-w-80"></div>
        <div class="ag-skel-line ag-skel-w-52"></div>
        <div class="ag-skel-line ag-skel-w-36"></div>
    </div>
</div>
<div class="ag-skel-row ag-skel-end">
    <div class="ag-skel-bubble">
        <div class="ag-skel-line ag-skel-w-64"></div>
        <div class="ag-skel-line ag-skel-w-40"></div>
    </div>
</div>
<div class="ag-skel-row">
    <div class="ag-skel-avatar"></div>
    <div class="ag-skel-bubble">
        <div class="ag-skel-line ag-skel-w-60"></div>
        <div class="ag-skel-line ag-skel-w-44"></div>
    </div>
</div>
<div class="ag-skel-row ag-skel-end">
    <div class="ag-skel-bubble">
        <div class="ag-skel-line ag-skel-w-48"></div>
    </div>
</div>`;

const SKELETON_MIN_MS = 900;

const TYPING_LABEL_HTML = `
<span class="ag-dots" aria-hidden="true"><span></span><span></span><span></span></span>
<span class="ag-typing-label">Typing</span>`;

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

function removeApprovalUiForId(root: HTMLElement, approvalId: string): void {
    const needle = `"id":"${approvalId}"`;
    const needleSpaced = `"id": "${approvalId}"`;

    root.querySelectorAll<HTMLElement>('.agentic-card-handoff, .agentic-card-approval, .agentic-card').forEach((card) => {
        const payload = card.querySelector<HTMLElement>('[data-payload]')?.dataset.payload ?? '';
        if (!payload.includes(approvalId) && !payload.includes(needle) && !payload.includes(needleSpaced)) {
            return;
        }
        const row = card.closest('.ag-row-has-approval');
        card.remove();
        if (row && !row.querySelector('.ag-bubble')) {
            row.remove();
        }
    });

    root.querySelectorAll<HTMLElement>('.agentic-actions').forEach((actions) => {
        if (actions.closest('.agentic-card-handoff, .agentic-card-approval')) {
            return;
        }
        const payload = actions.querySelector<HTMLElement>('[data-payload]')?.dataset.payload ?? '';
        if (payload.includes(approvalId) || payload.includes(needle) || payload.includes(needleSpaced)) {
            actions.remove();
        }
    });
}

function upgradeLegacyApprovalCards(root: HTMLElement): void {
    root.querySelectorAll('p').forEach((paragraph) => {
        const copy = paragraph.textContent?.trim() ?? '';
        if (copy.includes('needs your approval before it can run')) {
            paragraph.remove();
        }
    });

    root.querySelectorAll<HTMLElement>('.agentic-card:not(.agentic-card-handoff):not(.agentic-card-approval)').forEach((card) => {
        const title =
            card.querySelector('header strong')?.textContent?.trim()
            ?? card.querySelector('h3')?.textContent?.trim()
            ?? '';
        let actions = card.nextElementSibling as HTMLElement | null;
        if (!actions?.classList.contains('agentic-actions')) {
            actions = card.querySelector('.agentic-actions');
        }
        const isHandoff = /person/i.test(title);
        if (!isHandoff && !actions) {
            return;
        }
        if (actions && !card.contains(actions)) {
            card.appendChild(actions);
        }
        card.classList.add(isHandoff ? 'agentic-card-handoff' : 'agentic-card-approval');
        if (!card.querySelector('.agentic-card-head')) {
            const head = document.createElement('div');
            head.className = 'agentic-card-head';
            const icon = document.createElement('span');
            icon.className = 'agentic-card-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = '!';
            const copy = document.createElement('div');
            copy.className = 'agentic-card-copy';
            const heading = card.querySelector('header strong, h3');
            if (heading) {
                const h3 = document.createElement('h3');
                h3.textContent = heading.textContent?.trim() ?? title;
                copy.append(h3);
                heading.closest('header')?.remove();
                card.querySelector('h3')?.remove();
            }
            const body = card.querySelector('.agentic-card-body') ?? card.querySelector('p');
            if (body && !copy.querySelector('.agentic-card-body')) {
                const wrap = document.createElement('div');
                wrap.className = 'agentic-card-body';
                wrap.innerHTML = body instanceof HTMLElement ? body.innerHTML : '';
                if (wrap.textContent?.trim()) {
                    copy.append(wrap);
                }
                body.remove();
            } else if (!copy.querySelector('.agentic-card-body') && isHandoff) {
                const wrap = document.createElement('div');
                wrap.className = 'agentic-card-body';
                wrap.innerHTML = '<p>The agent needs your approval before running this action.</p>';
                copy.append(wrap);
            }
            head.append(icon, copy);
            card.prepend(head);
        }
    });
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
        const name = (agent as { name?: string }).name;
        if (typeof name === 'string' && name.trim() !== '') {
            return name.trim();
        }
        const slug = (agent as { slug?: string }).slug;
        if (typeof slug === 'string' && slug.trim() !== '') {
            return fallbackAgentLabel(slug);
        }

        return 'Assistant';
    }

    if (typeof agent === 'string' && agent.trim() !== '') {
        return fallbackAgentLabel(agent);
    }

    return 'Assistant';
}

function handoffStatusFromPayload(payload: {
    handoff?: { active?: boolean; staff_chat?: boolean; status?: string };
    metadata?: Record<string, unknown>;
} | null | undefined): string | null {
    const direct = payload?.handoff?.status;
    if (typeof direct === 'string' && direct !== '') {
        return direct;
    }
    const meta = payload?.metadata;
    if (!meta || typeof meta !== 'object') {
        return null;
    }
    const status = (meta as { handoff?: { status?: string } }).handoff?.status;

    return typeof status === 'string' && status !== '' ? status : null;
}

function handoffActiveFromPayload(payload: {
    handoff?: { active?: boolean; staff_chat?: boolean; status?: string };
    metadata?: Record<string, unknown>;
} | null | undefined): boolean {
    if (payload?.handoff?.active === true) {
        return true;
    }
    const status = handoffStatusFromPayload(payload);

    return status === 'requested' || status === 'taken';
}

function staffChatActiveFromPayload(payload: {
    handoff?: { active?: boolean; staff_chat?: boolean; status?: string };
    metadata?: Record<string, unknown>;
} | null | undefined): boolean {
    return payload?.handoff?.staff_chat === true;
}

function fallbackAgentLabel(agentSlug: string): string {
    const words = agentSlug.replace(/[-_]+/g, ' ').trim();
    if (words === '') {
        return 'Assistant';
    }

    return words.replace(/\b\w/g, (char) => char.toUpperCase());
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
    private titleEl: HTMLElement | null = null;
    private realtime: WidgetRealtimeConnection | null = null;
    private pollMs = 3000;
    private realtimeDriver = 'null';
    private channelPrefix = 'agentic-widget';
    private pusherConfig: { key?: string | null; cluster?: string | null } = {};
    private realtimeSinceId = 0;
    private replyMode: 'sync' | 'async' = 'sync';
    private typingBarEl: HTMLElement | null = null;
    private historyPageSize = 20;
    private historyMaxPageSize = 50;
    private messageBatchWindowMs = 0;
    private configReady = false;
    private historyStatusEl: HTMLElement | null = null;
    private skeletonShownAt = 0;
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
    private fileInput!: HTMLInputElement;
    private attachBtn!: HTMLButtonElement;
    private fileChipEl!: HTMLElement;
    private pendingFiles: File[] = [];
    private attachmentsEnabled = true;
    private maxFiles = 3;
    private humanTaken = false;
    private staffChatActive = false;
    private attachmentsStaffOnly = true;
    /** Highest message cursor loaded from history — ignore older staff events on realtime replay. */
    private maxHistoryCursor = 0;
    /** False until initial history + handoff meta are applied (blocks realtime replay enabling attach). */
    private composerHistorySynced = false;
    private streamEl: HTMLElement | null = null;
    private streamText = '';

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
                    <strong class="ag-title ag-title-loading" aria-busy="true">
                        <span class="ag-title-skel" aria-hidden="true"></span>
                    </strong>
                    <span class="ag-subtitle">
                        <span class="ag-presence-dot" aria-hidden="true"></span>
                        <span class="ag-realtime-dot" data-state="connected" title="Realtime" hidden></span>
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
        this.titleEl = header.querySelector('.ag-title');
        this.setPresenceOnline();
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
        this.loadMoreEl.innerHTML = `
            <div class="ag-skel-row">
                <div class="ag-skel-avatar"></div>
                <div class="ag-skel-bubble">
                    <div class="ag-skel-line ag-skel-w-72"></div>
                    <div class="ag-skel-line ag-skel-w-44"></div>
                </div>
            </div>`;
        this.messagesEl.appendChild(this.loadMoreEl);
        this.messagesEl.addEventListener('scroll', () => this.onMessagesScroll(), { passive: true });

        const form = document.createElement('form');
        form.className = 'ag-composer';
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = 'Type a message…';
        this.input.disabled = true;
        this.fileInput = document.createElement('input');
        this.fileInput.type = 'file';
        this.fileInput.hidden = true;
        this.fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif,application/pdf';
        this.fileInput.multiple = true;
        this.fileInput.addEventListener('change', () => {
            this.pendingFiles = Array.from(this.fileInput.files ?? []).slice(0, this.maxFiles);
            this.renderFileChip();
        });
        this.attachBtn = document.createElement('button');
        this.attachBtn.type = 'button';
        this.attachBtn.className = 'ag-attach-btn ag-emoji-btn';
        this.attachBtn.setAttribute('aria-label', 'Attach file');
        this.attachBtn.innerHTML = ICON_ATTACH;
        this.attachBtn.hidden = true;
        this.attachBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!this.staffChatActive) {
                return;
            }
            this.fileInput.click();
        });
        this.fileChipEl = document.createElement('div');
        this.fileChipEl.className = 'ag-file-chip';
        this.fileChipEl.hidden = true;
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
        form.append(this.fileChipEl, this.input, this.emojiBtn, send, this.emojiPop);
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

        this.typingBarEl = document.createElement('div');
        this.typingBarEl.className = 'ag-typing-bar';
        this.typingBarEl.hidden = true;
        this.typingBarEl.setAttribute('aria-live', 'polite');
        this.typingBarEl.setAttribute('aria-label', 'Assistant is typing');
        this.typingBarEl.innerHTML = TYPING_LABEL_HTML;

        this.panel.append(header, this.messagesEl, this.typingBarEl, form, this.fileInput, this.inboxBackdropEl, this.inboxEl);
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

        applyTheme(this.root, this.resolveThemeMode({}));
        this.syncComposerAttachments();
        if (this.config.open) {
            this.setPanelOpen(true);
        }
    }

    /** init theme wins unless it is the generic "system" placeholder — then use GET /config (AGENTIC_WIDGET_THEME). */
    private resolveThemeMode(themeCfg: Record<string, unknown>): string {
        const initTheme = this.config.theme;
        if (initTheme && initTheme !== 'system') {
            return initTheme;
        }

        return modeFromConfig(initTheme ?? 'system', themeCfg);
    }

    async bootstrap(): Promise<boolean> {
        try {
            const cfg = await this.api.fetchConfig(this.config.agent);

            const embed = cfg.data.embed ?? {};
            if (embed.auth_required && !this.config.bearerToken) {
                throw new Error('This embed token requires a signed-in Sanctum session or bearerToken in AgenticChat.init.');
            }

            this.agentLabel = agentDisplayName(cfg);
            this.setHeaderIdentity(this.agentLabel);

            const themeCfg = (cfg.data.theme ?? {}) as Record<string, unknown>;
            const mode = this.resolveThemeMode(themeCfg);
            applyTheme(this.root!, mode, themeVarsFromConfig(themeCfg));

            const rt = cfg.data.realtime ?? {};
            this.realtimeDriver = rt.driver ?? 'null';
            if (this.realtimeDot) {
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
            this.attachmentsEnabled = cfg.data.attachments?.enabled !== false;
            this.attachmentsStaffOnly = cfg.data.attachments?.staff_only !== false;
            this.maxFiles = Math.max(1, cfg.data.attachments?.max_files ?? 3);
            this.syncComposerAttachments();
            this.messageBatchWindowMs = Math.max(0, cfg.data.message_batch?.window_ms ?? 0);

            const welcome = cfg.data.welcome ?? cfg.data.intake?.welcome_message;
            this.welcomeText = typeof welcome === 'string' && welcome.trim() ? welcome : null;

            this.configReady = true;

            if (this.panelOpenPendingHistory) {
                this.panelOpenPendingHistory = false;
            }
            if (this.open) {
                await this.onPanelOpened();
            }

            this.setPresenceOnline();

            this.input.placeholder = 'Type a message…';
            this.input.disabled = false;

            return true;
        } catch (err) {
            console.error('[AgenticChat]', err);
            this.agentLabel = fallbackAgentLabel(this.config.agent);
            this.setHeaderIdentity(this.agentLabel);
            this.setPresenceOnline();
            if (this.input) {
                this.input.placeholder = 'Chat unavailable';
                this.input.disabled = true;
            }

            return false;
        }
    }

    private setHeaderIdentity(label: string): void {
        const title = this.titleEl ?? this.root?.querySelector('.ag-title');
        if (!title) {
            return;
        }
        title.classList.remove('ag-title-loading');
        title.removeAttribute('aria-busy');
        title.textContent = label.trim() !== '' ? label : 'Assistant';
        this.titleEl = title;
    }

    private setPresenceOnline(): void {
        this.setStatusText('Online');
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
            this.applyHandoffFromServer(handoffActiveFromPayload(storedRow), false);

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
        this.showMessageSkeleton();
        try {
            await this.resolveConversationForSession();

            if (!this.conversationId) {
                this.clearChatBubbles();
                this.seenMessageIds.clear();
                this.composerHistorySynced = true;
                this.showWelcomeIfEmpty();

                return;
            }

            await this.loadHistoryWithRecovery();
            this.startRealtime();
        } catch (err) {
            console.error('[AgenticChat] history', err);
            this.showWelcomeIfEmpty();
        } finally {
            this.historyLoading = false;
            await this.hideMessageSkeleton();
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
        this.humanTaken = false;
        this.staffChatActive = false;
        this.maxHistoryCursor = 0;
        this.composerHistorySynced = true;
        this.clearStreamBubble();
        this.setPresenceOnline();
        this.syncComposerAttachments();
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
        this.showMessageSkeleton();
        try {
            await this.loadHistoryPage(null);
            this.startRealtime();
        } catch (err) {
            console.error('[AgenticChat] open conversation', err);
        } finally {
            await this.hideMessageSkeleton();
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

    private showMessageSkeleton(): void {
        if (this.historyStatusEl) {
            return;
        }
        this.skeletonShownAt = Date.now();
        this.historyStatusEl = document.createElement('div');
        this.historyStatusEl.className = 'ag-skeleton';
        this.historyStatusEl.setAttribute('aria-busy', 'true');
        this.historyStatusEl.setAttribute('aria-label', 'Loading messages');
        this.historyStatusEl.innerHTML = MESSAGE_SKELETON_HTML;
        this.messagesEl.appendChild(this.historyStatusEl);
    }

    private async hideMessageSkeleton(): Promise<void> {
        const wait = Math.max(0, SKELETON_MIN_MS - (Date.now() - this.skeletonShownAt));
        if (wait > 0) {
            await new Promise((resolve) => window.setTimeout(resolve, wait));
        }
        this.historyStatusEl?.remove();
        this.historyStatusEl = null;
    }

    private showWelcomeIfEmpty(): void {
        if (this.conversationId) {
            return;
        }
        if (this.welcomeText && this.countBubbles() === 0) {
            this.addBubble('assistant', this.welcomeText, null, null);
        }
    }

    private countBubbles(): number {
        return this.messagesEl.querySelectorAll('.ag-bubble').length;
    }

    private clearChatBubbles(): void {
        this.messagesEl.querySelectorAll('.ag-row, .ag-bubble, .ag-day-divider').forEach((el) => el.remove());
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
            this.composerHistorySynced = false;
            this.clearChatBubbles();
            this.seenMessageIds.clear();
            this.maxHistoryCursor = 0;
            if (messages.length === 0) {
                this.showWelcomeIfEmpty();
            } else {
                for (const msg of messages) {
                    const cursor = typeof (msg as { cursor?: number }).cursor === 'number' ? (msg as { cursor: number }).cursor : 0;
                    this.maxHistoryCursor = Math.max(this.maxHistoryCursor, cursor);
                    this.registerHistoryMessage(msg);
                    this.insertHistoryMessage(msg, false);
                }
                this.refreshDayDividers();
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
                const el = this.createBubbleElement(bubble.role, bubble.text, bubble.html, bubble.sentAt);
                const anchor = this.loadMoreEl.nextElementSibling;
                if (anchor) {
                    this.messagesEl.insertBefore(el, anchor);
                } else {
                    this.messagesEl.appendChild(el);
                }
            }
            this.refreshDayDividers();
            this.messagesEl.scrollTop += this.messagesEl.scrollHeight - prevHeight;
        }

        this.hasMoreHistory = meta.has_more;
        this.nextBefore = meta.next_before;
        if (isInitial) {
            const staffChat = meta.handoff?.staff_chat === true;
            this.applyHandoffFromServer(meta.handoff?.active === true, staffChat);
            const tailId = (meta as { realtime_tail_id?: number }).realtime_tail_id;
            if (typeof tailId === 'number' && tailId > 0) {
                this.realtimeSinceId = Math.max(this.realtimeSinceId, tailId);
            }
            this.composerHistorySynced = true;
        }
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

        const el = this.createBubbleElement(bubble.role, bubble.text, bubble.html, bubble.sentAt);
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

    private createBubbleElement(
        role: 'user' | 'assistant',
        text: string,
        html: string | null,
        sentAt: string | null = null,
    ): HTMLElement {
        const row = document.createElement('div');
        row.className = `ag-row ag-row-${role}`;
        const el = document.createElement('div');
        el.className = `ag-bubble ag-${role}`;
        const stack = document.createElement('div');
        stack.className = 'ag-msg';
        if (role === 'assistant') {
            const rich = assistantBubbleHtml(html, text);
            const cards: HTMLElement[] = [];
            if (rich) {
                el.classList.add('ag-rich');
                el.innerHTML = rich;
                upgradeLegacyApprovalCards(el);
                el.querySelectorAll<HTMLElement>('.agentic-card-approval, .agentic-card-handoff').forEach((card) => {
                    cards.push(card);
                    card.remove();
                });
            }
            const leftover = (el.innerHTML || '').replace(/<p>\s*<\/p>/g, '').trim();
            const avatar = document.createElement('div');
            avatar.className = 'ag-mini-avatar';
            avatar.innerHTML = ICON_SPARK;
            if (leftover || !rich) {
                if (leftover) {
                    this.bindAssistantActions(el);
                    applyAssistantDirection(el, text, leftover);
                } else {
                    el.textContent = text;
                    applyAssistantDirection(el, text, html);
                }
                stack.append(el);
                this.appendMessageTime(stack, sentAt);
                if (cards.length > 0) {
                    const main = document.createElement('div');
                    main.className = 'ag-row-main';
                    main.append(avatar, stack);
                    row.append(main);
                } else {
                    row.append(avatar, stack);
                }
            }
            if (cards.length > 0) {
                row.classList.add('ag-row-has-approval');
                cards.forEach((card) => {
                    this.bindAssistantActions(card);
                    row.append(card);
                });
            }
        } else {
            const needsRichUserHtml = html != null && /<(figure|img|p\s+class="ag-attach)/i.test(html);
            if (needsRichUserHtml && html) {
                const rich = assistantBubbleHtml(html, text);
                if (rich) {
                    el.classList.add('ag-rich');
                    el.innerHTML = rich;
                } else {
                    el.textContent = text;
                }
            } else {
                el.textContent = text;
            }
            stack.append(el);
            this.appendMessageTime(stack, sentAt);
            row.append(stack);
        }
        if (sentAt) {
            row.dataset.sentAt = sentAt;
        }

        return row;
    }

    private appendMessageTime(stack: HTMLElement, sentAt: string | null): void {
        const label = formatMessageTimeEn(sentAt);
        if (!label || !sentAt) {
            return;
        }
        const time = document.createElement('time');
        time.className = 'ag-time';
        time.dateTime = sentAt;
        time.textContent = label;
        stack.append(time);
    }

    private bindAssistantActions(root: HTMLElement): void {
        root.querySelectorAll<HTMLButtonElement>('[data-action]').forEach((button) => {
            if (button.dataset.bound === '1') {
                return;
            }
            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                void this.handleAssistantAction(button);
            });
        });
    }

    private async handleAssistantAction(button: HTMLButtonElement): Promise<void> {
        const action = button.dataset.action ?? '';
        let payload: Record<string, unknown> = {};
        try {
            payload = JSON.parse(button.dataset.payload || '{}') as Record<string, unknown>;
        } catch {
            payload = {};
        }

        const id = typeof payload.id === 'string' ? payload.id : '';
        if ((action !== 'approve' && action !== 'reject') || id === '') {
            return;
        }

        const group = button.closest('.agentic-actions');
        group?.querySelectorAll('button').forEach((el) => {
            el.setAttribute('disabled', 'true');
        });

        try {
            const result = action === 'approve' ? await this.api.approve(id) : await this.api.reject(id);
            const handedOff = action === 'approve' && (result.tool === 'handoff' || result.handoff === true);
            removeApprovalUiForId(this.messagesEl, id);
            if (handedOff) {
                this.markHumanHelping();
            }

            const execution = result.execution as { resume?: { message?: { html?: string; text?: string } } } | undefined;
            const resumed = execution?.resume?.message;
            if (!handedOff && resumed && (resumed.html || resumed.text)) {
                this.messagesEl.appendChild(
                    this.createBubbleElement('assistant', resumed.text ?? '', resumed.html ?? null, new Date().toISOString()),
                );
                this.refreshDayDividers();
            }
        } catch (error) {
            group?.querySelectorAll('button').forEach((el) => el.removeAttribute('disabled'));
            const failed = document.createElement('p');
            failed.className = 'ag-approval-status';
            failed.textContent = error instanceof Error ? error.message : 'Approval failed.';
            group?.after(failed);
        }
    }

    private refreshDayDividers(): void {
        this.messagesEl.querySelectorAll('.ag-day-divider').forEach((el) => el.remove());
        let lastDay: string | null = null;
        this.messagesEl.querySelectorAll<HTMLElement>('.ag-row').forEach((row) => {
            if (row.querySelector('.ag-typing') || row.classList.contains('ag-typing') || row.classList.contains('ag-skel-row')) {
                return;
            }
            const key = dayKey(row.dataset.sentAt);
            if (!key || key === lastDay) {
                lastDay = key ?? lastDay;

                return;
            }
            lastDay = key;
            const divider = document.createElement('div');
            divider.className = 'ag-day-divider';
            divider.setAttribute('role', 'separator');
            const label = document.createElement('span');
            label.textContent = formatDayLabelEn(row.dataset.sentAt);
            divider.append(label);
            row.parentElement?.insertBefore(divider, row);
        });
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

        if (ev.event === 'handoff.updated' && ev.payload.handoff && typeof ev.payload.handoff === 'object') {
            const handoff = ev.payload.handoff as { active?: boolean; staff_chat?: boolean };
            this.applyHandoffFromServer(handoff.active === true, handoff.staff_chat === true);

            return;
        }

        const typing = typingActiveFromEvent(ev);
        if (typing !== null) {
            this.setTyping(typing);

            return;
        }

        if (streamDeltaFromEvent(ev)) {
            return;
        }

        const msg = ev.payload.message;
        if (msg && typeof msg === 'object' && (msg as { source?: string }).source === 'staff') {
            if (this.shouldEnableStaffAttachFromRealtime(msg as { cursor?: number })) {
                this.enableStaffChatUi();
            }
        }

        const reply = assistantMessageFromEvent(ev);
        if (!reply) {
            return;
        }
        const mid =
            msg && typeof msg === 'object' && typeof (msg as { id?: string }).id === 'string'
                ? (msg as { id: string }).id
                : null;

        const createdAt =
            msg && typeof msg === 'object' && typeof (msg as { created_at?: string }).created_at === 'string'
                ? (msg as { created_at: string }).created_at
                : null;
        this.showAssistantIfNew(mid, reply.text, reply.html, createdAt);
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

    private showAssistantIfNew(id: string | null, text: string, html: string | null, sentAt: string | null = null): boolean {
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
        this.clearStreamBubble();
        this.setTyping(false);
        this.awaitingAssistantReply = false;
        this.pendingAssistantAfterCursor = null;
        this.sending = false;
        if (this.configReady) {
            this.input.disabled = false;
        }
        this.addBubble('assistant', text, html, sentAt ?? new Date().toISOString());
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
                if (this.showAssistantIfNew(msg.id ?? null, bubble.text, bubble.html, bubble.sentAt)) {
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
            this.setTyping(false);

            return;
        }
        this.input.focus();
        if (!this.configReady) {
            this.showMessageSkeleton();
        }
        void this.onPanelOpened();
    }

    private toggle(): void {
        this.setPanelOpen(!this.open);
    }

    private setTyping(active: boolean): void {
        this.messagesEl?.querySelectorAll('.ag-row.ag-typing').forEach((row) => row.remove());
        if (active && this.streamEl) {
            return;
        }
        if (this.typingBarEl) {
            this.typingBarEl.hidden = !active;
        }
        this.panel?.classList.toggle('ag-is-typing', active);
    }

    private optimisticUserHtml(text: string, files: File[]): string | null {
        if (files.length === 0) {
            return null;
        }
        const escape = (value: string) =>
            value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        const parts: string[] = [];
        if (text) {
            parts.push(`<p>${escape(text)}</p>`);
        }
        for (const file of files) {
            if (file.type.startsWith('image/')) {
                parts.push(
                    `<figure class="ag-attach"><img src="${URL.createObjectURL(file)}" alt="" loading="lazy" decoding="async" class="ag-attach-img"><figcaption class="ag-attach-caption">${escape(file.name)}</figcaption></figure>`,
                );
            } else {
                parts.push(`<p class="ag-attach-file">${escape(file.name)}</p>`);
            }
        }

        return parts.join('');
    }

    private addBubble(role: 'user' | 'assistant', text: string, html: string | null = null, sentAt?: string | null): void {
        const at = sentAt === undefined ? new Date().toISOString() : sentAt;
        this.messagesEl.appendChild(this.createBubbleElement(role, text, html, at));
        this.refreshDayDividers();
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

    private renderFileChip(): void {
        if (this.pendingFiles.length === 0) {
            this.fileChipEl.hidden = true;
            this.fileChipEl.textContent = '';
            return;
        }
        this.fileChipEl.hidden = false;
        this.fileChipEl.textContent = this.pendingFiles.map((file) => file.name).join(', ');
    }

    private appendStreamDelta(delta: string): void {
        this.streamText += delta;
        if (!this.streamEl) {
            this.setTyping(false);
            this.streamEl = this.createBubbleElement('assistant', this.streamText, null, new Date().toISOString());
            this.streamEl.classList.add('ag-stream');
            this.messagesEl.appendChild(this.streamEl);
            this.refreshDayDividers();
        } else {
            const bubble = this.streamEl.querySelector('.ag-bubble');
            if (bubble) {
                bubble.textContent = this.streamText;
            }
        }
        this.scrollMessagesToBottom();
    }

    private clearStreamBubble(): void {
        this.streamEl?.remove();
        this.streamEl = null;
        this.streamText = '';
    }

    private markHumanHelping(): void {
        this.applyHandoffFromServer(true, false);
    }

    private applyHandoffFromServer(active: boolean, staffChat = false): void {
        this.humanTaken = active;
        this.staffChatActive = staffChat;
        if (active) {
            this.setTyping(false);
            this.awaitingAssistantReply = false;
            this.setStatusText(staffChat ? 'Person helping' : 'Waiting for a person');
        } else {
            this.setPresenceOnline();
        }
        this.syncComposerAttachments();
    }

    private shouldEnableStaffAttachFromRealtime(msg: { cursor?: number }): boolean {
        if (!this.composerHistorySynced) {
            return false;
        }

        const cursor = msg.cursor;
        if (typeof cursor !== 'number' || cursor <= this.maxHistoryCursor) {
            return false;
        }

        this.maxHistoryCursor = cursor;

        return true;
    }

    private enableStaffChatUi(): void {
        this.staffChatActive = true;
        this.humanTaken = true;
        this.setStatusText('Person helping');
        this.syncComposerAttachments();
    }

    /** Attachments only when a team member has taken the chat (not during AI auto-reply). */
    private syncComposerAttachments(): void {
        const show =
            this.attachmentsEnabled && (!this.attachmentsStaffOnly || this.staffChatActive);
        if (show) {
            if (!this.attachBtn.isConnected && this.emojiBtn?.parentElement) {
                this.emojiBtn.insertAdjacentElement('beforebegin', this.attachBtn);
            }
            this.attachBtn.hidden = false;
            this.attachBtn.classList.add('is-visible');
            this.attachBtn.setAttribute('aria-hidden', 'false');
        } else {
            this.attachBtn.classList.remove('is-visible');
            this.attachBtn.hidden = true;
            this.attachBtn.setAttribute('aria-hidden', 'true');
            this.attachBtn.remove();
            this.pendingFiles = [];
            this.fileInput.value = '';
            this.renderFileChip();
        }
    }

    private setStatusText(label: string): void {
        const status = this.root?.querySelector('.ag-status-text');
        if (status) {
            status.textContent = label;
        }
    }

    private async onSend(): Promise<void> {
        const text = this.input.value.trim();
        const files = this.pendingFiles;
        if ((!text && files.length === 0) || this.sending || !this.configReady) {
            return;
        }
        if (files.length > 0 && this.attachmentsStaffOnly && !this.staffChatActive) {
            return;
        }
        this.sending = true;
        this.input.value = '';
        this.input.disabled = true;
        this.pendingFiles = [];
        this.fileInput.value = '';
        this.renderFileChip();
        this.clearStreamBubble();
        this.pendingAssistantAfterCursor = await this.captureConversationTailCursor();
        const previewText = text || (files.length > 0 ? '' : '');
        this.addBubble('user', previewText, this.optimisticUserHtml(text, files));
        playWidgetSound('send', this.config.sounds ?? true);
        const batching = this.messageBatchWindowMs > 0 && files.length === 0;
        if (!this.humanTaken && !batching) {
            this.setTyping(true);
        }
        this.awaitingAssistantReply =
            !this.humanTaken && (batching || this.replyMode === 'async' || this.realtimeDriver === 'pusher');
        if (this.conversationId) {
            this.startRealtime();
        }
        try {
            const data = (await this.api.sendMessage(
                this.config.agent,
                text,
                this.conversationId,
                files,
            )) as WidgetMessageAck & { handoff?: boolean };

            if (typeof data.conversation_id === 'string') {
                this.conversationId = data.conversation_id;
                this.persistConversationId();
                void this.refreshConversationList().catch(() => undefined);
            }

            if (this.conversationId) {
                this.startRealtime();
            }

            if (data.handoff === true) {
                this.markHumanHelping();

                return;
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
            if (!this.awaitingAssistantReply) {
                this.sending = false;
                this.input.disabled = false;
                this.input.focus();
            }
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
