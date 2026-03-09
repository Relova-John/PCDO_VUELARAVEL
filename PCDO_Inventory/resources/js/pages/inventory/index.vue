<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayout.vue'
import { ref, computed, reactive, watch } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import type { Regions, Provinces, Cities, Barangays, CoopDetails } from '@/types/inventory'
import { BreadcrumbItem } from '@/types'
import { toast } from "vue-sonner"
// import { useDrafts } from '@/composables/useDrafts'
// import { usePolling } from '@/composables/usePolling'
import Label from '@/components/ui/label/Label.vue'
import Input from '@/components/ui/input/Input.vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage<{ flash: { success?: string } }>()
const submitted = computed(() => !!page.props.flash?.success)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory' }
]

/**
 * Props from backend and typescript interfaces.
 */

const today = new Date().toISOString().split('T')[0]

const props = defineProps<{
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
    inventory?: CoopDetails | null
}>()

/**
 * Normalize incoming backend data and provide default values.
 */
const normalized = computed(() => ({
    name: props.inventory?.name ?? '',
    region_code: props.inventory?.region_code ?? '1700000000',
    province_code: props.inventory?.province_code ?? '1705300000',
    city_code: props.inventory?.city_code ?? '',
    barangay_code: props.inventory?.barangay_code ?? '',
    email: props.inventory?.email ?? '@gmail.com',
    number: props.inventory?.number ?? '',
    inventoryItem: props.inventory?.inventoryItem ?? []
}))

/**
 * Initialize form with normalized data.
 */

