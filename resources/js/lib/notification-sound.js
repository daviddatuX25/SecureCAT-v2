// Notification sound utility using Web Audio API
// Two-tier context-aware chime: 'background' for poll, 'action' for direct user actions

// Singleton AudioContext instance
let audioContext = null;

function getAudioContext() {
    if (typeof window === 'undefined') return null;
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    // Browsers suspend AudioContext until user gesture — must resume
    if (audioContext.state === 'suspended') {
        audioContext.resume();
    }
    return audioContext;
}

/**
 * Play a context-aware notification chime.
 * @param {'background' | 'action'} tier
 *   - 'background': soft, short chime for poll-based notifications (0.15s, 600->400Hz, gain 0.08)
 *   - 'action': louder, longer chime for direct user actions (0.3s, 800->400Hz, gain 0.2)
 */
export function playChime(tier = 'background') {
    if (typeof window === 'undefined') return;

    const ctx = getAudioContext();
    if (!ctx) return;

    const oscillator = ctx.createOscillator();
    const gainNode = ctx.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(ctx.destination);

    if (tier === 'background') {
        // Soft, short chime — unobtrusive ambient alert for poll notifications
        oscillator.frequency.setValueAtTime(600, ctx.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(400, ctx.currentTime + 0.15);
        gainNode.gain.setValueAtTime(0.08, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.15);
    } else {
        // Louder, longer chime — confirms direct user action
        oscillator.frequency.setValueAtTime(800, ctx.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(400, ctx.currentTime + 0.3);
        gainNode.gain.setValueAtTime(0.2, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.3);
    }
}

/**
 * @deprecated Use playChime('action') instead. Kept for backward compatibility.
 */
export function playNotificationSound() {
    playChime('action');
}

/**
 * @deprecated Use playChime('action') or playChime('background') instead.
 */
export function playSound(type = 'info') {
    playChime('action');
}
