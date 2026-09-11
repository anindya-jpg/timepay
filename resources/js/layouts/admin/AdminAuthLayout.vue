<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="theme-admin flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                        <AppLogoIcon class="size-7 fill-current" />
                    </div>
                    <div class="space-y-1 text-center">
                        <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
                        <p class="text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>