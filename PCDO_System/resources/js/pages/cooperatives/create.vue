<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed, reactive } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import type { Cooperative, Regions, Provinces, Cities, Barangays, Details } from '@/types/cooperatives'
import { BreadcrumbItem } from '@/types'
import { usePolling } from '@/composables/usePolling'
import { useDrafts } from '@/composables/useDrafts'
import { toast } from "vue-sonner"

const props = defineProps<{
    cooperatives: Cooperative[],
    cooperative?: Cooperative,
    breadcrumbs?: BreadcrumbItem[],
    regions: Regions[],
    provinces: Provinces[],
    cities: Cities[],
    barangays: Barangays[],
    details?: Details,
}>()

const coop = props.cooperative
const details = props.details
const submitting = ref(false);

const form = useForm({
    id: coop?.id ?? '',
    name: coop?.name ?? '',
    region_code: details?.region_code ?? '1700000000',
    province_code: details?.province_code ?? '1705300000',
    city_code: details?.city_code ?? '',
    barangay_code: details?.barangay_code ?? '',
    asset_size: details?.asset_size ?? '',
    coop_type: details?.coop_type ?? '',
    status_category: details?.status_category ?? 'New',
    bond_of_membership: details?.bond_of_membership ?? '',
    area_of_operation: details?.area_of_operation ?? 'Municipal',
    citizenship: details?.citizenship ?? 'Filipino',
    members_count: details?.members_count ?? '',
    total_asset: details?.total_asset ?? '',
    net_surplus: details?.net_surplus ?? '',
    email: details?.email ?? '',
    number: details?.number ?? '',
})

const { drafts, useDraft, deleteDraft, clearDrafts } = useDrafts(form, 'cooperatives')

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
    props.cooperatives.some(c => c.id.toString() === form.id)
)

const isDuplicateName = computed(() =>
    props.cooperatives.some(c => c.name.toLowerCase() === form.name.toLowerCase())
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

// Email field validation
const validateEmail = () => {
    form.clearErrors('email')
    if (form.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(form.email)) {
            form.setError('email', 'Enter a valid email address.')
        }
    }
}

// Phone field validation
const validatePhone = () => {
    form.clearErrors('number')
    if (form.number) {
        const phoneRegex = /^09\d{9}$/
        if (!phoneRegex.test(form.number)) {
            form.setError('number', 'Enter a valid mobile number (e.g., 09123456789).')
        }
    }
}

validateEmail()
validatePhone()

// Location Dependency Map
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
        'email',
        'number',
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
        submitting.value = false;
        toast.error(`Please fill in all required fields:\n- ${emptyFields.join('\n- ')}`)
        return;
    }

    if (!isIdFormatValid(form.id)) {
        submitting.value = false;
        toast.error('Invalid ID format. Correct format is YYYY-XXXX (e.g., 2024-1234)')
        return;
    }

    if (isDuplicateId.value || isDuplicateName.value) {
        submitting.value = false;
        toast.error('Duplicate Cooperative ID or Name found.')
        return;
    }

    form.post('/cooperatives', {
        onError: (errors) => {
            submitting.value = false;
            const messages = Object.values(errors);
            if (messages.length) {
                toast.error(messages.map(msg => `- ${msg}`).join('\n'))
            }
        },
        onSuccess: () => {
            toast.success(`${form.name} created successfully!`)
            submitting.value = false;
            deleteDraft(form.id);
            form.reset();
            router.reload({ only: ['cooperatives'] })
        },
    });
}

