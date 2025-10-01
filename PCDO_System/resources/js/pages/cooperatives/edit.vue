<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import type { Cooperative, Regions, Provinces, Cities, Barangays, Details } from '@/types/cooperatives'
import { BreadcrumbItem } from '@/types'
import FlashToast from '@/components/FlashToast.vue'
import { usePolling } from '@/composables/usePolling'

const props = defineProps<{
    cooperatives: Cooperative[],
    cooperative: Cooperative,
    breadcrumbs?: BreadcrumbItem[],
    regions: Regions[],
    provinces: Provinces[],
    cities: Cities[],
    barangays: Barangays[],
    details: Details,
}>()

const coop = props.cooperative
const details = props.details
const submitting = ref(false);

const form = useForm({
    id: coop.id ?? null,
    name: coop.name ?? null,
    region_code: details.region_code ?? null,
    province_code: details.province_code ?? null,
    city_code: details.city_code ?? null,
    barangay_code: details.barangay_code ?? null,
    asset_size: details.asset_size ?? null,
    coop_type: details.coop_type ?? null,
    status_category: details.status_category ?? null,
    bond_of_membership: details.bond_of_membership ?? null,
    area_of_operation: details.area_of_operation ?? null,
    citizenship: details.citizenship ?? null,
    members_count: details.members_count ?? null,
    total_asset: details.total_asset ?? null,
    net_surplus: details.net_surplus ?? null,
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

// Dropdown Controls
const showIdDropdown = ref(false)
const showNameDropdown = ref(false)

function hideIdDropdown() {
    setTimeout(() => (showIdDropdown.value = false), 200)
}
function hideNameDropdown() {
    setTimeout(() => (showNameDropdown.value = false), 200)
}

// Reactive State
const searchState = reactive({
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: ''
})

const selectedState = reactive({
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

const searchAssetSize = ref('')
const dropDownAssetSizeOpen = ref(false)
const searchCoopType = ref('')
const dropDownCoopTypeOpen = ref(false)
const searchStatusCategory = ref('')
const dropDownStatusCategoryOpen = ref(false)
const searchBond = ref('')
const dropDownBondOpen = ref(false)
const searchArea = ref('')
const dropDownAreaOpen = ref(false)
const searchCitizenship = ref('')
const dropDownCitizenshipOpen = ref(false)

// Filter Duplicates
const filteredCooperativesId = computed(() =>
    !form.id ? props.cooperatives : props.cooperatives.filter(c => c.id.toString().includes(form.id))
)
const filteredCooperativesName = computed(() =>
    !form.name ? props.cooperatives : props.cooperatives.filter(c =>
        c.name.toLowerCase().includes(form.name.toLowerCase())
    )
)

const isDuplicateId = computed(() =>
    props.cooperatives.some(c => c.id.toString() === form.id && c.id !== coop.id)
)

const isDuplicateName = computed(() =>
    props.cooperatives.some(c => c.name.toLowerCase() === form.name.toLowerCase() && c.id !== coop.id)
)

// Format Checker ID
function isIdFormatValid(query: string) {
    const regex = /^\d{4}-\d{4,}$/ // e.g. 2024-1234
    return regex.test(query)
}

// Filtered Locations
const filteredProvinces = computed(() => {
    if (form.region_code === '1300000000') {
        return []
    }
    return props.provinces.filter(p => String(p.region_code) === String(form.region_code))
})
const filteredCities = computed(() => {
    if (form.region_code === '1300000000') {
        return props.cities.filter(c => String(c.region_code) === '1300000000')
    }
    return props.cities.filter(c => String(c.province_code) === String(form.province_code))
})

const filteredBarangays = computed(() =>
    props.barangays.filter(b => String(b.city_code) === String(form.city_code))
)

const toastRef = ref<InstanceType<typeof FlashToast>>();

// SelectSearch handlers
const dependencyMap = {
    region_code: ["province_code", "city_code", "barangay_code"],
    province_code: ["city_code", "barangay_code"],
    city_code: ["barangay_code"],
    barangay_code: []
} as const

type LocationFields = "region_code" | "province_code" | "city_code" | "barangay_code"

function onSelect(
    field: LocationFields,
    payload: { id: string; name: string }
) {
    form[field] = String(payload.id)
    searchState[field] = payload.name
    selectedState[field] = String(payload.id)
    openState[field] = false

    dependencyMap[field].forEach(dep => {
        form[dep] = ""
        searchState[dep] = ""
        selectedState[dep] = ""
    })
}

function handleSubmit() {
    submitting.value = true;
    const requiredFields = [
        'id',
        'name',
        'region_code',
        'city_code',
        'barangay_code',
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
        return (
            form[field as keyof typeof form] === '' ||
            form[field as keyof typeof form] === 0 ||
            form[field as keyof typeof form] === null
        );
    });

    if (form.region_code !== '1300000000' && !form.province_code) {
        emptyFields.push('province_code');
    }

    if (emptyFields.length) {
        toastRef.value?.showToast({
            type: 'error',
            message: `Please fill in all required fields:\n- ${emptyFields.join('\n- ')}`
        });
        return;
    }

    if (!isIdFormatValid(form.id)) {
        toastRef.value?.showToast({
            type: 'error',
            message: 'Invalid ID format. Correct format is YYYY-XXXX (e.g., 2024-1234)'
        });
        return;
    }

    if (isDuplicateId.value || isDuplicateName.value) {
        toastRef.value?.showToast({
            type: 'error',
            message: 'Duplicate Cooperative ID or Name found.'
        });
        return;
    }

    form.put(`/cooperatives/${coop.id}`, {
        onError: (errors) => {
            submitting.value = false;
            const messages = Object.values(errors);
            if (messages.length) {
                toastRef.value?.showToast({
                    type: 'error',
                    message: messages.map(msg => `- ${msg}`).join('\n')
                });
            }
        },
        onSuccess: () => {
            submitting.value = false;
        },
    });
}

usePolling(["cooperatives"], 30000, () => submitting.value);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7x7 p-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                    Edit Cooperative
                </h1>

                <form @submit.prevent="handleSubmit" @keydown.enter.prevent class="space-y-6">
                    <!-- Coop ID -->
                    <div>
                        <Label for="coop_id">Register Number</Label>
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
                            <input id="coop_id" v-model="form.id" placeholder="Enter Register Number (e.g., 2024-1234)"
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
                        <Label for="coop_name">Cooperative Name</Label>
                        <div v-if="isDuplicateName" class="mt-1 text-red-500">
                            Duplicate Cooperative Name Found
                        </div>
                        <div v-else-if="form.name" class="mt-1 text-green-500">
                            Valid Data Input
                        </div>
                        <div class="relative mt-1">
                            <input id="coop_name" v-model="form.name" placeholder="Enter Cooperative Name"
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
                                itemKeyProp="code"
                                v-model:search="searchState.region_code" 
                                :modelValue="form.region_code"
                                :open="openState.region_code" 
                                @select="val => onSelect('region_code', val)"
                                @update:open="val => openState.region_code = val" 
                                placeholder="Search Region"
                            />
                        </div>

                        <!-- Province -->
                        <div v-if="form.region_code !== '1300000000'">
                            <Label for="province" class="mb-2">Select Province</Label>
                            <SelectSearch
                                id="province"
                                :items="filteredProvinces"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="searchState.province_code"
                                :modelValue="form.province_code"
                                :open="openState.province_code"
                                @select="val => onSelect('province_code', val)"
                                @update:open="val => openState.province_code = val"
                                placeholder="Search Province"
                            />
                        </div>

                        <!-- City -->
                        <div>
                            <Label for="city" class="mb-2">Select City</Label>
                            <SelectSearch
                                id="city"
                                :items="filteredCities"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="searchState.city_code"
                                :modelValue="form.city_code"
                                :open="openState.city_code"
                                @select="val => onSelect('city_code', val)"
                                @update:open="val => openState.city_code = val"
                                placeholder="Search City"
                            />
                        </div>

                        <!-- Barangay -->
                        <div>
                            <Label for="barangay" class="mb-2">Select Barangay</Label>
                            <SelectSearch
                                id="barangay"
                                :items="filteredBarangays"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="searchState.barangay_code"
                                :modelValue="form.barangay_code"
                                :open="openState.barangay_code"
                                @select="val => onSelect('barangay_code', val)"
                                @update:open="val => openState.barangay_code = val"
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
                            <Label for="member" class="mb-2">Member Count</Label>
                            <input id="member" v-model="form.members_count" type="number"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="Enter Member Count" />
                        </div>

                        <!-- Total Asset -->
                        <div>
                            <Label for="asset" class="mb-2">Total Asset</Label>
                            <input id="asset" v-model="form.total_asset" type="number"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="Enter Total Asset" />
                        </div>

                        <!-- Net Surplus -->
                        <div>
                            <Label for="surplus" class="mb-2">Net Surplus</Label>
                            <input id="surplus" v-model="form.net_surplus" type="number"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="Enter Net Surplus" />
                        </div>

                        <!-- Submit -->
                        <div class="pt-6 md:col-span-2">
                            <button 
                                type="submit"
                                :disabled="submitting"
                                class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow hover:bg-indigo-700 transition"
                            >
                                <span v-if="submitting">Updating...</span>
                                <span v-else>Update Cooperative</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <FlashToast ref="toastRef" />
    </AppLayout>
</template>