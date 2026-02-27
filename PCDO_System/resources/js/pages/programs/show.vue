<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Filter } from 'lucide-vue-next'
import { Pin, Plus, ChevronDown, Building2, FileText, CheckCircle, CircleDashed, Upload } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'

const props = defineProps<{
    program: {
        id: number
        name: string
        details: string
    },
    cooperatives: Array<{
        id: number
        name: string
        start_date: string
        program_status: string
        has_checklist: boolean
        has_amortization: boolean
        coopProgramId: number
    }>
}>()

const statusFilter = ref<string>('all')
const showFilterDropdown = ref(false)

const statusOptions = [
    { value: 'all', label: 'All Status', icon: null },
    { value: 'Ongoing', label: 'Ongoing', icon: CircleDashed, color: 'text-yellow-600 dark:text-yellow-400' },
    { value: 'Finished', label: 'Finished', icon: CheckCircle, color: 'text-green-600 dark:text-green-400' },
    { value: 'Resolved', label: 'Resolved', icon: CheckCircle, color: 'text-blue-600 dark:text-blue-400' }
]

const filteredCooperatives = computed(() => {
    if (statusFilter.value === 'all') {
        return props.cooperatives
    }
    return props.cooperatives.filter(coop => coop.program_status === statusFilter.value)
})

const programGradients: Record<number, string> = {
    1: 'from-yellow-400 to-orange-500',
    2: 'from-blue-500 to-indigo-500',
    3: 'from-emerald-500 to-teal-600',
    4: 'from-red-400 to-pink-500',
    5: 'from-green-300 to-green-600'
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programs', href: '/programs' },
    { title: props.program.name, href: '#' },
]
</script>

<template>

    <Head :title="program.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="px-4 md:px-4 pt-5">
                <!-- Program Header -->
                <div class="p-5 pb-2">
                    <div
                        class="relative bg-gray-100 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m mb-6">
                        <div
                            :class="`absolute top-0 left-0 w-full h-2 rounded-t-xl bg-gradient-to-r ${programGradients[program.id] || 'from-blue-500 to-indigo-500'}`">
                        </div>
                        <div class="relative px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="w-full">
                                    <div class="flex items-center justify-between sm:justify-start gap-3">
                                        <h1
                                            class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <Pin class="w-8 h-8 text-red-600 dark:text-red-400" />
                                            {{ program.name }}
                                        </h1>
                                        <!-- Mobile Add Button -->
                                        <div class="sm:hidden">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <button
                                                        class="inline-flex items-center justify-between gap-1.5 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm font-medium transition">
                                                        <Plus class="w-4 h-4" />
                                                        <span>Add</span>
                                                        <ChevronDown class="w-4 h-4" />
                                                    </button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent side="bottom" align="end"
                                                    class="w-52 bg-white dark:bg-gray-900 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                                                    <DropdownMenuItem asChild>
                                                        <Link :href="`/programs/${program.id}/cooperatives/create`"
                                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                                        <Building2
                                                            class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                                        Add Cooperative
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link :href="`/programs/${program.id}/progress/create`"
                                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                                        <FileText
                                                            class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" />
                                                        Add Progress Report
                                                        </Link>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </div>
                                    <p
                                        class="text-gray-700 dark:text-gray-400 text-lg md:text-m font-medium leading-relaxed max-w-2xl mt-3">
                                        <em>{{ program.details }}</em>
                                    </p>
                                </div>
                                <!-- Desktop Add Button -->
                                <div class="hidden sm:block">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <button
                                                class="inline-flex items-center justify-between gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm font-medium transition w-33">
                                                <span class="flex items-center gap-2">
                                                    <Plus class="w-4 h-4" /> Add
                                                </span>
                                                <ChevronDown class="w-4 h-4" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent side="bottom" align="end"
                                            class="w-52 bg-white dark:bg-gray-900 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                                            <DropdownMenuItem asChild>
                                                <Link :href="`/programs/${program.id}/cooperatives/create`"
                                                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                                <Building2 class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                                Add Cooperative
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link :href="`/programs/${program.id}/progress/create`"
                                                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                                <FileText class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" />
                                                Add Progress Report
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cooperatives Table -->
                <div
                    class="mr-6 ml-6 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-md border-2 border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">
                        Cooperatives under this Program
                    </h2>

