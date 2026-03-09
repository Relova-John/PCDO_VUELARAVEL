<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

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
</script>

<template>

    <Head title="Cooperatives" />

    <div class="max-w-7xl mx-auto p-6">

        <h1 class="text-2xl font-bold mb-2">
            Cooperatives
        </h1>

        <p class="text-gray-500 mb-6">
            Reporting Period:
            {{ reportingDate?.reporting_month }}/{{ reportingDate?.reporting_year }}
        </p>

        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="flex items-center justify-between mb-6">

                <div class="flex items-center gap-3">

                    <select v-model="selectedDate" @change="filterDate" class="border rounded px-3 py-2">
                        <option v-for="date in reportingDates" :key="date.id" :value="date.id">
                            {{ date.reporting_month }}/{{ date.reporting_year }}
                        </option>
                    </select>

                </div>

                <button @click="openModal" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Add Reporting Date
                </button>

            </div>
            <table class="w-full">

                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 text-left">Cooperative</th>
                        <th class="p-4 text-left">Inventory Count</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="cooperatives.length === 0">
                        <td colspan="3" class="p-6 text-center text-gray-500">
                            No Cooperative Registered on Form
                        </td>
                    </tr>
                    <tr v-for="coop in cooperatives" :key="coop.id" class="border-b">

                        <td class="p-4">
                            {{ coop.name }}
                        </td>

                        <td class="p-4">
                            {{ inventoryCounts[coop.id] ?? 0 }}
                        </td>

                        <td class="p-4 text-right">
                            <button @click="openCoop(coop.id)" class="text-blue-600 hover:underline">
                                View
                            </button>
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>
    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">

        <div class="bg-white rounded-xl shadow-lg w-96 p-6 space-y-4">

            <h2 class="text-lg font-semibold">
                Add Reporting Date
            </h2>

            <input type="month" @change="handleDateInput" class="w-full border rounded px-3 py-2" />

            <div class="flex justify-end gap-3">

                <button @click="closeModal" class="px-4 py-2 rounded border">
                    Cancel
                </button>

                <button @click="submitReportingDate" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Save
                </button>
            </div>
        </div>
    </div>
</template>