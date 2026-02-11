<script setup lang="ts">
import AppContent from '@/components/AppContent.vue'
import AppShell from '@/components/AppShell.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppSidebarHeader from '@/components/AppSidebarHeader.vue'
import type { BreadcrumbItemType } from '@/types'

interface Props {
    breadcrumbs?: BreadcrumbItemType[]
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />

        <AppContent variant="sidebar" class="overflow-x-hidden">
            <!-- Header wrapper with full-width border -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-4 py-2">
                    <!-- Scroll only breadcrumbs on mobile -->
                    <div class="sm:overflow-visible overflow-x-auto touch-pan-x">
                        <div class="inline-block min-w-max sm:w-full pr-4 sm:pr-0">
                            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                        </div>
                    </div>
                </div>
            </div>

            <slot />
        </AppContent>
    </AppShell>
</template>
