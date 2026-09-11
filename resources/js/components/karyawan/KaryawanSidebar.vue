<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    LogIn,
    LogOut as LogOutIcon,
    History,
    Receipt,
    ClipboardPlus,
    User as UserIcon,
} from '@lucide/vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { useCurrentUrl } from '@/composables/useCurrentUrl';

type Karyawan = {
    nama?: string;
    jabatan?: {
        nama_jabatan?: string;
    };
    foto?: string | null;
};

const page = usePage();

const karyawan = page.props.auth?.karyawan as Karyawan | null;

const { isCurrentUrl } = useCurrentUrl();

const mainNavItems = [
    {
        title: 'Dashboard',
        href: '/karyawan/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Absen Masuk',
        href: '/karyawan/absen-masuk',
        icon: LogIn,
    },
    {
        title: 'Absen Pulang',
        href: '/karyawan/absen-pulang',
        icon: LogOutIcon,
    },
    {
        title: 'Izin / Sakit',
        href: '/karyawan/izin-sakit',
        icon: ClipboardPlus,
    },
    {
        title: 'Riwayat Absensi',
        href: '/karyawan/riwayat-absensi',
        icon: History,
    },
    {
        title: 'Slip Gaji',
        href: '/karyawan/slip-gaji',
        icon: Receipt,
    },
];

function logout() {
    router.post('/karyawan/logout');
}

function fotoUrl(foto: string | null | undefined) {
    if (!foto) {
        return '';
    }

    return foto.startsWith('http')
        ? foto
        : `/storage/${foto}`;
}
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="theme-karyawan"
    >
        <!-- ==================== HEADER ==================== -->
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                    >
                        <Link href="/karyawan/dashboard">
                            <!-- LOGO -->
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-white text-primary"
                            >
                                <div
                                    class="h-4 w-4 rounded-sm bg-primary"
                                />
                            </div>

                            <!-- BRAND -->
                            <div
                                class="ml-1 grid min-w-0 flex-1 text-left text-sm"
                            >
                                <span
                                    class="truncate leading-tight font-bold"
                                >
                                    ABSENSI & GAJI
                                </span>

                                <span
                                    class="truncate text-xs font-medium text-accent"
                                >
                                    PT.XXXXX
                                </span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- ==================== MENU ==================== -->
        <SidebarContent>
            <SidebarMenu class="px-2">
                <SidebarMenuItem
                    v-for="item in mainNavItems"
                    :key="item.title"
                >
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(item.href)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />

                            <span>
                                {{ item.title }}
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <!-- ==================== FOOTER ==================== -->
        <SidebarFooter>
            <SidebarMenu>
                <!-- PROFILE -->
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        :is-active="
                            isCurrentUrl('/karyawan/profil')
                        "
                    >
                        <Link href="/karyawan/profil">
                            <!-- FOTO PROFIL -->
                            <div
                                class="relative h-8 w-8 shrink-0 overflow-hidden rounded-full bg-sidebar-accent"
                            >
                                <img
                                    v-if="karyawan?.foto"
                                    :src="fotoUrl(karyawan.foto)"
                                    :alt="
                                        karyawan?.nama ??
                                        'Foto profil'
                                    "
                                    class="absolute inset-0 h-8 w-8 max-h-8 max-w-8 object-cover"
                                />

                                <UserIcon
                                    v-else
                                    class="absolute left-1/2 top-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2"
                                />
                            </div>

                            <!-- INFO KARYAWAN -->
                            <div
                                class="ml-1 grid min-w-0 flex-1 text-left text-sm"
                            >
                                <span
                                    class="truncate leading-tight font-medium"
                                >
                                    {{
                                        karyawan?.nama ??
                                        'Karyawan'
                                    }}
                                </span>

                                <span
                                    class="truncate text-xs text-sidebar-foreground/60"
                                >
                                    Lihat Profil
                                </span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- LOGOUT -->
                <SidebarMenuItem>
                    <SidebarMenuButton
                        tooltip="Logout"
                        class="text-red-400 hover:text-red-300"
                        @click="logout"
                    >
                        <LogOutIcon />

                        <span>
                            Logout / Keluar
                        </span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>