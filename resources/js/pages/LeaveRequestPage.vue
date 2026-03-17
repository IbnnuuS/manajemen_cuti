<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { router, Head } from '@inertiajs/vue3';

interface LeaveType {
    id: number;
    name: string;
}

const leaveTypes = ref<LeaveType[]>([]);
const form = ref({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: ''
});

const loading = ref(false);
const errorMsg = ref<string | null>(null);

const fetchLeaveTypes = async () => {
    try {
        const response = await api.get('/leave-types');
        leaveTypes.value = response.data.data;
    } catch (e) {
        console.error('Failed to load leave types', e);
    }
};

const submitRequest = async () => {
    loading.value = true;
    errorMsg.value = null;
    
    try {
        await api.post('/leave-requests', form.value);
        router.visit('/dashboard');
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || 'Failed to submit leave request. Please check your inputs.';
    } finally {
        loading.value = false;
    }
};

// Calculate min date (today) for date pickers
const today = new Date().toISOString().split('T')[0];

onMounted(() => {
    fetchLeaveTypes();
});
</script>

<template>
    <Head title="Submit Leave Request" />
    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto py-6 animate-fade-in-up">
            <div class="bg-white px-8 py-10 shadow-sm rounded-2xl border border-gray-100">
                <div class="mb-8 border-b border-gray-100 pb-5">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Request Leave</h2>
                    <p class="mt-2 text-sm text-gray-500">Fill out the form below to request time off.</p>
                </div>

                <form @submit.prevent="submitRequest" class="space-y-6">
                    <div v-if="errorMsg" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r flex gap-3 text-red-700 animate-pulse">
                        <svg class="w-5 h-5 flexible shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                        <p class="text-sm font-medium">{{ errorMsg }}</p>
                    </div>

                    <div>
                        <label for="leave_type" class="block text-sm font-medium text-gray-700">Leave Type</label>
                        <select id="leave_type" v-model="form.leave_type_id" required
                                class="mt-2 block w-full rounded-xl border-gray-300 py-3 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm bg-gray-50/50">
                            <option value="" disabled>Select a type...</option>
                            <option v-for="type in leaveTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" id="start_date" v-model="form.start_date" :min="today" required
                                   class="mt-2 block w-full rounded-xl border-gray-300 py-3 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm bg-gray-50/50" />
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" id="end_date" v-model="form.end_date" :min="form.start_date || today" required
                                   class="mt-2 block w-full rounded-xl border-gray-300 py-3 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm bg-gray-50/50" />
                        </div>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700">Reason</label>
                        <div class="mt-2">
                            <textarea id="reason" v-model="form.reason" rows="4" required placeholder="Briefly explain your reason..."
                                      class="block w-full rounded-xl border border-gray-300 py-3 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm bg-gray-50/50"></textarea>
                        </div>
                    </div>

                    <div class="pt-5 flex justify-end gap-3 border-t border-gray-50">
                        <button type="button" @click="router.visit('/dashboard')"
                                class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="loading"
                                class="inline-flex justify-center rounded-xl border border-transparent bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ loading ? 'Submitting...' : 'Submit Request' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
