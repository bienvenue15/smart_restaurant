export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore();
  if (!auth.accessToken.value) {
    return navigateTo('/staff/login');
  }

  const role = auth.staff.value?.role;
  if (role === 'SUPER_ADMIN') {
    const adminPath = useAdminPath();
    return navigateTo(adminPath.path('dashboard'));
  }

  const home = staffHomePath(auth.staff.value);

  const requiredPermissions = to.meta.permissions;
  if (requiredPermissions?.length) {
    const grants = auth.staff.value?.permissions ?? [];
    if (!requiredPermissions.some((code) => grants.includes(code))) {
      return navigateTo(home);
    }
    if (requiredPermissions.includes('handle_cash')) {
      const staff = auth.staff.value;
      const cashOk = staff?.role === 'ADMIN' || staff?.canHandleCash;
      if (!cashOk) return navigateTo(home);
    }
  }

  const requiredFeatures = to.meta.features;
  if (requiredFeatures?.length) {
    const plan = auth.staff.value?.plan;
    const missing = requiredFeatures.filter((code) => {
      if (code === 'kitchen_display' && kitchenDisplayAllowed(auth.staff.value)) return false;
      return !plan?.subscriptionActive || !plan.features.includes(code);
    });
    if (missing.length) return navigateTo(home);
  }

  const allowed = to.meta.roles;
  if (allowed?.length && (!role || !allowed.includes(role))) {
    return navigateTo(home);
  }
});
