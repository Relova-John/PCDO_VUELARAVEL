<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import type { BreadcrumbItem } from '@/types'
import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'

type LocationFields = 'region_code' | 'province_code' | 'city_code' | 'barangay_code'

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
    locked: boolean
    cooperatives: any[]
    inventoryCounts: Record<number, number>
    inventoryStatus: Record<number, any>
    reportingDate: any
    reportingDates: any[]
    selectedReportingDate: number
    locationScope: string | null
    locationName: string | null
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
    breadcrumbs: BreadcrumbItem[]
    categories: { value: string, label: string }[]
    inventorySummaryRows: SummaryRow[]
}>()

const page = usePage<{
    errors: Record<string, string>
    flash: { success?: string }
}>()

const selectedDate = ref(props.selectedReportingDate)
const search = ref('')
const inventoryFilter = ref('with-inventory')
const currentPage = ref(1)
const perPage = 10

const showModal = ref(false)
const showSummaryModal = ref(false)

const accessForm = useForm({
    code: ''
})

function activateCode() {
    accessForm.post('/officer/dashboard/access-control/activate')
}

function filterDate() {
    router.get(
        '/officer/dashboard',
        {
            reporting_date_id: selectedDate.value
        },
        { preserveState: true }
    )
}

function openCoop(id: number) {
    router.visit(`/officer/dashboard/${id}?reporting_date_id=${selectedDate.value}`)
}

function goToCreatePage() {
    router.visit('/officer/create')
}

function openSummaryModal() {
    showSummaryModal.value = true
}

function closeSummaryModal() {
    showSummaryModal.value = false
}

function findNameByCode<T extends { code: string | number; name: string }>(
    items: T[],
    code: string
) {
    return items.find(item => String(item.code) === String(code))?.name ?? ''
}

const normalizedScope = computed(() => {
    const scope = (props.locationScope ?? '').toLowerCase().trim()

    if (scope.includes('barangay')) return 'barangay'
    if (scope.includes('city')) return 'city'
    if (scope.includes('municipality')) return 'city'
    if (scope.includes('province')) return 'province'
    if (scope.includes('region')) return 'region'

    return ''
})

const assignedLocation = computed(() => {
    const first = props.cooperatives[0]

    return {
        region_code: first?.region_code ? String(first.region_code) : '',
        province_code: first?.province_code ? String(first.province_code) : '',
        city_code: first?.city_code ? String(first.city_code) : '',
        barangay_code: first?.barangay_code ? String(first.barangay_code) : ''
    }
})

const isRegionLocked = computed(() => {
    return ['region', 'province', 'city', 'barangay'].includes(normalizedScope.value)
})

const isProvinceLocked = computed(() => {
    return ['province', 'city', 'barangay'].includes(normalizedScope.value)
})

const isCityLocked = computed(() => {
    return ['city', 'barangay'].includes(normalizedScope.value)
})

const isBarangayLocked = computed(() => {
    return normalizedScope.value === 'barangay'
})

const locationFilter = reactive({
    region_code: assignedLocation.value.region_code,
    province_code: isProvinceLocked.value ? assignedLocation.value.province_code : '',
    city_code: isCityLocked.value ? assignedLocation.value.city_code : '',
    barangay_code: isBarangayLocked.value ? assignedLocation.value.barangay_code : ''
})

