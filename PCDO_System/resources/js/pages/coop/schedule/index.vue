<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import CoopLayout from '@/layouts/CoopLayout.vue'
import type { AppPageProps } from '@/types'


interface Schedule {
    id: number
    due_date: string
    installment: number
    penalty_amount: number
    status: string
    paid_at?: string | null
}

interface CoopProgram {
    id: number
    program_name: string
    loan_amount: number
    grace_period: number
    term_months: number
    schedules: Schedule[]
}

const page = usePage<AppPageProps<{
    coopProgram: CoopProgram | null
}>>()

const coopProgram = computed(() => page.props.coopProgram)

const breadcrumbs = [
    { title: 'Schedules', href: '/schedules' },
]


function formatDate(date: string | undefined) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString()
}


const expectedEndDate = computed(() => {
  if (!coopProgram.value?.schedules?.length) return undefined

  const last = coopProgram.value.schedules[
    coopProgram.value.schedules.length - 1
  ]

  return last?.due_date
})

const allPeriods = computed(() => {
    if (!coopProgram.value) return []

    const periods: any[] = []

    // Grace Period Rows
    for (let i = 1; i <= (coopProgram.value.grace_period || 0); i++) {
        periods.push({
            type: 'grace',
            label: `Grace Period ${i}`
        })
    }

    // Payment Rows
    coopProgram.value.schedules.forEach((schedule, index) => {
        periods.push({
            type: 'payment',
            label: `Payment ${index + 1}`,
            data: schedule,
            totalDue:
                (schedule.installment || 0) +
                (schedule.penalty_amount || 0)
        })
    })

    return periods
})

