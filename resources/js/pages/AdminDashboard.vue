<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head, Link } from '@inertiajs/vue3';

interface DashboardData {
    stats: {
        pending: number;
        approved: number;
        rejected: number;
    };
    actionables: any[];
}

const data = ref<DashboardData>({
    stats: { pending: 0, approved: 0, rejected: 0 },
    actionables: []
});
const loading = ref(true);

const fetchDashboard = async () => {
    loading.value = true;
    try {
        const response = await api.get('/admin/dashboard-stats');
        data.value = response.data.data;
    } catch (e) {
        console.error('Failed to load admin dashboard', e);
    } finally {
        loading.value = false;
    }
};

const respondToRequest = async (id: number, action: 'approve' | 'reject') => {
    const verb = action === 'approve' ? 'Menyetujui' : 'Menolak';
    const promptMessage = action === 'reject' 
        ? '⚠️ PENOLAKAN: Silakan masukkan alasan/catatan (wajib jika ditolak):'
        : 'Silakan masukkan catatan tambahan (opsional):';
        
    const notes = window.prompt(promptMessage);
    if (notes === null) return; 

    if (action === 'reject' && !notes.trim()) {
        alert("Gagal: Anda harus menyertakan alasan penolakan!");
        return;
    }

    try {
        await api.post(`/leave-requests/${id}/${action}`, { notes });
        alert(`Sukses ${verb} pengajuan cuti ini.`);
        await fetchDashboard();
    } catch (e: any) {
         alert(`Terjadi masalah saat memproses.`);
    }
};

onMounted(() => {
    fetchDashboard();
});
</script>

<template>
    <Head title="Dasbor Admin" />
    <AuthenticatedLayout>
        <div class="pagetitle">
            <h1>Dasbor Admin</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/">Beranda</Link></li>
                    <li class="breadcrumb-item active">Dasbor Admin</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <!-- Loading State -->
            <div v-if="loading" class="d-flex justify-content-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Memuat data...</span>
                </div>
            </div>

            <div v-else class="row">
                <!-- Data Cards -->
                <div class="col-xxl-4 col-md-4">
                    <div class="card info-card sales-card border-bottom border-warning border-3 border-0">
                        <div class="card-body">
                            <h5 class="card-title">Menunggu Tindakan</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-warning text-white">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ data.stats.pending }} <span class="text-muted small fs-6 fw-normal">Cuti</span></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-4 col-md-4">
                    <div class="card info-card revenue-card border-bottom border-success border-3 border-0">
                        <div class="card-body">
                            <h5 class="card-title">Disetujui <span>| Total</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-success text-white">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ data.stats.approved }} <span class="text-muted small fs-6 fw-normal">Cuti</span></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-4 col-md-4">
                    <div class="card info-card customers-card border-bottom border-danger border-3 border-0">
                        <div class="card-body">
                            <h5 class="card-title">Ditolak <span>| Total</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-danger text-white">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ data.stats.rejected }} <span class="text-muted small fs-6 fw-normal">Cuti</span></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Data Cards -->

                <!-- Recent Actionables Table -->
                <div class="col-12 mt-3">
                    <div class="card recent-sales overflow-auto shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title">Perlu Tindakan <span>| Menunggu Persetujuan Anda</span></h5>

                            <table class="table table-hover align-middle datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">No.</th>
                                        <th scope="col">Karyawan</th>
                                        <th scope="col">Tipe</th>
                                        <th scope="col">Mulai - Selesai</th>
                                        <th scope="col">Durasi</th>
                                        <th scope="col">Alasan</th>
                                        <th scope="col">Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="data.actionables.length === 0">
                                        <td colspan="7" class="text-center py-4 text-muted fst-italic">
                                            Tidak ada pengajuan cuti yang menunggu tindakan saat ini. Anda sudah menyelesaikan semua pekerjaan! 🎉
                                        </td>
                                    </tr>
                                    <tr v-for="(request, index) in data.actionables" :key="request.id">
                                        <th scope="row">{{ index + 1 }}</th>
                                        <td>
                                            <div class="fw-bold">{{ request.user?.name }}</div>
                                            <div class="small text-muted">{{ request.user?.email }}</div>
                                        </td>
                                        <td><span class="badge bg-primary">{{ request.leave_type?.name }}</span></td>
                                        <td class="small">{{ request.start_date }} <br> <span class="text-muted">s/d</span> <br> {{ request.end_date }}</td>
                                        <td><strong>{{ request.total_days }}</strong> Hari</td>
                                        <td class="small" style="max-width: 200px">
                                           {{ request.reason || '-' }}
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button @click="respondToRequest(request.id, 'approve')" class="btn btn-sm btn-success" title="Setujui Cuti">
                                                    <i class="bi bi-check-lg"></i> Setujui
                                                </button>
                                                <button @click="respondToRequest(request.id, 'reject')" class="btn btn-sm btn-outline-danger" title="Tolak Cuti">
                                                    <i class="bi bi-x-lg"></i> Tolak
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <div class="text-end pt-3" v-if="data.actionables.length >= 10">
                                <Link href="/admin/requests" class="btn btn-sm btn-light text-primary">Lihat Seluruh Riwayat &rarr;</Link>
                            </div>

                        </div>
                    </div>
                </div><!-- End Recent Actionables -->

            </div>
        </section>
    </AuthenticatedLayout>
</template>