<!-- Filter Section -->
<div class="mb-6 w-full">
    <div class="relative w-full sm:w-auto">
        <button @click="showFilterDropdown = !showFilterDropdown"
            class="w-full sm:w-64 inline-flex items-center justify-between gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            <div class="flex items-center gap-2">
                <Filter class="w-4 h-4" />
                <span>{{ statusFilter === 'all' ? 'Filter by Status' : statusOptions.find(o => o.value === statusFilter)?.label }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span v-if="statusFilter !== 'all'" class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full">
                    {{ filteredCooperatives.length }}
                </span>
                <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showFilterDropdown }" />
            </div>
        </button>
        
        <!-- Dropdown Menu -->
        <div v-if="showFilterDropdown" class="absolute left-0 mt-2 w-full sm:w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10">
            <div class="py-1">
                <!-- Total Count Option -->
                <button @click="statusFilter = 'all'; showFilterDropdown = false"
                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between"
                    :class="statusFilter === 'all' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-200'">
                    <span>All Status</span>
                    <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-0.5 rounded-full">
                        {{ props.cooperatives.length }}
                    </span>
                </button>
                
                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                
                <button v-for="option in statusOptions.filter(o => o.value !== 'all')" :key="option.value"
                    @click="statusFilter = option.value; showFilterDropdown = false"
                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between"
                    :class="statusFilter === option.value ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-200'">
                    <div class="flex items-center gap-2">
                        <component v-if="option.icon" :is="option.icon" class="w-4 h-4" :class="option.color" />
                        {{ option.label }}
                    </div>
                    <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-0.5 rounded-full">
                        {{ props.cooperatives.filter(c => c.program_status === option.value).length }}
                    </span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Active Filter Indicator (only shows on mobile below the button) -->
    <div v-if="statusFilter !== 'all'" class="mt-2 sm:hidden text-sm text-gray-600 dark:text-gray-400">
        Showing: <span class="font-medium">{{ statusOptions.find(o => o.value === statusFilter)?.label }}</span>
        <button @click="statusFilter = 'all'" class="ml-2 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
            Clear Filter
        </button>
    </div>
