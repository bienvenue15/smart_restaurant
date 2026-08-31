/**
 * Global flag set when the API returns MAINTENANCE_MODE (503).
 * The overlay in app.vue is the user-facing message; individual pages
 * should not also toast that same 503.
 */
export function useMaintenanceMode() {
  const active = useState('app:maintenance', () => false);

  function enter() {
    active.value = true;
  }

  function exit() {
    active.value = false;
  }

  return { active, enter, exit };
}
