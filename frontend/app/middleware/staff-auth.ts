export default defineNuxtRouteMiddleware(() => {
  const auth = useAuthStore();
  if (!auth.accessToken.value) {
    return navigateTo('/staff/login');
  }
});
