import { defineStore } from 'pinia';
import api from '../api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('auth_token') || null,
        loading: false,
        error: null as string | null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.role === 'admin',
    },
    actions: {
        async login(credentials: any) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post('/login', credentials);
                if (response.data.success) {
                    this.user = response.data.data.user;
                    this.token = response.data.data.token;
                    
                    localStorage.setItem('user', JSON.stringify(this.user));
                    localStorage.setItem('auth_token', this.token!);
                    
                    return true;
                }
            } catch (err: any) {
                this.error = err.response?.data?.message || 'Login failed';
                return false;
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            this.loading = true;
            try {
                if (this.token) {
                    await api.post('/logout');
                }
            } catch (e) {
                console.error('Logout error', e);
            } finally {
                this.user = null;
                this.token = null;
                localStorage.removeItem('user');
                localStorage.removeItem('auth_token');
                this.loading = false;
            }
        }
    }
});
