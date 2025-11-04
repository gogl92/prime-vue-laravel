import { Orion } from '@tailflow/laravel-orion/lib/orion';

class OrionService {
  constructor() {
    // Initialize Orion with the API base URL
    Orion.init(window.location.origin);

    // Setup authentication
    this.setupAuth();
  }

  private setupAuth() {
    // Get token from localStorage or cookie
    const token = localStorage.getItem('auth_token') ?? this.getCookie('auth_token');
    if (token) {
      console.log('Setting up Orion auth with token:', token.substring(0, 20) + '...');
      Orion.setToken(token);
    } else {
      console.log('No auth token found in localStorage or cookies');
    }
  }

  private getCookie(name: string): string | null {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop()?.split(';').shift() ?? null;
    return null;
  }

  public setAuthToken(token: string) {
    console.log('OrionService.setAuthToken called with:', token.substring(0, 20) + '...');
    localStorage.setItem('auth_token', token);
    Orion.setToken(token);
    console.log('Token set in Orion and localStorage');
  }

  public clearAuth() {
    Orion.setToken('');
    localStorage.removeItem('auth_token');
  }
}

// Export singleton instance
export const orionService = new OrionService();
export default orionService;
