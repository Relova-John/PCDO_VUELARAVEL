<script setup lang="ts">
import { ref, computed, reactive, nextTick } from 'vue'
import { useForm, Head, usePage, router } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'
import type { CoopDetails } from '@/types/inventory'
import { toast, Toaster } from 'vue-sonner'
import { useDrafts } from '@/composables/useDrafts'
import Input from '@/components/ui/input/Input.vue'

const navOpen = ref(false)
const page = usePage<{ flash: { success?: string } }>()
const submitted = computed(() => !!page.props.flash?.success)

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

const { drafts, useDraft, deleteDraft, clearDrafts } = useDrafts(form, 'inventory')

const validationErrors = ref<string[]>([])

function scrollToFirstError(selector?: string) {
    if (!selector) return

    nextTick(() => {
        const el = document.querySelector(selector) as HTMLElement | null
        if (!el) return

        el.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        })

        if ('focus' in el) {
            el.focus()
        }
    })
}

function showAllErrors(errors: string[]) {
    const reversed = [...errors].reverse()

    toast.error(`Please fix ${errors.length} error(s) first.`)

    reversed.forEach((err, index) => {
        setTimeout(() => {
            toast.error(err)
        }, index * 120)
    })
}

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
    const q = Number(quantity) || 0
    return Array.from({ length: q + 1 }, (_, i) => {
        const servicable = q - i
        const unservicable = i

        return {
            label: `Servicable ${servicable} | Unservicable ${unservicable}`,
            value: servicable
        }
    })

}

function onLocationModelUpdate(field: LocationFields, value: string | number) {
    form[field] = String(value)

    if (!value) {
        searchState[field] = ''
        openState[field] = false

        dependencyMap[field].forEach(dep => {
            form[dep] = ''
            searchState[dep] = ''
            openState[dep] = false
        })
    }
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

const categoryOptions = ['Equipment', 'Machinery', 'Facilities']

/**
 * Equipment management
 */

function addItem() {
    form.inventoryItem.push({
        id: Date.now(),
        category: '',
        name: '',
        granting_agency: '',
        location: '',
        value: 0,
        quantity: 0,
        status: null,
        acquired_date: ''
    })
}

function removeItem(index: number) {
    form.inventoryItem.splice(index, 1)
}

function retakeForm() {
    router.visit('/Form', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.dismiss()
        }
    })
}

function sanitizeEmail(value: string) {
    return value
        .toLowerCase()
        .replace(/\s+/g, '')
        .replace(/[^a-z0-9@._+-]/g, '')
        .replace(/@{2,}/g, '@')
}

function sanitizePhone(value: string) {
    let cleaned = value.replace(/\D/g, '')

    if (cleaned.startsWith('63')) {
        cleaned = '0' + cleaned.slice(2)
    }

    return cleaned.slice(0, 11)
}

function isValidEmail(value: string) {
    return /^[a-z0-9._+-]+@gmail\.com$/i.test(value.trim())
}

function isValidPhone(value: string) {
    return /^09\d{9}$/.test(value.trim())
}

function isAcronym(text: string) {
    return /^[A-Z0-9&.\-]{2,10}$/.test(text.trim())
}

async function confirmAcronym(field: string, value: string) {

    if (!isAcronym(value)) return true

    return confirm(
        `This "${value}" in ${field} appears to be an acronym.\n\n` +
        `We require the full name. Continue submitting?`
    )

}

