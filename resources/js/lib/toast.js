// Native Svelte 5 Toast notification utility
// Uses a writable store + toast component pattern

import { writable } from 'svelte/store';

// Singleton AudioContext for notification sounds
let audioContext = null;

function getAudioContext() {
    if (typeof window === 'undefined') return null;
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioContext;
}

/**
 * Play a subtle notification chime sound
 */
function playSound() {
    const ctx = getAudioContext();
    if (!ctx) return;

    if (ctx.state === 'suspended') {
        ctx.resume();
    }

    const oscillator = ctx.createOscillator();
    const gainNode = ctx.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(ctx.destination);

    // Pleasant chime sweep (800Hz -> 400Hz)
    oscillator.frequency.setValueAtTime(800, ctx.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(400, ctx.currentTime + 0.3);

    // Quick fade out
    gainNode.gain.setValueAtTime(0.2, ctx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);

    oscillator.start(ctx.currentTime);
    oscillator.stop(ctx.currentTime + 0.5);
}

// Toast store: array of { id, message, type, duration }
function createToastStore() {
    const { subscribe, update } = writable([]);

    return {
        subscribe,
        show(message, type = 'info', duration = 4000) {
            const id = Math.random().toString(36).slice(2) + Date.now().toString(36);
            update(toasts => [...toasts, { id, message, type, duration }]);

            if (duration > 0) {
                setTimeout(() => {
                    this.dismiss(id);
                }, duration);
            }

            return id;
        },
        dismiss(id) {
            update(toasts => toasts.filter(t => t.id !== id));
        },
        clear() {
            update(() => []);
        }
    };
}

export const toasts = createToastStore();

// Convenience functions (with sound for events, silent for background)
export function success(message, options = {}) {
    playSound();
    return toasts.show(message, 'success', options.duration ?? 4000);
}

export function error(message, options = {}) {
    playSound();
    return toasts.show(message, 'error', options.duration ?? 4000);
}

export function info(message, options = {}) {
    playSound();
    return toasts.show(message, 'info', options.duration ?? 4000);
}

export function message(message, options = {}) {
    playSound();
    return toasts.show(message, 'info', options.duration ?? 4000);
}

export function silent(message, options = {}) {
    return toasts.show(message, 'info', 0); // 0 = no auto-dismiss
}

export const dismiss = (id) => toasts.dismiss(id);