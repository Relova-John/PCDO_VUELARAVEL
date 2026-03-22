<script setup lang="ts">
import AppContent from '@/components/AppContent.vue'
import AppShell from '@/components/AppShell.vue'
import type { BreadcrumbItem } from '@/types'

type Props = {
  breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => []
})
</script>

<template>
  <AppShell variant="sidebar">
    <AppContent variant="sidebar" class="overflow-x-hidden">
      <slot />
    </AppContent>

  </AppShell>
</template>