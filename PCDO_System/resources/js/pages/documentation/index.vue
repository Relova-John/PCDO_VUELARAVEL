<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import { ref, onMounted, watch, reactive } from 'vue'
import PdfViewer from '@/components/PdfViewer.vue'
import { Cities } from '@/types/cooperatives'
import SelectSearch from '@/components/SelectSearch.vue'
import { toast } from 'vue-sonner'

const showFilters = ref(true)
const showFileModal = ref(false)
const pdfUrl = ref('/documentation/downloads')
const closeFileModal = () => {
    showFileModal.value = false
    pdfLoading.value = true
    pdfFailed.value = false
}

const pdfLoading = ref(true)
const pdfFailed = ref(false)

const props = defineProps<{
    years: Array<{
        year: string
        cooperatives: Array<{
            id: number
            name: string
            program_name: string
            completed_at: string
        }>
    }>
    programs: Array<{
        id: number
        name: string
    }>
    cities: Cities[],
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documentation', href: '#' },
]

const selectedCity = ref<string>('all')
const selectedProgram = ref('all')
const startDate = ref(new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().slice(0, 10))
const endDate = ref(new Date().toISOString().slice(0, 10))
const openYear = ref<string | null>(null)
const selectedFileType = ref(0)
const updateReport = () => {
    pdfLoading.value = true
    pdfUrl.value = `/documentation/downloads?program=${selectedProgram.value}&start_date=${startDate.value}&end_date=${endDate.value}&municipality=${selectedCity.value}&file_type=${selectedFileType.value}`
}

const toggleYear = (year: string) => {
    openYear.value = openYear.value === year ? null : year
}
const today = new Date().toISOString().split('T')[0]

const openState = reactive({
    city_code: false,
})

const isMobile = ref(false)

onMounted(() => {
    const uaCheck = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
    const sizeCheck = window.matchMedia('(max-width: 768px)').matches
    isMobile.value = uaCheck || sizeCheck

    updateReport()
})
</script>

<template>

    <Head title="Documentation" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen px-4 sm:px-6 py-8">
            <div class="mb-8 text-center">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">
                    Documentation by Year
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-2 mx-auto max-w-md">
                    Browse completed cooperative documentation organized by year.
                </p>
            </div>

            <div class="flex justify-end mb-4">
                <button @click="showFileModal = true"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg shadow hover:bg-emerald-700 transition">
                    Generate Report
                </button>
            </div>

            <div v-if="!props.years || props.years.length === 0" class="flex items-center justify-center py-32">
                <p class="text-gray-500 dark:text-gray-400 text-lg text-center">
                    No documentation data available.
                </p>
            </div>

            <div v-else class="max-w-6xl mx-auto space-y-4">
                <div v-for="yearGroup in props.years" :key="yearGroup.year"
                    class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                    <button
                        class="w-full flex items-center justify-between px-5 sm:px-6 py-3 bg-indigo-600 text-white font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition text-base sm:text-lg"
                        @click="toggleYear(yearGroup.year)">
                        <span>{{ yearGroup.year }}</span>
                        <ChevronDown class="w-5 h-5 transform transition-transform duration-200"
                            :class="{ 'rotate-180': openYear === yearGroup.year }" />
                    </button>

                    <div v-show="openYear === yearGroup.year" class="transition-all duration-300">
                        <div v-if="yearGroup.cooperatives.length > 0">
                            <div class="hidden sm:block">
                                <Table>
                                    <TableCaption>
                                        <span class="text-gray-600 dark:text-gray-400 text-sm">
                                            Completed cooperatives for {{ yearGroup.year }}
                                        </span>
                                    </TableCaption>

                                    <TableHeader>
                                        <TableRow class="bg-gray-100 dark:bg-gray-700">
                                            <TableHead class="text-gray-700 dark:text-gray-200">Name</TableHead>
                                            <TableHead class="text-gray-700 dark:text-gray-200">Program</TableHead>
                                            <TableHead class="text-gray-700 dark:text-gray-200">Completed At</TableHead>
                                        </TableRow>
                                    </TableHeader>

                                    <TableBody>
                                        <TableRow v-for="coop in yearGroup.cooperatives" :key="coop.id"
                                            @click="$inertia.get(`/documentation/cooperative/${coop.id}`)"
                                            class="cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                                            <TableCell class="font-medium text-gray-800 dark:text-gray-100">{{ coop.name
                                                }}</TableCell>
                                            <TableCell class="text-gray-700 dark:text-gray-300">{{ coop.program_name }}
                                            </TableCell>
                                            <TableCell class="text-gray-600 dark:text-gray-400">{{ coop.completed_at }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                                <div v-for="coop in yearGroup.cooperatives" :key="coop.id"
                                    @click="$inertia.get(`/documentation/cooperative/${coop.id}`)"
                                    class="p-4 flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-3 active:bg-indigo-50 dark:active:bg-gray-700 transition">
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ coop.name }}</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ coop.program_name
                                        }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ coop.completed_at
                                        }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-5 text-gray-500 dark:text-gray-400 text-center text-sm">
                            No cooperatives completed yet for this year.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Download Modal -->
        <Transition name="fade">
            <div v-if="showFileModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 sm:p-0"
                @click.self="closeFileModal">
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg w-full max-w-4xl max-h-[90vh] overflow-hidden sm:m-0 m-auto">
                    <header
                        class="flex flex-wrap justify-between items-center border-b border-gray-200 dark:border-gray-700 p-4 gap-4">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate">
                            Documentation Report
                        </h2>
                        <button v-if="isMobile" @click="closeFileModal"
                            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl leading-none">
                            ✕
                        </button>
                    </header>

                    <!-- Clickable Filter Header -->
                    <div @click="showFilters = !showFilters"
                        class="border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter Options</span>
                            <svg class="w-4 h-4 text-gray-500" :class="{ 'rotate-180': showFilters }" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

