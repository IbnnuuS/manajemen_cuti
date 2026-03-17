<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head, Link } from '@inertiajs/vue3';

interface PaginatedUsers {
    current_page: number;
    data: any[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

const tableData = ref<PaginatedUsers | null>(null);
const loading = ref(true);

const form = ref({
    name: '',
    email: '',
    password: ''
});
const saving = ref(false);
const errors = ref<Record<string, string[]>>({});

const loadUsers = async (url = '/admin/users') => {
    loading.value = true;
    try {
        const response = await api.get(url);
        tableData.value = response.data;
    } catch (e) {
        console.error('Failed to load users', e);
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (url: string | null) => {
    if (url) {
        // Axios base URL overrides full URLs from Laravel paginator if not careful.
        // We will extract just the path + query.
        const urlObj = new URL(url);
        loadUsers(urlObj.pathname + urlObj.search);
    }
};

const submitNewUser = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await api.post('/admin/users', form.value);
        alert('Pengguna berhasil ditambahkan! Sistem telah membuat alokasi cuti Tahunan (12) dan Sakit (6) default untuk akun ini.');

        // Reset form
        form.value = { name: '', email: '', password: '' };

        // Hide Bootstrap modal manually since we aren't pulling in Vue-Bootstrap components directly
        const modalElement = document.getElementById('addUserModal');
        if (modalElement) {
           const modalInstance = (window as any).bootstrap.Modal.getInstance(modalElement);
           if (modalInstance) {
               modalInstance.hide();
           }
        }

        // Reload the first page data
        await loadUsers();
    } catch (e: any) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
        } else if (e.response && e.response.data && e.response.data.message) {
            alert(e.response.data.message);
        } else {
            alert('Gagal membuat user baru. Silakan cek form Anda.');
        }
    } finally {
        saving.value = false;
    }
};

const deleteUser = async (user: any) => {
    const confirmed = window.confirm(`Yakin ingin menghapus karyawan "${user.name}"?\nSemua data cuti dan pengajuan miliknya akan ikut terhapus.`);
    if (!confirmed) return;

    try {
        const res = await api.delete(`/admin/users/${user.id}`);
        alert(res.data.message);
        await loadUsers();
    } catch (e: any) {
        const msg = e.response?.data?.message || 'Gagal menghapus karyawan.';
        alert(msg);
    }
};

onMounted(() => {
    loadUsers();
});
</script>

<template>
    <Head title="Kelola Pengguna" />
    <AuthenticatedLayout>
        <div class="pagetitle d-flex justify-content-between align-items-center">
            <div>
                <h1>Kelola Pengguna Sistem</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><Link href="/">Beranda</Link></li>
                        <li class="breadcrumb-item">Admin</li>
                        <li class="breadcrumb-item active">Kelola Pengguna</li>
                    </ol>
                </nav>
            </div>

            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Karyawan Baru
            </button>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">

                <!-- Quick Metric -->
                <div class="col-12 mb-3">
                     <div class="card info-card sales-card border-bottom border-primary border-3 border-0">
                        <div class="card-body">
                            <h5 class="card-title">Total Karyawan Terdaftar</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-primary text-white">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ps-3" v-if="!loading && tableData">
                                    <h6>{{ tableData.total }} <span class="text-muted small fs-6 fw-normal">Orang</span></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                                                       <div v-if="loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Memuat pengguna...</span>
                                </div>
                            </div>

                            <div v-else-if="tableData" class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">No.</th>
                                            <th scope="col">Nama Lengkap</th>
                                            <th scope="col">Email Login</th>
                                            <th scope="col">Peran Akses</th>
                                            <th scope="col">Informasi Kuota Saat Ini (Aktif)</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(user, index) in tableData.data" :key="user.id">
                                            <th scope="row">{{ (tableData.current_page - 1) * tableData.per_page + index + 1 }}</th>
                                            <td class="fw-bold">{{ user.name }}</td>
                                            <td>{{ user.email }}</td>
                                            <td>
                                                <span class="badge" :class="user.role === 'admin' ? 'bg-danger' : 'bg-success'">
                                                    {{ user.role === 'admin' ? 'Administrator' : 'Karyawan' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div v-if="user.leave_balances && user.leave_balances.length > 0" class="d-flex flex-wrap gap-2">
                                                    <span v-for="balance in user.leave_balances" :key="balance.id" class="badge bg-light text-dark border p-2 fw-normal">
                                                        <strong class="text-primary">{{ balance.leave_type?.name }}</strong>:
                                                        {{ balance.total_quota - balance.used }} / {{ balance.total_quota }}
                                                        <span class="text-muted">sisa</span>
                                                    </span>
                                                </div>
                                                <div v-else class="text-muted small fst-italic">
                                                    Belum ada alokasi cuti aktif.
                                                </div>
                                            </td>
                                            <td>
                                                <button
                                                    @click="deleteUser(user)"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Hapus Karyawan"
                                                >
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Pagination Links -->
                                <nav v-if="tableData.last_page > 1" class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="small text-muted">
                                        Menampilkan {{ tableData.from }} hingga {{ tableData.to }} dari total {{ tableData.total }} pegawai.
                                    </div>
                                    <ul class="pagination mb-0">
                                        <li class="page-item" :class="{'disabled': !tableData.prev_page_url}">
                                            <a class="page-link cursor-pointer" @click.prevent="handlePageChange(tableData.prev_page_url)">&laquo; Mundur</a>
                                        </li>
                                        <li class="page-item active"><span class="page-link">{{ tableData.current_page }}</span></li>
                                        <li class="page-item" :class="{'disabled': !tableData.next_page_url}">
                                            <a class="page-link cursor-pointer" @click.prevent="handlePageChange(tableData.next_page_url)">Maju &raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ADD USER MODAL -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold" id="addUserModalLabel">
                            <i class="bi bi-person-plus-fill me-1 text-primary"></i> Tambah Karyawan Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup" :disabled="saving"></button>
                    </div>

                    <form @submit.prevent="submitNewUser">
                        <div class="modal-body">
                            <div class="alert alert-info py-2 small mb-4">
                                <i class="bi bi-info-circle me-1"></i>
                                Akun ini akan otomatis mendapatkan jatah Cuti Tahunan (12) dan Sakit (6) saat pendaftaran.
                            </div>

                            <div class="mb-3">
                                <label for="nameInput" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" :class="{'is-invalid': errors.name}" id="nameInput" v-model="form.name" required>
                                <div class="invalid-feedback" v-if="errors.name">{{ errors.name[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="emailInput" class="form-label">Email Karyawan</label>
                                <input type="email" class="form-control" :class="{'is-invalid': errors.email}" id="emailInput" v-model="form.email" placeholder="nama@perusahaan.com" required>
                                <div class="invalid-feedback" v-if="errors.email">{{ errors.email[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="passwordInput" class="form-label">Kata Sandi Akses</label>
                                <input type="password" class="form-control" :class="{'is-invalid': errors.password}" id="passwordInput" v-model="form.password" required>
                                <div class="invalid-feedback" v-if="errors.password">{{ errors.password[0] }}</div>
                                <div class="form-text small text-muted">Pastikan kata sandi minimal berisi 8 karakter keamanan.</div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" :disabled="saving">Batalkan</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ saving ? 'Menyimpan...' : 'Simpan Karyawan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- END MODAL -->

    </AuthenticatedLayout>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
