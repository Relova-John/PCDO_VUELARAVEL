<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import type { Member } from '@/types/cooperatives';
import { router, usePage } from '@inertiajs/vue3';
import FlashToast from '@/components/FlashToast.vue';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string; info?: string });

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string }
    member: Member,
    files: File[],
}>()

const fullName = computed(() =>
  [props.member.first_name, props.member.middle_initial, props.member.last_name]
    .filter(Boolean)
    .join(' ')
)

function goToEditPage(id: number) {
    router.visit(`/cooperative/${props.cooperative.id}/members/${id}/edit`);
}

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div
                class="bg-white dark:bg-gray-900 shadow-lg rounded-2xl p-8 border border-gray-200 dark:border-gray-700">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        🏢 Cooperative Member Details
                    </h1>
                    
                    <!-- Right-side actions -->
                    <div class="flex items-center gap-3">
                    <!-- ID Badge -->
                        <span class="inline-flex gap-2 px-4 py-2 rounded-full text-sm font-medium
                        bg-indigo-100 text-indigo-700 dark:bg-indigo-800 dark:text-indigo-200">
                            ID: {{ member.id }}
                        </span>
                        <!-- Edit Button -->
                        <Button
                            @click="goToEditPage(member.id)"
                            class="inline-flex  gap-2 px-4 py-2 rounded-lg 
                            bg-gray-200 dark:bg-green-700 
                            text-green-700 dark:text-gray-200 
                            hover:bg-gray-300 dark:hover:bg-green-600 
                            text-sm font-medium transition">
                            Edit
                        </Button>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6 text-gray-800 dark:text-gray-200">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Member Name</p>
                        <p class="text-lg font-semibold">{{ fullName }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Position</p>
                        <p class="text-lg font-semibold">{{ member.position }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Is Representative?</p>
                        <p class="text-lg font-semibold">
                            <span v-if="member.is_representative" class="text-green-600">Yes</span>
                            <span v-else class="text-gray-500">No</span>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Member ID</p>
                        <p class="text-lg font-semibold">{{ member.id }}</p>

                    </div>

                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Uploaded Files</p>
                        <ul v-if="member.files?.length" class="space-y-2">
                            <li
                                v-for="file in member.files"
                                :key="file.id"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-sm"
                            >
                                <div>
                                    <p class="font-medium">{{ file.file_name }}</p>
                                    <p class="text-xs text-gray-500">{{ file.file_type }}</p>
                                </div>
                                <a
                                    :href="`/storage/${file.file_path}`"
                                    target="_blank"
                                    class="text-indigo-600 hover:underline text-sm"
                                >
                                    Download
                                </a>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500 italic">No files uploaded</p>
                    </div>
                </div>
            </div>
        </div>
        <FlashToast
            v-if="flash.success"
            type="success"
            title="Success"
            :message="flash.success"
        />
        <FlashToast
            v-if="flash.error"
            type="error"
            title="Error"
            :message="flash.error"
        />
        <FlashToast
            v-if="flash.info"
            type="info"
            title="Info"
            :message="flash.info"
        />
    </AppLayout>
</template>