<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const authStore = useAuthStore();
const email = ref('');
const password = ref('');

const handleLogin = async () => {
    const success = await authStore.login({ email: email.value, password: password.value });

    if (success) {
        if (authStore.isAdmin) {
            router.visit('/admin/requests');
        } else {
            router.visit('/dashboard');
        }
    }
};
</script>

<template>
    <Head title="Masuk" />
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                            <div class="d-flex justify-content-center py-4">
                                <a href="/" class="logo d-flex align-items-center w-auto">
                                    <span class="d-none d-lg-block">LeaveHub</span>
                                </a>
                            </div>

                            <div class="card mb-3 shadow">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">Masuk ke Akun Anda</h5>
                                        <p class="text-center small">Masukkan email & kata sandi untuk masuk</p>
                                    </div>

                                    <div v-if="authStore.error" class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-octagon me-1"></i>
                                        {{ authStore.error }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup" @click="authStore.error = null"></button>
                                    </div>

                                    <form @submit.prevent="handleLogin" class="row g-3 needs-validation">

                                        <div class="col-12">
                                            <label for="yourEmail" class="form-label">Email</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                <input type="email" v-model="email" name="email" class="form-control" id="yourEmail" required placeholder="nama@email.com">
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="yourPassword" class="form-label">Kata Sandi</label>
                                            <input type="password" v-model="password" name="password" class="form-control" id="yourPassword" required>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" value="true" id="rememberMe">
                                                <label class="form-check-label" for="rememberMe">Ingat saya</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit" :disabled="authStore.loading">
                                                <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                {{ authStore.loading ? 'Memproses...' : 'Masuk' }}
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            </div>

                            <div class="credits text-center">
                                Sistem Manajemen Cuti &bull; {{ new Date().getFullYear() }}<br>
                                <a href="https://github.com/IbnnuuS" target="_blank" class="text-muted small">
                                    <i class="bi bi-github me-1"></i>github.com/IbnnuuS
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
