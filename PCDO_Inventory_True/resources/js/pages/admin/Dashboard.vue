    <script setup lang="ts">
    import { Head, router } from '@inertiajs/vue3'
    import { ref, computed, reactive, watch } from 'vue'
    import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'
    import AppLayout from '@/layouts/AppLayout.vue'
    import type { BreadcrumbItem } from '@/types';
    import SelectSearch from '@/components/SelectSearch.vue'

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
        router.visit('/admin/cooperatives/create')
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
                        <button @click="openModal" class="coop-btn-primary">
                            +
                        </button>
                    </div>
                </div>

                <div class="coop-card">
                    <div class="coop-card-header">
                        <div class="coop-filter">
                            <input v-model="search" type="text" placeholder="Search cooperative..."
                                class="coop-search" />
                            <select v-model="inventoryFilter" class="coop-select">
                                <option value="all">All Cooperatives</option>
                                <option value="with-inventory">With Inventory Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="coop-card-header">
                        <div class="coop-filter">
                            <div>
                                <label class="form-label">Region</label>
                                <SelectSearch :items="regions" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="locationSearch.region_code" :modelValue="locationFilter.region_code"
                                    v-model:open="openState.region_code"
                                    @update:model-value="val => onLocationModelUpdate('region_code', val)"
                                    @select="val => onSelectLocation('region_code', val)" />
                            </div>

                            <div>
                                <label class="form-label">Province</label>
                                <SelectSearch :items="filteredProvinces" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="locationSearch.province_code"
                                    :modelValue="locationFilter.province_code" v-model:open="openState.province_code"
                                    @update:model-value="val => onLocationModelUpdate('province_code', val)"
                                    @select="val => onSelectLocation('province_code', val)" />
                            </div>

                            <div>
                                <label class="form-label">City</label>
                                <SelectSearch :items="filteredCities" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="locationSearch.city_code" :modelValue="locationFilter.city_code"
                                    v-model:open="openState.city_code"
                                    @update:model-value="val => onLocationModelUpdate('city_code', val)"
                                    @select="val => onSelectLocation('city_code', val)" />
                            </div>

                            <div>
                                <label class="form-label">Barangay</label>
                                <SelectSearch :items="filteredBarangays" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="locationSearch.barangay_code"
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
                                    No cooperatives found for "{{ search }}"
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