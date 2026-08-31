/**
 * Synthesized alert tones via Web Audio — no binary audio assets to ship.
 * Browsers block audio until a user gesture, so the shared context is
 * resumed lazily on first click/keypress; by the time a call or order
 * actually comes in, staff have long since interacted with the page.
 */
let sharedCtx: AudioContext | null = null;
let unlockRegistered = false;

function getContext(): AudioContext | null {
  if (!import.meta.client) return null;
  if (!sharedCtx) {
    const Ctor = window.AudioContext ?? (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;
    if (!Ctor) return null;
    sharedCtx = new Ctor();
  }
  if (sharedCtx.state === 'suspended') void sharedCtx.resume();
  if (!unlockRegistered) {
    unlockRegistered = true;
    const unlock = () => void sharedCtx?.resume();
    document.addEventListener('pointerdown', unlock, { once: true });
    document.addEventListener('keydown', unlock, { once: true });
  }
  return sharedCtx;
}

function tone(ctx: AudioContext, frequency: number, startAt: number, durationSec: number, peakGain: number) {
  const osc = ctx.createOscillator();
  const gain = ctx.createGain();
  osc.type = 'sine';
  osc.frequency.value = frequency;
  gain.gain.setValueAtTime(0.0001, startAt);
  gain.gain.exponentialRampToValueAtTime(peakGain, startAt + 0.02);
  gain.gain.exponentialRampToValueAtTime(0.0001, startAt + durationSec);
  osc.connect(gain).connect(ctx.destination);
  osc.start(startAt);
  osc.stop(startAt + durationSec + 0.02);
}

export function useAlertSound() {
  /** Two short rings, then silence — a new order arrived. Not a loop. */
  function playOrderChime() {
    const ctx = getContext();
    if (!ctx) return;
    const now = ctx.currentTime;
    tone(ctx, 880, now, 0.2, 0.3);
    tone(ctx, 880, now + 0.36, 0.2, 0.3);
  }

  /** Sharp double-ring — a waiter call. */
  function playCallRing() {
    const ctx = getContext();
    if (!ctx) return;
    const now = ctx.currentTime;
    tone(ctx, 1046.5, now, 0.16, 0.3);
    tone(ctx, 1046.5, now + 0.24, 0.16, 0.3);
  }

  let loopTimer: ReturnType<typeof setInterval> | null = null;

  function startCallLoop(intervalMs = 2500) {
    if (loopTimer) return;
    playCallRing();
    loopTimer = setInterval(playCallRing, intervalMs);
  }

  function stopCallLoop() {
    if (loopTimer) clearInterval(loopTimer);
    loopTimer = null;
  }

  onScopeDispose(stopCallLoop);

  return { playOrderChime, startCallLoop, stopCallLoop };
}