async function submit() {
    const errors: string[] = []
    let firstErrorSelector = ''

    validationErrors.value = []

    function setFirstError(selector: string) {
        if (!firstErrorSelector) {
            firstErrorSelector = selector
        }
    }

    if (!form.name.trim()) {
        errors.push('Cooperative Name is required')
        setFirstError('input[name="name"]')
    }

    if (!form.email.trim()) {
        errors.push('Email is required')
        setFirstError('input[name="email"]')
    } else if (!isValidEmail(form.email)) {
        errors.push('Email must be a valid Gmail address (example: example@gmail.com)')
        setFirstError('input[name="email"]')
    }

    if (!form.number.trim()) {
        errors.push('Contact Number is required')
        setFirstError('input[name="number"]')
    } else if (!isValidPhone(form.number)) {
        errors.push('Contact Number must be a valid 11-digit mobile number starting with 09 (example: 09123456789)')
        setFirstError('input[name="number"]')
    }

    if (!form.region_code) {
        errors.push('Region is required')
        setFirstError('[name="region"]')
    }

    if (!form.province_code) {
        errors.push('Province is required')
        setFirstError('[name="province"]')
    }

    if (!form.city_code) {
        errors.push('City is required')
        setFirstError('[name="city"]')
    }

    if (!form.barangay_code) {
        errors.push('Barangay is required')
        setFirstError('[name="barangay"]')
    }

    if (!form.inventoryItem.length) {
        errors.push('At least one inventory item is required')
        setFirstError('#item')
    }

    for (const [index, item] of form.inventoryItem.entries()) {
        const itemNo = index + 1
        const base = `#item-${index}`

        if (!item.category) {
            errors.push(`Item #${itemNo}: Category is required`)
            setFirstError(`${base} select[name="category"]`)
        }

        if (!item.name.trim()) {
            errors.push(`Item #${itemNo}: Name is required`)
            setFirstError(`${base} input[name="item_name"]`)
        }

        if (!item.granting_agency.trim()) {
            errors.push(`Item #${itemNo}: Granting Agency is required`)
            setFirstError(`${base} input[name="item_granting_agency"]`)
        }

        if (!item.location.trim()) {
            errors.push(`Item #${itemNo}: Location is required`)
            setFirstError(`${base} input[name="item_location"]`)
        }

        if (!item.value && item.value !== 0) {
            errors.push(`Item #${itemNo}: Value is required`)
            setFirstError(`${base} input[name="item_value"]`)
        } else if (Number(item.value) < 0) {
            errors.push(`Item #${itemNo}: Value cannot be negative`)
            setFirstError(`${base} input[name="item_value"]`)
        }

        if (!item.quantity && item.quantity !== 0) {
            errors.push(`Item #${itemNo}: Quantity is required`)
            setFirstError(`${base} input[name="item_quantity"]`)
        } else if (Number(item.quantity) < 0) {
            errors.push(`Item #${itemNo}: Quantity cannot be negative`)
            setFirstError(`${base} input[name="item_quantity"]`)
        }

        if (!item.status && item.status !== 0) {
            errors.push(`Item #${itemNo}: Status is required`)
            setFirstError(`${base} select[name="item_status"]`)

        } else if (Number(item.status) < 0) {
            errors.push(`Item #${itemNo}: Status cannot be negative`)
            setFirstError(`${base} select[name="item_status"]`)
        } else if (Number(item.status) > Number(item.quantity)) {
            errors.push(`Item #${itemNo}: Status cannot be greater than quantity`)
            setFirstError(`${base} select[name="item_status"]`)
        }

        if (!item.acquired_date) {
            errors.push(`Item #${itemNo}: Acquired Date is required`)
            setFirstError(`${base} input[name="item_acquired_date"]`)
        } else if (item.acquired_date > today) {
            errors.push(`Item #${itemNo}: Acquired Date cannot be in the future`)
            setFirstError(`${base} input[name="item_acquired_date"]`)
        }
    }

    if (errors.length) {
        validationErrors.value = errors
        showAllErrors(errors)
        scrollToFirstError(firstErrorSelector)
        return
    }

    if (!await confirmAcronym("Cooperative Name", form.name)) return

    for (const item of form.inventoryItem) {
        if (!await confirmAcronym("Granting Agency", item.granting_agency)) {
            return
        }
    }

    form.post('/Form', {
        onSuccess: () => {
            validationErrors.value = []
            toast.success('Inventory saved successfully')
        }
    })
}
</script>

