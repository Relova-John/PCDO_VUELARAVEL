<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { Pin, Plus, ChevronDown, Building2, FileText, CheckCircle, CircleDashed, Upload } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'

const props = defineProps<{
    program: { id: number, name: string, description: string },
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

const programDescriptions: Record<string, string> = {
    USAD: 'Upgrading Support for Advancement and Development of Enterprises in Cooperative',
    LICAP: 'Livelihood Credit Assistance Program',
    COPSE: 'Cooperative Program For Sustainable Enterprise',
    SULONG: 'Sustained Livelihood Opportunities and Growth',
    PCLRP: 'Provincial Cooperative Livelihood Recovery Program'
}

const fixedProgramGradients: Record<number, string> = {
    1: 'from-yellow-400 to-orange-500',
    2: 'from-blue-500 to-indigo-500',
    3: 'from-emerald-500 to-teal-600',
    4: 'from-red-400 to-pink-500',
    5: 'from-green-300 to-green-600'
}
const gradientPool = [
    'from-purple-500 to-violet-600',
    'from-cyan-500 to-blue-500',
    'from-fuchsia-500 to-pink-600',
    'from-amber-400 to-yellow-500',
    'from-lime-400 to-emerald-500',
    'from-sky-400 to-indigo-500',
    'from-rose-400 to-red-500',
    'from-teal-400 to-cyan-500',
]

const getProgramGradient = (id: number) => fixedProgramGradients[id] || gradientPool[id % gradientPool.length]

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programs', href: '/admin/programs' },
    { title: props.program.name, href: '#' },
]
</script>

