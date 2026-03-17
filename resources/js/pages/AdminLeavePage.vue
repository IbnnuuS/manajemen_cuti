<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head } from '@inertiajs/vue3';

interface LeaveRequest {
    id: number;
    user: { name: string; email: string };
    leave_type: { name: string };
    start_date: string;
    end_date: string;
    total_days: number;
    status: string;
    reason: string;
    admin_notes: string | null;
}

const requests = ref<LeaveRequest[]>([]);
const loading = ref(true);

const processingId = ref<number | null>(null);
const actionNotes = ref('');

const fetchRequests = async () => {
    loading.value = true;
    try {
        const response = await api.get('/leave-requests');
        requests.value = response.data.data;
    } catch (e) {
        console.error('Failed to load requests for admin', e);
    } finally {
        loading.value = false;
    }
};

const handleAction = async (id: number, action: 'approve' | 'reject') => {
    const notesInput = prompt(`Enter notes for ${action} (optional):`, '');
    if (notesInput === null) return; // User cancelled prompt

    processingId.value = id;
    try {
        await api.post(`/leave-requests/${id}/${action}`, { notes: notesInput });
        await fetchRequests(); // Refresh list to reflect changes
    } catch (e: any) {
        alert(e.response?.data?.message || `Failed to ${action} request`);
    } finally {
        processingId.value = null;
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
    fetchRequests();
});
</script>

<template>
    <Head title="Admin Dashboard" />
    <AuthenticatedLayout>
        <div class="space-y-6 animate-fade-in-up">
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Admin Command Center</h1>
                    <p class="mt-2 text-sm text-gray-600">Review and manage all employee leave requests globally.</p>
                </div>
            </div>

            <!-- Global loading state inside card -->
            <div v-if="loading" class="bg-white px-4 py-12 shadow-sm rounded-2xl flex justify-center border border-gray-100">
                <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            </div>

            <div v-else class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Details</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dates</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="req in requests" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold uppercase">
                                            {{ req.user.name.charAt(0) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ req.user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ req.user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium mb-1">{{ req.leave_type.name }} <span class="text-gray-400 font-normal">({{ req.total_days }} days)</span></div>
                                    <div class="text-sm text-gray-500 max-w-xs truncate" :title="req.reason">"{{ req.reason }}"</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="font-medium text-gray-900">{{ req.start_date }}</div>
                                    <div class="text-gray-400 text-xs mt-1">to {{ req.end_date }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize shadow-sm', getStatusBadgeClass(req.status)]">
                                        {{ req.status }}
                                    </span>
                                    <div v-if="req.admin_notes" class="text-xs text-indigo-600 mt-2 flex items-start truncate max-w-[150px]" :title="req.admin_notes">
                                        <svg class="w-4 h-4 mr-1 shrink-0 flex" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                        {{ req.admin_notes }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div v-if="req.status === 'pending'" class="flex justify-end gap-2">
                                        <button @click="handleAction(req.id, 'approve')" :disabled="processingId === req.id"
                                                class="text-green-700 bg-green-50 hover:bg-green-100 hover:text-green-900 px-3 py-1.5 rounded-lg transition-colors border border-green-200 disabled:opacity-50">
                                            Approve
                                        </button>
                                        <button @click="handleAction(req.id, 'reject')" :disabled="processingId === req.id"
                                                class="text-red-700 bg-red-50 hover:bg-red-100 hover:text-red-900 px-3 py-1.5 rounded-lg transition-colors border border-red-200 disabled:opacity-50">
                                            Reject
                                        </button>
                                    </div>
                                    <span v-else class="text-gray-400 text-xs italic">
                                        Responded
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="requests.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 bg-gray-50/30">
                                    No leave requests found system-wide.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