<template>
    <Toaster />

    <Head title="Inventory Form" />
    <div class="inventory-wrapper">
        <div class="gov-header">
            <div class="gov-header-inner">

                <div class="logo-left">
                    <img src="/img/province_of_palawan_logo.png" alt="Palawan Logo">
                </div>

                <div class="gov-text">
                    <div><strong>Republic of the Philippines</strong></div>
                    <div>Provincial Government of Palawan</div>
                    <div><strong>PROVINCIAL COOPERATIVE DEVELOPMENT OFFICE</strong></div>
                    <div>Capitol Bldg., Puerto Princesa City</div>
                    <div style="color:#1a73e8;">pcdo.palawan@gmail.com</div>
                    <div>(048) 434-4173</div>
                </div>

                <div class="logo-right">
                    <img src="/img/pcdo_logo.png" alt="PCDO Logo">
                </div>

            </div>
        </div>
        <div class="inventory-header">

            <h1 class="inventory-title">INVENTORY FORM</h1>
            <p class="inventory-subtitle">
                Create or update cooperative inventory details
            </p>

        </div>
        <div v-if="drafts.length && !submitted" class="draft-card">
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
        <!-- SUCCESS MESSAGE -->
        <div v-if="submitted" class="form-card" style="text-align:center">
            <h2 style="color:#188038">Form Submitted</h2>
            <p>Your inventory has been successfully recorded.</p>
            <button @click="retakeForm" class="add-btn">
                Submit Another Response
            </button>
        </div>
        <!-- FORM -->
        <form v-else @submit.prevent="submit">
            <!-- COOPERATIVE INFO -->
            <div class="form-card" id="coop-info">
                <div class="form-grid">
                    <div>
                        <label class="form-label">Cooperative Name</label>
                        <Input class="form-input" v-model="form.name" name="name" />
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <Input class="form-input" v-model="form.email" name="email"
                            @input="form.email = sanitizeEmail(form.email)" />
                    </div>
                    <div>
                        <label class="form-label">Contact Number</label>
                        <Input class="form-input" v-model="form.number" name="number"
                            @input="form.number = sanitizePhone(form.number)" />
                    </div>
                </div>
            </div>

            <!-- LOCATION -->
            <div class="form-card" id="location">
                <div class="form-grid">
                    <div>
                        <label class="form-label">Region</label>
                        <SelectSearch :items="regions" itemLabelKey="name" itemKeyProp="code"
                            v-model:search="searchState.region_code" :modelValue="form.region_code"
                            @update:modelValue="val => onLocationModelUpdate('region_code', val)"
                            v-model:open="openState.region_code" @select="val => onSelect('region_code', val)"
                            name="region" />

                    </div>
                    <div>
                        <label class="form-label">Province</label>
                        <SelectSearch :items="filteredProvinces" itemLabelKey="name" itemKeyProp="code"
                            v-model:search="searchState.province_code" :modelValue="form.province_code"
                            v-model:open="openState.province_code"
                            @update:modelValue="val => onLocationModelUpdate('province_code', val)"
                            @select="val => onSelect('province_code', val)" name="province" />

                    </div>
                    <div>
                        <label class="form-label">City</label>

                        <SelectSearch :items="filteredCities" itemLabelKey="name" itemKeyProp="code"
                            v-model:search="searchState.city_code" :modelValue="form.city_code"
                            @update:modelValue="val => onLocationModelUpdate('city_code', val)"
                            v-model:open="openState.city_code" @select="val => onSelect('city_code', val)"
                            name="city" />

                    </div>
                    <div>
                        <label class="form-label">Barangay</label>
                        <SelectSearch :items="filteredBarangays" itemLabelKey="name" itemKeyProp="code"
                            v-model:search="searchState.barangay_code" :modelValue="form.barangay_code"
                            v-model:open="openState.barangay_code"
                            @update:modelValue="val => onLocationModelUpdate('barangay_code', val)"
                            @select="val => onSelect('barangay_code', val)" name="barangay" />

                    </div>
                </div>
            </div>

            <!-- ITEMS -->
            <div class="form-card" id="item">
                <div style="display:flex; justify-content:space-between; align-items:center">
                    <h1 class="section-title">Equipment / Facilities / Machinery</h1>
                    <button type="button" class="add-btn" @click="addItem">
                        + Add Item
                    </button>
                </div>

                <!-- ITEM LIST -->
                <div v-for="(item, index) in form.inventoryItem" :key="item.id">

                    <hr v-if="index > 0" class="item-divider">

                    <div class="item-card" :id="'item-' + index">
                        <label class="item-title">
                            Item #{{ index + 1 }}:
                            <span v-if="item.name">{{ item.name }}</span>
                            <span v-else class="item-unnamed">Unnamed Item</span>
                        </label>

                        <button type="button" class="remove-x-btn" @click="removeItem(index)">
                            ✕
                        </button>

                        <div class="form-grid">
                            <div>
                                <label class="form-label">Category</label>

                                <select v-model="item.category" class="form-select" name="category">
                                    <option value="" disabled>Select Category</option>
                                    <option v-for="option in categoryOptions" :key="option">
                                        {{ option }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Name</label>
                                <Input class="form-input" v-model="item.name" name="item_name" />
                            </div>
                            <div>
                                <label class="form-label">Granting Agency</label>
                                <Input class="form-input" v-model="item.granting_agency" name="item_granting_agency" />
                            </div>
                            <div>
                                <label class="form-label">Location</label>
                                <Input class="form-input" v-model="item.location" name="item_location" />
                            </div>
                            <div>
                                <label class="form-label">Value</label>
                                <Input class="form-input" type="number" v-model="item.value" name="item_value" />
                            </div>
                            <div>
                                <label class="form-label">Quantity</label>
                                <Input class="form-input" type="number" v-model="item.quantity" name="item_quantity"
                                    @change="item.status = null" />
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <select v-model.number="item.status" class="form-select" name="item_status"
                                    :disabled="item.quantity === 0">

                                    <option :value="null">Select Status</option>

                                    <option v-for="option in getStatusOptions(item.quantity)" :key="option.value"
                                        :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Acquired Date</label>

                                <Input class="form-input" type="date" v-model="item.acquired_date" :max="today"
                                    name="item_acquired_date" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUBMIT -->

            <div style="margin-top:25px">

                <button type="submit" class="submit-btn">
                    Save Inventory
                </button>
            </div>
        </form>
        <div class="side-nav">

            <div class="side-nav-toggle" @click="navOpen = !navOpen">
                ☰ Sections
            </div>

            <div v-if="navOpen">

                <a href="#coop-info">Coop Info</a>
                <a href="#location">Location</a>

                <a v-for="(item, index) in form.inventoryItem" :key="index" :href="'#item-' + index">
                    Item #{{ index + 1 }}: {{ item.name || 'Unnamed Item' }}
                </a>

            </div>

        </div>
    </div>
</template>