<template>
    <Head :title="program.name" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen px-4 md:px-6 pt-5">

            <!-- Unified Card -->
            <div class="relative bg-gray-100 dark:bg-gray-800/80 border border-gray-300 dark:border-gray-700 rounded-xl shadow-md">

                <!-- Gradient Header -->
                <div :class="`absolute top-0 left-0 w-full h-2 rounded-t-xl bg-gradient-to-r ${getProgramGradient(program.id)}`"></div>

                <div class="relative px-6 py-6">

                    <!-- Program Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-full">
                            <div class="flex items-center justify-between sm:justify-start gap-3">
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <Pin class="w-8 h-8 text-red-600 dark:text-red-400" />
                                    {{ program.name }}
                                </h1>

                                <!-- Mobile Add Button -->
                                <div class="sm:hidden">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <button class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm font-medium transition">
                                                <Plus class="w-4 h-4" /> Add <ChevronDown class="w-4 h-4" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent side="bottom" align="end" class="w-52 bg-white dark:bg-gray-900 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                                            <DropdownMenuItem asChild>
                                                <Link :href="`/admin/programs/${program.id}/cooperatives/create`" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    <Building2 class="w-4 h-4 text-blue-600 dark:text-blue-400" /> Add Cooperative
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link :href="`/admin/programs/${program.id}/progress/create`" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    <FileText class="w-4 h-4 text-green-600 dark:text-green-400" /> Add Progress Report
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                            <p class="text-gray-700 dark:text-gray-400 text-lg font-medium mt-3">
                                <em>{{ programDescriptions[program.name] }}</em>
                            </p>
                        </div>

                        <!-- Desktop Add Button -->
                        <div class="hidden sm:block">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm font-medium transition">
                                        <Plus class="w-4 h-4" /> Add <ChevronDown class="w-4 h-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent side="bottom" align="end" class="w-52 bg-white dark:bg-gray-900 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                                    <DropdownMenuItem asChild>
                                        <Link :href="`/admin/programs/${program.id}/cooperatives/create`" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                            <Building2 class="w-4 h-4 text-blue-600 dark:text-blue-400" /> Add Cooperative
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link :href="`/admin/programs/${program.id}/progress/create`" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                            <FileText class="w-4 h-4 text-green-600 dark:text-green-400" /> Add Progress Report
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-300 dark:border-gray-700 my-6"></div>

                    <!-- Table Title -->
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">
                        Cooperatives under this Program
                    </h2>

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto rounded-2xl">
                        <Table class="min-w-full border-separate border-spacing-0 text-sm">
                            <TableHeader class="bg-gray-200/90 dark:bg-gray-700/50 border-b border-gray-500 dark:border-gray-500 text-gray-700 dark:text-gray-400">
                                <TableRow>
                                    <TableHead class="py-3 pl-6">#</TableHead>
                                    <TableHead class="pl-30 py-3">Cooperative Name</TableHead>
                                    <TableHead class="pl-30 py-3">Start Date</TableHead>
                                    <TableHead class="pl-30 py-3">Status</TableHead>
                                    <TableHead class="pl-30 py-3">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody class="bg-white dark:bg-gray-900/50">
                                <TableRow v-for="(coop, index) in cooperatives" :key="coop.id"
                                    class="hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors bg-gray-200/ dark:bg-gray-800">
                                    <TableCell class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ index + 1 }}</TableCell>
                                    <TableCell class="pl-30 py-3 font-medium text-gray-900 dark:text-gray-100">{{ coop.name }}</TableCell>
                                    <TableCell class="pl-30 py-3 text-gray-700 dark:text-gray-300">
                                        {{ new Date(coop.start_date).toLocaleDateString('ph-PH', { year:'numeric', month:'long', day:'numeric' }) }}
                                    </TableCell>
                                    <TableCell class="pl-30 py-3">
                                        <span v-if="coop.program_status==='Finished'" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                            <CheckCircle class="w-3 h-3" /> {{ coop.program_status }}
                                        </span>
                                        <span v-else-if="coop.program_status==='Resolved'" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                            <CheckCircle class="w-3 h-3" /> {{ coop.program_status }}
                                        </span>
                                        <span v-else-if="coop.program_status==='Ongoing' && !coop.has_amortization" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300">
                                            <CircleDashed class="w-3 h-3 animate-spin" /> {{ coop.program_status + ' - Checklist' }}
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                                            <CircleDashed class="w-3 h-3 animate-spin" /> {{ coop.program_status + ' - Program' }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="pl-30 py-3">
                                        <template v-if="coop.program_status!=='Finished' && coop.program_status!=='Resolved'">
                                            <Link v-if="!coop.has_checklist" :href="`/admin/programs/${program.id}/cooperatives/${coop.id}/checklist`"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-md transition">
                                                <Upload class="w-4 h-4" /> Upload Checklist
                                            </Link>
                                            <Link v-else-if="coop.has_checklist && !coop.has_amortization" :href="`/admin/programs/${program.id}/cooperatives/${coop.id}/checklist`"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg shadow-md transition">
                                                <Upload class="w-4 h-4" /> Continue Checklist
                                            </Link>
                                            <div v-else-if="coop.has_checklist && coop.has_amortization" class="flex gap-2">
                                                <Link :href="`/admin/programs/${program.id}/cooperatives/${coop.id}/checklist`"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg shadow-md transition">
                                                    <Upload class="w-4 h-4" /> Re-upload
                                                </Link>
                                                <Link :href="`/amortizations/${coop.coopProgramId}`"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md transition">
                                                    <FileText class="w-4 h-4" /> View Schedule
                                                </Link>
                                            </div>
                                        </template>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="cooperatives.length===0">
                                    <TableCell colspan="5" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                                        🚫 No cooperatives enrolled in this program yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Mobile View -->
                    <div class="md:hidden space-y-4">
                        <div v-for="(coop,index) in cooperatives" :key="coop.id" class="bg-white dark:bg-gray-900 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ coop.name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">#{{ index+1 }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ new Date(coop.start_date).toLocaleDateString('ph-PH',{year:'numeric',month:'long',day:'numeric'}) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-if="cooperatives.length===0" class="text-center text-gray-500 dark:text-gray-400 py-4">
                            🚫 No cooperatives enrolled in this program yet.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AdminLayout>
</template>