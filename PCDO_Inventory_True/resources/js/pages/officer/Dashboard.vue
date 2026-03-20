<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import type { BreadcrumbItem } from '@/types'
import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'

type LocationFields = 'region_code' | 'province_code' | 'city_code' | 'barangay_code'

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
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search cooperative..."
                            class="coop-search"
                        />

                        <select v-model="inventoryFilter" class="coop-select">
                            <option value="all">All Cooperatives</option>
                            <option value="with-inventory">With Inventory Only</option>
                        </select>

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

                <div class="coop-card-header">
                    <div class="coop-filter">
                        <div>
                            <label class="form-label">Region</label>

                            <input
                                v-if="isRegionLocked"
                                :value="locationSearch.region_code"
                                type="text"
                                class="coop-search"
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
                                class="coop-search"
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
                                class="coop-search"
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
                                class="coop-search"
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
                                No cooperatives found for "{{ search }}"
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
                        No cooperatives found for "{{ search }}"
                    </div>

                    <div v-else-if="startItem === endItem" class="pagination-info">
                        Showing {{ startItem }} of {{ filteredCooperatives.length }} cooperatives
                    </div>

                    <div v-else class="pagination-info">
                        Showing {{ startItem }} - {{ endItem }} of {{ filteredCooperatives.length }} cooperatives
                    </div>

                    <div class="pagination-controls">
                        <button
                            v-if="!isFirstPage"
                            class="pagination-btn"
                            @click="goToPreviousPage"
                        >
                            Previous
                        </button>

                        <button class="pagination-btn active">
                            {{ currentPage }}
                        </button>

                        <button
                            v-if="!isLastPage"
                            class="pagination-btn"
                            @click="goToNextPage"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>