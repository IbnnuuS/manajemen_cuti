<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head, Link } from '@inertiajs/vue3';

interface LeaveRequest {
    id: number;
    user: { id: number, name: string, email: string };
    leave_type: { name: string };
    start_date: string;
    end_date: string;
    total_days: number;
    reason: string | null;
    status: string;
    admin_notes: string | null;
}

const requests = ref<LeaveRequest[]>([]);
const loading = ref(true);

const fetchRequests = async () => {
    loading.value = true;
    try {
        const response = await api.get('/leave-requests');
        requests.value = response.data.data;
    } catch (e) {
        console.error('Failed to load leave requests for admin', e);
    } finally {
        loading.value = false;
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
    fetchRequests();
});
</script>

<template>
    <Head title="Seluruh Cuti Karyawan" />
    <AuthenticatedLayout>
        <div class="pagetitle">
            <h1>Review & Validasi Pengajuan</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/">Beranda</Link></li>
                    <li class="breadcrumb-item">Admin</li>
                    <li class="breadcrumb-item active">Seluruh Pengajuan</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title">Tabel Data Pengajuan Karyawan Publik</h5>
                            
                            <div v-if="loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Memuat riwayat otomatis...</span>
                                </div>
                            </div>

                            <!-- Table with stripped rows -->
                            <div v-else class="table-responsive">
                                <table class="table table-hover align-middle datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">No.</th>
                                            <th scope="col">Karyawan</th>
                                            <th scope="col">Tipe</th>
                                            <th scope="col">Tanggal Mulai</th>
                                            <th scope="col">Tanggal Selesai</th>
                                            <th scope="col">Hari</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="requests.length === 0">
                                            <td colspan="8" class="text-center py-4 text-muted fst-italic">Tidak ditemukan data pengajuan apapun.</td>
                                        </tr>
                                        <tr v-for="(request, index) in requests" :key="request.id">
                                            <th scope="row">{{ index + 1 }}</th>
                                            <td>
                                                <div class="fw-bold">{{ request.user?.name }}</div>
                                                <div class="small text-muted">{{ request.user?.email }}</div>
                                            </td>
                                            <td>{{ request.leave_type?.name }}</td>
                                            <td>{{ request.start_date }}</td>
                                            <td>{{ request.end_date }}</td>
                                            <td><strong>{{ request.total_days }}</strong> Hari</td>
                                            <td>
                                                <span class="badge" :class="getStatusBadgeClass(request.status)">{{ getStatusText(request.status) }}</span>
                                            </td>
                                            <td>
                                                <div class="text-muted small">
                                                    <span v-if="request.admin_notes"><i class="bi bi-chat-quote me-1"></i> <span title="Catatan Admin">{{ request.admin_notes }}</span></span>
                                                    <span v-else-if="request.status === 'pending'">Menunggu Tindakan</span>
                                                    <span v-else>Diselesaikan tanpa catatan</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- End Table with stripped rows -->

                        </div>
                    </div>

                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
