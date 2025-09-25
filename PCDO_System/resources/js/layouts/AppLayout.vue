<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { ToastProvider, ToastViewport } from "reka-ui";

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <ToastProvider>
            <slot />

            <ToastViewport
            class="fixed bottom-4 right-4 flex flex-col gap-2 w-80 z-50 outline-none"
            />
        </ToastProvider>
    </AppLayout>
</template>
