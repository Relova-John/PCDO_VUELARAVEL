<script setup lang="ts">
import { ref, computed, reactive, nextTick } from 'vue'
import { useForm, Head, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import type { Regions, Provinces, Cities, Barangays } from '@/types/locations'
import type { CoopDetails } from '@/types/inventory'
import { BreadcrumbItem } from '@/types'
import { toast } from 'vue-sonner'
import { useDrafts } from '@/composables/useDrafts'
import Input from '@/components/ui/input/Input.vue'

const navOpen = ref(false)
const page = usePage<{ flash: { success?: string } }>()
const submitted = computed(() => !!page.props.flash?.success)
const today = new Date().toISOString().split('T')[0]

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory Form', href: '/admin/create' }
]

const props = defineProps<{
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
    inventory?: CoopDetails | null
    inventoryNames: { id: number, name: string, category: string }[]
    grantingAgencyNames: { id: number, name: string }[]
}>()

const normalized = computed(() => ({
    name: props.inventory?.name ?? '',
    region_code: props.inventory?.region_code ?? '1700000000',
    province_code: props.inventory?.province_code ?? '1705300000',
    city_code: props.inventory?.city_code ?? '',
    barangay_code: props.inventory?.barangay_code ?? '',
    email: props.inventory?.email ?? '@gmail.com',
    number: props.inventory?.number ?? '',
    inventoryItem: (props.inventory?.inventoryItem ?? []).map(item => ({
        ...item,
        granting_agency: normalizeGrantingAgencyValue(item.granting_agency ?? 'Self', true),
        status: item.status ?? null,
        item_picture: null,
        moa_file: null,
        item_picture_meta: item.item_picture_meta ?? null,
        moa_file_meta: item.moa_file_meta ?? null,
    }))
}))

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
const fieldErrors = reactive<Record<string, boolean>>({})
const firstErrorSelector = ref('')

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

function clearAllFieldErrors() {
    Object.keys(fieldErrors).forEach(key => delete fieldErrors[key])
}

function markError(field: string, selector: string) {
    fieldErrors[field] = true
    if (!firstErrorSelector.value) {
        firstErrorSelector.value = selector
    }
}

function clearFieldError(field: string) {
    if (fieldErrors[field]) {
        delete fieldErrors[field]
    }
}

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
    region_code: ['province_code', 'city_code', 'barangay_code'],
    province_code: ['city_code', 'barangay_code'],
    city_code: ['barangay_code'],
    barangay_code: []
} as const

type LocationFields = 'region_code' | 'province_code' | 'city_code' | 'barangay_code'

function onSelect(field: LocationFields, payload: { id: string; name: string }) {
    form[field] = String(payload.id)
    searchState[field] = payload.name
    openState[field] = false
    clearFieldError(field)

    dependencyMap[field].forEach(dep => {
        form[dep] = ''
        searchState[dep] = ''
        openState[dep] = false
        clearFieldError(dep)
    })
}

