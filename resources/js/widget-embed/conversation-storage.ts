export function conversationStorageKey(agentSlug: string, guestId: string): string {
    return `agentic_widget_conversation_${agentSlug}_${guestId}`;
}

export function loadStoredConversationId(agentSlug: string, guestId: string): string | null {
    try {
        return localStorage.getItem(conversationStorageKey(agentSlug, guestId));
    } catch {
        return null;
    }
}

export function saveStoredConversationId(agentSlug: string, guestId: string, conversationId: string): void {
    try {
        localStorage.setItem(conversationStorageKey(agentSlug, guestId), conversationId);
    } catch {
        /* private mode */
    }
}

export function clearStoredConversationId(agentSlug: string, guestId: string): void {
    try {
        localStorage.removeItem(conversationStorageKey(agentSlug, guestId));
    } catch {
        /* private mode */
    }
}
