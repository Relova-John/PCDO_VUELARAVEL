<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const flash = computed(() => page.props.flash as {
    success?: string;
    error?: string;
    info?: string;
});

const show = ref(false);
const message = ref("");
const type = ref<"success" | "error" | "info">("success");

onMounted(() => {
    if (flash.value.success) {
        message.value = flash.value.success;
        type.value = "success";
        show.value = true;
    } else if (flash.value.error) {
        message.value = flash.value.error;
        type.value = "error";
        show.value = true;
    } else if (flash.value.info) {
        message.value = flash.value.info;
        type.value = "info";
        show.value = true;
    }

    if (show.value) {
        setTimeout(() => (show.value = false), 4000); // auto dismiss
    }
});

const colors: Record<typeof type.value, string> = {
    success: "bg-green-500",
    error: "bg-red-500",
    info: "bg-blue-500",
};
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-300"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
        v-if="show"
        :class="[
            'fixed bottom-4 right-4 text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2',
            colors[type]
        ]"
        >
        <span>{{ message }}</span>
        <button @click="show = false" class="ml-2 font-bold">×</button>
        </div>
    </transition>
</template>