usePolling(["cooperatives"], 30000, () => {
    if (document.hidden) return true
    if (!navigator.onLine) return true
    return submitting.value
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7x7 p-6">
                <div
                    class="bg-gray-100/80 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                        <Plus class="w-10 h-10 text-blue-600 dark:text-blue-400 flex-shrink-0" /> Create Cooperative
                    </h1>
                    <!-- Drafts List -->
                    <div v-if="drafts.length" class="mt-10 border-t border-gray-200 dark:border-gray-700 pt-2 pb-1">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                                Saved Drafts
                            </h2>
                            <button @click="clearDrafts"
                                class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                Clear All
                            </button>
                        </div>

                        <ul class="space-y-2">
                            <li v-for="draft in drafts" :key="draft.id"
                                class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-750 transition">

                                <button @click="useDraft(draft)"
                                    class="text-left flex-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <p class="font-medium">{{ draft.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Saved on {{ draft.savedAt }}
                                    </p>
                                </button>

                                <button @click="deleteDraft(draft.id)"
                                    class="ml-3 px-2 py-1 text-red-500 hover:text-red-700 rounded-md transition">
                                    ✕
                                </button>
                            </li>
                        </ul>
                    </div>

                    <form @submit.prevent="handleSubmit" @keydown.enter.prevent class="space-y-6">
                        <!-- Coop ID -->
                        <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-6">
                            <Label for="coop_id" class="block mb-2">Register Number</Label>
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
                                <input id="coop_id" v-model="form.id"
                                    placeholder="Enter Register Number (e.g., 2024-1234)" @focus="showIdDropdown = true"
                                    @blur="hideIdDropdown"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                <ul v-if="showIdDropdown"
                                    class="absolute z-10 bg-gray-100 dark:bg-gray-700 border mt-1 w-full rounded-xl shadow">
                                    <li v-for="coop in filteredCooperativesId" :key="coop.id"
                                        class="px-4 py-2 hover:bg-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                                        {{ coop.id }} - {{ coop.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Coop Name -->
                        <div>
                            <Label for="coop_name" class="block mb-2">Cooperative Name</Label>
                            <div v-if="isDuplicateName" class="mt-1 text-red-500">
                                Duplicate Cooperative Name Found
                            </div>
                            <div v-else-if="form.name" class="mt-1 text-green-500">
                                Valid Data Input
                            </div>
                            <div class="relative mt-1">
                                <input id="coop_name" v-model="form.name" placeholder="Enter Cooperative Name"
                                    @focus="showNameDropdown = true" @blur="hideNameDropdown"
                                    class="w-full pl-9 rounded-xl bg-white dark:bg-gray-700 border border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                <ul v-if="showNameDropdown"
                                    class="absolute z-10 bg-gray-100 dark:bg-gray-700 border mt-1 w-full rounded-xl shadow">
                                    <li v-for="coop in filteredCooperativesName" :key="coop.id"
                                        class="px-4 py-2 hover:bg-gray-300 dark:hover:bg-gray-600 cursor-pointer">
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
                        "
                            class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <!-- Region -->
                            <div>
                                <Label for="region" class="mb-2">Region</Label>
                                <SelectSearch id="region" :items="props.regions" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="searchState.region_code" :modelValue="form.region_code"
                                    :open="openState.region_code" @select="val => onSelect('region_code', val)"
                                    @update:open="val => openState.region_code = val" placeholder="Search Region" />
                            </div>

                            <!-- Province -->
                            <div v-if="form.region_code !== '1300000000'">
                                <Label for="province" class="mb-2">Province</Label>
                                <SelectSearch id="province" :items="filteredProvinces" itemLabelKey="name"
                                    itemKeyProp="code" v-model:search="searchState.province_code"
                                    :modelValue="form.province_code" :open="openState.province_code"
                                    @select="val => onSelect('province_code', val)"
                                    @update:open="val => openState.province_code = val" placeholder="Search Province" />
                            </div>

                            <!-- City -->
                            <div>
                                <Label for="city" class="mb-2">City</Label>
                                <SelectSearch id="city" :items="filteredCities" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="searchState.city_code" :modelValue="form.city_code"
                                    :open="openState.city_code" @select="val => onSelect('city_code', val)"
                                    @update:open="val => openState.city_code = val" placeholder="Search City" />
                            </div>

                            <!-- Barangay -->
                            <div>
                                <Label for="barangay" class="mb-2">Barangay</Label>
                                <SelectSearch id="barangay" :items="filteredBarangays" itemLabelKey="name"
                                    itemKeyProp="code" v-model:search="searchState.barangay_code"
                                    :modelValue="form.barangay_code" :open="openState.barangay_code"
                                    @select="val => onSelect('barangay_code', val)"
                                    @update:open="val => openState.barangay_code = val" placeholder="Search Barangay" />
                            </div>

                            <!-- Asset Size -->
                            <div>
                                <Label for="asset_size" class="mb-2">Asset Size</Label>
                                <SelectSearch id="asset_size" :items="assetSizes" itemKeyProp="id" itemLabelKey="name"
                                    v-model:search="searchAssetSize" :modelValue="form.asset_size"
                                    @select="val => form.asset_size = val.id" :open="dropDownAssetSizeOpen"
                                    @update:open="val => dropDownAssetSizeOpen = val" placeholder="Select Asset Size" />
                            </div>

                            <!-- Cooperative Type -->
                            <div>
                                <Label for="coop_type" class="mb-2">Cooperative Type</Label>
                                <SelectSearch id="coop_type" :items="coopTypes" itemKeyProp="id" itemLabelKey="name"
                                    v-model:search="searchCoopType" :modelValue="form.coop_type"
                                    @select="val => form.coop_type = val.id" :open="dropDownCoopTypeOpen"
                                    @update:open="val => dropDownCoopTypeOpen = val"
                                    placeholder="Select Cooperative Type" />
                            </div>

                            <!-- Status Category -->
                            <div>
                                <Label for="status_category" class="mb-2">Status Category</Label>
                                <SelectSearch id="status_category" :items="statusCategories" itemKeyProp="id"
                                    itemLabelKey="name" v-model:search="searchStatusCategory"
                                    :modelValue="form.status_category" @select="val => form.status_category = val.id"
                                    :open="dropDownStatusCategoryOpen"
                                    @update:open="val => dropDownStatusCategoryOpen = val"
                                    placeholder="Select Status Category" />
                            </div>

                            <!-- Bond of Membership -->
                            <div>
                                <Label for="bond_of_membership" class="mb-2">Bond of Membership</Label>
                                <SelectSearch id="bond_of_membership" :items="bonds" itemKeyProp="id"
                                    itemLabelKey="name" v-model:search="searchBond"
                                    :modelValue="form.bond_of_membership"
                                    @select="val => form.bond_of_membership = val.id" :open="dropDownBondOpen"
                                    @update:open="val => dropDownBondOpen = val"
                                    placeholder="Select Bond of Membership" />
                            </div>

                            <!-- Area of Operation -->
                            <div>
                                <Label for="area_of_operation" class="mb-2">Area of Operation</Label>
                                <SelectSearch id="area_of_operation" :items="areas" itemKeyProp="id" itemLabelKey="name"
                                    v-model:search="searchArea" :modelValue="form.area_of_operation"
                                    @select="val => form.area_of_operation = val.id" :open="dropDownAreaOpen"
                                    @update:open="val => dropDownAreaOpen = val"
                                    placeholder="Select Area of Operation" />
                            </div>

                            <!-- Citizenship -->
                            <div>
                                <Label for="citizenship" class="mb-2">Citizenship</Label>
                                <SelectSearch id="citizenship" :items="citizenships" itemKeyProp="id"
                                    itemLabelKey="name" v-model:search="searchCitizenship"
                                    :modelValue="form.citizenship" @select="val => form.citizenship = val.id"
                                    :open="dropDownCitizenshipOpen" @update:open="val => dropDownCitizenshipOpen = val"
                                    placeholder="Select Citizenship" />
                            </div>

                            <!-- Member Count -->
                            <div>
                                <Label for="member" class="mb-2">Member Count</Label>
                                <input id="member" v-model="form.members_count" type="number"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    placeholder="Enter Member Count" />
                            </div>

                            <!-- Total Asset -->
                            <div>
                                <Label for="asset" class="mb-2">Total Asset</Label>
                                <input id="asset" v-model="form.total_asset" type="number"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    placeholder="Enter Total Asset" />
                            </div>

                            <!-- Net Surplus -->
                            <div>
                                <Label for="surplus" class="mb-2">Net Surplus</Label>
                                <input id="surplus" v-model="form.net_surplus" type="number"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    placeholder="Enter Net Surplus" />
                            </div>

                            <!-- Email -->
                            <div>
                                <Label for="email" class="mb-2">Email</Label>
                                <input id="email" v-model="form.email" type="email"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    @blur="validateEmail" placeholder="Enter Email" />
                            </div>

                            <!-- Mobile Number -->
                            <div>
                                <Label for="number" class="mb-2">Contact Number</Label>
                                <input id="number" v-model="form.number" type="text" maxlength="11"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    @blur="validatePhone" placeholder="e.g. 09123456789" />
                            </div>

                            <!-- Submit -->
                            <div class="pt-6 md:col-span-2 flex justify-center md:justify-end">
                                <button type="submit" :disabled="submitting"
                                    class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow hover:bg-indigo-700 transition">
                                    <span v-if="submitting">Submitting...</span>
                                    <span v-else>Create Cooperative</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>