const form = useForm({
    name: normalized.value.name,
    region_code: normalized.value.region_code,
    province_code: normalized.value.province_code,
    city_code: normalized.value.city_code,
    barangay_code: normalized.value.barangay_code,
    email: normalized.value.email,
    number: normalized.value.number,
    inventoryItem: normalized.value.inventoryItem.length
        ? normalized.value.inventoryItem
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

function getStatusOptions(quantity: number) {
    const q = Number(quantity) || 1
    const options: string[] = []

    for (let servicable = q; servicable >= 0; servicable--) {
        const unservicable = q - servicable

        if (servicable === 0) {
            options.push(`Unservicable ${unservicable}`)
        } else if (unservicable === 0) {
            options.push(`Servicable ${servicable}`)
        } else {
            options.push(`Servicable ${servicable} | Unservicable ${unservicable}`)
        }
    }

    return options
}

watch(() => form.inventoryItem.map(item => item.quantity), () => {
    form.inventoryItem.forEach(item => {
        item.status = ""
    })
})

const filteredProvinces = computed(() =>
    props.provinces.filter(p => String(p.region_code) === String(form.region_code))
)

const filteredCities = computed(() =>
    props.cities.filter(c => String(c.province_code) === String(form.province_code))
)

const filteredBarangays = computed(() =>
    props.barangays.filter(b => String(b.city_code) === String(form.city_code))
)

const categoryOptions = ['Equipment', 'Machinery', 'Facilities']

/**
 * Equipment management
 */

function addEquipment() {
    form.inventoryItem.push({
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
    form.inventoryItem.splice(index, 1)
}

function retakeForm() {
    router.visit('/inventory')
}

function submit() {
    if (!form.name.trim()) {
        toast.error("Cooperative Name is required")
        return
    }

    if (!form.email.trim()) {
        toast.error("Email is required")
        return
    }

    if (!form.number.trim()) {
        toast.error("Contact number is required")
        return
    }

    for (const [index, item] of form.inventoryItem.entries()) {
        if (
            !item.category ||
            !item.name.trim() ||
            !item.guarantor_agency.trim() ||
            !item.location.trim() ||
            !item.value ||
            !item.quantity
        ) {
            toast.error(`Please fill all fields for Inventory Item #${index + 1}`)
            return
        }
    }

    form.post('/inventory', {
        onSuccess: () => toast.success('Inventory saved successfully')
    })
}
</script>

<template>

    <Head title="Inventory Form" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto p-6 space-y-6">

            <!-- HEADER -->
            <div>
                <h1 class="text-2xl font-bold">Inventory Form</h1>
                <p class="text-gray-500 text-sm">
                    Create or update cooperative inventory details
                </p>
            </div>
            <div class="max-w-7xl mx-auto p-6">

                <div v-if="submitted" class="text-center space-y-6 py-20">

                    <h1 class="text-3xl font-bold text-green-600">
                        Form Submitted
                    </h1>

                    <p class="text-gray-500">
                        Your inventory has been successfully recorded.
                    </p>

                    <button @click="retakeForm" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Submit Another Response
                    </button>

                </div>

                <div v-else>
                    <form @submit.prevent="submit" class="space-y-8">

                        <!-- COOPERATIVE INFO -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <Label>Cooperative Name</Label>
                                <span class="text-red-500 text-sm">Full Name Required</span>
                                <Input v-model="form.name" type="text" />
                            </div>
                            <div>
                                <Label>Email</Label>
                                <Input v-model="form.email" type="email" />
                            </div>
                            <div>
                                <Label>Contact Number</Label>
                                <Input v-model="form.number" type="text" />
                            </div>
                        </div>

                        <!-- LOCATION -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <Label>Region</Label>
                                <SelectSearch :items="regions" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="searchState.region_code" :modelValue="form.region_code"
                                    :open="openState.region_code" @select="val => onSelect('region_code', val)"
                                    @update:open="val => openState.region_code = val" />
                            </div>

                            <div>
                                <Label>Province</Label>
                                <SelectSearch :items="filteredProvinces" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="searchState.province_code" :modelValue="form.province_code"
                                    :open="openState.province_code" @select="val => onSelect('province_code', val)"
                                    @update:open="val => openState.province_code = val" />
                            </div>

                            <div>
                                <Label>City</Label>
                                <SelectSearch :items="filteredCities" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="searchState.city_code" :modelValue="form.city_code"
                                    :open="openState.city_code" @select="val => onSelect('city_code', val)"
                                    @update:open="val => openState.city_code = val" />
                            </div>

                            <div>
                                <Label>Barangay</Label>
                                <SelectSearch :items="filteredBarangays" itemLabelKey="name" itemKeyProp="code"
                                    v-model:search="searchState.barangay_code" :modelValue="form.barangay_code"
                                    :open="openState.barangay_code" @select="val => onSelect('barangay_code', val)"
                                    @update:open="val => openState.barangay_code = val" />
                            </div>
                        </div>

                        <!-- EQUIPMENT -->
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h2 class="text-xl font-semibold">Equipment</h2>
                                <button type="button" @click="addEquipment"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    + Add Equipment
                                </button>
                            </div>

                            <div v-for="(equipment, index) in form.inventoryItem" :key="equipment.id"
                                class="border rounded-xl p-6 space-y-6 bg-white shadow-sm">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                                    <div class="space-y-1">
                                        <Label>Category</Label>
                                        <select v-model="equipment.category" class="border rounded px-3 py-2 w-full">
                                            <option value="" disabled>Select Category</option>
                                            <option v-for="option in categoryOptions" :key="option" :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Name</Label>
                                        <Input v-model="equipment.name" />
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Guarantor Agency</Label>
                                        <span class="text-red-500 text-sm">Full Name Required</span>
                                        <Input v-model="equipment.guarantor_agency" />
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Location</Label>
                                        <Input v-model="equipment.location" />
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Value</Label>
                                        <Input v-model="equipment.value" type="number" min="1" />
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Quantity</Label>
                                        <Input v-model="equipment.quantity" type="number" min="1" />
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Status</Label>
                                        <p v-if="equipment.quantity === 0" class="text-gray-500 text-sm mt-1">
                                            Set quantity first to choose status
                                        </p>
                                        <select v-model="equipment.status" :disabled="equipment.quantity === 0"
                                            class="border rounded px-3 py-2 w-full">
                                            <option value="" disabled>Select Status</option>
                                            <option v-for="option in getStatusOptions(equipment.quantity)" :key="option"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Acquired Date</Label>
                                        <Input v-model="equipment.acquired_date" type="date" :max="today" />
                                    </div>

                                </div>

                                <div class="flex justify-end">
                                    <button type="button" @click="removeEquipment(index)"
                                        class="text-red-600 text-sm font-medium hover:underline">
                                        Remove Equipment
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- SUBMIT -->
                        <div class="pt-6">
                            <button type="submit"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Save Inventory
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
