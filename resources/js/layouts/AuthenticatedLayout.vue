<script setup lang="ts">
import { useAuthStore } from '../stores/auth';
import { router, Link } from '@inertiajs/vue3';

const authStore = useAuthStore();

const handleLogout = async () => {
    await authStore.logout();
    router.visit('/login');
};

const navigation = [
    { name: 'Dashboard', href: '/dashboard', role: 'user' },
    { name: 'Submit Leave', href: '/requests/create', role: 'user' },
    { name: 'All Requests (Admin)', href: '/admin/requests', role: 'admin' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
        <!-- Navigation Bar -->
        <nav class="bg-white sticky top-0 z-50 shadow-sm border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center bg-indigo-600 text-white w-10 h-10 rounded-xl justify-center font-bold text-xl shadow-md">
                            LR
                        </div>
                        <div class="hidden md:ml-8 md:flex md:space-x-8">
                            <template v-for="item in navigation" :key="item.name">
                                <Link v-if="(item.role === 'admin' && authStore.isAdmin) || (item.role === 'user' && !authStore.isAdmin)"
                                   :href="item.href"
                                   class="inline-flex items-center px-1 pt-1 border-b-2"
                                   :class="[ $page.url.startsWith(item.href) ? 'border-indigo-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ]">
                                    {{ item.name }}
                                </Link>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex sm:items-center">
                            <div class="text-sm border-r border-gray-200 pr-4 mr-4 text-gray-700">
                                Welcome, <span class="font-bold text-gray-900">{{ authStore.user?.name }}</span>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                      :class="authStore.isAdmin ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'">
                                    {{ authStore.user?.role }}
                                </span>
                            </div>
                            <button @click="handleLogout"
                               class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="flex-1 w-full max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Global loading state -->
            <div v-if="authStore.loading" class="fixed inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center">
                <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            </div>
            
            <slot />
        </main>
    </div>
</template>
