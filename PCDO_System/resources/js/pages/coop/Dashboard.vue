<script setup lang="ts">
import CoopLayout from '@/layouts/CoopLayout.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: dashboard().url },
]

const {
    activePrograms,
    totalLoanAmount,
    totalPaid,
    totalBalance,
    memberCounts,
    totalMembers,
    totalChecklist,
    completedChecklist,
    checklists,
} = defineProps<{
    activePrograms: Array<{ id: number, name: string }>
    totalLoanAmount: number
    totalPaid: number
    totalBalance: number
    monthlyData: number[]
    monthlyCategories: string[]
    memberCounts: Record<string, number>
    totalMembers: number
    totalChecklist: number
    completedChecklist: number
    checklists?: Array<{
        id: number
        name: string
        status: string
    }>
}>()


function formatCurrency(value: number | null | undefined) {
    if (!value) return '₱0'
    return '₱' + Number(value).toLocaleString()
}


const tab = ref<'checklist' | 'schedule'>('checklist')

const checklistPercentage = computed(() => {
    if (!totalChecklist) return 0
    return Math.round((completedChecklist / totalChecklist) * 100)
})

const incompleteChecklist = computed(() => {
    if (!checklists) return []
    return checklists.filter(item => item.status !== 'Completed')
})

const progressColor = computed(() => {
    if (checklistPercentage.value < 40) return 'bg-red-600'
    if (checklistPercentage.value < 80) return 'bg-yellow-500'
    return 'bg-green-600'
})
</script>

<template>

    <Head title="Dashboard" />

    <CoopLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="grid gap-4 md:grid-cols-4 mt-6 px-4 pb-4">

                <!-- Active Program -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">

                    <h3 class="text-lg font-semibold">Active Program</h3>

                    <div v-if="activePrograms.length > 0" class="mt-3 space-y-1">
                        <p v-for="program in activePrograms" :key="program.id" class="text-blue-600 font-bold text-lg">
                            {{ program.name }}
                        </p>
                    </div>

                    <p v-else class="text-gray-500 mt-2">
                        No Active Program
                    </p>

                </div>

                <!-- Total Loan -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
                    <h3 class="text-lg font-semibold">Total Loan</h3>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">
                        {{ formatCurrency(totalLoanAmount) }}
                    </p>
                </div>

                <!-- Total Paid -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
                    <h3 class="text-lg font-semibold">Total Paid</h3>
                    <p class="text-3xl font-bold text-green-600 mt-2">
                        {{ formatCurrency(totalPaid) }}
                    </p>
                </div>

                <!-- Remaining Balance -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
                    <h3 class="text-lg font-semibold">Remaining Balance</h3>
                    <p class="text-3xl font-bold text-red-600 mt-2">
                        {{ formatCurrency(totalBalance) }}
                    </p>
                </div>

                <!-- Chart -->
                <div class="col-span-4 md:col-span-3 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        Checklist and Schedule Overview
                    </h2>
                    <div class="flex gap-3 mb-6">
                        <button class="px-4 py-2 rounded-lg font-medium transition" :class="tab === 'checklist'
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-200 dark:bg-gray-700'" @click="tab = 'checklist'">
                            Checklist
                        </button>

                        <button class="px-4 py-2 rounded-lg font-medium transition" :class="tab === 'schedule'
                            ? 'bg-green-600 text-white'
                            : 'bg-gray-200 dark:bg-gray-700'" @click="tab = 'schedule'">
                            Schedule
                        </button>
                    </div>

                    <div v-if="tab === 'checklist'">

                        <!-- Progress Section -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">Checklist Completion</span>
                                <span class="font-bold text-blue-600">
                                    {{ checklistPercentage }}%
                                </span>
                            </div>

                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div :class="[
                                    progressColor,
                                    'h-4 rounded-full transition-all duration-500'
                                ]" :style="{ width: checklistPercentage + '%' }"></div>
                            </div>
                        </div>

                        <!-- Uncompleted Items -->
                        <div>
                            <h3 class="font-semibold mb-3">Remaining Requirements</h3>

                            <ul class="space-y-2">
                                <li v-for="item in incompleteChecklist" :key="item.id"
                                    class="bg-red-50 dark:bg-red-900/30 text-red-600 p-3 rounded-lg text-sm">
                                    {{ item.name }}
                                </li>
                            </ul>

                            <p v-if="incompleteChecklist.length === 0" class="text-green-600 font-medium">
                                All checklist completed!
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Member Count -->
                <div class="col-span-4 md:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">

                    <h2 class="text-xl font-semibold mb-4 text-center">
                        Members
                    </h2>

                    <!-- Total Members -->
                    <div class="text-center mb-4">
                        <p class="text-sm text-gray-500">Total Members</p>
                        <p class="text-3xl font-bold text-indigo-600">
                            {{ totalMembers }}
                        </p>
                    </div>

                    <!-- Position Breakdown -->
                    <div class="space-y-3 text-sm">

                        <div class="flex justify-between">
                            <span>Chairman</span>
                            <span class="font-semibold text-blue-600">
                                {{ memberCounts.Chairman }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span>Treasurer</span>
                            <span class="font-semibold text-green-600">
                                {{ memberCounts.Treasurer }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span>Manager</span>
                            <span class="font-semibold text-yellow-600">
                                {{ memberCounts.Manager }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span>Members</span>
                            <span class="font-semibold text-gray-600 dark:text-gray-300">
                                {{ memberCounts.Member }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </CoopLayout>
</template>