<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { BreadcrumbItem } from '@/types'
import { toast } from 'vue-sonner'

interface Cooperative {
    id: number
    name: string
}

interface CoopProgram {
    id: number
    cooperative: Cooperative
}

interface Program {
    id: number
    name: string
}

const props = defineProps<{
    program: Program
    coopPrograms: CoopProgram[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programs', href: '/programs' },
    { title: props.program.name, href: `/programs/${props.program.id}` },
    { title: 'Add Progress Report', href: '#' },
]

const form = useForm({
    coop_program_id: '',
    title: '',
    description: '',
    file: [] as File[],
})

const previews = ref<string[]>([])

function handleFiles(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files) {
    const newFiles = Array.from(target.files)

    //  Append new files to existing ones instead of replacing
    form.file = [...form.file, ...newFiles]
    previews.value.push(...newFiles.map(file => URL.createObjectURL(file)))

    //  Reset input value so re-selecting same files will trigger change again
    target.value = ''
  }
}

function removeFile(index: number) {
    form.file.splice(index, 1)
    previews.value.splice(index, 1)
}

function submitForm() {
    form.post(`/programs/${props.program.id}/progress`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Progress report uploaded successfully!')
            form.reset()
            previews.value = []
        },
    })
}
</script>

<template>

    <Head :title="`Add Progress Report - ${props.program.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7x7 p-6">
                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-md space-y-6">
                    <h2 class="text-3xl font-semibold text-gray-800 dark:text-gray-200 text-center">
                        Upload Progress Report
                    </h2>

                    <!-- Cooperative Program -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Select Cooperative Program
                        </label>
                        <select v-model="form.coop_program_id" required
                            class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Cooperative --</option>
                            <option v-for="coop in coopPrograms" :key="coop.id" :value="coop.id">
                                {{ coop.cooperative.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Progress Title
                        </label>
                        <input v-model="form.title" type="text" placeholder="Enter progress title" required
                            class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description
                        </label>
                        <textarea v-model="form.description" rows="3" placeholder="Add a short description..."
                            class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <!-- Image Previews (show only when files are selected) -->
                        <div v-if="previews.length" class="mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Selected Images: {{ previews.length }}
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                <div v-for="(src, index) in previews" :key="index"
                                    class="relative group rounded-lg overflow-hidden shadow-md">
                                    <img :src="src"
                                        class="object-cover w-full h-32 rounded-lg border border-gray-200 dark:border-gray-700" />
                                    <button @click="removeFile(index)" type="button"
                                        class="absolute top-1 right-1 bg-red-500 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Upload Images
                        </label>

                        <label
                            class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center">
                                <p class="text-xs text-gray-500">PNG, JPG, or JPEG (multiple allowed)</p>
                            </div>
                            <input id="file" type="file" multiple accept="image/*" class="hidden"
                                @change="handleFiles" />
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button @click.prevent="submitForm"
                            class="bg-indigo-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors"
                            :disabled="form.processing">
                            <span v-if="!form.processing">Upload Progress</span>
                            <span v-else>Uploading...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
