export const ENFORCEABLE_PLAN_FEATURES = ['basic_pos', 'qr_ordering', 'kitchen_display', 'analytics'] as const;
export type EnforceablePlanFeature = (typeof ENFORCEABLE_PLAN_FEATURES)[number];

export function usePlan() {
  const auth = useAuthStore();

  const plan = computed(() => auth.staff.value?.plan ?? null);
  const subscriptionActive = computed(() => plan.value?.subscriptionActive !== false);

  function hasFeature(code: string): boolean {
    if (auth.staff.value?.role === 'SUPER_ADMIN') return true;
    if (!plan.value) return false;
    if (!plan.value.subscriptionActive) return false;
    return plan.value.features.includes(code);
  }

  return { plan, subscriptionActive, hasFeature };
}
