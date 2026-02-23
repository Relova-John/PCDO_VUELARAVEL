<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed, reactive } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import type { Cooperative, Regions, Provinces, Cities, Barangays, InventoryDetails } from '@/types/cooperatives'
import { BreadcrumbItem } from '@/types'
import { toast } from "vue-sonner"
import Label from '@/components/ui/label/Label.vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory' }
]

/**
 * Props from backend and typescript interfaces.
 */

const props = defineProps<{
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
    cooperatives: Cooperative[]
    inventory?: InventoryDetails | null
}>()

/**
 * Normalize incoming backend data and provide default values.
 */
const normalized = computed(() => ({
    id: props.inventory?.id ?? '',
    name: props.inventory?.name ?? '',
    region_code: props.inventory?.region_code ?? '',
    province_code: props.inventory?.province_code ?? '',
    city_code: props.inventory?.city_code ?? '',
    barangay_code: props.inventory?.barangay_code ?? '',
    inventory: props.inventory?.inventory ?? []
}))

/**
 * Initialize form with normalized data.
 */

const form = useForm({
    id: normalized.value.id,
    name: normalized.value.name,
    region_code: normalized.value.region_code,
    province_code: normalized.value.province_code,
    city_code: normalized.value.city_code,
    barangay_code: normalized.value.barangay_code,
    equipment: normalized.value.inventory.length
        ? normalized.value.inventory
        : []
})

/**
 * Location selection state.
 */
const searchState = reactive({
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
    region_code: ["province_code", "city_code", "barangay_code"],
    province_code: ["city_code", "barangay_code"],
    city_code: ["barangay_code"],
    barangay_code: []
} as const

type LocationFields = "region_code" | "province_code" | "city_code" | "barangay_code"

function onSelect(field: LocationFields, payload: { id: string; name: string }) {
    form[field] = String(payload.id)
    searchState[field] = payload.name
    openState[field] = false

    dependencyMap[field].forEach(dep => {
        form[dep] = ""
        searchState[dep] = ""
    })
}


const filteredProvinces = computed(() =>
    props.provinces.filter(p => String(p.region_code) === String(form.region_code))
)

const filteredCities = computed(() =>
    props.cities.filter(c => String(c.province_code) === String(form.province_code))
)

const filteredBarangays = computed(() =>
    props.barangays.filter(b => String(b.city_code) === String(form.city_code))
)

/**
 * Equipment management
 */

function addEquipment() {
    form.equipment.push({
        id: Date.now(),
        category: '',
        name: '',
        guarantor_agency: '',
        location: '',
        value: 0,
        quantity: 0,
        status: '',
        acquired_date: ''
    })
}

function removeEquipment(index: number) {
    form.equipment.splice(index, 1)
}


function submit() {
    form.post('/inventory', {
        onSuccess: () => toast.success('Inventory saved successfully')
    })
}
</script>

<template>
    <Head title="Inventory Form" />

    <div :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto p-6 space-y-6">

            <!-- HEADER -->
            <div>
                <h1 class="text-2xl font-bold">Inventory Form</h1>
                <p class="text-gray-500 text-sm">
                    Create or update cooperative inventory details
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">

                <!-- COOPERATIVE INFO -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <Label>Cooperative</Label>
                        <SelectSearch
                            :items="cooperatives"
                            itemLabelKey="name"
                            itemKeyProp="id"
                            placeholder="Select Cooperative"
                        />
                    </div>
                    <div v-if="form.id === '-1'">
                        <Label>Cooperative Name</Label>
                        <Input v-model="form.name" type="text" />
                    </div>
                </div>

                <!-- LOCATION -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <Label>Region</Label>
                        <SelectSearch
                            :items="regions"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="searchState.region_code"
                            :modelValue="form.region_code"
                            :open="openState.region_code"
                            @select="val => onSelect('region_code', val)"
                            @update:open="val => openState.region_code = val"
                        />
                    </div>

                    <div>
                        <Label>Province</Label>
                        <SelectSearch
                            :items="filteredProvinces"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="searchState.province_code"
                            :modelValue="form.province_code"
                            :open="openState.province_code"
                            @select="val => onSelect('province_code', val)"
                            @update:open="val => openState.province_code = val"
                        />
                    </div>

                    <div>
                        <Label>City</Label>
                        <SelectSearch
                            :items="filteredCities"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="searchState.city_code"
                            :modelValue="form.city_code"
                            :open="openState.city_code"
                            @select="val => onSelect('city_code', val)"
                            @update:open="val => openState.city_code = val"
                        />
                    </div>

                    <div>
                        <Label>Barangay</Label>
                        <SelectSearch
                            :items="filteredBarangays"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="searchState.barangay_code"
                            :modelValue="form.barangay_code"
                            :open="openState.barangay_code"
                            @select="val => onSelect('barangay_code', val)"
                            @update:open="val => openState.barangay_code = val"
                        />
                    </div>
                </div>

                <!-- EQUIPMENT -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold">Equipment</h2>
                        <button
                            type="button"
                            @click="addEquipment"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            + Add Equipment
                        </button>
                    </div>

                    <div
                        v-for="(equipment, index) in form.equipment"
                        :key="equipment.id"
                        class="border rounded-xl p-6 space-y-4"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <Label>Category</Label>
                            <Input v-model="equipment.category"/>
                            <Label>Name</Label>
                            <Input v-model="equipment.name"/>
                            <Label>Guarantor Agency</Label>
                            <Input v-model="equipment.guarantor_agency"/>
                            <Label>Location</Label>
                            <Input v-model="equipment.location"/>
                            <Label>Value</Label>
                            <Input v-model="equipment.value" type="number"/>
                            <Label>Quantity</Label>
                            <Input v-model="equipment.quantity" type="number"/>
                            <Label>Status</Label>
                            <Input v-model="equipment.status"/>
                            <Label>Acquired Date</Label>
                            <Input v-model="equipment.acquired_date" type="date"/>
                        </div>

                        <button
                            type="button"
                            @click="removeEquipment(index)"
                            class="text-red-600 text-sm"
                        >
                            Remove
                        </button>
                    </div>
                </div>

                <!-- SUBMIT -->
                <div class="pt-6">
                    <button
                        type="submit"
                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        Save Inventory
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>
