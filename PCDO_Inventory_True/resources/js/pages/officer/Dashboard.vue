<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
    locked: boolean
    cooperatives: any[]
    inventoryCounts: Record<number, number>
    reportingDate: any
    reportingDates: any[]
    selectedReportingDate: number
    locationScope: string | null
    locationName: string | null
}>()

const page = usePage<{
    errors: Record<string, string>
    flash: { success?: string }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/officer/dashboard' }
]

const selectedDate = ref(props.selectedReportingDate)
const search = ref('')

const accessForm = useForm({
    code: ''
})

function activateCode() {
    accessForm.post('/officer/dashboard/access-control/activate')
}

function filterDate() {
    router.get('/officer/dashboard', {
        reporting_date_id: selectedDate.value
    }, { preserveState: true })
}

function openCoop(id: number) {
    router.visit(`/officer/dashboard/${id}?reporting_date_id=${selectedDate.value}`)
}

const filteredCooperatives = computed(() => {
    if (!search.value) return props.cooperatives

    return props.cooperatives.filter(coop =>
        coop.name.toLowerCase().includes(search.value.toLowerCase())
    )
})
</script>

<template>

    <Head title="Officer Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="coop-page">
            <div class="coop-header">
                <div class="coop-header-left">
                    <h1 class="coop-title">Officer Dashboard</h1>
                    <p class="coop-description">
                        View inventory reports based on your assigned location access.
                    </p>
                </div>

                <div class="coop-header-right">
                    <span class="report-label">Reporting Period</span>
                    <span class="report-badge">
                        {{ reportingDate?.reporting_month }}/{{ reportingDate?.reporting_year }}
                    </span>
                </div>
            </div>

            <div v-if="locked" class="coop-card">
                <div class="p-6 max-w-md mx-auto">
                    <h2 class="text-xl font-semibold mb-2">Enter Access Code</h2>
                    <p class="text-sm text-gray-500 mb-4">
                        Your code determines which location data you are allowed to view.
                    </p>

                    <form @submit.prevent="activateCode" class="space-y-4">
                        <input v-model="accessForm.code" type="text" placeholder="Enter access code"
                            class="coop-search w-full" />

                        <div v-if="page.props.errors.code" class="text-red-500 text-sm">
                            {{ page.props.errors.code }}
                        </div>

                        <button type="submit" class="coop-btn-primary w-full">
                            Activate Access
                        </button>
                    </form>
                </div>
            </div>

            <div v-else class="coop-card">
                <div class="coop-card-header">
                    <div class="coop-filter">
                        <input v-model="search" type="text" placeholder="Search cooperative..." class="coop-search" />

                        <select v-model="selectedDate" @change="filterDate" class="coop-select">
                            <option v-for="date in reportingDates" :key="date.id" :value="date.id">
                                {{ date.reporting_month }}/{{ date.reporting_year }}
                            </option>
                        </select>
                    </div>

                    <div class="text-sm font-medium text-gray-600">
                        {{ locationScope }}
                        <span v-if="locationName" class="text-blue-600"> - {{ locationName }}</span>
                    </div>
                </div>

                <table class="coop-table">
                    <thead>
                        <tr>
                            <th>Cooperative</th>
                            <th>Inventory Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cooperatives.length === 0">
                            <td colspan="2" class="coop-empty">
                                No cooperatives available for your assigned location
                            </td>
                        </tr>

                        <tr v-else-if="filteredCooperatives.length === 0">
                            <td colspan="2" class="coop-empty">
                                No cooperatives found for "{{ search }}"
                            </td>
                        </tr>

                        <tr v-for="coop in filteredCooperatives" :key="coop.id" class="coop-row"
                            @click="openCoop(coop.id)">
                            <td>{{ coop.name }}</td>
                            <td>{{ inventoryCounts[coop.id] ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="coop-pagination">
                    <div class="pagination-info">
                        Showing 1–{{ filteredCooperatives.length }} of {{ cooperatives.length }} cooperatives
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn">Previous</button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>