<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import { ref, onMounted } from 'vue'
import PdfViewer from '@/components/PdfViewer.vue'

const props = defineProps<{
    cooperative: {
        id: number
        name: string
        program_id: number
        program_name: string
        program_status: string
        start_date: string
        completed_at: string
    }
}>()

// Breadcrumbs for navigation
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documentation', href: '/documentation' },
    { title: props.cooperative.name, href: '#' },
]

// reactive state for selected file preview
const selectedFile = ref<{ name: string; url: string } | null>(null)

const isMobile = ref(false)
onMounted(() => {
    const uaCheck = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
    const sizeCheck = window.matchMedia('(max-width: 768px)').matches
    isMobile.value = uaCheck || sizeCheck
})
</script>

<template>

    <Head :title="cooperative.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen px-4 py-6">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Cooperative Info Card with Start Date -->
                <div
                    class="bg-gray-200/40 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-md px-6 py-5 mb-6">

                    <!-- Header -->
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <Building2 class="w-10 h-10 text-emerald-600 dark:text-emerald-400" />
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ cooperative.name }}</h2>
                        </div>
                        <!-- ID Badge -->
                        <span
                            class="inline-flex gap-2 px-4 py-2 rounded-full text-sm font-medium bg-indigo-200/40 text-lime-700 dark:bg-lime-800 dark:text-fuchsia-200">
                            ID: {{ cooperative.id }}
                        </span>
                    </div>

                    <!-- Program Info -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 text-gray-700 dark:text-gray-300">
                        <div
                            class="bg-gray-50 dark:bg-gray-800/80 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border border-gray-300 dark:border-gray-700 shadow-sm">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Program</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{
                                cooperative.program_name }}</p>
                        </div>

                        <div
                            class="bg-gray-50 dark:bg-gray-800/80 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border border-gray-300 dark:border-gray-700 shadow-sm">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{
                                cooperative.program_status }}</p>
                        </div>

                        <div
                            class="bg-gray-50 dark:bg-gray-800/80 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border border-gray-300 dark:border-gray-700 shadow-sm">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Start Date</p>
                            <p class="mt-1 text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ cooperative.start_date || 'N/A' }}
                            </p>
                        </div>

                        <div
                            class="bg-gray-50 dark:bg-gray-800/80 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border border-gray-300 dark:border-gray-700 shadow-sm">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Completed At</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">{{
                                cooperative.completed_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Files & Preview Card -->
                <div
                    class="bg-gray-200/40 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">

                    <!-- Header: Dropdown Button -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-end gap-4 mb-4">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    class="inline-flex items-center justify-between gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 text-sm font-medium transition w-full md:w-auto shadow-md">
                                    <span class="flex items-center gap-2">
                                        <FileText class="w-4 h-4" />
                                        View Files
                                    </span>
                                    <ChevronDown class="w-4 h-4" />
                                </button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent side="bottom" align="end"
                                class="w-80 bg-white dark:bg-gray-900 shadow-2xl rounded-lg border border-gray-200 dark:border-gray-700 p-1 mt-1">
                                <!-- Dropdown Items -->
                                <template v-for="(item, i) in [
                                    { name: 'All Files', url: `/documentation/${cooperative.program_id}/allfiles` },
                                    { name: 'Cooperative Details', url: `/documentation/${cooperative.program_id}/details` },
                                    { name: 'Amortization Schedule', url: `/documentation/${cooperative.program_id}/amortization` },
                                    { name: 'Checklist of Documents', url: `/documentation/${cooperative.program_id}/checklist` },
                                    { name: 'Cooperative Members Documents', url: `/documentation/${cooperative.program_id}/member-files` },
                                    { name: 'Delinquent Reports', url: `/documentation/${cooperative.program_id}/delinquent` },
                                    { name: 'Progress Reports', url: `/documentation/${cooperative.program_id}/progress` },
                                    { name: 'Resolved File', url: `/documentation/${cooperative.program_id}/resolved` },
                                ]" :key="i">
                                    <DropdownMenuItem asChild>
                                        <button @click="selectedFile = { name: item.name, url: item.url }"
                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                            <FileText class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                            {{ item.name }}
                                        </button>
                                    </DropdownMenuItem>
                                </template>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- File Preview Section -->
                    <div
                        class="rounded-xl border border-gray-300/50 dark:border-gray-700 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm shadow-inner p-6 transition">
                        <template v-if="selectedFile && selectedFile.url">
                            <h3
                                class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                                <FileText class="w-5 h-5 text-indigo-500" />
                                {{ selectedFile.name }} Preview
                            </h3>

                            <PdfViewer v-if="isMobile" type="documentation" :url="selectedFile.url" />

                            <iframe v-else :src="selectedFile.url"
                                class="w-full h-[80vh] rounded-lg border border-gray-300 dark:border-gray-700 shadow-sm"></iframe>
                        </template>

                        <p v-else
                            class="text-gray-500 dark:text-gray-400 text-sm flex items-center justify-center gap-2 py-24 italic">
                            <FileText class="w-4 h-4 text-indigo-400" />
                            Select a file to preview it here.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
