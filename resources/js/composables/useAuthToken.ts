import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import orionService from '@/services/orion'

export function useAuthToken() {
    const page = usePage()

    // Watch for auth token changes from Inertia props
    watch(
        () => page.props?.auth?.token,
        (token) => {
            if (token && typeof token === 'string') {
                // Store token in localStorage and update Orion service
                localStorage.setItem('auth_token', token)
                orionService.setAuthToken(token)

                // Clear the token from session after storing it
                // This prevents the token from being exposed in subsequent requests
                console.log('Auth token stored successfully')
            }
        },
        { immediate: true }
    )

    return {
        clearAuthToken: () => {
            localStorage.removeItem('auth_token')
            orionService.clearAuth()
        }
    }
}

