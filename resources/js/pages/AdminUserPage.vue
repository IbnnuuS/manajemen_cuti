<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head, Link } from '@inertiajs/vue3';

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
        const response = await api.get('/admin/users');
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
    <Head title="Sisa Cuti Karyawan" />
    <AuthenticatedLayout>
        <div class="pagetitle">
            <h1>Direktori Karyawan</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/">Beranda</Link></li>
                    <li class="breadcrumb-item">Admin</li>
                    <li class="breadcrumb-item active">Karyawan</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row" v-if="loading">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat data karyawan...</span>
                    </div>
                </div>
            </div>

            <div class="row" v-else>
                <div v-for="user in users" :key="user.id" class="col-xl-4 col-md-6 mb-4">
                    <div class="card info-card h-100 shadow-sm border-0">
                        <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                            
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-3 text-uppercase" style="width: 64px; height: 64px; font-size: 2rem; font-weight: bold;">
                                {{ user.name.charAt(0) }}
                            </div>

                            <h5 class="fw-bold fs-5 text-dark">{{ user.name }}</h5>
                            <h6 class="text-muted small">{{ user.email }}</h6>
                            <span class="badge mb-3" :class="user.role === 'admin' ? 'bg-danger' : 'bg-success'">
                                {{ user.role === 'admin' ? 'Administrator' : 'Karyawan' }}
                            </span>
                            
                            <div class="w-100 mt-2 px-3">
                                <p class="text-muted small fw-bold mb-2 text-uppercase text-start" style="letter-spacing: 0.5px">Kuota Cuti Berjalan</p>
                                
                                <div v-if="user.balances && user.balances.length > 0">
                                    <div v-for="(balance, index) in user.balances" :key="index" class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                        <span class="text-dark small">{{ balance.leave_type.name }}</span>
                                        <span class="badge bg-light text-dark border">
                                            Sisa: {{ balance.total_quota - balance.used }} / {{ balance.total_quota }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-center text-muted small fst-italic py-2 border rounded">
                                    Belum ada jatah cuti.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="users.length === 0" class="col-12 text-center text-muted py-5">
                    <i class="bi bi-people-fill fs-1"></i>
                    <p class="mt-2">Tidak ada pengguna terdaftar di sistem.</p>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
