<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import type { Member } from '@/types/cooperatives';
import { router, usePage } from '@inertiajs/vue3';
import FlashToast from '@/components/FlashToast.vue';
import { computed, ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string; info?: string });
const showFileModal = ref(false);
const selectedFile = ref<any | null>(null);

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string }
    member: Member,
}>()

const fullName = computed(() =>
    [props.member.first_name, props.member.middle_initial, props.member.last_name]
    .filter(Boolean)
    .join(' ')
)

function goToEditPage(id: number) {
    router.visit(`/cooperatives/${props.cooperative.id}/members/${id}/edit`);
}

function openFileModal(file: any) {
    selectedFile.value = file;
    showFileModal.value = true;
}

function closeFileModal() {
    selectedFile.value = null;
    showFileModal.value = false;
}

function downloadFile(file: any) {
    window.open(
        `/cooperatives/${props.cooperative.id}/members/${props.member.id}/files/${file.id}/download`,
        '_blank'
    );
    closeFileModal();
}

function deleteFile(file: any) {
    if (!confirm(`Are you sure you want to delete "${file.file_name}"?`)) return;

    router.delete(
        `/cooperatives/${props.cooperative.id}/members/${props.member.id}/files/${file.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                closeFileModal();
            },
        }
    );
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
                                <Button
                                    type="button"
                                    @click="openFileModal(file)"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg 
                                    bg-blue-600 text-white 
                                    hover:bg-blue-700 
                                    text-sm font-medium transition"
                                >
                                    View
                                </Button>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500 italic">No files uploaded</p>
                    </div>
                </div>
            </div>
            <!-- File Preview Modal -->
            <div
                v-if="showFileModal && selectedFile"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            >
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-3xl w-full p-6 relative">
                    <!-- Close button -->
                    <button
                        @click="closeFileModal"
                        class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                    >
                        ✕
                    </button>

                    <h2 class="text-xl font-bold mb-4">{{ selectedFile.file_name }}</h2>

                    <!-- Preview -->
                    <div class="mb-6">
                        <template v-if="selectedFile.file_type.includes('image')">
                            <img :src="selectedFile.url" class="max-h-96 mx-auto rounded-lg shadow" />
                        </template>
                        <template v-else-if="selectedFile.file_type.includes('pdf')">
                            <iframe
                                :src="selectedFile.url"
                                class="w-full h-96 border rounded-lg"
                            ></iframe>
                        </template>
                        <template v-else>
                            <p class="text-gray-500">Preview not available. You can download the file.</p>
                        </template>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Button
                            type="button"
                            @click="downloadFile(selectedFile)"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            Download
                        </Button>
                        <Button
                            type="button"
                            @click="deleteFile(selectedFile)"
                            class="bg-red-600 hover:bg-red-700 text-white"
                        >
                            Delete
                        </Button>
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