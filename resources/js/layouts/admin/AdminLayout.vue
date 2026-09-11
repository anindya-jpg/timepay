
<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AdminSidebar from '@/components/admin/AdminSidebar.vue';
import AdminSidebarHeader from '@/components/admin/AdminSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { router } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount } from 'vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

/*
|--------------------------------------------------------------------------
| ADMIN SESSION CONTROL
|--------------------------------------------------------------------------
|
| 1. Back browser ke /admin/login → logout
| 2. Tidak ada aktivitas selama 30 detik → logout
| 3. Tab/minimize → diberikan waktu 30 detik
| 4. Jika halaman kembali setelah 30 detik → logout
|
*/

const IDLE_TIMEOUT = 30 * 1000; // 30 detik

let idleTimer: ReturnType<typeof setTimeout> | null = null;
let logoutProcessing = false;

// Menyimpan waktu ketika halaman mulai tidak terlihat.
let hiddenAt: number | null = null;

/*
|--------------------------------------------------------------------------
| Logout Admin
|--------------------------------------------------------------------------
*/

const logoutAdmin = () => {
    // Jangan melakukan logout berkali-kali.
    if (logoutProcessing) {
        return;
    }

    logoutProcessing = true;

    router.post(
        '/admin/logout',
        {},
        {
            preserveState: false,
            preserveScroll: false,

            onFinish: () => {
                window.location.replace('/admin/login');
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Idle Timer
|--------------------------------------------------------------------------
*/

const resetIdleTimer = () => {
    // Kalau sedang logout, jangan buat timer baru.
    if (logoutProcessing) {
        return;
    }

    if (idleTimer) {
        clearTimeout(idleTimer);
    }

    idleTimer = setTimeout(() => {
        logoutAdmin();
    }, IDLE_TIMEOUT);
};

/*
|--------------------------------------------------------------------------
| Deteksi Aktivitas Admin
|--------------------------------------------------------------------------
*/

const handleActivity = () => {
    resetIdleTimer();
};

/*
|--------------------------------------------------------------------------
| Deteksi Browser Back
|--------------------------------------------------------------------------
*/

const handleBrowserBack = () => {
    const currentPath = window.location.pathname;

    // Hanya logout ketika Back menuju halaman login admin.
    if (currentPath !== '/admin/login') {
        return;
    }

    logoutAdmin();
};

/*
|--------------------------------------------------------------------------
| Deteksi Halaman Tidak Terlihat
|--------------------------------------------------------------------------
|
| Ketika halaman menjadi hidden:
|
| - pindah tab
| - browser diminimize
| - pindah aplikasi
| - layar dikunci
|
| Kita TIDAK langsung logout.
|
| Waktu hidden 30detik dicatat dan akan diperiksa ketika halaman kembali terlihat.
|
*/

const handleVisibilityChange = () => {
    if (document.visibilityState === 'hidden') {
        hiddenAt = Date.now();

        return;
    }

    if (
        document.visibilityState === 'visible' &&
        hiddenAt !== null
    ) {
        const hiddenDuration = Date.now() - hiddenAt;

        /*
        |--------------------------------------------------------------------------
        | Jika halaman tidak terlihat selama 30 detik atau lebih,
        | logout ketika halaman kembali.
        |--------------------------------------------------------------------------
        */

        if (hiddenDuration >= IDLE_TIMEOUT) {
            logoutAdmin();
        }

        hiddenAt = null;

        // Mulai ulang timer aktivitas.
        resetIdleTimer();
    }
};

/*
|--------------------------------------------------------------------------
| Mount
|--------------------------------------------------------------------------
*/

onMounted(() => {
    // Timer pertama dimulai ketika admin masuk halaman.
    resetIdleTimer();

    // Aktivitas user.
    window.addEventListener('mousemove', handleActivity);
    window.addEventListener('mousedown', handleActivity);
    window.addEventListener('keydown', handleActivity);
    window.addEventListener('scroll', handleActivity);
    window.addEventListener('touchstart', handleActivity);

    // Back / Forward browser.
    window.addEventListener('popstate', handleBrowserBack);

    // Browser/tab menjadi tidak terlihat.
    document.addEventListener(
        'visibilitychange',
        handleVisibilityChange,
    );
});

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

onBeforeUnmount(() => {
    if (idleTimer) {
        clearTimeout(idleTimer);
        idleTimer = null;
    }

    window.removeEventListener(
        'mousemove',
        handleActivity,
    );

    window.removeEventListener(
        'mousedown',
        handleActivity,
    );

    window.removeEventListener(
        'keydown',
        handleActivity,
    );

    window.removeEventListener(
        'scroll',
        handleActivity,
    );

    window.removeEventListener(
        'touchstart',
        handleActivity,
    );

    window.removeEventListener(
        'popstate',
        handleBrowserBack,
    );

    document.removeEventListener(
        'visibilitychange',
        handleVisibilityChange,
    );
});
</script>

<template>
    <div class="theme-admin">
        <AppShell variant="sidebar">
            <AdminSidebar />

            <AppContent
                variant="sidebar"
                class="overflow-x-hidden bg-background"
            >
                <AdminSidebarHeader :breadcrumbs="breadcrumbs" />

                <div class="p-4 md:p-6">
                    <slot />
                </div>
            </AppContent>

            <Toaster />
        </AppShell>
    </div>
</template>

