<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { House, Users, BookUser} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from './CoopMenuContent.vue';
import { useSyncStatus } from '@/composables/useSyncStatus';
import { computed } from 'vue'

const page = usePage();
const user = page.props.auth.user;
const { isMobile, state } = useSidebar();

const { isOnline } = useSyncStatus()

const statusColor = computed(() => {
    return isOnline.value ? 'bg-green-500' : 'bg-red-500'
})


const mainNavItems: NavItem[] = [
    {
        title: 'Home',
        href: '/coop/dashboard',
        icon: House,
    },

    {
        title: 'Details',
        href: '/coop/details',
        icon: BookUser,
    },

    {
        title: 'Members',
        href: '/coop/members',
        icon: Users,
    }

];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="'/coop/dashboard'" class="flex items-center gap-x-2">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton size="lg"
                                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                                <div class="flex items-center gap-2 text-sm">
                                    <div :class="[
                                        'w-2.5 h-2.5 rounded-full transition-all duration-300',
                                        statusColor
                                    ]" />
                                </div>
                                <UserInfo :user="user" />
                                <ChevronsUpDown class="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg" :side="isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                                ? 'left'
                                : 'bottom'
                            " align="end" :side-offset="4">
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
