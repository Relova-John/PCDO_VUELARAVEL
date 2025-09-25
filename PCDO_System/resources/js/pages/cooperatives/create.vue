<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import type { Cooperative, Regions, Provinces, Municipalities, Barangays } from '@/types/cooperatives'
import { BreadcrumbItem } from '@/types'

const props = defineProps<{
    cooperatives: Cooperative[],
    breadcrumbs?: BreadcrumbItem[],
    regions: Regions[],
    provinces: Provinces[],
    municipalities: Municipalities[],
    barangays: Barangays[],
}>()

const form = useForm({
    id: '',
    name: '',
    region_id: null as number | null,
    province_id: null as number | null,
    municipality_id: null as number | null,
    barangay_id: null as number | null,
    asset_size: '',
    coop_type: '',
    status_category: 'New',
    bond_of_membership: '',
    area_of_operation: '',
    citizenship: 'Filipino',
    members_count: 0,
    total_asset: 0,
    net_surplus: 0,
})

const assetSizes = [
  { id: 'Micro', name: 'Micro' },
  { id: 'Small', name: 'Small' },
  { id: 'Medium', name: 'Medium' },
  { id: 'Large', name: 'Large' },
]

const coopTypes = [
  { id: 'Credit', name: 'Credit' },
  { id: 'Consumers', name: 'Consumers' },
  { id: 'Producers', name: 'Producers' },
  { id: 'Marketing', name: 'Marketing' },
  { id: 'Service', name: 'Service' },
  { id: 'Multipurpose', name: 'Multipurpose' },
  { id: 'Advocacy', name: 'Advocacy' },
  { id: 'Agrarian Reform', name: 'Agrarian Reform' },
  { id: 'Bank', name: 'Bank' },
  { id: 'Diary', name: 'Diary' },
  { id: 'Education', name: 'Education' },
  { id: 'Electric', name: 'Electric' },
  { id: 'Financial', name: 'Financial' },
  { id: 'Fishermen', name: 'Fishermen' },
  { id: 'Health Services', name: 'Health Services' },
  { id: 'Housing', name: 'Housing' },
  { id: 'Insurance', name: 'Insurance' },
  { id: 'Water Service', name: 'Water Service' },
  { id: 'Workers', name: 'Workers' },
  { id: 'Others', name: 'Others' },
]

const statusCategories = [
  { id: 'Reporting', name: 'Reporting' },
  { id: 'Non-Reporting', name: 'Non-Reporting' },
  { id: 'New', name: 'New' },
]

const bonds = [
  { id: 'Residential', name: 'Residential' },
  { id: 'Insitutional', name: 'Insitutional' },
  { id: 'Associational', name: 'Associational' },
  { id: 'Occupational', name: 'Occupational' },
  { id: 'Unspecified', name: 'Unspecified' },
]

const areas = [
  { id: 'Municipal', name: 'Municipal' },
  { id: 'Provincial', name: 'Provincial' },
]

const citizenships = [
  { id: 'Filipino', name: 'Filipino' },
  { id: 'Foreign', name: 'Foreign' },
  { id: 'Others', name: 'Others' },
]

// dropdown visibility
const showIdDropdown = ref(false)
const showNameDropdown = ref(false)

function hideIdDropdown() {
    setTimeout(() => (showIdDropdown.value = false), 200)
}
function hideNameDropdown() {
    setTimeout(() => (showNameDropdown.value = false), 200)
}

// SelectSearch bindings
const searchRegion = ref('')
const dropDownRegionOpen = ref(false)
const selectedRegion = ref<number | ''>('')

const searchProvince = ref('')
const dropDownProvinceOpen = ref(false)
const selectedProvince = ref<number | ''>('')

const searchMunicipality = ref('')
const dropDownMunicipalityOpen = ref(false)
const selectedMunicipality = ref<number | ''>('')

const searchBarangay = ref('')
const dropDownBarangayOpen = ref(false)
const selectedBarangay = ref<number | ''>('')

const searchAssetSize = ref('')
const dropDownAssetSizeOpen = ref(false)
const selectedAssetSize = ref('')

