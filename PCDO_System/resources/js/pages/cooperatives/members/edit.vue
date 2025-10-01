<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { useForm, router } from '@inertiajs/vue3';
import type { Member } from '@/types/cooperatives';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string }
    member: Member
}>()

// Form state
const form = useForm({
    first_name: props.member.first_name || '',
    middle_initial: props.member.middle_initial || '',
    last_name: props.member.last_name || '',
    position: props.member.position || '',
    is_representative: props.member.is_representative ? true : false,
    files: [] as File[]
})

// Existing files list (from DB)
const existingFiles = props.member.files || []

function handleSubmit() {
    form.post(`/cooperative/${props.cooperative.id}/members/${props.member.id}`, {
        onSuccess: () => {
            alert('Member details updated successfully!');
        },
        onError: (errors) => {
            console.error('Failed to update member details:', errors);
        }
    });
}

function removeExistingFile(fileId: number) {
    if (confirm('Are you sure you want to remove this file?')) {
        router.delete(`/cooperative/${props.cooperative.id}/members/${props.member.id}/files/${fileId}`, {
            onSuccess: () => {
                alert('File removed successfully');
            },
            onError: (errors) => {
                console.error('Failed to remove file:', errors);
            }
        })
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                    Edit Member Details
                </h1>

                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- First Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            First Name
                        </label>
                        <input v-model="form.first_name" type="text"
                               class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3
                                      focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>

                    <!-- Middle Initial -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Middle Initial
                        </label>
                        <input v-model="form.middle_initial" type="text" maxlength="1"
                               class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3
                                      focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Last Name
                        </label>
                        <input v-model="form.last_name" type="text"
                               class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3
                                      focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>

                    <!-- Position -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Position
                        </label>
                        <input v-model="form.position" type="text"
                               class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3
                                      focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>

                    <!-- Representative -->
                    <div class="flex items-center gap-3">
                        <input id="is_representative" v-model="form.is_representative" type="checkbox"
                               class="h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                        <label for="is_representative" class="text-sm text-gray-700 dark:text-gray-300">
                            Is Representative?
                        </label>
                    </div>

                    <!-- Existing Files -->
                    <div v-if="existingFiles.length">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Existing Files</p>
                        <ul class="space-y-2">
                            <li v-for="file in existingFiles" :key="file.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-700 p-2 rounded-lg">
                                <span>{{ file.file_name }}</span>
                                <button type="button"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                        @click="removeExistingFile(file.id)">
                                    Remove
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Upload New Files -->
                    <div>
                        <label for="files" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Upload New Files
                        </label>
                        <input
                            id="files"
                            type="file"
                            multiple
                            @change="form.files = Array.from(($event.target as HTMLInputElement).files || [])"
                            class="w-full text-sm text-gray-500 dark:text-gray-300
                                   file:mr-4 file:py-2 file:px-4
                                   file:rounded-full file:border-0
                                   file:text-sm file:font-semibold
                                   file:bg-indigo-50 file:text-indigo-700
                                   hover:file:bg-indigo-100"
                        />
                    </div>

                    <!-- Submit -->
                    <div class="pt-6 flex gap-4">
                        <button type="submit"
                                class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow
                                       hover:bg-indigo-700 transition">
                            Update Member
                        </button>
                        <button type="button"
                                @click="$inertia.visit(`/cooperative/${props.cooperative.id}/members/${props.member.id}`)"
                                class="px-6 py-3 bg-gray-200 text-gray-800 font-semibold rounded-xl shadow
                                       hover:bg-gray-300 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>