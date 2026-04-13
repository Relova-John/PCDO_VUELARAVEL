<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import type { BreadcrumbItem } from '@/types'
import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'

type LocationFields = 'region_code' | 'province_code' | 'city_code' | 'barangay_code'

type CooperativeRow = {
    id: number
    name: string
    region_code: string | number | null
    province_code: string | number | null
    city_code: string | number | null
    barangay_code: string | number | null
}

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
    cooperatives: CooperativeRow[]
    inventoryCounts: Record<number, number>
    reportingDate: any
    reportingDates: any[]
    selectedReportingDate: number
    locationScope: string | null
    locationName: string | null
    assignedLocation: {
        region_code?: string | number | null
        province_code?: string | number | null
        city_code?: string | number | null
        barangay_code?: string | number | null
    }
    availableLocationCodes: {
        region_codes: Array<string | number>
        province_codes: Array<string | number>
        city_codes: Array<string | number>
        barangay_codes: Array<string | number>
    }
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
    breadcrumbs: BreadcrumbItem[]
    categories: { value: string; label: string }[]
    inventorySummaryRows: SummaryRow[]
}>()

const selectedDate = ref(props.selectedReportingDate)
const search = ref('')
const inventoryFilter = ref<'all' | 'with-inventory'>('with-inventory')
const currentPage = ref(1)
const perPage = 10
const showSummaryModal = ref(false)