const locationSearch = reactive({
    region_code: findNameByCode(props.regions, locationFilter.region_code),
    province_code: findNameByCode(props.provinces, locationFilter.province_code),
    city_code: findNameByCode(props.cities, locationFilter.city_code),
    barangay_code: findNameByCode(props.barangays, locationFilter.barangay_code)
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

function onSelectLocation(field: LocationFields, payload: { id: string; name: string }) {
    if (
        (field === 'region_code' && isRegionLocked.value) ||
        (field === 'province_code' && isProvinceLocked.value) ||
        (field === 'city_code' && isCityLocked.value) ||
        (field === 'barangay_code' && isBarangayLocked.value)
    ) {
        return
    }

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
    if (
        (field === 'region_code' && isRegionLocked.value) ||
        (field === 'province_code' && isProvinceLocked.value) ||
        (field === 'city_code' && isCityLocked.value) ||
        (field === 'barangay_code' && isBarangayLocked.value)
    ) {
        return
    }

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

const filteredRegions = computed(() => {
    if (isRegionLocked.value && assignedLocation.value.region_code) {
        return props.regions.filter(
            region => String(region.code) === String(assignedLocation.value.region_code)
        )
    }

    return props.regions
})

const filteredProvinces = computed(() => {
    const baseRegion = locationFilter.region_code || assignedLocation.value.region_code

    let result = props.provinces

    if (baseRegion) {
        result = result.filter(
            province => String(province.region_code) === String(baseRegion)
        )
    }

    if (isProvinceLocked.value && assignedLocation.value.province_code) {
        result = result.filter(
            province => String(province.code) === String(assignedLocation.value.province_code)
        )
    }

    return result
})

const filteredCities = computed(() => {
    const baseProvince = locationFilter.province_code || assignedLocation.value.province_code

    let result = props.cities

    if (baseProvince) {
        result = result.filter(
            city => String(city.province_code) === String(baseProvince)
        )
    }

    if (isCityLocked.value && assignedLocation.value.city_code) {
        result = result.filter(
            city => String(city.code) === String(assignedLocation.value.city_code)
        )
    }

    return result
})

const filteredBarangays = computed(() => {
    const baseCity = locationFilter.city_code || assignedLocation.value.city_code

    let result = props.barangays

    if (baseCity) {
        result = result.filter(
            barangay => String(barangay.city_code) === String(baseCity)
        )
    }

    if (isBarangayLocked.value && assignedLocation.value.barangay_code) {
        result = result.filter(
            barangay => String(barangay.code) === String(assignedLocation.value.barangay_code)
        )
    }

    return result
})

const filteredCooperatives = computed(() => {
    let result = props.cooperatives

    if (inventoryFilter.value === 'with-inventory') {
        result = result.filter(coop => (props.inventoryCounts[coop.id] ?? 0) > 0)
    }

    if (search.value) {
        result = result.filter((coop: any) =>
            coop.name.toLowerCase().includes(search.value.toLowerCase())
        )
    }

    if (locationFilter.region_code) {
        result = result.filter(
            (coop: any) => String(coop.region_code) === String(locationFilter.region_code)
        )
    }

    if (locationFilter.province_code) {
        result = result.filter(
            (coop: any) => String(coop.province_code) === String(locationFilter.province_code)
        )
    }

    if (locationFilter.city_code) {
        result = result.filter(
            (coop: any) => String(coop.city_code) === String(locationFilter.city_code)
        )
    }

    if (locationFilter.barangay_code) {
        result = result.filter(
            (coop: any) => String(coop.barangay_code) === String(locationFilter.barangay_code)
        )
    }

    result = [...result].sort((a: any, b: any) => {
        const aCount = props.inventoryCounts[a.id] ?? 0
        const bCount = props.inventoryCounts[b.id] ?? 0
        return bCount - aCount
    })

    return result
})

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
    if (!isFirstPage.value) currentPage.value--
}

function goToNextPage() {
    if (!isLastPage.value) currentPage.value++
}

watch(search, () => {
    currentPage.value = 1
})

watch(
    () => [
        locationFilter.region_code,
        locationFilter.province_code,
        locationFilter.city_code,
        locationFilter.barangay_code,
        inventoryFilter.value
    ],
    () => {
        currentPage.value = 1
    }
)

watch(filteredCooperatives, () => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = totalPages.value
    }
})

/* =========================
   SUMMARY MODAL LOGIC
========================= */

const summaryView = ref<'inventory' | 'cooperative'>('inventory')

const summaryFilters = reactive({
    region_code: assignedLocation.value.region_code,
    province_code: isProvinceLocked.value ? assignedLocation.value.province_code : '',
    city_code: isCityLocked.value ? assignedLocation.value.city_code : '',
    barangay_code: isBarangayLocked.value ? assignedLocation.value.barangay_code : '',
    category: '',
    status: 'all'
})