<!-- Filters Section (Collapsible) -->
<div v-if="showFilters && !isMobile"
    class="border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
    <div class="space-y-4">
        <!-- First Row - Program and File Type -->
        <div class="flex flex-col sm:flex-row gap-4">
            <!-- Program Selector -->
            <select v-model="selectedProgram" @change="updateReport" 
                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="all">All Programs</option>
                <option v-for="program in props.programs" :key="program.id" :value="program.id">
                    {{ program.name }}
                </option>
            </select>

            <!-- File Type Selector -->
            <select v-model="selectedFileType" @change="updateReport" 
                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="0">All Files</option>
                <option value="1">Cooperative Details</option>
                <option value="2">Amortization Schedule</option>
                <option value="3">Checklist of Documents</option>
                <option value="4">Cooperative Members Documents</option>
                <option value="5">Delinquent Reports</option>
                <option value="6">Progress Reports</option>
                <option value="7">Resolved File'</option>
                <option value="8">Memorandum of Agreement</option>
            </select>
        </div>

        <!-- Second Row - Municipality and Date Range -->
        <div class="flex flex-col sm:flex-row gap-4 items-center">
            <!-- Municipality/Location Selector -->
            <div class="flex-1 w-full">
                <SelectSearch id="city" :items="props.cities" itemLabelKey="name" itemKeyProp="code"
                    v-model="selectedCity" :open="openState.city_code"
                    @update:open="val => openState.city_code = val" placeholder="Search Municipality"
                    class="w-full !p-2 !text-sm !rounded-md !border-gray-300 dark:!border-gray-700" />
            </div>

            <!-- Date Range -->
            <div class="flex-1 w-full flex flex-col sm:flex-row gap-2">
                <input type="date" v-model="startDate" :max="today" @change="updateReport"
                    placeholder="Start Date" 
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                    text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                <input type="date" v-model="endDate" :max="today" @change="updateReport"
                    placeholder="End Date" 
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                    text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
            </div>
        </div>
    </div>