function filterDate() {
    router.get(
        '/officer/dashboard',
        {
            reporting_date_id: selectedDate.value,
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

function openSummaryModalFn() {
    showSummaryModal.value = true
}

function closeSummaryModal() {
    showSummaryModal.value = false
}

function findNameByCode<T extends { code: string | number; name: string }>(
    items: T[] | undefined | null,
    code: string | number | null | undefined
) {
    if (!Array.isArray(items) || code === null || code === undefined || code === '') {
        return ''
    }

    return items.find(item => String(item.code) === String(code))?.name ?? ''
}

function normalizeCode(value: string | number | null | undefined) {
    return value === null || value === undefined || value === '' ? '' : String(value)
}

function uniqueStringCodes(values: Array<string | number | null | undefined>) {
    return [...new Set(values.map(normalizeCode).filter(Boolean))]
}

const assignedLocation = computed(() => ({
    region_code: normalizeCode(props.assignedLocation?.region_code),
    province_code: normalizeCode(props.assignedLocation?.province_code),
    city_code: normalizeCode(props.assignedLocation?.city_code),
    barangay_code: normalizeCode(props.assignedLocation?.barangay_code),
}))

const availableRegionCodes = computed(() =>
    uniqueStringCodes(props.availableLocationCodes?.region_codes ?? [])
)

const availableProvinceCodes = computed(() =>
    uniqueStringCodes(props.availableLocationCodes?.province_codes ?? [])
)

const availableCityCodes = computed(() =>
    uniqueStringCodes(props.availableLocationCodes?.city_codes ?? [])
)

const availableBarangayCodes = computed(() =>
    uniqueStringCodes(props.availableLocationCodes?.barangay_codes ?? [])
)

const filteredRegions = computed(() => {
    return (props.regions ?? []).filter(region =>
        availableRegionCodes.value.length === 0 ||
        availableRegionCodes.value.includes(String(region.code))
    )
})

const filteredProvincesBase = computed(() => {
    return (props.provinces ?? []).filter(province =>
        availableProvinceCodes.value.length === 0 ||
        availableProvinceCodes.value.includes(String(province.code))
    )
})

const filteredCitiesBase = computed(() => {
    return (props.cities ?? []).filter(city =>
        availableCityCodes.value.length === 0 ||
        availableCityCodes.value.includes(String(city.code))
    )
})

const filteredBarangaysBase = computed(() => {
    return (props.barangays ?? []).filter(barangay =>
        availableBarangayCodes.value.length === 0 ||
        availableBarangayCodes.value.includes(String(barangay.code))
    )
})

const preselectedRegionCode = computed(() => {
    if (assignedLocation.value.region_code) return assignedLocation.value.region_code
    if (availableRegionCodes.value.length === 1) return availableRegionCodes.value[0]

    const mimaropa = filteredRegions.value.find(region =>
        String(region.name).trim().toLowerCase() === 'mimaropa'
    )

    if (mimaropa) return String(mimaropa.code)

    return ''
})

const preselectedProvinceCode = computed(() => {
    if (assignedLocation.value.province_code) return assignedLocation.value.province_code
    if (availableProvinceCodes.value.length === 1) return availableProvinceCodes.value[0]
    return ''
})

const preselectedCityCode = computed(() => {
    if (assignedLocation.value.city_code) return assignedLocation.value.city_code
    if (availableCityCodes.value.length === 1) return availableCityCodes.value[0]
    return ''
})

const preselectedBarangayCode = computed(() => {
    if (assignedLocation.value.barangay_code) return assignedLocation.value.barangay_code
    if (availableBarangayCodes.value.length === 1) return availableBarangayCodes.value[0]
    return ''
})

const locationFilter = reactive<Record<LocationFields, string>>({
    region_code: preselectedRegionCode.value,
    province_code: '',
    city_code: '',
    barangay_code: '',
})

const locationSearch = reactive<Record<LocationFields, string>>({
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: '',
})

const openState = reactive<Record<LocationFields, boolean>>({
    region_code: false,
    province_code: false,
    city_code: false,
    barangay_code: false,
})

const dependencyMap: Record<LocationFields, LocationFields[]> = {
    region_code: ['province_code', 'city_code', 'barangay_code'],
    province_code: ['city_code', 'barangay_code'],
    city_code: ['barangay_code'],
    barangay_code: [],
}

const filteredProvinces = computed(() => {
    let result = filteredProvincesBase.value

    if (locationFilter.region_code) {
        result = result.filter(
            province => String(province.region_code) === String(locationFilter.region_code)
        )
    }

    return result
})

const filteredCities = computed(() => {
    let result = filteredCitiesBase.value

    if (locationFilter.province_code) {
        result = result.filter(
            city => String(city.province_code) === String(locationFilter.province_code)
        )
    }

    return result
})

const filteredBarangays = computed(() => {
    let result = filteredBarangaysBase.value

    if (locationFilter.city_code) {
        result = result.filter(
            barangay => String(barangay.city_code) === String(locationFilter.city_code)
        )
    }

    return result
})

const isRegionLocked = computed(() => filteredRegions.value.length === 1)
const isProvinceLocked = computed(() => !!locationFilter.region_code && filteredProvinces.value.length === 1)
const isCityLocked = computed(() => !!locationFilter.province_code && filteredCities.value.length === 1)
const isBarangayLocked = computed(() => !!locationFilter.city_code && filteredBarangays.value.length === 1)

const isProvinceDisabled = computed(() => !locationFilter.region_code || filteredProvinces.value.length === 0)
const isCityDisabled = computed(() => !locationFilter.province_code || filteredCities.value.length === 0)
const isBarangayDisabled = computed(() => !locationFilter.city_code || filteredBarangays.value.length === 0)

function syncLocationSearch() {
    locationSearch.region_code = findNameByCode(props.regions, locationFilter.region_code)
    locationSearch.province_code = findNameByCode(props.provinces, locationFilter.province_code)
    locationSearch.city_code = findNameByCode(props.cities, locationFilter.city_code)
    locationSearch.barangay_code = findNameByCode(props.barangays, locationFilter.barangay_code)
}

function applyAutoSelections() {
    if (!locationFilter.region_code && preselectedRegionCode.value) {
        locationFilter.region_code = preselectedRegionCode.value
    }

    if (!locationFilter.region_code) {
        locationFilter.province_code = ''
        locationFilter.city_code = ''
        locationFilter.barangay_code = ''
        syncLocationSearch()
        return
    }

    if (
        locationFilter.province_code &&
        !filteredProvinces.value.some(item => String(item.code) === String(locationFilter.province_code))
    ) {
        locationFilter.province_code = ''
    }

    if (
        !locationFilter.province_code &&
        (
            preselectedProvinceCode.value &&
            filteredProvinces.value.some(item => String(item.code) === String(preselectedProvinceCode.value))
        )
    ) {
        locationFilter.province_code = preselectedProvinceCode.value
    } else if (!locationFilter.province_code && filteredProvinces.value.length === 1) {
        locationFilter.province_code = String(filteredProvinces.value[0].code)
    }

    if (!locationFilter.province_code) {
        locationFilter.city_code = ''
        locationFilter.barangay_code = ''
        syncLocationSearch()
        return
    }

    if (
        locationFilter.city_code &&
        !filteredCities.value.some(item => String(item.code) === String(locationFilter.city_code))
    ) {
        locationFilter.city_code = ''
    }

    if (
        !locationFilter.city_code &&
        (
            preselectedCityCode.value &&
            filteredCities.value.some(item => String(item.code) === String(preselectedCityCode.value))
        )
    ) {
        locationFilter.city_code = preselectedCityCode.value
    } else if (!locationFilter.city_code && filteredCities.value.length === 1) {
        locationFilter.city_code = String(filteredCities.value[0].code)
    }

    if (!locationFilter.city_code) {
        locationFilter.barangay_code = ''
        syncLocationSearch()
        return
    }

    if (
        locationFilter.barangay_code &&
        !filteredBarangays.value.some(item => String(item.code) === String(locationFilter.barangay_code))
    ) {
        locationFilter.barangay_code = ''
    }

    if (
        !locationFilter.barangay_code &&
        (
            preselectedBarangayCode.value &&
            filteredBarangays.value.some(item => String(item.code) === String(preselectedBarangayCode.value))
        )
    ) {
        locationFilter.barangay_code = preselectedBarangayCode.value
    } else if (!locationFilter.barangay_code && filteredBarangays.value.length === 1) {
        locationFilter.barangay_code = String(filteredBarangays.value[0].code)
    }

    syncLocationSearch()
}

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

    applyAutoSelections()
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

    locationFilter[field] = value ? String(value) : ''

    if (!value) {
        locationSearch[field] = ''
        openState[field] = false

        dependencyMap[field].forEach(dep => {
            locationFilter[dep] = ''
            locationSearch[dep] = ''
            openState[dep] = false
        })
    }

    applyAutoSelections()
    currentPage.value = 1
}

applyAutoSelections()

const filteredCooperatives = computed(() => {
    let result = [...props.cooperatives]

    if (inventoryFilter.value === 'with-inventory') {
        result = result.filter(coop => (props.inventoryCounts[coop.id] ?? 0) > 0)
    }

    if (search.value.trim()) {
        const keyword = search.value.toLowerCase()
        result = result.filter(coop => coop.name.toLowerCase().includes(keyword))
    }

    if (locationFilter.region_code) {
        result = result.filter(
            coop => String(coop.region_code ?? '') === String(locationFilter.region_code)
        )
    }

    if (locationFilter.province_code) {
        result = result.filter(
            coop => String(coop.province_code ?? '') === String(locationFilter.province_code)
        )
    }

    if (locationFilter.city_code) {
        result = result.filter(
            coop => String(coop.city_code ?? '') === String(locationFilter.city_code)
        )
    }

    if (locationFilter.barangay_code) {
        result = result.filter(
            coop => String(coop.barangay_code ?? '') === String(locationFilter.barangay_code)
        )
    }

    return result.sort((a, b) => {
        const aCount = props.inventoryCounts[a.id] ?? 0
        const bCount = props.inventoryCounts[b.id] ?? 0
        return bCount - aCount
    })
})

const totalItems = computed(() => filteredCooperatives.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / perPage)))