const summaryLocationSearch = reactive({
    region_code: findNameByCode(props.regions, summaryFilters.region_code),
    province_code: findNameByCode(props.provinces, summaryFilters.province_code),
    city_code: findNameByCode(props.cities, summaryFilters.city_code),
    barangay_code: findNameByCode(props.barangays, summaryFilters.barangay_code)
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
    if (
        (field === 'region_code' && isRegionLocked.value) ||
        (field === 'province_code' && isProvinceLocked.value) ||
        (field === 'city_code' && isCityLocked.value) ||
        (field === 'barangay_code' && isBarangayLocked.value)
    ) {
        return
    }

    summaryFilters[field] = String(payload.id)
    summaryLocationSearch[field] = payload.name
    summaryOpenState[field] = false
    resetSummaryLocationChildren(field)
}

function onSummaryLocationModelUpdate(field: LocationFields, value: string | number) {
    if (
        (field === 'region_code' && isRegionLocked.value) ||
        (field === 'province_code' && isProvinceLocked.value) ||
        (field === 'city_code' && isCityLocked.value) ||
        (field === 'barangay_code' && isBarangayLocked.value)
    ) {
        return
    }

    summaryFilters[field] = String(value)

    if (!value) {
        summaryLocationSearch[field] = ''
        summaryOpenState[field] = false
        resetSummaryLocationChildren(field)
    }
}

const summaryFilteredRegions = computed(() => {
    if (isRegionLocked.value && assignedLocation.value.region_code) {
        return props.regions.filter(
            region => String(region.code) === String(assignedLocation.value.region_code)
        )
    }

    return props.regions
})

const summaryFilteredProvinces = computed(() => {
    const baseRegion = summaryFilters.region_code || assignedLocation.value.region_code

    let result = props.provinces

    if (baseRegion) {
        result = result.filter(
            province => String(province.region_code) === String(baseRegion)
        )
    }

    if (isProvinceLocked.value && assignedLocation.value.province_code) {
        result = result.filter(
            province => String(province.code) === String(assignedLocation.value.province_code)
        )
    }

    return result
})

const summaryFilteredCities = computed(() => {
    const baseProvince = summaryFilters.province_code || assignedLocation.value.province_code

    let result = props.cities

    if (baseProvince) {
        result = result.filter(
            city => String(city.province_code) === String(baseProvince)
        )
    }

    if (isCityLocked.value && assignedLocation.value.city_code) {
        result = result.filter(
            city => String(city.code) === String(assignedLocation.value.city_code)
        )
    }

    return result
})

const summaryFilteredBarangays = computed(() => {
    const baseCity = summaryFilters.city_code || assignedLocation.value.city_code

    let result = props.barangays

    if (baseCity) {
        result = result.filter(
            barangay => String(barangay.city_code) === String(baseCity)
        )
    }

    if (isBarangayLocked.value && assignedLocation.value.barangay_code) {
        result = result.filter(
            barangay => String(barangay.code) === String(assignedLocation.value.barangay_code)
        )
    }

    return result
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

                    <select class="report-badge" v-model="selectedDate" @change="filterDate">
                        <option v-for="date in reportingDates" :key="date.id" :value="date.id">
                            {{ date.reporting_month }}/{{ date.reporting_year }}
                        </option>
                    </select>

                    <button v-if="!locked" @click="openSummaryModal" class="coop-btn-secondary">
                        Summary
                    </button>
                </div>
            </div>

            <div v-if="locked" class="coop-card">
                <div class="p-6 max-w-md mx-auto">
                    <h2 class="text-xl font-semibold mb-2">Enter Access Code</h2>
                    <p class="text-sm text-gray-500 mb-4">
                        Your code determines which location data you are allowed to view.
                    </p>

                    <form @submit.prevent="activateCode" class="space-y-4">
                        <input
                            v-model="accessForm.code"
                            type="text"
                            placeholder="Enter access code"
                            class="coop-search w-full"
                        />

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

                        <select v-model="inventoryFilter" class="coop-select">
                            <option value="all">All Cooperatives</option>
                            <option value="with-inventory">With Inventory Only</option>
                        </select>
                    </div>

                    <div class="text-sm font-medium text-gray-600">
                        {{ locationScope }}
                        <span v-if="locationName" class="text-blue-600"> - {{ locationName }}</span>
                    </div>
                </div>

                <div class="coop-card-header">
                    <div class="coop-filter location-grid">
                        <div>
                            <label class="form-label">Region</label>

                            <input
                                v-if="isRegionLocked"
                                :value="locationSearch.region_code"
                                type="text"
                                class="coop-search locked-input"
                                readonly
                            />

                            <SelectSearch
                                v-else
                                :items="filteredRegions"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="locationSearch.region_code"
                                :modelValue="locationFilter.region_code"
                                v-model:open="openState.region_code"
                                @update:model-value="val => onLocationModelUpdate('region_code', val)"
                                @select="val => onSelectLocation('region_code', val)"
                            />
                        </div>

                        <div>
                            <label class="form-label">Province</label>

                            <input
                                v-if="isProvinceLocked"
                                :value="locationSearch.province_code"
                                type="text"
                                class="coop-search locked-input"
                                readonly
                            />

                            <SelectSearch
                                v-else
                                :items="filteredProvinces"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="locationSearch.province_code"
                                :modelValue="locationFilter.province_code"
                                v-model:open="openState.province_code"
                                @update:model-value="val => onLocationModelUpdate('province_code', val)"
                                @select="val => onSelectLocation('province_code', val)"
                            />
                        </div>

                        <div>
                            <label class="form-label">City</label>

                            <input
                                v-if="isCityLocked"
                                :value="locationSearch.city_code"
                                type="text"
                                class="coop-search locked-input"
                                readonly
                            />

                            <SelectSearch
                                v-else
                                :items="filteredCities"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="locationSearch.city_code"
                                :modelValue="locationFilter.city_code"
                                v-model:open="openState.city_code"
                                @update:model-value="val => onLocationModelUpdate('city_code', val)"
                                @select="val => onSelectLocation('city_code', val)"
                            />
                        </div>

                        <div>
                            <label class="form-label">Barangay</label>

                            <input
                                v-if="isBarangayLocked"
                                :value="locationSearch.barangay_code"
                                type="text"
                                class="coop-search locked-input"
                                readonly
                            />

                            <SelectSearch
                                v-else
                                :items="filteredBarangays"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="locationSearch.barangay_code"
                                :modelValue="locationFilter.barangay_code"
                                v-model:open="openState.barangay_code"
                                @update:model-value="val => onLocationModelUpdate('barangay_code', val)"
                                @select="val => onSelectLocation('barangay_code', val)"
                            />
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
                                No cooperatives available for your assigned location
                            </td>
                        </tr>

                        <tr v-else-if="filteredCooperatives.length === 0">
                            <td colspan="2" class="coop-empty">
                                {{ emptyStateMessage }}
                            </td>
                        </tr>

                        <tr
                            v-for="coop in paginatedCooperatives"
                            :key="coop.id"
                            class="coop-row"
                            @click="openCoop(coop.id)"
                        >
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

        <!-- summary modal -->
        <div v-if="showSummaryModal" class="modal-overlay">
            <div class="modal-box summary-modal">
                <div class="summary-header">
                    <h2 class="modal-title">Inventory Summary</h2>

                    <button class="modal-cancel icon-close" @click="closeSummaryModal">
                        ✕
                    </button>
                </div>

                <div class="summary-switch">
                    <button
                        class="summary-tab"
                        :class="{ active: summaryView === 'cooperative' }"
                        @click="summaryView = 'cooperative'"
                    >
                        Cooperatives
                    </button>

                    <button
                        class="summary-tab"
                        :class="{ active: summaryView === 'inventory' }"
                        @click="summaryView = 'inventory'"
                    >
                        Inventory
                    </button>
                </div>

                <div class="summary-filters location-grid">
                    <div>
                        <label class="form-label">Region</label>

                        <input
                            v-if="isRegionLocked"
                            :value="summaryLocationSearch.region_code"
                            type="text"
                            class="coop-search locked-input"
                            readonly
                        />

                        <SelectSearch
                            v-else
                            :items="summaryFilteredRegions"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="summaryLocationSearch.region_code"
                            :modelValue="summaryFilters.region_code"
                            v-model:open="summaryOpenState.region_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('region_code', val)"
                            @select="val => onSelectSummaryLocation('region_code', val)"
                        />
                    </div>

                    <div>
                        <label class="form-label">Province</label>

                        <input
                            v-if="isProvinceLocked"
                            :value="summaryLocationSearch.province_code"
                            type="text"
                            class="coop-search locked-input"
                            readonly
                        />

                        <SelectSearch
                            v-else
                            :items="summaryFilteredProvinces"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="summaryLocationSearch.province_code"
                            :modelValue="summaryFilters.province_code"
                            v-model:open="summaryOpenState.province_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('province_code', val)"
                            @select="val => onSelectSummaryLocation('province_code', val)"
                        />
                    </div>

                    <div>
                        <label class="form-label">City</label>

                        <input
                            v-if="isCityLocked"
                            :value="summaryLocationSearch.city_code"
                            type="text"
                            class="coop-search locked-input"
                            readonly
                        />

                        <SelectSearch
                            v-else
                            :items="summaryFilteredCities"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="summaryLocationSearch.city_code"
                            :modelValue="summaryFilters.city_code"
                            v-model:open="summaryOpenState.city_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('city_code', val)"
                            @select="val => onSelectSummaryLocation('city_code', val)"
                        />
                    </div>

                    <div>
                        <label class="form-label">Barangay</label>

                        <input
                            v-if="isBarangayLocked"
                            :value="summaryLocationSearch.barangay_code"
                            type="text"
                            class="coop-search locked-input"
                            readonly
                        />

                        <SelectSearch
                            v-else
                            :items="summaryFilteredBarangays"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="summaryLocationSearch.barangay_code"
                            :modelValue="summaryFilters.barangay_code"
                            v-model:open="summaryOpenState.barangay_code"
                            @update:model-value="val => onSummaryLocationModelUpdate('barangay_code', val)"
                            @select="val => onSelectSummaryLocation('barangay_code', val)"
                        />
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
                                                {{ formatMoney(row.value) }} x {{ row.display_quantity }}
                                                ({{ formatMoney(row.total) }})
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
                                            <td>{{ formatMoney(row.value) }}</td>
                                            <td>{{ row.display_quantity }}</td>
                                            <td>{{ formatMoney(row.total) }}</td>
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
.coop-search {
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 10px 12px;
    background: white;
    width: 100%;
}

.locked-input {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
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

.summary-table th {
    position: sticky;
    top: 0;
    background: white;
    z-index: 1;
    color: #111827;
    font-weight: 700;
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