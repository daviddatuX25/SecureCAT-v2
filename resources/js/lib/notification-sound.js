// Notification sound utility using Web Audio API
// Provides a pleasant chime sound for notifications

// Singleton AudioContext instance
let audioContext = null;

function getAudioContext() {
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioContext;
}

/**
 * Play a subtle notification chime sound
 * Uses Web Audio API to generate a pleasant frequency sweep
 */
export function playNotificationSound() {
    if (typeof window === 'undefined') return;

    const audioContext = getAudioContext();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    // Pleasant chime frequency (800Hz -> 400Hz sweep)
    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.3);

    // Quick fade out
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.5);
}

/**
 * Play different sound types
 * @param {'success' | 'error' | 'info'} type
 */
export function playSound(type = 'info') {
    if (typeof window === 'undefined') return;

    const audioContext = getAudioContext();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    // Different frequencies for different types
    const frequencies = {
        success: { start: 600, end: 900 },
        error: { start: 400, end: 200 },
        info: { start: 800, end: 400 }
    };

    const { start, end } = frequencies[type] || frequencies.info;

    oscillator.frequency.setValueAtTime(start, audioContext.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(end, audioContext.currentTime + 0.3);

    gainNode.gain.setValueAtTime(0.25, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.5);
}