const paginatedCooperatives = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filteredCooperatives.value.slice(start, start + perPage)
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
        filteredRegions.value.map(item => item.code).join(','),
        filteredProvinces.value.map(item => item.code).join(','),
        filteredCities.value.map(item => item.code).join(','),
        filteredBarangays.value.map(item => item.code).join(','),
    ],
    () => {
        applyAutoSelections()
    }
)

watch(
    () => [
        locationFilter.region_code,
        locationFilter.province_code,
        locationFilter.city_code,
        locationFilter.barangay_code,
        inventoryFilter.value,
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

const summaryView = ref<'inventory' | 'cooperative'>('inventory')

const summaryFilters = reactive({
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: '',
    category: '',
    status: 'all',
})

const summaryLocationSearch = reactive<Record<LocationFields, string>>({
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: '',
})

const summaryOpenState = reactive<Record<LocationFields, boolean>>({
    region_code: false,
    province_code: false,
    city_code: false,
    barangay_code: false,
})

function syncSummaryLocationSearch() {
    summaryLocationSearch.region_code = findNameByCode(props.regions, summaryFilters.region_code)
    summaryLocationSearch.province_code = findNameByCode(props.provinces, summaryFilters.province_code)
    summaryLocationSearch.city_code = findNameByCode(props.cities, summaryFilters.city_code)
    summaryLocationSearch.barangay_code = findNameByCode(props.barangays, summaryFilters.barangay_code)
}

const summaryFilteredRegions = computed(() => filteredRegions.value)

const summaryFilteredProvinces = computed(() => {
    let result = filteredProvincesBase.value

    if (summaryFilters.region_code) {
        result = result.filter(
            province => String(province.region_code) === String(summaryFilters.region_code)
        )
    }

    return result
})

const summaryFilteredCities = computed(() => {
    let result = filteredCitiesBase.value

    if (summaryFilters.province_code) {
        result = result.filter(
            city => String(city.province_code) === String(summaryFilters.province_code)
        )
    }

    return result
})

const summaryFilteredBarangays = computed(() => {
    let result = filteredBarangaysBase.value

    if (summaryFilters.city_code) {
        result = result.filter(
            barangay => String(barangay.city_code) === String(summaryFilters.city_code)
        )
    }

    return result
})

const isSummaryRegionLocked = computed(() => summaryFilteredRegions.value.length === 1)
const isSummaryProvinceLocked = computed(() => !!summaryFilters.region_code && summaryFilteredProvinces.value.length === 1)
const isSummaryCityLocked = computed(() => !!summaryFilters.province_code && summaryFilteredCities.value.length === 1)
const isSummaryBarangayLocked = computed(() => !!summaryFilters.city_code && summaryFilteredBarangays.value.length === 1)

const isSummaryProvinceDisabled = computed(() => !summaryFilters.region_code || summaryFilteredProvinces.value.length === 0)
const isSummaryCityDisabled = computed(() => !summaryFilters.province_code || summaryFilteredCities.value.length === 0)
const isSummaryBarangayDisabled = computed(() => !summaryFilters.city_code || summaryFilteredBarangays.value.length === 0)

function applySummaryAutoSelections() {
    if (!summaryFilters.region_code && preselectedRegionCode.value) {
        summaryFilters.region_code = preselectedRegionCode.value
    }

    if (!summaryFilters.region_code) {
        summaryFilters.province_code = ''
        summaryFilters.city_code = ''
        summaryFilters.barangay_code = ''
        syncSummaryLocationSearch()
        return
    }

    if (
        summaryFilters.province_code &&
        !summaryFilteredProvinces.value.some(item => String(item.code) === String(summaryFilters.province_code))
    ) {
        summaryFilters.province_code = ''
    }

    if (
        !summaryFilters.province_code &&
        (
            preselectedProvinceCode.value &&
            summaryFilteredProvinces.value.some(item => String(item.code) === String(preselectedProvinceCode.value))
        )
    ) {
        summaryFilters.province_code = preselectedProvinceCode.value
    } else if (!summaryFilters.province_code && summaryFilteredProvinces.value.length === 1) {
        summaryFilters.province_code = String(summaryFilteredProvinces.value[0].code)
    }

    if (!summaryFilters.province_code) {
        summaryFilters.city_code = ''
        summaryFilters.barangay_code = ''
        syncSummaryLocationSearch()
        return
    }

    if (
        summaryFilters.city_code &&
        !summaryFilteredCities.value.some(item => String(item.code) === String(summaryFilters.city_code))
    ) {
        summaryFilters.city_code = ''
    }

    if (
        !summaryFilters.city_code &&
        (
            preselectedCityCode.value &&
            summaryFilteredCities.value.some(item => String(item.code) === String(preselectedCityCode.value))
        )
    ) {
        summaryFilters.city_code = preselectedCityCode.value
    } else if (!summaryFilters.city_code && summaryFilteredCities.value.length === 1) {
        summaryFilters.city_code = String(summaryFilteredCities.value[0].code)
    }

    if (!summaryFilters.city_code) {
        summaryFilters.barangay_code = ''
        syncSummaryLocationSearch()
        return
    }

    if (
        summaryFilters.barangay_code &&
        !summaryFilteredBarangays.value.some(item => String(item.code) === String(summaryFilters.barangay_code))
    ) {
        summaryFilters.barangay_code = ''
    }

    if (
        !summaryFilters.barangay_code &&
        (
            preselectedBarangayCode.value &&
            summaryFilteredBarangays.value.some(item => String(item.code) === String(preselectedBarangayCode.value))
        )
    ) {
        summaryFilters.barangay_code = preselectedBarangayCode.value
    } else if (!summaryFilters.barangay_code && summaryFilteredBarangays.value.length === 1) {
        summaryFilters.barangay_code = String(summaryFilteredBarangays.value[0].code)
    }

    syncSummaryLocationSearch()
}

function resetSummaryLocationChildren(field: LocationFields) {
    dependencyMap[field].forEach(dep => {
        summaryFilters[dep] = ''
        summaryLocationSearch[dep] = ''
        summaryOpenState[dep] = false
    })
}

function onSelectSummaryLocation(field: LocationFields, payload: { id: string; name: string }) {
    if (
        (field === 'region_code' && isSummaryRegionLocked.value) ||
        (field === 'province_code' && isSummaryProvinceLocked.value) ||
        (field === 'city_code' && isSummaryCityLocked.value) ||
        (field === 'barangay_code' && isSummaryBarangayLocked.value)
    ) {
        return
    }

    summaryFilters[field] = String(payload.id)
    summaryLocationSearch[field] = payload.name
    summaryOpenState[field] = false
    resetSummaryLocationChildren(field)
    applySummaryAutoSelections()
}

function onSummaryLocationModelUpdate(field: LocationFields, value: string | number) {
    if (
        (field === 'region_code' && isSummaryRegionLocked.value) ||
        (field === 'province_code' && isSummaryProvinceLocked.value) ||
        (field === 'city_code' && isSummaryCityLocked.value) ||
        (field === 'barangay_code' && isSummaryBarangayLocked.value)
    ) {
        return
    }

    summaryFilters[field] = value ? String(value) : ''

    if (!value) {
        summaryLocationSearch[field] = ''
        summaryOpenState[field] = false
        resetSummaryLocationChildren(field)
    }

    applySummaryAutoSelections()
}

applySummaryAutoSelections()

watch(
    () => [
        summaryFilteredRegions.value.map(item => item.code).join(','),
        summaryFilteredProvinces.value.map(item => item.code).join(','),
        summaryFilteredCities.value.map(item => item.code).join(','),
        summaryFilteredBarangays.value.map(item => item.code).join(','),
    ],
    () => {
        applySummaryAutoSelections()
    }
)

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
        maximumFractionDigits: 2,
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
                rows: [],
            })
        }

        map.get(key)!.rows.push({
            coop_name: row.coop_name,
            coop_location: row.coop_location,
            item_location: row.item_location,
            display_quantity: displayQuantity,
            value: row.value,
            total: row.value * displayQuantity,
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
                item_rows: [],
            })
        }

        map.get(key)!.item_rows.push({
            item_name: row.name,
            coop_location: row.coop_location,
            item_location: row.item_location,
            value: row.value,
            display_quantity: displayQuantity,
            total: row.value * displayQuantity,
        })
    }

    return [...map.values()].sort((a, b) => a.coop_name.localeCompare(b.coop_name))
})
</script>

