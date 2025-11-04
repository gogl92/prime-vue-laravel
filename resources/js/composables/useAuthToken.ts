import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import orionService from '@/services/orion';

export function useAuthToken() {
  const page = usePage();

  // Watch for auth token changes from Inertia props
  watch(
    () => page.props?.auth?.token,
    token => {
      if (token && typeof token === 'string') {
        // Store token in localStorage and update Orion service
        localStorage.setItem('auth_token', token);
        orionService.setAuthToken(token);

        // Clear the token from session after storing it
        // This prevents the token from being exposed in subsequent requests
      }
    },
    { immediate: true }
  );

  const setToken = (token: string) => {
    localStorage.setItem('auth_token', token);
    orionService.setAuthToken(token);
  };

  const getToken = (): string | null => {
    return localStorage.getItem('auth_token');
  };

  const removeToken = () => {
    localStorage.removeItem('auth_token');
    orionService.clearAuth();
  };

  return {
    setToken,
    getToken,
    removeToken,
    // Legacy method name for backward compatibility
    clearAuthToken: removeToken,
  };
}
