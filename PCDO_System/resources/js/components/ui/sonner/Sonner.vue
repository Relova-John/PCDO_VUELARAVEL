<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { Toaster as Sonner } from "vue-sonner"

const props = withDefaults(defineProps<ToasterProps>(), {
  position: "top-right",
  visibleToasts: 3,
  duration: 5000,
  closeButton: true,
  pauseOnHover: true,
})


</script>

<template>
  <Sonner class="toaster group" v-bind="props" :toast-options="{
    classes: {
      toast: 'group toast whitespace-pre-line group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg',
      description: 'group-[.toast]:text-muted-foreground whitespace-pre-line',
      actionButton:
        'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
      cancelButton:
        'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
    },
  }" />
</template>