<template>
    <Head title="Officer Dashboard" />
    <!-- <div class="fixed top-4 right-4 p-4 bg-white border rounded shadow z-50 max-h-96 overflow-y-auto">
        <pre>{{ props }}</pre>
    </div> -->

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="officer-page">
            <div class="officer-header">
                <div class="officer-header-left">
                    <h1 class="officer-title">Officer Dashboard</h1>
                    <p class="officer-description">
                        View inventory reports based on your assigned location access.
                    </p>

                    <div v-if="!locked" class="officer-location-badge">
                        <span class="badge-label">{{ locationScope }}</span>
                        <span v-if="locationName" class="badge-value">{{ locationName }}</span>
                    </div>
                </div>

                <div class="officer-header-right">
                    <div class="officer-controls-group">
                        <div class="officer-inputs-row">
                            <span class="officer-label-white">Reporting Period:</span>

                            <select class="officer-report-badge" v-model="selectedDate" @change="filterDate">
                                <option v-for="date in reportingDates" :key="date.id" :value="date.id">
                                    {{ date.reporting_month }}/{{ date.reporting_year }}
                                </option>
                            </select>
                        </div>

                        <button v-if="!locked" @click="openSummaryModalFn" class="officer-btn-summary">
                            Summary Report
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="locked" class="officer-card">
                <div class="p-6 max-w-md mx-auto">
                    <h2 class="text-xl font-semibold mb-2">No Location Access</h2>
                    <p class="text-sm text-gray-500 mb-4">
                        Your code determines which location data you are allowed to view.
                        Please contact your administrator if you believe this is an error or to request access.
                    </p>
                </div>
            </div>

            <div v-else class="officer-card">
                <div class="officer-card-header">
                    <div class="officer-filter-row-top">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search cooperative..."
                            class="officer-search"
                        />

                        <select v-model="inventoryFilter" class="officer-select">
                            <option value="all">All Cooperatives</option>
                            <option value="with-inventory">With Inventory Only</option>
                        </select>
                    </div>

                    <div class="officer-divider"></div>

                    <div class="officer-location-grid">
                        <div>
                            <label class="officer-form-label">Region</label>
                            <input
                                v-if="isRegionLocked"
                                :value="locationSearch.region_code"
                                type="text"
                                class="officer-search officer-locked-input"
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
                            <label class="officer-form-label">Province</label>
                            <input
                                v-if="isProvinceLocked"
                                :value="locationSearch.province_code"
                                type="text"
                                class="officer-search officer-locked-input"
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
                                :disabled="isProvinceDisabled"
                                @update:model-value="val => onLocationModelUpdate('province_code', val)"
                                @select="val => onSelectLocation('province_code', val)"
                            />
                        </div>

                        <div>
                            <label class="officer-form-label">City</label>
                            <input
                                v-if="isCityLocked"
                                :value="locationSearch.city_code"
                                type="text"
                                class="officer-search officer-locked-input"
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
                                :disabled="isCityDisabled"
                                @update:model-value="val => onLocationModelUpdate('city_code', val)"
                                @select="val => onSelectLocation('city_code', val)"
                            />
                        </div>

                        <div>
                            <label class="officer-form-label">Barangay</label>
                            <input
                                v-if="isBarangayLocked"
                                :value="locationSearch.barangay_code"
                                type="text"
                                class="officer-search officer-locked-input"
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
                                :disabled="isBarangayDisabled"
                                @update:model-value="val => onLocationModelUpdate('barangay_code', val)"
                                @select="val => onSelectLocation('barangay_code', val)"
                            />
                        </div>
                    </div>
                </div>

                <table class="officer-table">
                    <thead>
                        <tr>
                            <th>Cooperative</th>
                            <th>Inventory Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cooperatives.length === 0">
                            <td colspan="2" class="officer-empty">
                                No cooperatives available for your assigned location
                            </td>
                        </tr>

                        <tr v-else-if="filteredCooperatives.length === 0">
                            <td colspan="2" class="officer-empty">
                                {{ emptyStateMessage }}
                            </td>
                        </tr>

                        <tr
                            v-for="coop in paginatedCooperatives"
                            :key="coop.id"
                            class="officer-row"
                            @click="openCoop(coop.id)"
                        >
                            <td>{{ coop.name }}</td>
                            <td>{{ inventoryCounts[coop.id] ?? 0 }}</td>
                        </tr>

                        <tr class="officer-row create-row" @click="goToCreatePage">
                            <td colspan="2" style="text-align: center; font-weight: 600; cursor: pointer;">
                                + Submit New Cooperative
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="officer-pagination">
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

        <div v-if="showSummaryModal" class="officer-modal-overlay">
            <div class="officer-modal-box summary-modal">
                <div class="summary-header">
                    <h2 class="officer-modal-title">Inventory Summary</h2>

                    <button class="officer-modal-btn-cancel icon-close" @click="closeSummaryModal">
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
                            v-if="isSummaryRegionLocked"
                            :value="summaryLocationSearch.region_code"
                            type="text"
                            class="officer-search locked-input"
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
                            v-if="isSummaryProvinceLocked"
                            :value="summaryLocationSearch.province_code"
                            type="text"
                            class="officer-search locked-input"
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
                            :disabled="isSummaryProvinceDisabled"
                            @update:model-value="val => onSummaryLocationModelUpdate('province_code', val)"
                            @select="val => onSelectSummaryLocation('province_code', val)"
                        />
                    </div>

                    <div>
                        <label class="form-label">City</label>

                        <input
                            v-if="isSummaryCityLocked"
                            :value="summaryLocationSearch.city_code"
                            type="text"
                            class="officer-search locked-input"
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
                            :disabled="isSummaryCityDisabled"
                            @update:model-value="val => onSummaryLocationModelUpdate('city_code', val)"
                            @select="val => onSelectSummaryLocation('city_code', val)"
                        />
                    </div>

                    <div>
                        <label class="form-label">Barangay</label>

                        <input
                            v-if="isSummaryBarangayLocked"
                            :value="summaryLocationSearch.barangay_code"
                            type="text"
                            class="officer-search locked-input"
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
                            :disabled="isSummaryBarangayDisabled"
                            @update:model-value="val => onSummaryLocationModelUpdate('barangay_code', val)"
                            @select="val => onSelectSummaryLocation('barangay_code', val)"
                        />
                    </div>

                    <div>
                        <label class="form-label">Category</label>
                        <select v-model="summaryFilters.category" class="officer-select">
                            <option value="">All Categories</option>
                            <option
                                v-for="category in categories"
                                :key="category.value"
                                :value="category.value"
                            >
                                {{ category.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Status</label>
                        <select v-model="summaryFilters.status" class="officer-select">
                            <option value="all">All</option>
                            <option value="serviceable">Serviceable</option>
                            <option value="unserviceable">Unserviceable</option>
                        </select>
                    </div>
                </div>

                <div v-if="summaryView === 'inventory'" class="summary-body">
                    <div v-if="inventoryGroupedRows.length === 0" class="officer-empty">
                        No inventory found.
                    </div>

                    <div
                        v-for="group in inventoryGroupedRows"
                        :key="group.key"
                        class="summary-group"
                    >
                        <div class="summary-group-title">
                            {{ group.itemName }}
                            <span v-if="group.category">({{ group.category }})</span>
                        </div>

                        <table class="officer-table">
                            <thead>
                                <tr>
                                    <th>Cooperative</th>
                                    <th>Coop Location</th>
                                    <th>Item Location</th>
                                    <th>Qty</th>
                                    <th>Value</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in group.rows" :key="`${group.key}-${index}`">
                                    <td>{{ row.coop_name }}</td>
                                    <td>{{ row.coop_location }}</td>
                                    <td>{{ row.item_location }}</td>
                                    <td>{{ row.display_quantity }}</td>
                                    <td>{{ formatMoney(row.value) }}</td>
                                    <td>{{ formatMoney(row.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="summary-body">
                    <div v-if="cooperativeGroupedRows.length === 0" class="officer-empty">
                        No cooperatives found.
                    </div>

                    <div
                        v-for="group in cooperativeGroupedRows"
                        :key="group.key"
                        class="summary-group"
                    >
                        <div class="summary-group-title">
                            {{ group.coop_name }}
                        </div>

                        <table class="officer-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Coop Location</th>
                                    <th>Item Location</th>
                                    <th>Qty</th>
                                    <th>Value</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in group.item_rows" :key="`${group.key}-${index}`">
                                    <td>{{ row.item_name }}</td>
                                    <td>{{ row.coop_location }}</td>
                                    <td>{{ row.item_location }}</td>
                                    <td>{{ row.display_quantity }}</td>
                                    <td>{{ formatMoney(row.value) }}</td>
                                    <td>{{ formatMoney(row.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>