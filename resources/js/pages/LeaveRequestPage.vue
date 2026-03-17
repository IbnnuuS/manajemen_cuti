<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '../layouts/AuthenticatedLayout.vue';
import api from '../api';
import { Head, Link, router } from '@inertiajs/vue3';

const leaveTypes = ref<{ id: number; name: string; default_quota: number }[]>([]);
const form = ref({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: ''
});
const loading = ref(false);
const submitting = ref(false);
const errors = ref<Record<string, string[]>>({});

onMounted(async () => {
    loading.value = true;
    try {
        const response = await api.get('/leave-types');
        leaveTypes.value = response.data.data;
        if (leaveTypes.value.length > 0) {
            form.value.leave_type_id = leaveTypes.value[0].id.toString();
        }
    } catch (e) {
        console.error('Failed to load leave types', e);
    } finally {
        loading.value = false;
    }
});

const submitRequest = async () => {
    submitting.value = true;
    errors.value = {};
    try {
        await api.post('/leave-requests', form.value);
        alert('Cuti berhasil diajukan! Menunggu persetujuan admin.');
        router.visit('/dashboard');
    } catch (e: any) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
        } else if (e.response && e.response.data && e.response.data.message) {
            alert(e.response.data.message);
        } else {
            console.error('Failed submitting leave request', e);
            alert('Gagal mengajukan cuti. Silakan coba lagi.');
        }
    } finally {
        submitting.value = false;
    }
};

const getTodayDate = () => {
    const today = new Date();
    return today.toISOString().split('T')[0];
};
</script>

<template>
    <Head title="Ajukan Cuti Baru" />
    <AuthenticatedLayout>
        <div class="pagetitle">
            <h1>Formulir Pengajuan Cuti</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/">Beranda</Link></li>
                    <li class="breadcrumb-item"><Link href="/dashboard">Dasbor</Link></li>
                    <li class="breadcrumb-item active">Pengajuan Baru</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title pb-0">Isi Rincian Cuti Anda</h5>
                            <p class="text-muted small pb-3">Sistem akan menolak secara otomatis jika durasi cuti melebihi sisa kuota yang tersedia atau bertabrakan dengan hari yang telah diproses.</p>

                            <div v-if="loading" class="d-flex justify-content-center my-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Memuat formulir...</span>
                                </div>
                            </div>

                            <!-- General Form Elements -->
                            <form v-else @submit.prevent="submitRequest">
                                
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Tipe Cuti</label>
                                    <div class="col-sm-9">
                                        <select v-model="form.leave_type_id" class="form-select" :class="{'is-invalid': errors.leave_type_id}" aria-label="Pilih Tipe Cuti" required>
                                            <option value="" disabled>Pilih Tipe Cuti</option>
                                            <option v-for="type in leaveTypes" :key="type.id" :value="type.id.toString()">
                                                {{ type.name }} (Maks Kuota: {{ type.default_quota }} Hari)
                                            </option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errors.leave_type_id">
                                            {{ errors.leave_type_id[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="inputDate" class="col-sm-3 col-form-label">Tanggal Mulai Cuti</label>
                                    <div class="col-sm-9">
                                        <input type="date" v-model="form.start_date" :min="getTodayDate()" class="form-control" :class="{'is-invalid': errors.start_date}" required>
                                        <div class="invalid-feedback" v-if="errors.start_date">
                                            {{ errors.start_date[0] }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="inputDate" class="col-sm-3 col-form-label">Tanggal Selesai Cuti</label>
                                    <div class="col-sm-9">
                                        <input type="date" v-model="form.end_date" :min="form.start_date || getTodayDate()" class="form-control" :class="{'is-invalid': errors.end_date}" required>
                                        <div class="invalid-feedback" v-if="errors.end_date">
                                            {{ errors.end_date[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="inputReason" class="col-sm-3 col-form-label">Keterangan / Alasan</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" v-model="form.reason" style="height: 100px" id="inputReason" :class="{'is-invalid': errors.reason}" placeholder="Cth: Mengunjungi keluarga yang sedang sakit..."></textarea>
                                        <div class="invalid-feedback" v-if="errors.reason">
                                            {{ errors.reason[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3 mt-4">
                                    <div class="col-sm-9 offset-sm-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" :disabled="submitting">
                                            <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            {{ submitting ? 'Mengirim Data...' : 'Kirim Pengajuan' }}
                                        </button>
                                        <Link href="/dashboard" class="btn btn-outline-secondary" v-if="!submitting">Batal</Link>
                                    </div>
                                </div>

                            </form><!-- End General Form Elements -->

                        </div>
                    </div>

                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
