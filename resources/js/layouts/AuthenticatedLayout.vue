<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { router, Link } from '@inertiajs/vue3';

const authStore = useAuthStore();
const isSidebarOpen = ref(true);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    if (isSidebarOpen.value) {
        document.body.classList.remove('toggle-sidebar');
    } else {
        document.body.classList.add('toggle-sidebar');
    }
};

const handleLogout = async () => {
    await authStore.logout();
    router.visit('/login');
};

// Ensure body class is correct on load
onMounted(() => {
    document.body.classList.remove('toggle-sidebar');
    // For smaller screens, usually sidebar is closed initially
    if (window.innerWidth < 1200) {
        isSidebarOpen.value = false;
        document.body.classList.add('toggle-sidebar');
    }
});

const navigation = [
    // Karyawan Menu
    { name: 'Dasbor', href: '/dashboard', icon: 'bi-grid', role: 'user' },
    { name: 'Pengajuan Cuti', href: '/requests/create', icon: 'bi-journal-text', role: 'user' },
    
    // Admin Menu
    { name: 'Dasbor Statistik', href: '/admin/dashboard', icon: 'bi-grid', role: 'admin' },
    { name: 'Kelola Karyawan', href: '/admin/users/manage', icon: 'bi-people', role: 'admin' },
    { name: 'Seluruh Riwayat Cuti', href: '/admin/requests', icon: 'bi-journals', role: 'admin' },
];
</script>

<template>
    <div class="h-100">
        <!-- ======= Header ======= -->
        <header id="header" class="header fixed-top d-flex align-items-center bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <Link href="/" class="logo d-flex align-items-center">
                    <span class="d-none d-lg-block text-primary">Leave<span class="text-dark">Hub</span></span>
                </Link>
                <i class="bi bi-list toggle-sidebar-btn cursor-pointer" @click="toggleSidebar"></i>
            </div>

            <nav class="header-nav ms-auto">
                <ul class="d-flex align-items-center">
                    <li class="nav-item dropdown pe-3">
                        <a class="nav-link nav-profile d-flex align-items-center pe-0 cursor-pointer" href="#" data-bs-toggle="dropdown">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: bold;">
                                {{ authStore.user?.name?.charAt(0) }}
                            </div>
                            <span class="d-none d-md-block dropdown-toggle ps-2">{{ authStore.user?.name }}</span>
                        </a><!-- End Profile Iamge Icon -->

                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                            <li class="dropdown-header">
                                <h6>{{ authStore.user?.name }}</h6>
                                <span>{{ authStore.user?.role === 'admin' ? 'Administrator' : 'Karyawan' }}</span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#" @click.prevent="handleLogout">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Keluar</span>
                                </a>
                            </li>
                        </ul><!-- End Profile Dropdown Items -->
                    </li><!-- End Profile Nav -->
                </ul>
            </nav>
        </header><!-- End Header -->

        <!-- ======= Sidebar ======= -->
        <aside id="sidebar" class="sidebar">
            <ul class="sidebar-nav" id="sidebar-nav">
                <li class="nav-heading">Halaman Utama</li>

                <template v-for="item in navigation" :key="item.name">
                    <li class="nav-item" v-if="(item.role === 'admin' && authStore.isAdmin) || (item.role === 'user' && !authStore.isAdmin)">
                        <Link :href="item.href" class="nav-link" :class="{ 'collapsed': !$page.url.startsWith(item.href) }">
                            <i class="bi" :class="item.icon"></i>
                            <span>{{ item.name }}</span>
                        </Link>
                    </li>
                </template>
            </ul>
        </aside><!-- End Sidebar-->

        <main id="main" class="main mt-5 pt-4">
            <!-- Global loading state -->
            <div v-if="authStore.loading" class="position-absolute w-100 h-100 top-0 start-0 bg-white" style="z-index: 999; opacity: 0.7;">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                </div>
            </div>
            
            <section class="section">
                <slot />
            </section>
        </main>
    </div>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
