<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { UserCog, LayoutGrid } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage<{
    auth?: {
        user?: {
            roles?: {
                id: number
                name: string
            }[]
        }
    }
}>()

const userRoles = computed(() => page.props.auth?.user?.roles ?? [])

const hasAdminAccess = computed(() =>
    userRoles.value.some(role => ['admin', 'superadmin'].includes(role.name))
)

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
    ]

    if (hasAdminAccess.value) {
        items.push({
            title: 'Access Control',
            href: '/access-control',
            icon: UserCog,
        })
    }

    return items
})

const footerNavItems: NavItem[] = []
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="''">
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>