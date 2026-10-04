/*
 * Member actions (bookmark, event registration, attendance) + toasts.
 *
 * Like the ica-guest prototype, state is per-browser localStorage and every
 * toast nudges the visitor to sign in. Phase two swaps the toggle bodies for
 * authenticated API calls; the components only depend on isOn()/toggle().
 */
import { reactive } from 'vue';

const read = (key) => {
    try {
        return JSON.parse(localStorage.getItem(key) || '[]');
    } catch (e) {
        return [];
    }
};
const write = (key, value) => {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {}
};

export const KEYS = {
    bookmarks: 'ica-bookmarks',
    favorites: 'ica-favorites',
    events: 'ica-events',
    attendance: 'ica-attendance',
};

// Module-level so every component on the page shares one copy.
const state = reactive(Object.fromEntries(Object.entries(KEYS).map(([name, key]) => [name, read(key)])));
const toasts = reactive([]);
let toastId = 0;

export function toast(message) {
    const t = { id: ++toastId, message, leaving: false };
    toasts.push(t);
    setTimeout(() => {
        t.leaving = true;
        setTimeout(() => toasts.splice(toasts.indexOf(t), 1), 300);
    }, 2500);
}

export function useToasts() {
    return toasts;
}

const MESSAGES = {
    bookmarks: ['Saved to bookmarks · Sign in to sync', 'Removed from bookmarks'],
    favorites: ['Added to favorites · Sign in to sync', 'Removed from favorites'],
    events: ["You're registered · Sign in to confirm", 'Registration cancelled'],
    attendance: ['Attendance recorded · Sign in to save', 'Attendance cleared'],
};

export function useMemberActions() {
    const isOn = (kind, id) => state[kind].includes(String(id));

    const toggle = (kind, id) => {
        id = String(id);
        const list = state[kind];
        const i = list.indexOf(id);
        i === -1 ? list.push(id) : list.splice(i, 1);
        write(KEYS[kind], list);
        const on = i === -1;
        toast(MESSAGES[kind][on ? 0 : 1]);
        return on;
    };

    return { isOn, toggle };
}

/* Plain localStorage list helpers for per-sermon highlights and comments. */
export const storedList = { read, write };
