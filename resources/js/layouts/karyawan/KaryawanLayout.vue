
<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import KaryawanSidebar from '@/components/karyawan/KaryawanSidebar.vue';
import KaryawanSidebarHeader from '@/components/karyawan/KaryawanSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <div class="theme-karyawan min-h-screen">
        <AppShell variant="sidebar">
            <KaryawanSidebar />

            <AppContent
                variant="sidebar"
                class="min-w-0 overflow-x-hidden bg-background"
            >
                <KaryawanSidebarHeader
                    :breadcrumbs="breadcrumbs"
                />

                <main class="w-full p-3 sm:p-4 md:p-5 lg:p-6">
                    <div class="mx-auto w-full max-w-[1600px]">
                        <slot />
                    </div>
                </main>
            </AppContent>

            <Toaster />
        </AppShell>
    </div>
</template>

