<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Users,
    Briefcase,
    Wallet,
    Clock,
    MapPin,
    CalendarOff,
    Timer,
    Percent,
    CalendarCheck,
    CheckCircle2,
    Banknote,
    UserCog,
    LogOut,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const page = usePage();
const admin = page.props.auth?.admin as { nama?: string; username?: string } | null;

const { isCurrentUrl } = useCurrentUrl();

const mainNavItems = [
    { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutGrid },
    { title: 'Data Karyawan', href: '/admin/karyawan', icon: Users },
    { title: 'Data Jabatan', href: '/admin/jabatan', icon: Briefcase },
    { title: 'Data Tunjangan', href: '/admin/tunjangan', icon: Wallet },
    { title: 'Pengaturan Jam Kerja', href: '/admin/pengaturan-jam-kerja', icon: Clock },
    { title: 'Pengaturan Lokasi', href: '/admin/pengaturan-lokasi', icon: MapPin },
    { title: 'Hari Libur', href: '/admin/hari-libur', icon: CalendarOff },
    { title: 'Tarif Lembur', href: '/admin/tarif-lembur', icon: Timer },
    { title: 'Aturan Potongan', href: '/admin/potongan-rule', icon: Percent },
    { title: 'Data Absensi', href: '/admin/absensi', icon: CalendarCheck },
    { title: 'Approval Lembur', href: '/admin/approval-lembur', icon: CheckCircle2 },
    { title: 'Penggajian', href: '/admin/penggajian', icon: Banknote },
    { title: 'Profil Admin', href: '/admin/admin-akun', icon: UserCog },
];

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="theme-admin">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/admin/dashboard">
                            <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                                <AppLogoIcon class="size-5 fill-current" />
                            </div>
                            <div class="ml-1 grid flex-1 text-left text-sm">
                                <span class="truncate leading-tight font-semibold">TimePay</span>
                                <span v-if="admin?.nama" class="truncate text-xs text-sidebar-foreground/70">
                                    Halo, {{ admin.nama }}
                                </span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Menu</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
                        <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href)" :tooltip="item.title">
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton tooltip="Logout" @click="logout">
                        <LogOut />
                        <span>Logout</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>