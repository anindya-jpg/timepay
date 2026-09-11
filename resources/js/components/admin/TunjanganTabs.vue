<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    active: 'daftar' | 'nominal';
}>();
</script>

<template>
    <div class="inline-flex rounded-lg border border-border bg-muted p-1">
        <Link
            href="/admin/tunjangan"
            class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
            :class="active === 'daftar' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
        >
            Daftar Tunjangan
        </Link>
        <Link
            href="/admin/tunjangan-nominal"
            class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
            :class="active === 'nominal' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
        >
            Atur Nominal per Jabatan
        </Link>
    </div>
</template>