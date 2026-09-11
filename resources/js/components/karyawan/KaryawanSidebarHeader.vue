<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { User as UserIcon } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

type Karyawan = { nama?: string; jabatan?: { nama_jabatan?: string }; foto?: string | null };

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const karyawan = page.props.auth?.karyawan as Karyawan | null;
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-right leading-tight">
                <p class="text-sm font-medium text-foreground">{{ karyawan?.nama ?? 'Karyawan' }}</p>
                <p class="text-xs text-muted-foreground">{{ karyawan?.jabatan?.nama_jabatan ?? '-' }}</p>
            </div>
            <div class="flex size-9 items-center justify-center overflow-hidden rounded-full bg-muted">
                <img
                    v-if="karyawan?.foto"
                    :src="karyawan.foto.startsWith('http') ? karyawan.foto : `/storage/${karyawan.foto}`"
                    :alt="karyawan?.nama"
                    class="size-full object-cover"
                />
                <UserIcon v-else class="size-4 text-muted-foreground" />
            </div>
        </div>
    </header>
</template>