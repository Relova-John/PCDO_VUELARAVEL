<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cooperatives', href: '/cooperatives' }
]

const props = defineProps<{
    cooperatives: any[]
    inventoryCounts: Record<number, number>
    inventoryStatus: Record<number, any>
    reportingDate: any
    reportingDates: any[]
    selectedReportingDate: number
    categories: { value: string, label: string }[]
    categoryCounts: Record<string, Record<string, number>>
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
}>()

const showModal = ref(false)
const form = ref({
    reporting_month: '',
    reporting_year: ''
})
const selectedDate = ref(props.selectedReportingDate)

function filterDate() {
    router.get('/admin/dashboard', {
        reporting_date_id: selectedDate.value
    }, { preserveState: true })
}

function openCoop(id: number) {
    router.visit(`/admin/dashboard/${id}?reporting_date_id=${props.selectedReportingDate}`)
}

function openModal() {
    showModal.value = true
}

function closeModal() {
    showModal.value = false
}

function submitReportingDate() {
    if (!form.value.reporting_month || !form.value.reporting_year) return

    router.post('/admin/reporting-dates', form.value, {
        onSuccess: () => {
            closeModal()
        }
    })
}

function handleDateInput(event: Event) {
    const value = (event.target as HTMLInputElement).value

    if (!value) return

    const date = new Date(value)

    form.value.reporting_month = String(date.getMonth() + 1)
    form.value.reporting_year = String(date.getFullYear())
}

const search = ref('')

const inventoryFilter = ref('with-inventory')

const filteredCooperatives = computed(() => {
    let result = props.cooperatives
    if (inventoryFilter.value === 'with-inventory') {
        result = result.filter(coop => (props.inventoryCounts[coop.id] ?? 0) > 0)
    }

    if (search.value) {
        result = result.filter(coop =>
            coop.name.toLowerCase().includes(search.value.toLowerCase())
        )
    }

    result = [...result].sort((a, b) => {
        const aCount = props.inventoryCounts[a.id] ?? 0
        const bCount = props.inventoryCounts[b.id] ?? 0
        return bCount - aCount
    })
    return result
})

const perPage = 10
const currentPage = ref(1)

const totalItems = computed(() => filteredCooperatives.value.length)

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(totalItems.value / perPage))
})

const paginatedCooperatives = computed(() => {
    const start = (currentPage.value - 1) * perPage
    const end = start + perPage
    return filteredCooperatives.value.slice(start, end)
})

const startItem = computed(() => {
    if (totalItems.value === 0) return 0
    return (currentPage.value - 1) * perPage + 1
})

const endItem = computed(() => {
    if (totalItems.value === 0) return 0
    return Math.min(currentPage.value * perPage, totalItems.value)
})

const isFirstPage = computed(() => currentPage.value === 1)
const isLastPage = computed(() => currentPage.value === totalPages.value)

function goToPreviousPage() {
    if (!isFirstPage.value) {
        currentPage.value--
    }
}

function goToNextPage() {
    if (!isLastPage.value) {
        currentPage.value++
    }
}

watch(search, () => {
    currentPage.value = 1
})

watch(filteredCooperatives, () => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = totalPages.value
    }
})
</script>

<template>

    <Head title="Cooperatives" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="coop-page">
            <div class="coop-header">
                <div class="coop-header-left">
                    <h1 class="coop-title">
                        Cooperatives
                    </h1>

                    <p class="coop-description">
                        Manage cooperative inventory reports.
                    </p>
                </div>

                <div class="coop-header-right">
                    <span class="report-label">Reporting Period</span>
                    <select class="report-badge" v-model="selectedDate" @change="filterDate">
                        <option v-for="date in reportingDates" :key="date.id" :value="date.id">
                            {{ date.reporting_month }}/{{ date.reporting_year }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="coop-card">
                <div class="coop-card-header">
                    <div class="coop-filter">
                        <input v-model="search" type="text" placeholder="Search cooperative..." class="coop-search" />
                        <select v-model="inventoryFilter" class="coop-select">
                            <option value="all">All Cooperatives</option>
                            <option value="with-inventory">With Inventory Only</option>
                        </select>
                    </div>

                    <button @click="openModal" class="coop-btn-primary">
                        Add Reporting Date
                    </button>
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
                                No Cooperative Registered on Form
                            </td>
                        </tr>

                        <tr v-else-if="filteredCooperatives.length === 0">
                            <td colspan="2" class="coop-empty">
                                No cooperatives found for "{{ search }}"
                            </td>
                        </tr>

                        <tr v-for="coop in paginatedCooperatives" :key="coop.id" class="coop-row"
                            @click="openCoop(coop.id)">
                            <td>{{ coop.name }}</td>
                            <td>{{ inventoryCounts[coop.id] ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="coop-pagination">
                    <div v-if="filteredCooperatives.length === 0" class="pagination-info">
                        No cooperatives found for "{{ search }}"
                    </div>

                    <div v-else-if="startItem === endItem" class="pagination-info">
                        Showing {{ startItem }} of {{ filteredCooperatives.length }} cooperatives
                    </div>

                    <div v-else class="pagination-info">
                        Showing {{ startItem }} - {{ endItem }} of {{ filteredCooperatives.length }} cooperatives
                    </div>

                    <div class="pagination-controls">
                        <button v-if="!isFirstPage" class="pagination-btn" @click="goToPreviousPage">
                            Previous
                        </button>

                        <button class="pagination-btn active">
                            {{ currentPage }}
                        </button>

                        <button v-if="!isLastPage" class="pagination-btn" @click="goToNextPage">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="modal-overlay">
            <div class="modal-box">
                <h2 class="modal-title">
                    Add Reporting Date
                </h2>

                <input type="month" @change="handleDateInput" class="modal-input" />

                <div class="modal-actions">
                    <button @click="closeModal" class="modal-cancel">
                        Cancel
                    </button>

                    <button @click="submitReportingDate" class="modal-save">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>