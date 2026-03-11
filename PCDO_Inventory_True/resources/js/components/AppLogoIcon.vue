<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div class="w-25 h-25 flex items-center justify-center rounded-full bg-white overflow-hidden">
    <img 
      src="/img/pcdo_logo.png" 
      alt="App Logo" 
      class="w-full h-full object-contain"
    />
  </div>
</template>