</div>
<!-- Filters Section (Collapsible) - MOBILE VIEW -->
<div v-if="showFilters && isMobile"
    class="border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
    <div class="space-y-4">
        <!-- Program Selector - Full Width -->
        <div class="w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Program</label>
            <select v-model="selectedProgram" @change="updateReport" 
                class="w-full px-3 py-3 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                text-gray-800 dark:text-gray-100 text-base focus:ring-2 focus:ring-blue-500 focus:outline-none appearance-none
                bg-no-repeat bg-right-2 bg-[length:20px]">
                <option value="all">All Programs</option>
                <option v-for="program in props.programs" :key="program.id" :value="program.id">
                    {{ program.name }}
                </option>
            </select>
        </div>

        <!-- File Type Selector - Full Width -->
        <div class="w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">File Type</label>
            <select v-model="selectedFileType" @change="updateReport" 
                class="w-full px-3 py-3 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                text-gray-800 dark:text-gray-100 text-base focus:ring-2 focus:ring-blue-500 focus:outline-none appearance-none
                bg-no-repeat bg-right-2 bg-[length:20px]">
                <option value="0">All Files</option>
                <option value="1">Cooperative Details</option>
                <option value="2">Amortization Schedule</option>
                <option value="3">Checklist of Documents</option>
                <option value="4">Cooperative Members Documents</option>
                <option value="5">Delinquent Reports</option>
                <option value="6">Progress Reports</option>
                <option value="7">Resolved File</option>
                <option value="8">Memorandum of Agreement</option>
            </select>
        </div>

        <!-- Municipality/Location Selector - Full Width with custom styling -->
        <div class="w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Municipality</label>
            <div class="relative">
                <SelectSearch id="city" :items="props.cities" itemLabelKey="name" itemKeyProp="code"
                    v-model="selectedCity" :open="openState.city_code"
                    @update:open="val => openState.city_code = val" placeholder="All Municipality"
                    class="w-full [&_input]:w-full [&_input]:px-3 [&_input]:py-3 [&_input]:border [&_input]:border-gray-300 [&_input]:dark:border-gray-700 
                    [&_input]:rounded-md [&_input]:bg-white [&_input]:dark:bg-gray-800 [&_input]:text-gray-800 [&_input]:dark:text-gray-100 
                    [&_input]:text-base [&_input]:focus:ring-2 [&_input]:focus:ring-blue-500 [&_input]:focus:outline-none
                    [&_input]:pl-6[&_input]:bg-no-repeat [&_input]:bg-left-2 [&_input]:bg-[length:20px]
                    [&_input]:placeholder:text-gray-400 [&_input]:dark:placeholder:text-gray-500" />
            </div>
        </div>

        <!-- Date Range - Stacked on Mobile -->
        <div class="w-full space-y-3">
            <div class="w-full">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Start Date</label>
                <input type="date" v-model="startDate" :max="today" @change="updateReport"
                    class="w-full px-3 py-3 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                    text-gray-800 dark:text-gray-100 text-base focus:ring-2 focus:ring-blue-500 focus:outline-none" />
            </div>
            <div class="w-full">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">End Date</label>
                <input type="date" v-model="endDate" :max="today" @change="updateReport"
                    class="w-full px-3 py-3 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                    text-gray-800 dark:text-gray-100 text-base focus:ring-2 focus:ring-blue-500 focus:outline-none" />
            </div>
        </div>
    </div>
</div>
                    <!-- Content -->
                    <div class="p-4 overflow-auto max-h-[80vh] bg-gray-50 dark:bg-gray-800 rounded-b-2xl">
                        <div v-if="pdfLoading"
                            class="flex justify-center items-center h-[80vh] text-gray-600 dark:text-gray-300">
                            <div
                                class="animate-spin rounded-full h-10 w-10 border-4 border-gray-400 border-t-transparent">
                            </div>
                        </div>
                        <!-- Desktop PDF -->
                        <iframe v-if="!isMobile" :src="`${pdfUrl}`" class="w-full h-[75vh] rounded" key="pdfUrl"
                            @load="pdfLoading = false"></iframe>

                        <!-- Mobile PDF -->
                        <template v-else>
                            <PdfViewer v-if="!pdfFailed" :url="`${pdfUrl}`" type="report"
                                @error="pdfFailed = true; pdfLoading = false" :key="pdfUrl"
                                @load="pdfLoading = false" />

                            <div v-else class="text-center text-gray-600 dark:text-gray-400">
                                <p class="mb-2">PDF preview not supported on your device.</p>
                                <a :href="`${pdfUrl}?download=1`"
                                    class="text-blue-600 hover:underline font-medium">Download the PDF
                                    file</a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
