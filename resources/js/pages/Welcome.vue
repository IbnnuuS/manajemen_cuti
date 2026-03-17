<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />
    <div class="relative flex items-top justify-center min-h-screen bg-gray-100 sm:items-center py-4 sm:pt-0">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-center pt-8 sm:justify-start sm:pt-0">
                <h1 class="text-4xl font-extrabold text-indigo-600 font-sans tracking-tight block w-full text-center sm:text-left">
                    LeaveRequest<span class="text-gray-900">Hub</span>
                </h1>
            </div>

            <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-2xl">
                <div class="p-6 sm:p-20 bg-white border-b border-gray-200 text-center">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6 font-sans">Selamat Datang di Portal Karyawan</h2>
                    <p class="text-gray-500 mb-8 max-w-xl mx-auto text-lg">
                        Kelola cuti tahunan dan cuti sakit Anda dengan mudah. Persetujuan diarahkan secara instan.
                    </p>
                    <Link href="/login" class="inline-flex justify-center items-center rounded-xl bg-primary px-8 py-4 text-lg font-semibold text-white shadow-md hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-all duration-200">
                        Masuk ke Portal
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