function onLocationModelUpdate(field: LocationFields, value: string | number) {
    form[field] = String(value)
    clearFieldError(field)

    if (!value) {
        searchState[field] = ''
        openState[field] = false

        dependencyMap[field].forEach(dep => {
            form[dep] = ''
            searchState[dep] = ''
            openState[dep] = false
            clearFieldError(dep)
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

const nameOpenState = reactive<Record<string | number, boolean>>({})
const grantingAgencyOpenState = reactive<Record<string | number, boolean>>({})

function getNameOptions(category: string) {
    if (!props.inventoryNames) return []
    return props.inventoryNames.filter(item => item.category === category)
}

function getGrantingAgencyOptions() {
    if (!props.grantingAgencyNames) return []
    return props.grantingAgencyNames
}

function resetItemFilesOnNameChange(item: any, value: string | number | null | undefined) {
    const newName = sanitizeGeneralName(String(value ?? '')).trim()
    const oldName = String(item.name ?? '').trim()

    if (newName !== oldName) {
        item.item_picture = null
        item.item_picture_meta = null
        item.moa_file = null
        item.moa_file_meta = null
    }

    item.name = newName

    const index = getItemIndexById(item.id)
    if (index !== -1) {
        clearFieldError(`item-${index}-name`)
        clearFieldError(`item-${index}-item_picture`)
        clearFieldError(`item-${index}-moa_file`)
    }
}

function normalizeGrantingAgencyValue(value: string | number | null | undefined, trimEdges = false) {
    const text = sanitizeGeneralName(String(value ?? ''), trimEdges)
    return text.trim().toLowerCase() === 'self' ? 'Self' : text
}

function onGrantingAgencyInput(item: any, value: string | number | null | undefined) {
    item.granting_agency = normalizeGrantingAgencyValue(value, false)

    if (isSelfAgency(item)) {
        item.granting_agency = 'Self'
        item.moa_file = null
    }
}

function isSelfAgency(item: any) {
    return String(item.granting_agency ?? '').trim().toLowerCase() === 'self'
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

function isAllowedFile(file: File) {
    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/pdf'
    ]

    return allowedTypes.includes(file.type)
}

function handleFileSelect(
    event: Event,
    item: any,
    field: 'item_picture' | 'moa_file',
    errorKey: string
) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0] ?? null

    if (!file) {
        item[field] = null
        return
    }

    if (!isAllowedFile(file)) {
        toast.error('Only JPG, JPEG, PNG, or PDF files are allowed.')
        target.value = ''
        item[field] = null
        return
    }

    item[field] = file
    clearFieldError(errorKey)
}

function handleDrop(
    event: DragEvent,
    item: any,
    field: 'item_picture' | 'moa_file',
    errorKey: string
) {
    event.preventDefault()

    const file = event.dataTransfer?.files?.[0] ?? null
    if (!file) return

    if (!isAllowedFile(file)) {
        toast.error('Only JPG, JPEG, PNG, or PDF files are allowed.')
        return
    }

    item[field] = file
    clearFieldError(errorKey)
}

function fileName(file: File | null | undefined) {
    return file?.name ?? ''
}

const categoryOptions = [
    { label: 'Equipment', value: 'Equipment' },
    { label: 'Facilities', value: 'Facilities' },
    { label: 'Machinery', value: 'Machinery' }
]

function getItemsByCategory(category: string) {
    return form.inventoryItem.filter(item => item.category === category)
}

function getItemIndexById(id: number) {
    return form.inventoryItem.findIndex(item => item.id === id)
}

function addItem(category: string) {
    form.inventoryItem.push({
        id: Date.now() + Math.floor(Math.random() * 1000),
        category,
        name: '',
        granting_agency: 'Self',
        location: '',
        value: 0,
        quantity: 0,
        status: null,
        acquired_date: '',
        item_picture: null,
        moa_file: null,
        item_picture_meta: null,
        moa_file_meta: null,
    })
}

function removeItemById(id: number) {
    const index = form.inventoryItem.findIndex(item => item.id === id)
    if (index === -1) return

    form.inventoryItem.splice(index, 1)

    Object.keys(fieldErrors).forEach(key => {
        if (key.startsWith(`item-${index}-`)) {
            delete fieldErrors[key]
        }
    })

    const shiftedErrors: Record<string, boolean> = {}

    Object.keys(fieldErrors).forEach(key => {
        const match = key.match(/^item-(\d+)-(.*)$/)
        if (!match) {
            shiftedErrors[key] = fieldErrors[key]
            return
        }

        const itemIndex = Number(match[1])
        const suffix = match[2]

        if (itemIndex > index) {
            shiftedErrors[`item-${itemIndex - 1}-${suffix}`] = true
        } else {
            shiftedErrors[key] = true
        }
    })

    clearAllFieldErrors()
    Object.assign(fieldErrors, shiftedErrors)
}

function retakeForm() {
    router.visit(`/admin/create`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.dismiss()
        }
    })
}

