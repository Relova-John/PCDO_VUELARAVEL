<script setup lang="ts">
import AppLayout from '@/layouts/app/AppInventoryLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Toaster } from 'vue-sonner';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <Toaster />
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
