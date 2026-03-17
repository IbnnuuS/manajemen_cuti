<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head } from '@inertiajs/vue3';

interface LeaveBalance {
    id: number;
    leave_type: { name: string; default_quota: number };
    total_quota: number;
    used: number;
    year: number;
}

interface LeaveRequest {
    id: number;
    leave_type: { name: string };
    start_date: string;
    end_date: string;
    total_days: number;
    status: string;
    reason: string;
    admin_notes: string | null;
}

const balances = ref<LeaveBalance[]>([]);
const requests = ref<LeaveRequest[]>([]);
const loading = ref(true);

const fetchDashboardData = async () => {
    loading.value = true;
    try {
        const [balanceRes, requestsRes] = await Promise.all([
            api.get('/my-leave-balances'),
            api.get('/my-leave-requests')
        ]);
        balances.value = balanceRes.data.data;
        requests.value = requestsRes.data.data;
    } catch (e) {
        console.error('Failed to fetch dashboard data', e);
    } finally {
        loading.value = false;
    }
};

const cancelRequest = async (id: number) => {
    if (!confirm('Are you sure you want to cancel this leave request?')) return;
    try {
        await api.post(`/leave-requests/${id}/cancel`);
        await fetchDashboardData();
    } catch (e) {
        alert('Failed to cancel request');
    }
};

const deleteRequest = async (id: number) => {
    if (!confirm('Are you sure you want to permanently delete this leave request record?')) return;
    try {
        await api.delete(`/leave-requests/${id}`);
        await fetchDashboardData();
    } catch (e) {
        alert('Failed to delete request');
    }
};

const getStatusBadgeClass = (status: string) => {
    switch(status) {
        case 'approved': return 'bg-green-100 text-green-800 border border-green-200';
        case 'rejected': return 'bg-red-100 text-red-800 border border-red-200';
        case 'canceled': return 'bg-gray-100 text-gray-800 border border-gray-200';
        default: return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
    }
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div v-if="loading" class="space-y-4">
            <div class="h-32 bg-gray-200 rounded-2xl animate-pulse"></div>
            <div class="h-64 bg-gray-200 rounded-2xl animate-pulse"></div>
        </div>

        <div v-else class="space-y-8 animate-fade-in-up">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">My Dashboard</h1>
                    <p class="mt-2 text-sm text-gray-600">Overview of your leave balances and recent requests.</p>
                </div>
            </div>

            <!-- Balances Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="balance in balances" :key="balance.id" 
                     class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-3 bg-indigo-50 rounded-xl">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">{{ balance.leave_type.name }}</dt>
                                    <dd>
                                        <div class="text-2xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-2">{{ balance.total_quota - balance.used }} <span class="text-sm font-normal text-gray-500">days left</span></div>
                                        <div class="text-xs text-gray-500 flex justify-between">
                                            <span>Used: {{ balance.used }}</span>
                                            <span>Total: {{ balance.total_quota }}</span>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900">Recent Requests</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dates</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Days</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Notes / Reason</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="req in requests" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ req.leave_type.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ req.start_date }} <span class="text-gray-400 mx-1">→</span> {{ req.end_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ req.total_days }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize shadow-sm', getStatusBadgeClass(req.status)]">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" :title="req.reason">
                                    {{ req.reason }}
                                    <div v-if="req.admin_notes" class="text-xs text-red-500 mt-1 font-medium bg-red-50 p-1 rounded">Note: {{ req.admin_notes }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button v-if="req.status === 'pending'" @click="cancelRequest(req.id)" class="text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 px-3 py-1 rounded-lg transition-colors mr-2">
                                        Cancel
                                    </button>
                                    <button v-if="['approved', 'rejected', 'canceled'].includes(req.status)" @click="deleteRequest(req.id)" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-lg transition-colors">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="requests.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 bg-gray-50/30">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                                    You haven't requested any leave yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out forwards;
}
</style>