</div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto rounded-2xl">
                        <Table class="min-w-full border-separate border-spacing-0 text-sm">
                            <TableHeader
                                class="bg-gray-200/90 dark:bg-gray-700/50 border-b border-gray-500 dark:border-gray-500 text-gray-700 dark:text-gray-400">
                                <TableRow>
                                    <TableHead class="py-3 pl-6">#</TableHead>
                                    <TableHead class="pl-30 py-3">Cooperative Name</TableHead>
                                    <TableHead class="pl-30 py-3">Start Date</TableHead>
                                    <TableHead class="pl-30 py-3">Status</TableHead>
                                    <TableHead class="pl-30 py-3">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody class="bg-white dark:bg-gray-900/50">
                                <TableRow v-for="(coop, index) in filteredCooperatives" :key="coop.id"
                                    class="hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors bg-gray-200/ dark:bg-gray-800">
                                    <TableCell class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ index + 1 }}
                                    </TableCell>
                                    <TableCell class="pl-30 py-3 font-medium text-gray-900 dark:text-gray-100">{{
                                        coop.name }}</TableCell>
                                    <TableCell class="pl-30 py-3 text-gray-700 dark:text-gray-300">
                                        {{ new Date(coop.start_date).toLocaleDateString('ph-PH', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric'
                                        }) }}
                                    </TableCell>
                                    <TableCell class="pl-30 py-3">
                                        <span v-if="coop.program_status === 'Finished'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                            <CheckCircle class="w-3 h-3" />
                                            {{ coop.program_status }}
                                        </span>
                                        <span v-else-if="coop.program_status === 'Resolved'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                            <CheckCircle class="w-3 h-3" />
                                            {{ coop.program_status }}
                                        </span>
                                        <span v-else-if="coop.program_status === 'Ongoing' && !coop.has_amortization"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300">
                                            <CircleDashed class="w-3 h-3 animate-spin" />
                                            {{ coop.program_status + ' - Checklist' }}
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                                            <CircleDashed class="w-3 h-3 animate-spin" />
                                            {{ coop.program_status + ' - Program' }}
                                        </span>
                                    </TableCell>

                                    <TableCell class="pl-30 py-3">
                                        <template
                                            v-if="coop.program_status !== 'Finished' && coop.program_status !== 'Resolved'">
                                            <!-- Upload Checklist -->
                                            <Link v-if="!coop.has_checklist"
                                                :href="`/coopProgram/${coop.coopProgramId}/checklist`"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-md transition">
                                            <Upload class="w-4 h-4" />
                                            <span>Upload Checklist</span>
                                            </Link>

                                            <!-- Continue only -->
                                            <Link v-else-if="coop.has_checklist && !coop.has_amortization"
                                                :href="`/coopProgram/${coop.coopProgramId}/checklist`"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg shadow-md transition">
                                            <Upload class="w-4 h-4" />
                                            <span>Continue Checklist</span>
                                            </Link>

                                            <!-- Re-upload + View Amortization -->
                                            <div v-else-if="coop.has_checklist && coop.has_amortization"
                                                class="flex gap-2">
                                                <Link
                                                    :href="`/coopProgram/${coop.coopProgramId}/checklist`"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg shadow-md transition">
                                                <Upload class="w-4 h-4" />
                                                <span>Re-upload</span>
                                                </Link>
                                                <Link :href="`/amortizations/${coop.coopProgramId}`"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md transition">
                                                <FileText class="w-4 h-4" />
                                                <span>View Schedule</span>
                                                </Link>
                                            </div>
                                        </template>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredCooperatives.length === 0">
                                    <TableCell colspan="5" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                                        🚫 No cooperatives found matching the selected filter.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="cooperatives.length === 0">
                                    <TableCell colspan="4"
                                        class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                                        🚫 No cooperatives enrolled in this program yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Mobile View -->
                    <div class="md:hidden space-y-4">
                        <div v-for="(coop, index) in filteredCooperatives" :key="coop.id"
                            class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ coop.name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">#{{ index + 1 }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ new Date(coop.start_date).toLocaleDateString('ph-PH', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric'
                                        }) }}
                                    </span>
                                    <div class="flex items-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[11px] font-medium rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': coop.program_status === 'Finished',
                                                'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300': coop.program_status === 'Resolved',
                                                'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300': coop.program_status === 'Ongoing' && !coop.has_amortization,
                                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300': coop.program_status === 'Ongoing' && coop.has_amortization,
                                                'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300': !coop.program_status
                                            }">
                                            <component
                                                :is="coop.program_status === 'Finished' || coop.program_status === 'Resolved' ? CheckCircle : CircleDashed"
                                                class="w-3 h-3" :class="{
                                                    'animate-spin': coop.program_status?.includes('Ongoing')
                                                }" />
                                            <span class="truncate">
                                                {{
                                                    coop.program_status === 'Ongoing'
                                                        ? coop.has_amortization
                                                            ? 'Ongoing Program'
                                                            : 'Ongoing Checklist'
                                                        : coop.program_status || 'N/A'
                                                }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="coop.program_status !== 'Finished' && coop.program_status !== 'Resolved'"
                                class="mt-4 space-y-2">
                                <!-- Upload Checklist -->
                                <Link v-if="!coop.has_checklist"
                                    :href="`/coopProgram/${coop.coopProgramId}/checklist`"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-md transition w-full justify-center">
                                <Upload class="w-4 h-4" />
                                <span>Upload Checklist</span>
                                </Link>

                                <!-- Continue only -->
                                <Link v-else-if="coop.has_checklist && !coop.has_amortization"
                                    :href="`/coopProgram/${coop.coopProgramId}/checklist`"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg shadow-md transition w-full justify-center">
                                <Upload class="w-4 h-4" />
                                <span>Continue Checklist</span>
                                </Link>

                                <!-- Re-upload + View Amortization -->
                                <template v-else-if="coop.has_checklist && coop.has_amortization">
                                    <Link :href="`/coopProgram/${coop.coopProgramId}/checklist`"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg shadow-md transition w-full justify-center">
                                    <Upload class="w-4 h-4" />
                                    <span>Re-upload</span>
                                    </Link>
                                    <Link :href="`/amortizations/${coop.coopProgramId}`"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md transition w-full justify-center">
                                    <FileText class="w-4 h-4" />
                                    <span>View Schedule</span>
                                    </Link>
                                </template>
                            </div>
                        </div>

                        <div v-if="filteredCooperatives.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-4">
                            🚫 No cooperatives found matching the selected filter.
                        </div>

                        <div v-if="cooperatives.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-4">
                            🚫 No cooperatives enrolled in this program yet.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