function getStatus(schedule: Schedule) {
    const today = new Date()
    const due = new Date(schedule.due_date)

    if (schedule.status === 'paid') {
        return { type: 'Paid', label: 'Paid' }
    }

    if (due < today) {
        return { type: 'Overdue', label: 'Overdue' }
    }

    return { type: 'Pending', label: 'Pending' }
}
</script>
<template>
    <CoopLayout :breadcrumbs="breadcrumbs">

        <div v-if="coopProgram" class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto p-6 space-y-6">

                <!-- Loan Information Card -->
                <div class="bg-white dark:bg-gray-800/80 border 
        ring-1 ring-gray-300 dark:ring-gray-700 
        border-gray-300 dark:border-gray-700 
        rounded-xl shadow-sm px-6 py-5">

                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        Loan Tracker
                    </h2>

                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <p>
                            Program:
                            <span class="font-semibold text-gray-800 dark:text-gray-100">
                                {{ coopProgram.program_name }}
                            </span>
                        </p>

                        <p>
                            Loan Amount:
                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">
                                ₱{{ Math.round(coopProgram.loan_amount).toLocaleString() }}
                            </span>
                        </p>
                    </div>

                    <!-- Loan Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">

                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-xl">
                            <p class="text-xs text-gray-500">Start Date</p>
                            <p class="text-lg font-semibold">
                                {{ formatDate(coopProgram.schedules?.[0]?.due_date) }}
                            </p>
                        </div>

                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-xl">
                            <p class="text-xs text-gray-500">Grace Period</p>
                            <p class="text-lg font-semibold">
                                {{ coopProgram.grace_period || 0 }} months
                            </p>
                        </div>

                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-xl">
                            <p class="text-xs text-gray-500">Loan Term</p>
                            <p class="text-lg font-semibold">
                                {{ coopProgram.term_months || 0 }} months
                            </p>
                        </div>

                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-xl">
                            <p class="text-xs text-gray-500">Expected End Date</p>
                            <p class="text-lg font-semibold">
                                {{ formatDate(expectedEndDate) }}
                            </p>
                        </div>

                    </div>

                </div>


                <!-- Payment Schedule -->
                <div class="bg-white dark:bg-gray-800/80 border 
            ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 
            rounded-xl shadow-sm px-6 py-5">

                    <h3 class="text-lg font-semibold mb-4">
                        Payment Schedule
                    </h3>

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <Table class="min-w-full text-sm border-separate border-spacing-0">

                            <TableHeader class="bg-gray-200 dark:bg-gray-700/50 border-b border-gray-500">
                                <TableRow>
                                    <TableHead class="py-3 pl-6 text-left">Period</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Due Date</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Installment</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Penalty</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Total Due</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Status</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody class="bg-white dark:bg-gray-800">

                                <TableRow v-for="(row, index) in allPeriods" :key="index"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">

                                    <template v-if="row.type === 'grace'">
                                        <TableCell class="pl-6 py-3 font-medium text-indigo-500">
                                            {{ row.label }}
                                        </TableCell>
                                        <TableCell colspan="5" class="pl-6 py-3 text-yellow-600 font-medium">
                                            No payment due (Grace Period)
                                        </TableCell>
                                    </template>

                                    <template v-else>
                                        <TableCell class="pl-6 py-3 font-medium">
                                            {{ row.label }}
                                        </TableCell>

                                        <TableCell class="pl-6 py-3">
                                            {{ formatDate(row.data?.due_date) }}
                                        </TableCell>

                                        <TableCell class="pl-6 py-3">
                                            ₱{{ Math.round(row.data?.installment || 0).toLocaleString() }}
                                        </TableCell>

                                        <TableCell class="pl-6 py-3">
                                            ₱{{ Math.round(row.data?.penalty_amount || 0).toLocaleString() }}
                                        </TableCell>

                                        <TableCell class="pl-6 py-3 font-semibold text-indigo-600">
                                            ₱{{ row.totalDue?.toLocaleString() }}
                                        </TableCell>

                                        <TableCell class="pl-6 py-3">
                                            <span :class="[
                                                'text-xs font-semibold px-3 py-1 rounded-full',
                                                getStatus(row.data!).type === 'Paid'
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                                    : getStatus(row.data!).type === 'Overdue'
                                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                                                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'
                                            ]">
                                                {{ getStatus(row.data!).label }}
                                            </span>
                                        </TableCell>

                                    </template>

                                </TableRow>

                            </TableBody>
                        </Table>
                    </div>


                    <!-- Mobile Cards -->
                    <div class="md:hidden space-y-4">

                        <div v-for="(row, index) in allPeriods" :key="index" class="bg-white dark:bg-gray-800 
            border border-gray-200 dark:border-gray-700 
            rounded-2xl shadow-sm p-4">

                            <template v-if="row.type === 'grace'">
                                <p class="font-semibold text-indigo-500 mb-2">
                                    {{ row.label }}
                                </p>
                                <p class="text-yellow-600">
                                    No payment due (Grace Period)
                                </p>
                            </template>

                            <template v-else>

                                <div class="flex justify-between items-center mb-2">
                                    <p class="font-semibold">{{ row.label }}</p>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full" :class="getStatus(row.data!).type === 'Paid'
                                        ? 'bg-green-100 text-green-700'
                                        : getStatus(row.data!).type === 'Overdue'
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-yellow-100 text-yellow-700'">
                                        {{ getStatus(row.data!).label }}
                                    </span>
                                </div>

                                <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                    <p><strong>Due:</strong> {{ formatDate(row.data?.due_date) }}</p>
                                    <p><strong>Installment:</strong> ₱{{ Math.round(row.data?.installment ||
                                        0).toLocaleString() }}</p>
                                    <p><strong>Penalty:</strong> ₱{{ Math.round(row.data?.penalty_amount ||
                                        0).toLocaleString() }}</p>
                                    <p><strong>Total Due:</strong> ₱{{ row.totalDue?.toLocaleString() }}</p>
                                </div>

                            </template>

                        </div>

                    </div>

                </div>

            </div>
        </div>

    </CoopLayout>
</template>