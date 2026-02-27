<script setup lang="ts">
import CoopLayout from '@/layouts/CoopLayout.vue'
import { computed } from 'vue'
import { useDateFormat } from '@vueuse/core'
import { BreadcrumbItem } from '@/types'

interface ChecklistItem {
    id: number
    file_name?: string
}

interface ProgramChecklist {
    id: number
    name: string
}

interface ProgramData {
    id: number
    created_at: string
    checklist: ChecklistItem[]
    amortizationSchedules: any[]
    program: {
        name: string
        checklists: ProgramChecklist[]
    }
}

const props = defineProps<{
    coop: {
        programs: ProgramData[]
    }
}>()
const coop = computed(() => props.coop)

const programStatuses = computed(() => {
    if (!coop.value?.programs?.length) return []

    return coop.value.programs.map(program => {
        const required = program.program.checklists.length
        const submitted = program.checklist.length

        if (submitted < required) {
            return { label: 'Registering', color: 'red' }
        }

        if (program.amortizationSchedules.length > 0) {
            return { label: 'Ongoing', color: 'yellow' }
        }

        return { label: 'Completed', color: 'green' }
    })
})

const formatDate = (date: string) =>
    useDateFormat(date, 'MM/DD/YYYY').value

const checklistProgress = (program: any) => {
    const total = program.program.checklists.length
    const current = program.checklist.length
    return { current, total }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Checklist', href: '/coop/checklist' },
]

</script>

<template>
<CoopLayout :breadcrumbs="breadcrumbs">

    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto p-6 space-y-6">

            <!-- Programs Container -->
            <div
                v-for="(program, index) in coop.programs"
                :key="program.id"
                class="bg-white dark:bg-gray-800/80 border 
                ring-1 ring-gray-300 dark:ring-gray-700 
                border-gray-300 dark:border-gray-700 
                rounded-xl shadow-sm px-6 py-5 space-y-6"
            >

                <h1 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100">
                    Checklist
                </h1>

                <hr class="border-t border-gray-200 dark:border-gray-700" />

                <!-- Program Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ program.program.name }}
                        </h2>

                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Created: {{ formatDate(program.created_at) }}
                        </p>
                    </div>

                    <!-- Status Badge -->
                    <span
                        class="text-xs font-medium px-3 py-1 rounded-full"
                        :class="{
                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300':
                                programStatuses[index].color === 'red',
                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300':
                                programStatuses[index].color === 'yellow',
                            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300':
                                programStatuses[index].color === 'green',
                        }"
                    >
                        {{ programStatuses[index].label }}
                    </span>

                </div>

                <!-- REGISTERING -->
                <div v-if="programStatuses[index].label === 'Registering'">

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <Table class="min-w-full text-sm border-separate border-spacing-0">

                            <TableHeader class="bg-gray-200 dark:bg-gray-700/50 border-b border-gray-500">
                                <TableRow>
                                    <TableHead class="py-3 pl-6 text-left">
                                        Checklist Item
                                    </TableHead>
                                    <TableHead class="py-3 pl-6 text-left">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody class="bg-white dark:bg-gray-800">

                                <TableRow
                                    v-for="checklist in program.program.checklists"
                                    :key="checklist.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition"
                                >
                                    <TableCell class="pl-6 py-3 text-gray-700 dark:text-gray-300">
                                        {{ checklist.name }}
                                    </TableCell>

                                    <TableCell class="pl-6 py-3">

                                        <span
                                            class="text-xs font-medium px-2 py-1 rounded-full"
                                            :class="program.checklist.some(c => c.id === checklist.id)
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'">

                                            {{ program.checklist.some(c => c.id === checklist.id)
                                                ? 'Submitted'
                                                : 'Pending' }}
                                        </span>

                                        <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">
                                            {{ program.checklist.find(c => c.id === checklist.id)?.file_name || 'No file uploaded' }}
                                        </span>

                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="program.program.checklists.length === 0">
                                    <TableCell colspan="2" class="text-center py-6 text-gray-500">
                                        No checklist items found.
                                    </TableCell>
                                </TableRow>

                            </TableBody>
                        </Table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden space-y-4">
                        <div
                            v-for="checklist in program.program.checklists"
                            :key="checklist.id"
                            class="bg-white dark:bg-gray-800 
                            border border-gray-200 dark:border-gray-700 
                            rounded-2xl shadow-sm p-4 transition hover:shadow-md"
                        >

                            <div class="flex justify-between items-start">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ checklist.name }}
                                </h3>

                                <span
                                    class="text-xs font-medium px-2 py-1 rounded-full"
                                    :class="program.checklist.some(c => c.id === checklist.id)
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'">
                                    {{ program.checklist.some(c => c.id === checklist.id)
                                        ? 'Submitted'
                                        : 'Pending' }}
                                </span>
                            </div>

                            <div class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3 text-sm text-gray-600 dark:text-gray-300">
                                File:
                                {{ program.checklist.find(c => c.id === checklist.id)?.file_name || 'No file uploaded' }}
                            </div>

                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="text-sm text-gray-500 dark:text-gray-400 pt-2">
                        Progress: {{ checklistProgress(program).current }}/{{ checklistProgress(program).total }}
                    </div>

                </div>

                <!-- ONGOING -->
                <div
                    v-else-if="programStatuses[index].label === 'Ongoing'"
                    class="text-sm text-gray-500 dark:text-gray-400"
                >
                    Your program is currently ongoing.
                </div>

            </div>

        </div>
    </div>

</CoopLayout>
</template>