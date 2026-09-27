export type EmojiCategory = {
    id: string;
    label: string;
    icon: string;
    emojis: string[];
};

export const EMOJI_CATEGORIES: EmojiCategory[] = [
    {
        id: 'smileys',
        label: 'Smileys',
        icon: '😊',
        emojis: [
            '😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊',
            '😇', '🙂', '😉', '😍', '🥰', '😘', '😋', '😛',
            '🤔', '🤗', '🤭', '😐', '😏', '😒', '😔', '😢',
            '😭', '😤', '😡', '🤯', '😱', '😴', '😷', '🤒',
        ],
    },
    {
        id: 'gestures',
        label: 'Gestures',
        icon: '👍',
        emojis: [
            '👍', '👎', '👏', '🙌', '🙏', '👌', '✌️', '🤞',
            '🤝', '💪', '🫶', '👋', '✋', '👊', '☝️', '👀',
        ],
    },
    {
        id: 'hearts',
        label: 'Hearts',
        icon: '❤️',
        emojis: [
            '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍',
            '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '✨',
            '⭐', '🌟', '🔥', '💯', '🎉', '🥳',
        ],
    },
    {
        id: 'objects',
        label: 'More',
        icon: '💬',
        emojis: [
            '✅', '❌', '⚠️', '📌', '📎', '📝', '📅', '⏰',
            '💡', '🏠', '🚗', '✈️', '☕', '🍕', '🎂', '🎁',
            '📱', '💻', '📷', '🎵', '☀️', '🌙', '🌈', '🌸',
        ],
    },
];

export function insertAtCursor(input: HTMLInputElement, emoji: string): void {
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? start;
    input.value = `${input.value.slice(0, start)}${emoji}${input.value.slice(end)}`;
    const pos = start + emoji.length;
    input.setSelectionRange(pos, pos);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.focus();
}