function sanitizeGeneralName(value: string, trimEdges = false) {
    const smallWords = ['of', 'and', 'the', 'for', 'in', 'on', 'at', 'to']

    const normalized = String(value ?? '').replace(/\s+/g, ' ')
    const prepared = trimEdges ? normalized.trim() : normalized

    return prepared
        .split(' ')
        .map((word, index) => {
            if (!word) return word

            const isAllCaps = /^[A-Z0-9&.-]+$/.test(word)
            const isShort = word.length <= 4
            const hasSpecial = /[&.-]/.test(word)

            if (isAllCaps && (isShort || hasSpecial)) {
                return word
            }

            const lowered = word.toLowerCase()

            if (index > 0 && smallWords.includes(lowered)) {
                return lowered
            }

            return lowered.charAt(0).toUpperCase() + lowered.slice(1)
        })
        .join(' ')
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

function hasAtMostTwoDecimals(value: string | number) {
    return /^\d+(\.\d{1,2})?$/.test(String(value).trim())
}

function isAcronym(text: string) {
    return /^[A-Z0-9&.\-]{2,10}$/.test(text.trim())
}

function confirmWithToast(
    message: string,
    description?: string,
    confirmLabel = 'Continue',
    cancelLabel = 'Cancel'
) {
    return new Promise<boolean>((resolve) => {
        let settled = false

        const finish = (value: boolean) => {
            if (settled) return
            settled = true
            resolve(value)
        }

        toast(message, {
            description,
            duration: Infinity,
            closeButton: true,
            action: {
                label: confirmLabel,
                onClick: () => finish(true),
            },
            cancel: {
                label: cancelLabel,
                onClick: () => finish(false),
            },
            onDismiss: () => finish(false),
            onAutoClose: () => finish(false),
        })
    })
}

async function confirmAcronym(field: string, value: string) {
    if (!isAcronym(value)) return true

    return await confirmWithToast(
        'Possible acronym detected',
        `This "${value}" in ${field} appears to be an acronym. Full name is preferred. Do you want to continue?`,
        'Continue',
        'Cancel'
    )
}

async function confirmMissingMoa(itemNo: number | string, itemName: string = '', category: string = '') {
    return await confirmWithToast(
        'MOA file missing',
        `${itemName} at ${category} #${itemNo} has no MOA file attached. Do you want to continue without the MOA?`,
        'Submit anyway',
        'Cancel'
    )
}

async function submit() {
    const errors: string[] = []

    validationErrors.value = []
    firstErrorSelector.value = ''
    clearAllFieldErrors()

    if (!form.name.trim()) {
        errors.push('Cooperative Name is required')
        markError('name', 'input[name="name"]')
    }

    if (!form.email.trim()) {
        errors.push('Email is required')
        markError('email', 'input[name="email"]')
    } else if (!isValidEmail(form.email)) {
        errors.push('Email must be a valid Gmail address (example: example@gmail.com)')
        markError('email', 'input[name="email"]')
    }

    if (!form.number.trim()) {
        errors.push('Contact Number is required')
        markError('number', 'input[name="number"]')
    } else if (!isValidPhone(form.number)) {
        errors.push('Contact Number must be a valid 11-digit mobile number starting with 09 (example: 09123456789)')
        markError('number', 'input[name="number"]')
    }

    function validateLocationRequiredOrInvalid(
        field: LocationFields,
        label: string,
        selector: string
    ) {
        const typed = searchState[field]?.trim()
        const selected = String(form[field] ?? '').trim()

        if (!selected) {
            if (typed) {
                errors.push(`${label} does not exist`)
            } else {
                errors.push(`${label} is required`)
            }

            markError(field, selector)
        }
    }

    validateLocationRequiredOrInvalid('region_code', 'Region', '[name="region"]')
    validateLocationRequiredOrInvalid('province_code', 'Province', '[name="province"]')
    validateLocationRequiredOrInvalid('city_code', 'City', '[name="city"]')
    validateLocationRequiredOrInvalid('barangay_code', 'Barangay', '[name="barangay"]')

    if (!form.inventoryItem.length) {
        errors.push('At least one inventory item is required')
        markError('inventoryItem', '#item')
    }

    for (const [index, item] of form.inventoryItem.entries()) {
        const itemNo = index + 1
        const base = `#item-${index}`

        if (!item.category) {
            errors.push(`Item #${itemNo}: Category is required`)
            markError(`item-${index}-category`, `${base} select[name="category"]`)
        }

        if (!item.name.trim()) {
            errors.push(`Item #${itemNo}: Name is required`)
            markError(`item-${index}-name`, `${base} [name="item_name"]`)
        }

        item.granting_agency = normalizeGrantingAgencyValue(item.granting_agency, true)

        if (!item.granting_agency.trim()) {
            errors.push(`Item #${itemNo}: Granting Agency name is required`)
            markError(`item-${index}-granting_agency`, `${base} [name="item_granting_agency"]`)
        }

        if (isSelfAgency(item)) {
            item.granting_agency = 'Self'
            item.moa_file = null
        }

        if (!item.item_picture && !item.item_picture_meta) {
            errors.push(`Item #${itemNo}: ${item.category} Picture is required`)
            markError(`item-${index}-item_picture`, `${base} input[name="item_picture"]`)
        }

        if (!item.location.trim()) {
            errors.push(`Item #${itemNo}: Location is required`)
            markError(`item-${index}-location`, `${base} input[name="item_location"]`)
        }

        if (!item.value && item.value !== 0) {
            errors.push(`Item #${itemNo}: Value is required`)
            markError(`item-${index}-value`, `${base} input[name="item_value"]`)
        } else if (Number(item.value) < 0) {
            errors.push(`Item #${itemNo}: Value cannot be negative`)
            markError(`item-${index}-value`, `${base} input[name="item_value"]`)
        } else if (Number(item.value) === 0) {
            errors.push(`Item #${itemNo}: Value cannot be zero`)
            markError(`item-${index}-value`, `${base} input[name="item_value"]`)
        } else if (!hasAtMostTwoDecimals(item.value)) {
            errors.push(`Item #${itemNo}: Value can have at most two decimal places`)
            markError(`item-${index}-value`, `${base} input[name="item_value"]`)
        }

        if (!item.quantity && item.quantity !== 0) {
            errors.push(`Item #${itemNo}: Quantity is required`)
            markError(`item-${index}-quantity`, `${base} input[name="item_quantity"]`)
        } else if (Number(item.quantity) < 0) {
            errors.push(`Item #${itemNo}: Quantity cannot be negative`)
            markError(`item-${index}-quantity`, `${base} input[name="item_quantity"]`)
        } else if (Number(item.quantity) === 0) {
            errors.push(`Item #${itemNo}: Quantity cannot be zero`)
            markError(`item-${index}-quantity`, `${base} input[name="item_quantity"]`)
        }

        if (item.status === null || item.status === undefined) {
            errors.push(`Item #${itemNo}: Status is required`)
            markError(`item-${index}-status`, `${base} select[name="item_status"]`)
        } else if (Number(item.status) < 0) {
            errors.push(`Item #${itemNo}: Status cannot be negative`)
            markError(`item-${index}-status`, `${base} select[name="item_status"]`)
        } else if (Number(item.status) > Number(item.quantity)) {
            errors.push(`Item #${itemNo}: Status cannot be greater than quantity`)
            markError(`item-${index}-status`, `${base} select[name="item_status"]`)
        }

        if (!item.acquired_date) {
            errors.push(`Item #${itemNo}: Acquired Date is required`)
            markError(`item-${index}-acquired_date`, `${base} input[name="item_acquired_date"]`)
        } else if (item.acquired_date > today) {
            errors.push(`Item #${itemNo}: Acquired Date cannot be in the future`)
            markError(`item-${index}-acquired_date`, `${base} input[name="item_acquired_date"]`)
        }
    }

    if (errors.length) {
        validationErrors.value = errors
        showAllErrors(errors)
        scrollToFirstError(firstErrorSelector.value)
        return
    }

    if (!await confirmAcronym('Cooperative Name', form.name)) return

    for (const item of form.inventoryItem) {
        if (!isSelfAgency(item)) {
            if (!await confirmAcronym('Granting Agency', item.granting_agency)) {
                return
            }
        }
    }

    for (const [index, item] of form.inventoryItem.entries()) {
        const itemNo = index + 1

        if (!isSelfAgency(item) && !item.moa_file && !item.moa_file_meta) {
            const proceed = await confirmMissingMoa(itemNo, item.name, item.category)
            if (!proceed) return
        }
    }

    form.post('/admin/create', {
        forceFormData: true,
        onSuccess: () => {
            validationErrors.value = []
            clearAllFieldErrors()
            toast.success('Inventory saved successfully')
        },
        onError: () => {
            toast.error('An error occurred while saving the inventory. Please try again.')
        }
    })
}
</script>

<template>

    <Head title="Inventory Form" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="edit-inventory-wrapper">

            <div class="inventory-header">
                <h1 class="inventory-title">ADD COOPERATIVE INVENTORY</h1>
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

            <div v-if="submitted" class="form-card" style="text-align:center">
                <h2 style="color:#188038">Form Submitted</h2>
                <p>Your inventory has been successfully recorded.</p>
                <button @click="retakeForm" class="add-btn">
                    Submit Another Response
                </button>
            </div>

            <form v-else @submit.prevent="submit">
                <div class="form-card" id="coop-info">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">
                                Cooperative Name
                                <span v-if="fieldErrors.name" class="error-star">*</span>
                            </label>
                            <Input class="form-input" :class="{ 'error-border': fieldErrors.name }" v-model="form.name"
                                name="name"
                                @input="form.name = sanitizeGeneralName(form.name); clearFieldError('name')" />
                        </div>

                        <div>
                            <label class="form-label">
                                Email
                                <span v-if="fieldErrors.email" class="error-star">*</span>
                            </label>
                            <Input class="form-input" :class="{ 'error-border': fieldErrors.email }"
                                v-model="form.email" name="email"
                                @input="form.email = sanitizeEmail(form.email); clearFieldError('email')" />
                        </div>

                        <div>
                            <label class="form-label">
                                Contact Number
                                <span v-if="fieldErrors.number" class="error-star">*</span>
                            </label>
                            <Input class="form-input" :class="{ 'error-border': fieldErrors.number }"
                                v-model="form.number" name="number"
                                @input="form.number = sanitizePhone(form.number); clearFieldError('number')" />
                        </div>
                    </div>
                </div>

                <div class="form-card" id="location">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">
                                Region
                                <span v-if="fieldErrors.region_code" class="error-star">*</span>
                            </label>
                            <div :class="{ 'error-border': fieldErrors.region_code }"
                                @click="clearFieldError('region_code')">
                                <SelectSearch :clearOnFocus="true" :items="regions" itemLabelKey="name"
                                    itemKeyProp="code" v-model:search="searchState.region_code"
                                    :modelValue="form.region_code"
                                    @update:modelValue="val => { onLocationModelUpdate('region_code', val); clearFieldError('region_code') }"
                                    v-model:open="openState.region_code"
                                    @select="val => { onSelect('region_code', val); clearFieldError('region_code') }"
                                    name="region" />
                            </div>
                        </div>

                        <div>
                            <label class="form-label">
                                Province
                                <span v-if="fieldErrors.province_code" class="error-star">*</span>
                            </label>
                            <div :class="{ 'error-border': fieldErrors.province_code }"
                                @click="clearFieldError('province_code')">
                                <SelectSearch :clearOnFocus="true" :items="filteredProvinces" itemLabelKey="name"
                                    itemKeyProp="code" v-model:search="searchState.province_code"
                                    :modelValue="form.province_code" v-model:open="openState.province_code"
                                    @update:modelValue="val => { onLocationModelUpdate('province_code', val); clearFieldError('province_code') }"
                                    @select="val => { onSelect('province_code', val); clearFieldError('province_code') }"
                                    name="province" />
                            </div>
                        </div>

                        <div>
                            <label class="form-label">
                                City
                                <span v-if="fieldErrors.city_code" class="error-star">*</span>
                            </label>
                            <div :class="{ 'error-border': fieldErrors.city_code }"
                                @click="clearFieldError('city_code')">
                                <SelectSearch :clearOnFocus="true" :items="filteredCities" itemLabelKey="name"
                                    itemKeyProp="code" v-model:search="searchState.city_code"
                                    :modelValue="form.city_code"
                                    @update:modelValue="val => { onLocationModelUpdate('city_code', val); clearFieldError('city_code') }"
                                    v-model:open="openState.city_code"
                                    @select="val => { onSelect('city_code', val); clearFieldError('city_code') }"
                                    name="city" />
                            </div>
                        </div>

                        <div>
                            <label class="form-label">
                                Barangay
                                <span v-if="fieldErrors.barangay_code" class="error-star">*</span>
                            </label>
                            <div :class="{ 'error-border': fieldErrors.barangay_code }"
                                @click="clearFieldError('barangay_code')">
                                <SelectSearch :clearOnFocus="true" :items="filteredBarangays" itemLabelKey="name"
                                    itemKeyProp="code" v-model:search="searchState.barangay_code"
                                    :modelValue="form.barangay_code" v-model:open="openState.barangay_code"
                                    @update:modelValue="val => { onLocationModelUpdate('barangay_code', val); clearFieldError('barangay_code') }"
                                    @select="val => { onSelect('barangay_code', val); clearFieldError('barangay_code') }"
                                    name="barangay" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card" id="item">
                    <div v-for="category in categoryOptions" :key="category.value" class="category-section">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px;">
                            <h1 class="section-title">{{ category.label }}</h1>
                            <button type="button" class="add-btn" @click="addItem(category.value)">
                                + Add {{ category.label }}
                            </button>
                        </div>

                        <div v-for="(item, categoryIndex) in getItemsByCategory(category.value)" :key="item.id">
                            <div v-if="getItemIndexById(item.id) !== -1">
                                <hr v-if="categoryIndex > 0" class="item-divider">

                                <div class="item-card" :id="'item-' + getItemIndexById(item.id)">
                                    <label class="item-title">
                                        {{ item.category || 'Item' }} #{{ categoryIndex + 1 }}:
                                        <span v-if="item.name">{{ item.name }}</span>
                                        <span v-else class="item-unnamed">Unnamed Item</span>
                                    </label>

                                    <button type="button" class="remove-x-btn" @click="removeItemById(item.id)">
                                        ✕
                                    </button>

                                    <div class="form-grid">
                                        <div>
                                            <label class="form-label">
                                                Category
                                                <span v-if="fieldErrors[`item-${getItemIndexById(item.id)}-category`]"
                                                    class="error-star">*</span>
                                            </label>
                                            <select v-model="item.category" class="form-select"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-category`] }"
                                                name="category" @change="() => {
                                                    item.name = ''
                                                    clearFieldError(`item-${getItemIndexById(item.id)}-category`)
                                                    clearFieldError(`item-${getItemIndexById(item.id)}-name`)
                                                }">
                                                <option value="" disabled>Select Category</option>
                                                <option v-for="option in categoryOptions" :key="option.value"
                                                    :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                Name
                                                <span v-if="fieldErrors[`item-${getItemIndexById(item.id)}-name`]"
                                                    class="error-star">*</span>
                                            </label>

                                            <div :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-name`] }"
                                                @click="clearFieldError(`item-${getItemIndexById(item.id)}-name`)">
                                                <SelectSearch :items="getNameOptions(item.category)" itemLabelKey="name"
                                                    itemKeyProp="name" :modelValue="item.name" :freeInput="true"
                                                    :clearOnFocus="false" v-model:open="nameOpenState[item.id]"
                                                    @update:modelValue="val => resetItemFilesOnNameChange(item, val)"
                                                    @select="val => resetItemFilesOnNameChange(item, val?.name)"
                                                    name="item_name" />
                                            </div>
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                Granting Agency Name
                                                <span
                                                    v-if="fieldErrors[`item-${getItemIndexById(item.id)}-granting_agency`]"
                                                    class="error-star">*</span>
                                            </label>

                                            <div :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-granting_agency`] }"
                                                @click="clearFieldError(`item-${getItemIndexById(item.id)}-granting_agency`)">
                                                <SelectSearch :items="getGrantingAgencyOptions()" itemLabelKey="name"
                                                    itemKeyProp="name" :modelValue="item.granting_agency"
                                                    :freeInput="true" :clearOnFocus="false"
                                                    v-model:open="grantingAgencyOpenState[item.id]" @update:search="val => {
                                                        onGrantingAgencyInput(item, val)
                                                        clearFieldError(`item-${getItemIndexById(item.id)}-granting_agency`)
                                                    }" @update:modelValue="val => {
                                                        onGrantingAgencyInput(item, val)
                                                        clearFieldError(`item-${getItemIndexById(item.id)}-granting_agency`)
                                                    }" @select="val => {
                                                        item.granting_agency = normalizeGrantingAgencyValue(String(val?.name ?? '').trim(), true)
                                                        clearFieldError(`item-${getItemIndexById(item.id)}-granting_agency`)

                                                        if (isSelfAgency(item)) {
                                                            item.granting_agency = 'Self'
                                                            item.moa_file = null
                                                        }
                                                    }" name="item_granting_agency" />
                                            </div>
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                {{ item.category || 'Item' }} Picture
                                                <span
                                                    v-if="fieldErrors[`item-${getItemIndexById(item.id)}-item_picture`]"
                                                    class="error-star">*</span>
                                            </label>

                                            <label class="file-drop"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-item_picture`] }"
                                                @dragover.prevent
                                                @drop="handleDrop($event, item, 'item_picture', `item-${getItemIndexById(item.id)}-item_picture`)">
                                                <input type="file" class="hidden-file" name="item_picture"
                                                    accept=".jpg,.jpeg,.png,.pdf"
                                                    @change="handleFileSelect($event, item, 'item_picture', `item-${getItemIndexById(item.id)}-item_picture`)" />

                                                <div class="file-drop-text">
                                                    <strong>Drag and drop or select file</strong>
                                                    <div class="file-subtext">Accepted: JPG, JPEG, PNG, PDF</div>
                                                    <div v-if="item.item_picture" class="file-name">
                                                        {{ fileName(item.item_picture) }}
                                                    </div>
                                                    <div v-else-if="item.item_picture_meta" class="file-name">
                                                        Previous file: {{ item.item_picture_meta.name }}
                                                    </div>
                                                    <div v-if="!item.item_picture && item.item_picture_meta"
                                                        class="file-subtext">
                                                        Existing file will be kept if you do not replace it.
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        <div v-if="!isSelfAgency(item)">
                                            <label class="form-label">
                                                MOA File <span class="text-xs text-gray-500">(Optional)</span>
                                            </label>

                                            <label class="file-drop"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-moa_file`] }"
                                                @dragover.prevent
                                                @drop="handleDrop($event, item, 'moa_file', `item-${getItemIndexById(item.id)}-moa_file`)">
                                                <input type="file" class="hidden-file" name="moa_file"
                                                    accept=".jpg,.jpeg,.png,.pdf"
                                                    @change="handleFileSelect($event, item, 'moa_file', `item-${getItemIndexById(item.id)}-moa_file`)" />

                                                <div class="file-drop-text">
                                                    <strong>Drag and drop or select file</strong>
                                                    <div class="file-subtext">Accepted: JPG, JPEG, PNG, PDF</div>
                                                    <div v-if="item.moa_file" class="file-name">
                                                        {{ fileName(item.moa_file) }}
                                                    </div>
                                                    <div v-else-if="item.moa_file_meta" class="file-name">
                                                        Previous file: {{ item.moa_file_meta.name }}
                                                    </div>
                                                    <div v-if="!item.moa_file && item.moa_file_meta"
                                                        class="file-subtext">
                                                        Existing file will be kept if you do not replace it.
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                Location
                                                <span v-if="fieldErrors[`item-${getItemIndexById(item.id)}-location`]"
                                                    class="error-star">*</span>
                                            </label>
                                            <Input class="form-input"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-location`] }"
                                                v-model="item.location" name="item_location"
                                                @input="clearFieldError(`item-${getItemIndexById(item.id)}-location`)" />
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                Value
                                                <span v-if="fieldErrors[`item-${getItemIndexById(item.id)}-value`]"
                                                    class="error-star">*</span>
                                            </label>
                                            <Input class="form-input"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-value`] }"
                                                type="number" v-model="item.value" name="item_value"
                                                @input="clearFieldError(`item-${getItemIndexById(item.id)}-value`)"
                                                step="0.01" />
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                Quantity
                                                <span v-if="fieldErrors[`item-${getItemIndexById(item.id)}-quantity`]"
                                                    class="error-star">*</span>
                                            </label>
                                            <Input class="form-input"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-quantity`] }"
                                                type="number" v-model="item.quantity" name="item_quantity"
                                                @input="clearFieldError(`item-${getItemIndexById(item.id)}-quantity`)"
                                                @change="item.status = null" />
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                Status
                                                <span v-if="fieldErrors[`item-${getItemIndexById(item.id)}-status`]"
                                                    class="error-star">*</span>
                                            </label>
                                            <select v-model.number="item.status" class="form-select"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-status`] }"
                                                name="item_status" :disabled="item.quantity <= 0"
                                                @change="clearFieldError(`item-${getItemIndexById(item.id)}-status`)">
                                                <option :value="null" disabled>Select Status</option>

                                                <option v-for="option in getStatusOptions(item.quantity)"
                                                    :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="form-label">
                                                Acquired Date
                                                <span
                                                    v-if="fieldErrors[`item-${getItemIndexById(item.id)}-acquired_date`]"
                                                    class="error-star">*</span>
                                            </label>
                                            <Input class="form-input"
                                                :class="{ 'error-border': fieldErrors[`item-${getItemIndexById(item.id)}-acquired_date`] }"
                                                type="date" v-model="item.acquired_date" :max="today"
                                                name="item_acquired_date"
                                                @input="clearFieldError(`item-${getItemIndexById(item.id)}-acquired_date`)"
                                                @change="clearFieldError(`item-${getItemIndexById(item.id)}-acquired_date`)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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

                    <template v-for="category in categoryOptions" :key="category.value">
                        <a v-for="(item, categoryIndex) in getItemsByCategory(category.value)" :key="item.id"
                            :href="'#item-' + getItemIndexById(item.id)">
                            {{ category.label }} #{{ categoryIndex + 1 }}: {{ item.name || 'Unnamed Item' }}
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.error-star {
    color: #dc2626;
    font-weight: 700;
    margin-left: 4px;
}

.error-border {
    border: 1px solid #dc2626 !important;
    box-shadow: 0 0 0 1px #dc2626 !important;
    border-radius: 8px;
}

.file-drop {
    display: block;
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 18px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: 0.2s ease;
}

.file-drop:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.hidden-file {
    display: none;
}

.file-drop-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.file-subtext {
    font-size: 12px;
    color: #64748b;
}

.file-name {
    font-size: 13px;
    font-weight: 600;
    color: #1d4ed8;
    word-break: break-word;
    margin-top: 6px;
}

.side-nav {
    position: fixed;
    right: 1px;
    top: 50%;
    transform: translateY(-50%);

    width: 130px;
    max-height: 70vh;
    overflow-y: auto;

    padding: 12px;

    background: transparent;
    border: none;
    box-shadow: none;

    font-size: 14px;
    opacity: 0.45;
    transition: opacity 0.2s ease;
}
</style>