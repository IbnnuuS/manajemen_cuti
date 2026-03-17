<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head } from '@inertiajs/vue3';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    balances: {
        leave_type: { name: string, default_quota: number };
        total_quota: number;
        used: number;
        year: number;
    }[];
}

const users = ref<User[]>([]);
const loading = ref(true);

const fetchUsers = async () => {
    loading.value = true;
    try {
        const response = await api.get('/admin/users'); // Requires a generic backend endpoint
        users.value = response.data.data;
    } catch (e) {
        console.error('Failed to load users for admin', e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <Head title="Employee Balances" />
    <AuthenticatedLayout>
        <div class="space-y-6 animate-fade-in-up">
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Employee Directory</h1>
                    <p class="mt-2 text-sm text-gray-600">Overview of all employees and their respective leave counters.</p>
                </div>
            </div>

            <div v-if="loading" class="bg-white px-4 py-12 shadow-sm rounded-2xl flex justify-center border border-gray-100">
                <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="user in users" :key="user.id" class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                                {{ user.name.charAt(0) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ user.name }}</h3>
                                <p class="text-sm text-gray-500">{{ user.email }}</p>
                            </div>
                        </div>
                        <span :class="{'bg-purple-100 text-purple-800': user.role === 'admin', 'bg-green-100 text-green-800': user.role === 'user'}" class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize">
                            {{ user.role }}
                        </span>
                    </div>
                    <div class="px-6 py-4 bg-gray-50/50">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Leave Balances</h4>
                        <div class="space-y-3">
                            <div v-for="(balance, index) in user.balances" :key="index" class="flex justify-between items-center text-sm">
                                <span class="font-medium text-gray-700">{{ balance.leave_type.name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-900 font-bold bg-white px-2 py-0.5 rounded border border-gray-200">{{ balance.total_quota - balance.used }} left</span>
                                    <span class="text-gray-400 text-xs">/ {{ balance.total_quota }}</span>
                                </div>
                            </div>
                            <div v-if="!user.balances || user.balances.length === 0" class="text-sm text-gray-500 italic">
                                No balances recorded.
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="users.length === 0" class="col-span-full py-12 text-center text-gray-500 bg-white rounded-2xl border border-gray-100">
                    No users found in the system.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