const searchCoopType = ref('')
const dropDownCoopTypeOpen = ref(false)
const selectedCoopType = ref('')

const searchStatusCategory = ref('')
const dropDownStatusCategoryOpen = ref(false)
const selectedStatusCategory = ref('')

const searchBond = ref('')
const dropDownBondOpen = ref(false)
const selectedBond = ref('')

const searchArea = ref('')
const dropDownAreaOpen = ref(false)
const selectedArea = ref('')

const searchCitizenship = ref('')
const dropDownCitizenshipOpen = ref(false)
const selectedCitizenship = ref('')

// filter for duplicate checking
const filteredCooperativesId = computed(() =>
    !form.id ? props.cooperatives : props.cooperatives.filter(c => c.id.toString().includes(form.id))
)
const filteredCooperativesName = computed(() =>
    !form.name ? props.cooperatives : props.cooperatives.filter(c =>
        c.name.toLowerCase().includes(form.name.toLowerCase())
    )
)

const isDuplicateId = computed(() =>
    props.cooperatives.some(c => c.id.toString() === form.id)
)

const isDuplicateName = computed(() =>
    props.cooperatives.some(c => c.name.toLowerCase() === form.name.toLowerCase())
)

// Filtered Locations
const filteredProvinces = computed(() =>
  selectedRegion.value
    ? props.provinces.filter(p => Number(p.region_id) === Number(selectedRegion.value))
    : []
)

const filteredMunicipalities = computed(() =>
  selectedProvince.value
    ? props.municipalities.filter(m => Number(m.province_id) === Number(selectedProvince.value))
    : []
)

const filteredBarangays = computed(() =>
  selectedMunicipality.value
    ? props.barangays.filter(b => Number(b.municipality_id) === Number(selectedMunicipality.value))
    : []
)

// helper
function isIdFormatValid(query: string) {
    const regex = /^\d{4}-\d{4,}$/ // e.g. 2024-1234
    return regex.test(query)
}


// SelectSearch handlers
function onRegionSelect(payload: { name: string, id: number }) {
    if (selectedRegion.value === payload.id) return
    selectedRegion.value = payload.id
    form.region_id = payload.id
    searchRegion.value = payload.name
    dropDownRegionOpen.value = false

    form.province_id = 0
    form.municipality_id = 0
    form.barangay_id = 0

    selectedProvince.value = ''
    selectedMunicipality.value = ''
    selectedBarangay.value = ''

    searchProvince.value = ''
    searchMunicipality.value = ''
    searchBarangay.value = ''
}

function onProvinceSelect(payload: { name: string, id: number }) {
    if (selectedProvince.value === payload.id) return
    selectedProvince.value = payload.id
    form.province_id = payload.id
    searchProvince.value = payload.name
    dropDownProvinceOpen.value = false

    form.municipality_id = 0
    form.barangay_id = 0

    selectedMunicipality.value = ''
    selectedBarangay.value = ''

    searchMunicipality.value = ''
    searchBarangay.value = ''
}

function onMunicipalitySelect(payload: { name: string, id: number }) {
    if (selectedMunicipality.value === payload.id) return
    selectedMunicipality.value = payload.id
    form.municipality_id = payload.id
    searchMunicipality.value = payload.name
    dropDownMunicipalityOpen.value = false

    form.barangay_id = 0
    selectedBarangay.value = ''
    searchBarangay.value = ''
}

function onBarangaySelect(payload: { name: string, id: number }) {
    if (selectedBarangay.value === payload.id) return
    selectedBarangay.value = payload.id
    form.barangay_id = payload.id
    searchBarangay.value = payload.name
    dropDownBarangayOpen.value = false
}

