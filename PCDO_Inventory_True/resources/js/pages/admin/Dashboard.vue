<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, reactive, watch } from 'vue'
import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { toast } from 'vue-sonner'
import SelectSearch from '@/components/SelectSearch.vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '#' }
]

type SummaryRow = {
    id: number
    name: string
    category: string
    item_location: string
    value: number
    quantity: number
    serviceable: number
    unserviceable: number
    status_raw: number | null
    coop_id: number
    coop_name: string
    region_code: string
    province_code: string
    city_code: string
    barangay_code: string
    region_name: string
    province_name: string
    city_name: string
    barangay_name: string
    coop_location: string
    total: number
}

const props = defineProps<{
    cooperatives: any[]
    inventoryCounts: Record<number, number>
    reportingDate: any
    reportingDates: any[]
    selectedReportingDate: number
    categories: { value: string, label: string }[]
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
    inventorySummaryRows: SummaryRow[]
}>()

const showModal = ref(false)
const showSummaryModal = ref(false)

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

function openSummaryModal() {
    showSummaryModal.value = true
}

function closeSummaryModal() {
    showSummaryModal.value = false
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

const locationFilter = reactive({
    region_code: '1700000000',
    province_code: '1705300000',
    city_code: '',
    barangay_code: ''
})

const locationSearch = reactive({
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: ''
})

const openState = reactive({
    region_code: false,
    province_code: false,
    city_code: false,
    barangay_code: false
})

const dependencyMap = {
    region_code: ['province_code', 'city_code', 'barangay_code'],
    province_code: ['city_code', 'barangay_code'],
    city_code: ['barangay_code'],
    barangay_code: []
} as const

type LocationFields = 'region_code' | 'province_code' | 'city_code' | 'barangay_code'

function onSelectLocation(field: LocationFields, payload: { id: string; name: string }) {
    locationFilter[field] = String(payload.id)
    locationSearch[field] = payload.name
    openState[field] = false

    dependencyMap[field].forEach(dep => {
        locationFilter[dep] = ''
        locationSearch[dep] = ''
        openState[dep] = false
    })

    currentPage.value = 1
}

function onLocationModelUpdate(field: LocationFields, value: string | number) {
    locationFilter[field] = String(value)

    if (!value) {
        locationSearch[field] = ''
        openState[field] = false

        dependencyMap[field].forEach(dep => {
            locationFilter[dep] = ''
            locationSearch[dep] = ''
            openState[dep] = false
        })
    }

    currentPage.value = 1
}

const filteredProvinces = computed(() => {
    if (!locationFilter.region_code) return []
    return props.provinces.filter(
        p => String(p.region_code) === String(locationFilter.region_code)
    )
})

const filteredCities = computed(() => {
    if (!locationFilter.province_code) return []
    return props.cities.filter(
        c => String(c.province_code) === String(locationFilter.province_code)
    )
})

const filteredBarangays = computed(() => {
    if (!locationFilter.city_code) return []
    return props.barangays.filter(
        b => String(b.city_code) === String(locationFilter.city_code)
    )
})

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

    if (locationFilter.region_code) {
        result = result.filter(
            coop => String(coop.region_code) === String(locationFilter.region_code)
        )
    }

    if (locationFilter.province_code) {
        result = result.filter(
            coop => String(coop.province_code) === String(locationFilter.province_code)
        )
    }

    if (locationFilter.city_code) {
        result = result.filter(
            coop => String(coop.city_code) === String(locationFilter.city_code)
        )
    }

    if (locationFilter.barangay_code) {
        result = result.filter(
            coop => String(coop.barangay_code) === String(locationFilter.barangay_code)
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

const hasActiveLocationFilter = computed(() => {
    return !!(
        locationFilter.region_code ||
        locationFilter.province_code ||
        locationFilter.city_code ||
        locationFilter.barangay_code
    )
})

const emptyStateMessage = computed(() => {
    if (search.value.trim()) {
        return `No cooperatives found for "${search.value}"`
    }

    if (hasActiveLocationFilter.value) {
        return 'No cooperatives found for the selected location filter.'
    }

    if (inventoryFilter.value === 'with-inventory') {
        return 'No cooperatives with inventory found.'
    }

    return 'No cooperatives found.'
})

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

function goToCreatePage() {
    router.visit('/admin/create')
}

watch(search, () => {
    currentPage.value = 1
})

watch(filteredCooperatives, () => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = totalPages.value
    }
})

watch(
    () => [
        locationFilter.region_code,
        locationFilter.province_code,
        locationFilter.city_code,
        locationFilter.barangay_code
    ],
    () => {
        currentPage.value = 1
    }
)

const summaryView = ref<'inventory' | 'cooperative'>('inventory')

const summaryFilters = reactive({
    region_code: '1700000000',
    province_code: '1705300000',
    city_code: '',
    barangay_code: '',
    category: '',
    status: 'all',
    name: '',
})

const summaryLocationSearch = reactive({
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: ''
})

const summaryOpenState = reactive({
    region_code: false,
    province_code: false,
    city_code: false,
    barangay_code: false
})

function resetSummaryLocationChildren(field: LocationFields) {
    dependencyMap[field].forEach(dep => {
        summaryFilters[dep] = ''
        summaryLocationSearch[dep] = ''
        summaryOpenState[dep] = false
    })
}

function onSelectSummaryLocation(field: LocationFields, payload: { id: string; name: string }) {
    summaryFilters[field] = String(payload.id)
    summaryLocationSearch[field] = payload.name
    summaryOpenState[field] = false
    resetSummaryLocationChildren(field)
}

function onSummaryLocationModelUpdate(field: LocationFields, value: string | number) {
    summaryFilters[field] = String(value)

    if (!value) {
        summaryLocationSearch[field] = ''
        summaryOpenState[field] = false
        resetSummaryLocationChildren(field)
    }
}

const summaryFilteredProvinces = computed(() => {
    if (!summaryFilters.region_code) return []
    return props.provinces.filter(
        p => String(p.region_code) === String(summaryFilters.region_code)
    )
})

const summaryFilteredCities = computed(() => {
    if (!summaryFilters.province_code) return []
    return props.cities.filter(
        c => String(c.province_code) === String(summaryFilters.province_code)
    )
})

const summaryFilteredBarangays = computed(() => {
    if (!summaryFilters.city_code) return []
    return props.barangays.filter(
        b => String(b.city_code) === String(summaryFilters.city_code)
    )
})

const filteredSummaryRows = computed(() => {
    let rows = [...props.inventorySummaryRows]

    if (summaryFilters.region_code) {
        rows = rows.filter(row => String(row.region_code) === String(summaryFilters.region_code))
    }

    if (summaryFilters.province_code) {
        rows = rows.filter(row => String(row.province_code) === String(summaryFilters.province_code))
    }

    if (summaryFilters.city_code) {
        rows = rows.filter(row => String(row.city_code) === String(summaryFilters.city_code))
    }

    if (summaryFilters.barangay_code) {
        rows = rows.filter(row => String(row.barangay_code) === String(summaryFilters.barangay_code))
    }

    if (summaryFilters.category) {
        rows = rows.filter(row => row.category === summaryFilters.category)
    }

    if (summaryFilters.status === 'serviceable') {
        rows = rows.filter(row => row.serviceable > 0)
    }

    if (summaryFilters.status === 'unserviceable') {
        rows = rows.filter(row => row.unserviceable > 0)
    }

    if (summaryFilters.name.trim()) {
        const keyword = summaryFilters.name.trim().toLowerCase()

        rows = rows.filter(row =>
            String(row.name || '').toLowerCase().includes(keyword)
        )
    }

    return rows
})

function formatMoney(value: number) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(value || 0)
}

