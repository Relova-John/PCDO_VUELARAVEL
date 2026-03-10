<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/InventoryLayout.vue'
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cooperatives', href: '/cooperatives' }
]

const props = defineProps<{
    cooperatives: any[]
    inventoryCounts: Record<number, number>
    reportingDate: any
    reportingDates: any[]
    selectedReportingDate: number
}>()

const showModal = ref(false)
const form = ref({
    reporting_month: '',
    reporting_year: ''
})
const selectedDate = ref(props.selectedReportingDate)

function filterDate() {
    router.get('/cooperatives', {
        reporting_date_id: selectedDate.value
    }, { preserveState: true })
}

function openCoop(id: number) {
    router.visit(`/cooperatives/${id}?reporting_date_id=${props.selectedReportingDate}`)
}

function openModal() {
    showModal.value = true
}

function closeModal() {
    showModal.value = false
}

function submitReportingDate() {
    if (!form.value.reporting_month || !form.value.reporting_year) return

    router.post('/reporting-dates', form.value, {
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

const filteredCooperatives = computed(() => {
    if (!search.value) return props.cooperatives

    return props.cooperatives.filter(coop =>
        coop.name.toLowerCase().includes(search.value.toLowerCase())
    )
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
                    <span class="report-badge">
                        {{ reportingDate?.reporting_month }}/{{ reportingDate?.reporting_year }}
                    </span>
                </div>

            </div>

            <div class="coop-card">
                <!-- HEADER -->
                <div class="coop-card-header">
                    <div class="coop-filter">
                        <input v-model="search" type="text" placeholder="Search cooperative..." class="coop-search" />
                        <select v-model="selectedDate" @change="filterDate" class="coop-select">
                            <option v-for="date in reportingDates" :key="date.id" :value="date.id">
                                {{ date.reporting_month }}/{{ date.reporting_year }}
                            </option>
                        </select>
                    </div>
                    <button @click="openModal" class="coop-btn-primary">
                        Add Reporting Date
                    </button>
                </div>


                <!-- TABLE -->
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
                        <tr v-for="coop in filteredCooperatives" :key="coop.id" class="coop-row" @click="openCoop(coop.id)">
                            <td>
                                {{ coop.name }}
                            </td>
                            <td>
                                {{ inventoryCounts[coop.id] ?? 0 }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- SIMPLE PAGINATION -->
                <div class="coop-pagination">
                    <div class="pagination-info">
                        Showing 1–{{ cooperatives.length }} of {{ cooperatives.length }} cooperatives
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn">Previous</button>

                        <button class="pagination-btn active">1</button>

                        <button class="pagination-btn">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL -->
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