function handleSubmit() {
    const requiredFields = [
        'id',
        'name',
        'region_id',
        'province_id',
        'municipality_id',
        'barangay_id',
        'asset_size',
        'coop_type',
        'status_category',
        'bond_of_membership',
        'area_of_operation',
        'citizenship',
        'members_count',
        'total_asset',
        'net_surplus',
    ];

    const emptyFields = requiredFields.filter(field => {
        return form[field as keyof typeof form] === '' || form[field as keyof typeof form] === 0 || form[field as keyof typeof form] === null;
    });

    if (emptyFields.length) {
        alert(`Please fill in all required fields before submitting: ${emptyFields.join(', ')}`);
        return;
    }

    if (!isIdFormatValid(form.id)) {
        alert('Invalid ID format. Correct format is YYYY-XXXX (e.g., 2024-1234)');
        return;
    }

    if (isDuplicateId.value || isDuplicateName.value) {
        alert('Duplicate Cooperative ID or Name found.');
        return;
    }

    form.post('/cooperatives', {
        onError: (errors) => {
            console.log(errors);
            alert('Validation failed. Check your input.');
        }
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7x7 p-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                    Create Cooperative
                </h1>

                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Coop ID -->
                    <div>
                        <Label for="id">Register Number</Label>
                        <div v-if="!isIdFormatValid(form.id) && form.id" class="mt-1 text-red-500">
                            Invalid ID Format. Correct format is YYYY-XXXX (e.g., 2024-1234)
                        </div>
                        <div v-else-if="isDuplicateId" class="mt-1 text-red-500">
                            Duplicate Cooperative ID Found
                        </div>
                        <div v-else-if="isIdFormatValid(form.id) && form.id" class="mt-1 text-green-500">
                            Valid Data Input
                        </div> 
                        <div class="relative mt-1">
                            <input id="id" v-model="form.id" placeholder="Enter Register Number (e.g., 2024-1234)"
                                @focus="showIdDropdown = true" @blur="hideIdDropdown"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            <ul v-if="showIdDropdown"
                                class="absolute z-10 bg-white dark:bg-gray-700 border mt-1 w-full rounded-xl shadow">
                                <li v-for="coop in filteredCooperativesId" :key="coop.id"
                                    class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer">
                                    {{ coop.id }} - {{ coop.name }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Coop Name -->
                    <div>
                        <Label for="name">Cooperative Name</Label>
                        <div v-if="isDuplicateName" class="mt-1 text-red-500">
                            Duplicate Cooperative Name Found
                        </div>
                        <div v-else-if="form.name" class="mt-1 text-green-500">
                            Valid Data Input
                        </div>
                        <div class="relative mt-1">
                            <input id="name" v-model="form.name" placeholder="Enter Cooperative Name"
                                @focus="showNameDropdown = true" @blur="hideNameDropdown"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            <ul v-if="showNameDropdown"
                                class="absolute z-10 bg-white dark:bg-gray-700 border mt-1 w-full rounded-xl shadow">
                                <li v-for="coop in filteredCooperativesName" :key="coop.id"
                                    class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer">
                                    {{ coop.name }} ({{ coop.id }})
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Additional fields only when valid -->
                    <div v-if="
                        !isDuplicateId &&
                        isIdFormatValid(form.id) &&
                        !isDuplicateName &&
                        form.name
                    " class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t">
                        <!-- Region -->
                        <div>
                            <Label for="region" class="mb-2">Select Region</Label>
                            <SelectSearch 
                                id="region" 
                                :items="props.regions" 
                                itemLabelKey="name" 
                                itemKeyProp="id"
                                v-model:search="searchRegion" 
                                :modelValue="selectedRegion"
                                :open="dropDownRegionOpen" 
                                @select="onRegionSelect"
                                @update:open="val => dropDownRegionOpen = val" 
                                placeholder="Search Region"
                            />
                        </div>

                        <!-- Province -->
                        <div>
                            <Label for="province" class="mb-2">Select Province</Label>
                            <SelectSearch
                                id="province"
                                :items="filteredProvinces"
                                itemLabelKey="name"
                                itemKeyProp="id"
                                v-model:search="searchProvince"
                                :modelValue="selectedProvince"
                                :open="dropDownProvinceOpen"
                                @select="onProvinceSelect"
                                @update:open="val => dropDownProvinceOpen = val"
                                placeholder="Search Province"
                            />
                        </div>

                        <!-- Municipality -->
                        <div>
                            <Label for="municipality" class="mb-2">Select Municipality</Label>
                            <SelectSearch
                                id="municipality"
                                :items="filteredMunicipalities"
                                itemLabelKey="name"
                                itemKeyProp="id"
                                v-model:search="searchMunicipality"
                                :modelValue="selectedMunicipality"
                                :open="dropDownMunicipalityOpen"
                                @select="onMunicipalitySelect"
                                @update:open="val => dropDownMunicipalityOpen = val"
                                placeholder="Search Municipality"
                            />
                        </div>

                        <!-- Barangay -->
                        <div>
                            <Label for="barangay" class="mb-2">Select Barangay</Label>
                            <SelectSearch
                                id="barangay"
                                :items="filteredBarangays"
                                itemLabelKey="name"
                                itemKeyProp="id"
                                v-model:search="searchBarangay"
                                :modelValue="selectedBarangay"
                                :open="dropDownBarangayOpen"
                                @select="onBarangaySelect"
                                @update:open="val => dropDownBarangayOpen = val"
                                placeholder="Search Barangay"
                            />
                        </div>
                        <!-- Asset Size -->
                        <SelectSearch
                            id="asset_size"
                            :items="assetSizes"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model:search="searchAssetSize"
                            :modelValue="form.asset_size"
                            @select="val => form.asset_size = val.id"
                            :open="dropDownAssetSizeOpen"
                            @update:open="val => dropDownAssetSizeOpen = val"
                            placeholder="Select Asset Size"
                        />
                        <!-- Cooperative Type -->
                        <SelectSearch
                            id="coop_type"
                            :items="coopTypes"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model:search="searchCoopType"
                            :modelValue="form.coop_type"
                            @select="val => form.coop_type = val.id"
                            :open="dropDownCoopTypeOpen"
                            @update:open="val => dropDownCoopTypeOpen = val"
                            placeholder="Select Cooperative Type"
                        />
                        <!-- Status Category -->
                        <SelectSearch
                            id="status_category"
                            :items="statusCategories"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model:search="searchStatusCategory"
                            :modelValue="form.status_category"
                            @select="val => form.status_category = val.id"
                            :open="dropDownStatusCategoryOpen"
                            @update:open="val => dropDownStatusCategoryOpen = val"
                            placeholder="Select Status Category"
                        />
                        <!-- Bond of Membership -->
                        <SelectSearch
                            id="bond_of_membership"
                            :items="bonds"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model:search="searchBond"
                            :modelValue="form.bond_of_membership"
                            @select="val => form.bond_of_membership = val.id"
                            :open="dropDownBondOpen"
                            @update:open="val => dropDownBondOpen = val"
                            placeholder="Select Bond of Membership"
                        />
                        <!-- Area of Operation -->
                        <SelectSearch
                            id="area_of_operation"
                            :items="areas"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model:search="searchArea"
                            :modelValue="form.area_of_operation"
                            @select="val => form.area_of_operation = val.id"
                            :open="dropDownAreaOpen"
                            @update:open="val => dropDownAreaOpen = val"
                            placeholder="Select Area of Operation"
                        />
                        <!-- Citizenship -->
                        <SelectSearch
                            id="citizenship"
                            :items="citizenships"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model:search="searchCitizenship"
                            :modelValue="form.citizenship"
                            @select="val => form.citizenship = val.id"
                            :open="dropDownCitizenshipOpen"
                            @update:open="val => dropDownCitizenshipOpen = val"
                            placeholder="Select Citizenship"
                        />
                        <!-- Member Count -->
                        <div>
                            <Label class="mb-2">Member Count</Label>
                            <input v-model="form.members_count" type="number"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <!-- Total Asset -->
                        <div>
                            <Label class="mb-2">Total Asset</Label>
                            <input v-model="form.total_asset" type="number"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                        <!-- Net Surplus -->
                        <div>
                            <Label class="mb-2">Net Surplus</Label>
                            <input v-model="form.net_surplus" type="number"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                        <!-- Submit -->
                        <div class="pt-6 md:col-span-2">
                            <button type="submit"
                                class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow hover:bg-indigo-700 transition">
                                Create Cooperative
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