const inventoryGroupedRows = computed(() => {
    const map = new Map<string, {
        key: string
        itemName: string
        category: string
        rows: Array<{
            coop_name: string
            coop_location: string
            item_location: string
            display_quantity: number
            value: number
            total: number
        }>
    }>()

    for (const row of filteredSummaryRows.value) {
        const displayQuantity =
            summaryFilters.status === 'unserviceable'
                ? row.unserviceable
                : summaryFilters.status === 'serviceable'
                    ? row.serviceable
                    : row.quantity

        if (displayQuantity <= 0) continue

        const key = `${row.category}__${row.name}`

        if (!map.has(key)) {
            map.set(key, {
                key,
                itemName: row.name,
                category: row.category,
                rows: []
            })
        }

        map.get(key)!.rows.push({
            coop_name: row.coop_name,
            coop_location: row.coop_location,
            item_location: row.item_location,
            display_quantity: displayQuantity,
            value: row.value,
            total: row.value * displayQuantity
        })
    }

    return [...map.values()]
})

const cooperativeGroupedRows = computed(() => {
    const map = new Map<string, {
        key: string
        coop_name: string
        item_rows: Array<{
            item_name: string
            coop_location: string
            item_location: string
            value: number
            display_quantity: number
            total: number
        }>
    }>()

    for (const row of filteredSummaryRows.value) {
        const displayQuantity =
            summaryFilters.status === 'unserviceable'
                ? row.unserviceable
                : summaryFilters.status === 'serviceable'
                    ? row.serviceable
                    : row.quantity

        if (displayQuantity <= 0) continue

        const key = `${row.coop_id}`

        if (!map.has(key)) {
            map.set(key, {
                key,
                coop_name: row.coop_name,
                item_rows: []
            })
        }

        map.get(key)!.item_rows.push({
            item_name: row.name,
            coop_location: row.coop_location,
            item_location: row.item_location,
            value: row.value,
            display_quantity: displayQuantity,
            total: row.value * displayQuantity
        })
    }

    return [...map.values()].sort((a, b) => a.coop_name.localeCompare(b.coop_name))
})

