<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head, Link, router } from '@inertiajs/vue3';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const leaveBalances = ref<any[]>([]);
const recentRequests = ref<any[]>([]);
const loading = ref(true);

const loadDashboardData = async () => {
    loading.value = true;
    try {
        const [balanceRes, requestsRes] = await Promise.all([
            api.get('/my-leave-balances'),
            api.get('/my-leave-requests')
        ]);
        
        leaveBalances.value = balanceRes.data.data;
        recentRequests.value = requestsRes.data.data;
    } catch (e) {
        console.error('Failed to load dashboard', e);
    } finally {
        loading.value = false;
    }
};

const handleAction = async (id: number, action: 'cancel' | 'delete') => {
    if (!confirm(`Apakah Anda yakin ingin ${action === 'cancel' ? 'membatalkan' : 'menghapus'} pengajuan ini?`)) return;
    try {
        if (action === 'cancel') {
            await api.post(`/leave-requests/${id}/cancel`);
        } else {
            await api.delete(`/leave-requests/${id}`);
        }
        await loadDashboardData();
    } catch (e) {
        console.error(`Failed to ${action} request`, e);
        alert(`Gagal ${action} cuti.`);
    }
};

const getStatusBadgeClass = (status: string) => {
    switch(status) {
        case 'pending': return 'bg-warning text-dark';
        case 'approved': return 'bg-success';
        case 'rejected': return 'bg-danger';
        case 'cancelled': return 'bg-secondary';
        default: return 'bg-primary';
    }
};

const getStatusText = (status: string) => {
    switch(status) {
        case 'pending': return 'Menunggu';
        case 'approved': return 'Disetujui';
        case 'rejected': return 'Ditolak';
        case 'cancelled': return 'Dibatalkan';
        default: return status;
    }
};

onMounted(() => {
    loadDashboardData();
});
</script>

<template>
    <Head title="Dasbor Karyawan" />
    <AuthenticatedLayout>
        <div class="pagetitle">
            <h1>Dasbor</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/">Beranda</Link></li>
                    <li class="breadcrumb-item active">Dasbor</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Left side columns -->
                <div class="col-lg-12">
                    <div class="row">

                        <!-- Leave Balance Cards -->
                        <div v-if="loading" class="col-12 text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Memuat...</span>
                            </div>
                        </div>

                        <div v-else v-for="balance in leaveBalances" :key="balance.id" class="col-xxl-6 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title">Sisa Cuti <span>| {{ balance.leave_type.name }}</span></h5>

                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-primary text-white">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ balance.total_quota - balance.used }} <span class="text-muted small fw-normal">hari</span></h6>
                                            <span class="text-danger small pt-1 fw-bold">{{ balance.used }} hari</span> <span class="text-muted small pt-2 ps-1">telah digunakan dari total {{ balance.total_quota }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!loading && leaveBalances.length === 0" class="col-12">
                            <div class="alert alert-info">Belum ada data jatah cuti untuk tahun ini.</div>
                        </div>

                        <!-- Recent Leave Requests Table -->
                        <div class="col-12">
                            <div class="card recent-sales overflow-auto">
                                <div class="card-body">
                                    <h5 class="card-title">Riwayat Pengajuan Cuti <span>| Terbaru</span></h5>

                                    <table class="table table-borderless datatable">
                                        <thead>
                                            <tr>
                                                <th scope="col">No.</th>
                                                <th scope="col">Tipe</th>
                                                <th scope="col">Mulai</th>
                                                <th scope="col">Selesai</th>
                                                <th scope="col">Durasi</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="loading">
                                                <td colspan="7" class="text-center">Memuat riwayat otomatis...</td>
                                            </tr>
                                            <tr v-else-if="recentRequests.length === 0">
                                                <td colspan="7" class="text-center text-muted">Belum ada riwayat pengajuan cuti yang diajukan.</td>
                                            </tr>
                                            <tr v-else v-for="(request, index) in recentRequests" :key="request.id">
                                                <th scope="row">{{ index + 1 }}</th>
                                                <td>{{ request.leave_type?.name }}</td>
                                                <td>{{ request.start_date }}</td>
                                                <td>{{ request.end_date }}</td>
                                                <td><span class="fw-bold">{{ request.total_days }}</span> hari</td>
                                                <td><span class="badge" :class="getStatusBadgeClass(request.status)">{{ getStatusText(request.status) }}</span></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button v-if="request.status === 'pending'" @click="handleAction(request.id, 'cancel')" class="btn btn-sm btn-outline-warning" title="Batalkan">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                        <button v-if="['approved', 'rejected', 'cancelled'].includes(request.status)" @click="handleAction(request.id, 'delete')" class="btn btn-sm btn-outline-danger" title="Hapus Riwayat">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div><!-- End Recent Leave Requests -->

                    </div>
                </div><!-- End Left side columns -->

            </div>
        </section>
    </AuthenticatedLayout>
</template>