function downloadSummaryPdf() {
    const params = new URLSearchParams({
        reporting_date_id: String(selectedDate.value ?? ''),
        view: summaryView.value,
        region_code: summaryFilters.region_code || '',
        province_code: summaryFilters.province_code || '',
        city_code: summaryFilters.city_code || '',
        barangay_code: summaryFilters.barangay_code || '',
        category: summaryFilters.category || '',
        status: summaryFilters.status || 'all',
        name: summaryFilters.name.trim() || ''
    })

    window.open(`/admin/dashboard/summary-report/pdf?${params.toString()}`, '_blank')
}
</script>

<template>

    <Head title="Cooperatives" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="coop-page">
            <div class="coop-header">
                <div class="coop-header-left">
                    <h1 class="coop-title">Cooperatives</h1>
                    <p class="coop-description">Manage cooperative inventory reports.</p>
                </div>

                <div class="coop-header-right">
                    <div class="controls-group">
                        <div class="inputs-row">
                            <span class="label-black">Reporting Period:</span>

                            <select class="report-badge" v-model="selectedDate" @change="filterDate">
                                <option v-for="date in reportingDates" :key="date.id" :value="date.id">
                                    {{ date.reporting_month }}/{{ date.reporting_year }}
                                </option>
                            </select>

                            <button @click="openModal" class="coop-btn-primary">+</button>
                        </div>
                        <button @click="openSummaryModal" class="coop-btn-summary">
                            Summary Report
                        </button>
                    </div>
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
                </div>

                <div class="coop-card-header">
                    <div class="coop-filter location-grid">
                        <div>
                            <label class="form-label">Region</label>
                            <SelectSearch :clearOnFocus="true" :items="regions" itemLabelKey="name" itemKeyProp="code"
                                v-model:search="locationSearch.region_code" :modelValue="locationFilter.region_code"
                                v-model:open="openState.region_code"
                                @update:model-value="val => onLocationModelUpdate('region_code', val)"
                                @select="val => onSelectLocation('region_code', val)" />
                        </div>

                        <div>
                            <label class="form-label">Province</label>
                            <SelectSearch :clearOnFocus="true" :items="filteredProvinces" itemLabelKey="name"
                                itemKeyProp="code" v-model:search="locationSearch.province_code"
                                :modelValue="locationFilter.province_code" v-model:open="openState.province_code"
                                @update:model-value="val => onLocationModelUpdate('province_code', val)"
                                @select="val => onSelectLocation('province_code', val)" />
                        </div>

                        <div>
                            <label class="form-label">City</label>
                            <SelectSearch :clearOnFocus="true" :items="filteredCities" itemLabelKey="name"
                                itemKeyProp="code" v-model:search="locationSearch.city_code"
                                :modelValue="locationFilter.city_code" v-model:open="openState.city_code"
                                @update:model-value="val => onLocationModelUpdate('city_code', val)"
                                @select="val => onSelectLocation('city_code', val)" />
                        </div>

                        <div>
                            <label class="form-label">Barangay</label>
                            <SelectSearch :clearOnFocus="true" :items="filteredBarangays" itemLabelKey="name"
                                itemKeyProp="code" v-model:search="locationSearch.barangay_code"
                                :modelValue="locationFilter.barangay_code" v-model:open="openState.barangay_code"
                                @update:model-value="val => onLocationModelUpdate('barangay_code', val)"
                                @select="val => onSelectLocation('barangay_code', val)" />
                        </div>
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
                                No Cooperative Registered on Form
                            </td>
                        </tr>

                        <tr v-else-if="filteredCooperatives.length === 0">
                            <td colspan="2" class="coop-empty">
                                {{ emptyStateMessage }}
                            </td>
                        </tr>

                        <tr v-for="coop in paginatedCooperatives" :key="coop.id" class="coop-row"
                            @click="openCoop(coop.id)">
                            <td>{{ coop.name }}</td>
                            <td>{{ inventoryCounts[coop.id] ?? 0 }}</td>
                        </tr>

                        <tr class="coop-row create-row" @click="goToCreatePage">
                            <td colspan="2" style="text-align: center; font-weight: 600; cursor: pointer;">
                                + Add New Cooperative
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="coop-pagination">
                    <div v-if="filteredCooperatives.length === 0" class="pagination-info">
                        {{ emptyStateMessage }}
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

        <!-- reporting date modal -->
        <div v-if="showModal" class="modal-overlay">
            <div class="modal-box small-modal">
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

        <!-- summary modal -->
        <div v-if="showSummaryModal" class="modal-overlay">
            <div class="modal-box summary-modal">
                <div class="summary-header">
                    <h2 class="modal-title">Inventory Summary</h2>
                </div>

                <div class="summary-switch">
                    <button class="summary-tab" :class="{ active: summaryView === 'cooperative' }"
                        @click="summaryView = 'cooperative'">
                        Cooperatives
                    </button>

                    <button class="summary-tab" :class="{ active: summaryView === 'inventory' }"
                        @click="summaryView = 'inventory'">
                        Inventory
                    </button>
                </div>

                <div class="summary-filters location-grid">
                    <div>
                        <label class="form-label">Region</label>
                        <SelectSearch :clearOnFocus="true" :items="regions" itemLabelKey="name" itemKeyProp="code"
                            v-model:search="summaryLocationSearch.region_code" :modelValue="summaryFilters.region_code"
                            v-model:open="summaryOpenState.region_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('region_code', val)"
                            @select="val => onSelectSummaryLocation('region_code', val)" />
                    </div>

                    <div>
                        <label class="form-label">Province</label>
                        <SelectSearch :clearOnFocus="true" :items="summaryFilteredProvinces" itemLabelKey="name"
                            itemKeyProp="code" v-model:search="summaryLocationSearch.province_code"
                            :modelValue="summaryFilters.province_code" v-model:open="summaryOpenState.province_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('province_code', val)"
                            @select="val => onSelectSummaryLocation('province_code', val)" />
                    </div>

                    <div>
                        <label class="form-label">City</label>
                        <SelectSearch :clearOnFocus="true" :items="summaryFilteredCities" itemLabelKey="name"
                            itemKeyProp="code" v-model:search="summaryLocationSearch.city_code"
                            :modelValue="summaryFilters.city_code" v-model:open="summaryOpenState.city_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('city_code', val)"
                            @select="val => onSelectSummaryLocation('city_code', val)" />
                    </div>

                    <div>
                        <label class="form-label">Barangay</label>
                        <SelectSearch :clearOnFocus="true" :items="summaryFilteredBarangays" itemLabelKey="name"
                            itemKeyProp="code" v-model:search="summaryLocationSearch.barangay_code"
                            :modelValue="summaryFilters.barangay_code" v-model:open="summaryOpenState.barangay_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('barangay_code', val)"
                            @select="val => onSelectSummaryLocation('barangay_code', val)" />
                    </div>
                    <div >
                        <label class="form-label">Search Name</label>
                        <input v-model="summaryFilters.name" type="text" placeholder="Search..."
                            class="coop-search" />
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <select v-model="summaryFilters.category" class="coop-select">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.value" :value="category.value">
                                {{ category.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Status</label>
                        <select v-model="summaryFilters.status" class="coop-select">
                            <option value="all">All</option>
                            <option value="serviceable">Serviceable</option>
                            <option value="unserviceable">Unserviceable</option>
                        </select>
                    </div>
                </div>

                <div class="summary-table-wrap">
                    <!-- INVENTORY VIEW -->
                    <template v-if="summaryView === 'inventory'">
                        <table class="coop-table summary-table">
                            <thead>
                                <tr>
                                    <th>{{ summaryFilters.category || 'Category / Name' }}</th>
                                    <th>Quantity</th>
                                    <th>Cooperative</th>
                                    <th>Coop Location</th>
                                    <th>Item Location</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="inventoryGroupedRows.length">
                                    <template v-for="group in inventoryGroupedRows" :key="group.key">
                                        <tr v-for="(row, index) in group.rows" :key="`${group.key}-${index}`">
                                            <td>
                                                <template v-if="index === 0">
                                                    <div class="group-title">
                                                        <div class="group-category">{{ group.category }}</div>
                                                        <div class="group-name">{{ group.itemName }}</div>
                                                    </div>
                                                </template>
                                            </td>
                                            <td>{{ row.display_quantity }}</td>
                                            <td>{{ row.coop_name }}</td>
                                            <td>{{ row.coop_location || '-' }}</td>
                                            <td>{{ row.item_location || '-' }}</td>
                                            <td>
                                                {{ formatMoney(row.value) }} ₱ x {{ row.display_quantity }}
                                                ({{ formatMoney(row.total) }} ₱)
                                            </td>
                                        </tr>
                                    </template>
                                </template>

                                <tr v-else>
                                    <td colspan="6" class="coop-empty">
                                        No summary records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>

                    <!-- COOPERATIVE VIEW -->
                    <template v-else>
                        <table class="coop-table summary-table">
                            <thead>
                                <tr>
                                    <th>Coop Name</th>
                                    <th>Item Name</th>
                                    <th>Coop Location</th>
                                    <th>Location</th>
                                    <th>Amount</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="cooperativeGroupedRows.length">
                                    <template v-for="group in cooperativeGroupedRows" :key="group.key">
                                        <tr v-for="(row, index) in group.item_rows" :key="`${group.key}-${index}`">
                                            <td>
                                                <template v-if="index === 0">
                                                    {{ group.coop_name }}
                                                </template>
                                            </td>
                                            <td>{{ row.item_name }}</td>
                                            <td>{{ row.coop_location || '-' }}</td>
                                            <td>{{ row.item_location || '-' }}</td>
                                            <td>{{ formatMoney(row.value) }} ₱</td>
                                            <td>{{ row.display_quantity }}</td>
                                            <td>{{ formatMoney(row.total) }} ₱</td>
                                        </tr>
                                    </template>
                                </template>

                                <tr v-else>
                                    <td colspan="7" class="coop-empty">
                                        No summary records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>

                <div class="modal-actions">
                    <button @click="downloadSummaryPdf" class="modal-save">
                        Download as PDF
                    </button>
                    <button @click="closeSummaryModal" class="modal-cancel">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.coop-page {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.coop-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.coop-header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.coop-title {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.coop-description {
    margin: 0;
    color: #64748b;
}

.coop-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.report-label {
    font-size: 14px;
    color: #64748b;
}

.report-badge,
.coop-select,
.modal-input,
.coop-search {
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 10px 12px;
    background: white;
    width: 100%;
}

.coop-btn-primary,
.coop-btn-secondary,
.modal-save,
.modal-cancel,
.pagination-btn,
.summary-tab {
    border: none;
    border-radius: 10px;
    padding: 10px 14px;
    font-weight: 600;
    cursor: pointer;
}

.coop-btn-primary,
.modal-save,
.summary-tab.active,
.pagination-btn.active {
    background: #111827;
    color: white;
}

.coop-btn-secondary {
    background: #e5e7eb;
    color: #111827;
}

.modal-cancel,
.pagination-btn,
.summary-tab {
    background: #f3f4f6;
    color: #111827;
}

.coop-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.coop-card-header {
    margin-bottom: 14px;
}

.coop-filter {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.location-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    color: #374151;
}

.coop-table {
    width: 100%;
    border-collapse: collapse;
}

.coop-table th,
.coop-table td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

.coop-row {
    cursor: pointer;
}

.coop-row:hover {
    background: #f9fafb;
}

.coop-empty {
    text-align: center;
    color: #6b7280;
    padding: 24px;
}

.coop-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    gap: 12px;
    flex-wrap: wrap;
}

.pagination-info {
    color: #6b7280;
    font-size: 14px;
}

.pagination-controls {
    display: flex;
    gap: 8px;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    padding: 20px;
}

.modal-box {
    background: white;
    border-radius: 18px;
    width: 100%;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2);
}

.small-modal {
    max-width: 420px;
    padding: 20px;
}

.summary-modal {
    max-width: 1300px;
    padding: 20px;
    max-height: 90vh;
    overflow: auto;
}

.modal-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
}

.modal-actions {
    margin-top: 16px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.icon-close {
    min-width: 40px;
    padding: 10px;
}

.summary-switch {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

.summary-filters {
    margin-bottom: 18px;
}

.summary-table-wrap {
    overflow-x: auto;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
}

.summary-table th,
.summary-table td {
    color: #111827 !important;
    border-bottom: 1px solid #e5e7eb;
}

.summary-table thead th {
    position: sticky;
    top: 0;
    background: #ffffff !important;
    z-index: 1;
    color: #111827 !important;
    font-weight: 700;
}

.summary-table tbody td {
    background: #ffffff;
    color: #111827 !important;
}

.group-title {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.group-category {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #6b7280;
}

.group-name {
    font-weight: 700;
}

@media (max-width: 1100px) {
    .location-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .location-grid {
        grid-template-columns: 1fr;
    }

    .coop-pagination {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>