<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { computed, reactive, ref, nextTick, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import Input from '@/components/ui/input/Input.vue'
import type { BreadcrumbItem } from '@/types'
import { toast } from 'vue-sonner'
import type { Regions, Provinces, Cities, Barangays, CoopDetails } from '@/types/inventory'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Edit', href: `` }
]

const props = defineProps<{
    cooperative: CoopDetails
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays: Barangays[]
    inventoryItem: any[]
    inventoryNames: { id: number, name: string, category: string }[]
    grantingAgencyNames: { id: number, name: string }[]
    locationLock?: {
        region_code?: string | number | null
        province_code?: string | number | null
        city_code?: string | number | null
        barangay_code?: string | number | null
    }
}>()

const today = new Date().toISOString().split('T')[0]

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

function normalizeGrantingAgencyValue(value: string | number | null | undefined, trimEdges = false) {
    const text = sanitizeGeneralName(String(value ?? ''), trimEdges)
    return text.trim().toLowerCase() === 'self' ? 'Self' : text
}

function isSelfAgency(item: any) {
    return String(item.granting_agency ?? '').trim().toLowerCase() === 'self'
}

const form = useForm({
    name: props.cooperative.name ?? '',
    email: props.cooperative.email ?? '',
    number: props.cooperative.number ?? '',
    region_code: String(props.cooperative.region_code ?? props.locationLock?.region_code ?? ''),
    province_code: String(props.cooperative.province_code ?? props.locationLock?.province_code ?? ''),
    city_code: String(props.cooperative.city_code ?? props.locationLock?.city_code ?? ''),
    barangay_code: String(props.cooperative.barangay_code ?? props.locationLock?.barangay_code ?? ''),
    inventoryItem: (props.inventoryItem ?? []).map((item) => ({
        ...item,
        granting_agency: normalizeGrantingAgencyValue(item.granting_agency ?? 'Self', true),
        item_picture: null,
        moa_file: null,
        name_search: item.name_search ?? item.name ?? '',
    }))
})

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

/**
 * Scoped location lock
 */
const scopeLevel = computed<'none' | 'region' | 'province' | 'city' | 'barangay'>(() => {
    if (props.locationLock?.barangay_code) return 'barangay'
    if (props.locationLock?.city_code) return 'city'
    if (props.locationLock?.province_code) return 'province'
    if (props.locationLock?.region_code) return 'region'
    return 'none'
})

const isRegionLocked = computed(() =>
    ['region', 'province', 'city', 'barangay'].includes(scopeLevel.value)
)

const isProvinceLocked = computed(() =>
    ['province', 'city', 'barangay'].includes(scopeLevel.value)
)

const isCityLocked = computed(() =>
    ['city', 'barangay'].includes(scopeLevel.value)
)

const isBarangayLocked = computed(() =>
    scopeLevel.value === 'barangay'
)

/**
 * Location selection state
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
    region_code: ['province_code', 'city_code', 'barangay_code'],
    province_code: ['city_code', 'barangay_code'],
    city_code: ['barangay_code'],
    barangay_code: []
} as const

type LocationFields = 'region_code' | 'province_code' | 'city_code' | 'barangay_code'

function getRegionName(code: string | number | null | undefined) {
    return props.regions.find(r => String(r.code) === String(code ?? ''))?.name ?? ''
}

function getProvinceName(code: string | number | null | undefined) {
    return props.provinces.find(p => String(p.code) === String(code ?? ''))?.name ?? ''
}

function getCityName(code: string | number | null | undefined) {
    return props.cities.find(c => String(c.code) === String(code ?? ''))?.name ?? ''
}

function getBarangayName(code: string | number | null | undefined) {
    return props.barangays.find(b => String(b.code) === String(code ?? ''))?.name ?? ''
}

function syncLocationSearchState() {
    searchState.region_code = getRegionName(form.region_code)
    searchState.province_code = getProvinceName(form.province_code)
    searchState.city_code = getCityName(form.city_code)
    searchState.barangay_code = getBarangayName(form.barangay_code)
}

function onSelect(field: LocationFields, payload: { id: string; name: string }) {
    form[field] = String(payload.id)
    searchState[field] = payload.name
    openState[field] = false
    clearFieldError(field)

    if (field === 'region_code') {
        if (!isProvinceLocked.value) {
            form.province_code = ''
            searchState.province_code = ''
            openState.province_code = false
            clearFieldError('province_code')
        }

        if (!isCityLocked.value) {
            form.city_code = ''
            searchState.city_code = ''
            openState.city_code = false
            clearFieldError('city_code')
        }

        if (!isBarangayLocked.value) {
            form.barangay_code = ''
            searchState.barangay_code = ''
            openState.barangay_code = false
            clearFieldError('barangay_code')
        }

        return
    }

    if (field === 'province_code') {
        if (!isCityLocked.value) {
            form.city_code = ''
            searchState.city_code = ''
            openState.city_code = false
            clearFieldError('city_code')
        }

        if (!isBarangayLocked.value) {
            form.barangay_code = ''
            searchState.barangay_code = ''
            openState.barangay_code = false
            clearFieldError('barangay_code')
        }

        return
    }

    if (field === 'city_code') {
        if (!isBarangayLocked.value) {
            form.barangay_code = ''
            searchState.barangay_code = ''
            openState.barangay_code = false
            clearFieldError('barangay_code')
        }
    }
}

function onLocationModelUpdate(field: LocationFields, value: string | number) {
    form[field] = String(value ?? '')
    clearFieldError(field)

    if (!value) {
        searchState[field] = ''
        openState[field] = false

        if (field === 'region_code') {
            if (!isProvinceLocked.value) {
                form.province_code = ''
                searchState.province_code = ''
                openState.province_code = false
                clearFieldError('province_code')
            }

            if (!isCityLocked.value) {
                form.city_code = ''
                searchState.city_code = ''
                openState.city_code = false
                clearFieldError('city_code')
            }

            if (!isBarangayLocked.value) {
                form.barangay_code = ''
                searchState.barangay_code = ''
                openState.barangay_code = false
                clearFieldError('barangay_code')
            }

            return
        }

        if (field === 'province_code') {
            if (!isCityLocked.value) {
                form.city_code = ''
                searchState.city_code = ''
                openState.city_code = false
                clearFieldError('city_code')
            }

            if (!isBarangayLocked.value) {
                form.barangay_code = ''
                searchState.barangay_code = ''
                openState.barangay_code = false
                clearFieldError('barangay_code')
            }

            return
        }

        if (field === 'city_code') {
            if (!isBarangayLocked.value) {
                form.barangay_code = ''
                searchState.barangay_code = ''
                openState.barangay_code = false
                clearFieldError('barangay_code')
            }
        }
    }
}

function validateLocationRequiredOrInvalid(
    errors: string[],
    field: LocationFields,
    label: string,
    selector: string
) {
    const typed = searchState[field]?.trim()
    const selected = String(form[field] ?? '').trim()

    if (!selected) {
        errors.push(typed ? `${label} does not exist` : `${label} is required`)
        markError(field, selector)
    }
}

/**
 * Filtered Location Lists
 */
const filteredProvinces = computed(() =>
    props.provinces.filter(p => String(p.region_code) === String(form.region_code))
)

const filteredCities = computed(() =>
    props.cities.filter(c => String(c.province_code) === String(form.province_code))
)

const filteredBarangays = computed(() =>
    props.barangays.filter(b => String(b.city_code) === String(form.city_code))
)

watch(
    () => form.region_code,
    (value, oldValue) => {
        if (value === oldValue) return

        searchState.region_code = getRegionName(value)

        if (!value) {
            if (!isProvinceLocked.value) searchState.province_code = ''
            if (!isCityLocked.value) searchState.city_code = ''
            if (!isBarangayLocked.value) searchState.barangay_code = ''
            return
        }

        if (!isProvinceLocked.value && form.province_code) {
            const provinceExists = filteredProvinces.value.some(
                province => String(province.code) === String(form.province_code)
            )

            if (!provinceExists) {
                form.province_code = ''
                searchState.province_code = ''
            }
        }

        if (!isCityLocked.value && form.city_code) {
            const cityExists = props.cities.some(
                city =>
                    String(city.code) === String(form.city_code) &&
                    String(city.province_code) === String(form.province_code)
            )

            if (!cityExists) {
                form.city_code = ''
                searchState.city_code = ''
            }
        }

        if (!isBarangayLocked.value && form.barangay_code) {
            const barangayExists = props.barangays.some(
                barangay =>
                    String(barangay.code) === String(form.barangay_code) &&
                    String(barangay.city_code) === String(form.city_code)
            )

            if (!barangayExists) {
                form.barangay_code = ''
                searchState.barangay_code = ''
            }
        }
    }
)

watch(
    () => form.province_code,
    (value, oldValue) => {
        if (value === oldValue) return

        searchState.province_code = getProvinceName(value)

        if (!value) {
            if (!isCityLocked.value) searchState.city_code = ''
            if (!isBarangayLocked.value) searchState.barangay_code = ''
            return
        }

        if (!isCityLocked.value && form.city_code) {
            const cityExists = filteredCities.value.some(
                city => String(city.code) === String(form.city_code)
            )

            if (!cityExists) {
                form.city_code = ''
                searchState.city_code = ''
            }
        }

        if (!isBarangayLocked.value && form.barangay_code) {
            const barangayExists = props.barangays.some(
                barangay =>
                    String(barangay.code) === String(form.barangay_code) &&
                    String(barangay.city_code) === String(form.city_code)
            )

            if (!barangayExists) {
                form.barangay_code = ''
                searchState.barangay_code = ''
            }
        }
    }
)

watch(
    () => form.city_code,
    (value, oldValue) => {
        if (value === oldValue) return

        searchState.city_code = getCityName(value)

        if (!value) {
            if (!isBarangayLocked.value) searchState.barangay_code = ''
            return
        }

        if (!isBarangayLocked.value && form.barangay_code) {
            const barangayExists = filteredBarangays.value.some(
                barangay => String(barangay.code) === String(form.barangay_code)
            )

            if (!barangayExists) {
                form.barangay_code = ''
                searchState.barangay_code = ''
            }
        }
    }
)

watch(
    () => form.barangay_code,
    (value) => {
        searchState.barangay_code = getBarangayName(value)
    }
)

syncLocationSearchState()

/**
 * Inventory Item Logic
 */
const categories = [
    { label: 'Equipment', value: 'Equipment' },
    { label: 'Machinery', value: 'Machinery' },
    { label: 'Facilities', value: 'Facilities' }
]

const nameOpenState = reactive<Record<string | number, boolean>>({})
const grantingAgencyOpenState = reactive<Record<string | number, boolean>>({})

function getItemsByCategory(category: string) {
    return form.inventoryItem
        .map((item, originalIndex) => ({
            item,
            originalIndex,
        }))
        .filter(entry => entry.item.category === category)
}

function getNameOptions(category: string) {
    if (!props.inventoryNames) return []
    return props.inventoryNames.filter(item => item.category === category)
}

function getGrantingAgencyOptions() {
    if (!props.grantingAgencyNames) return []
    return props.grantingAgencyNames
}

function onItemNameInput(item: any, value: string | number | null | undefined) {
    const text = String(value ?? '').trimStart()
    item.name_search = text
    item.name = text
}

function onGrantingAgencyInput(item: any, value: string | number | null | undefined) {
    item.granting_agency = normalizeGrantingAgencyValue(value, false)

    if (isSelfAgency(item)) {
        item.granting_agency = 'Self'
        item.moa_file = null
    }
}

function getStatusOptions(quantity: number) {
    const q = Number(quantity) || 0
    const options = []

    for (let i = 0; i <= q; i++) {
        options.push({
            label: `Servicable ${q - i} | Unserviceable ${i}`,
            value: q - i
        })
    }

    return options
}

function removeItemByOriginalIndex(originalIndex: number) {
    form.inventoryItem.splice(originalIndex, 1)

    Object.keys(fieldErrors).forEach(key => {
        if (key.startsWith(`item-${originalIndex}-`)) {
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

        if (itemIndex > originalIndex) {
            shiftedErrors[`item-${itemIndex - 1}-${suffix}`] = true
        } else {
            shiftedErrors[key] = true
        }
    })

    clearAllFieldErrors()
    Object.assign(fieldErrors, shiftedErrors)
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

function onFileChange(
    event: Event,
    item: any,
    field: 'item_picture' | 'moa_file',
    errorKey?: string
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

    if (errorKey) {
        clearFieldError(errorKey)
    }
}

function getPreviewUrl(fileMeta: any) {
    if (!fileMeta?.file_path) return ''

    if (
        String(fileMeta.file_path).startsWith('http://') ||
        String(fileMeta.file_path).startsWith('https://')
    ) {
        return fileMeta.file_path
    }

    return `/storage/${fileMeta.file_path}`
}

function fieldError(path: string) {
    return form.errors[path as keyof typeof form.errors]
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

/**
 * Form Submission
 */
const page = usePage<any>()
const user = page.props.auth.user

const baseDashboardPath = computed(() => {
    return user.role === 'officer' ? '/officer/dashboard' : '/admin/dashboard'
})

function submit() {
    const errors: string[] = []

    validationErrors.value = []
    firstErrorSelector.value = ''
    clearAllFieldErrors()
    form.clearErrors()

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

    validateLocationRequiredOrInvalid(errors, 'region_code', 'Region', '[name="region"]')
    validateLocationRequiredOrInvalid(errors, 'province_code', 'Province', '[name="province"]')
    validateLocationRequiredOrInvalid(errors, 'city_code', 'City', '[name="city"]')
    validateLocationRequiredOrInvalid(errors, 'barangay_code', 'Barangay', '[name="barangay"]')

    if (!form.inventoryItem.length) {
        errors.push('At least one inventory item is required')
        markError('inventoryItem', '#inventory-items')
    }

    for (const [index, item] of form.inventoryItem.entries()) {
        const itemNo = index + 1
        const base = `#item-${index}`

        item.granting_agency = normalizeGrantingAgencyValue(item.granting_agency, true)

        if (!item.name?.trim()) {
            errors.push(`Item #${itemNo}: Name is required`)
            markError(`item-${index}-name`, `${base} [name="item_name"]`)
        }

        if (!item.granting_agency?.trim()) {
            errors.push(`Item #${itemNo}: Granting Agency is required`)
            markError(`item-${index}-granting_agency`, `${base} [name="item_granting_agency"]`)
        }

        if (isSelfAgency(item)) {
            item.granting_agency = 'Self'
            item.moa_file = null
        }

        if (!item.location?.trim()) {
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

        if (item.status === '' || item.status === null || item.status === undefined) {
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

    form
        .transform((data) => ({
            ...data,
            _method: 'put',
        }))
        .post(`${baseDashboardPath.value}/${props.cooperative.id}`, {
            forceFormData: true,
            onSuccess: () => {
                validationErrors.value = []
                clearAllFieldErrors()
                toast.success('Cooperative updated successfully')
            },
            onError: () => {
                toast.error('Check fields for errors')
            },
        })
}
</script>

<template>
    <Head title="Edit Cooperative" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="edit-inventory-wrapper">
            <div class="inventory-header">
                <h1 class="inventory-title">EDIT COOPERATIVE</h1>
                <p class="inventory-subtitle">Update registration and inventory information</p>
            </div>

            <form @submit.prevent="submit">
                <div class="form-card">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">
                                Cooperative Name
                                <span v-if="fieldErrors.name" class="error-star">*</span>
                            </label>
                            <Input
                                class="form-input"
                                :class="{ 'error-border': fieldErrors.name }"
                                v-model="form.name"
                                name="name"
                                @input="form.name = sanitizeGeneralName(form.name); clearFieldError('name')"
                            />
                            <p v-if="fieldError('name')" class="form-error">{{ fieldError('name') }}</p>
                        </div>

                        <div>
                            <label class="form-label">
                                Email
                                <span v-if="fieldErrors.email" class="error-star">*</span>
                            </label>
                            <Input
                                class="form-input"
                                :class="{ 'error-border': fieldErrors.email }"
                                v-model="form.email"
                                name="email"
                                @input="form.email = sanitizeEmail(form.email); clearFieldError('email')"
                            />
                            <p v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</p>
                        </div>

                        <div>
                            <label class="form-label">
                                Contact Number
                                <span v-if="fieldErrors.number" class="error-star">*</span>
                            </label>
                            <Input
                                class="form-input"
                                :class="{ 'error-border': fieldErrors.number }"
                                v-model="form.number"
                                name="number"
                                @input="form.number = sanitizePhone(form.number); clearFieldError('number')"
                            />
                            <p v-if="fieldError('number')" class="form-error">{{ fieldError('number') }}</p>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">
                                Region
                                <span v-if="fieldErrors.region_code" class="error-star">*</span>
                            </label>
                            <div
                                :class="{
                                    'error-border': fieldErrors.region_code,
                                    'locked-field': isRegionLocked
                                }"
                                @click="clearFieldError('region_code')"
                            >
                                <SelectSearch
                                    :clearOnFocus="true"
                                    :items="regions"
                                    itemLabelKey="name"
                                    itemKeyProp="code"
                                    v-model:search="searchState.region_code"
                                    :modelValue="form.region_code"
                                    v-model:open="openState.region_code"
                                    :disabled="isRegionLocked"
                                    @update:modelValue="val => { onLocationModelUpdate('region_code', val); clearFieldError('region_code') }"
                                    @select="val => { onSelect('region_code', val); clearFieldError('region_code') }"
                                    name="region"
                                />
                            </div>
                            <p v-if="fieldError('region_code')" class="form-error">{{ fieldError('region_code') }}</p>
                        </div>

                        <div>
                            <label class="form-label">
                                Province
                                <span v-if="fieldErrors.province_code" class="error-star">*</span>
                            </label>
                            <div
                                :class="{
                                    'error-border': fieldErrors.province_code,
                                    'locked-field': isProvinceLocked
                                }"
                                @click="clearFieldError('province_code')"
                            >
                                <SelectSearch
                                    :clearOnFocus="true"
                                    :items="filteredProvinces"
                                    itemLabelKey="name"
                                    itemKeyProp="code"
                                    v-model:search="searchState.province_code"
                                    :modelValue="form.province_code"
                                    v-model:open="openState.province_code"
                                    :disabled="isProvinceLocked || !form.region_code"
                                    @update:modelValue="val => { onLocationModelUpdate('province_code', val); clearFieldError('province_code') }"
                                    @select="val => { onSelect('province_code', val); clearFieldError('province_code') }"
                                    name="province"
                                />
                            </div>
                            <p v-if="fieldError('province_code')" class="form-error">{{ fieldError('province_code') }}</p>
                        </div>

                        <div>
                            <label class="form-label">
                                City
                                <span v-if="fieldErrors.city_code" class="error-star">*</span>
                            </label>
                            <div
                                :class="{
                                    'error-border': fieldErrors.city_code,
                                    'locked-field': isCityLocked
                                }"
                                @click="clearFieldError('city_code')"
                            >
                                <SelectSearch
                                    :clearOnFocus="true"
                                    :items="filteredCities"
                                    itemLabelKey="name"
                                    itemKeyProp="code"
                                    v-model:search="searchState.city_code"
                                    :modelValue="form.city_code"
                                    v-model:open="openState.city_code"
                                    :disabled="isCityLocked || !form.province_code"
                                    @update:modelValue="val => { onLocationModelUpdate('city_code', val); clearFieldError('city_code') }"
                                    @select="val => { onSelect('city_code', val); clearFieldError('city_code') }"
                                    name="city"
                                />
                            </div>
                            <p v-if="fieldError('city_code')" class="form-error">{{ fieldError('city_code') }}</p>
                        </div>

                        <div>
                            <label class="form-label">
                                Barangay
                                <span v-if="fieldErrors.barangay_code" class="error-star">*</span>
                            </label>
                            <div
                                :class="{
                                    'error-border': fieldErrors.barangay_code,
                                    'locked-field': isBarangayLocked
                                }"
                                @click="clearFieldError('barangay_code')"
                            >
                                <SelectSearch
                                    :clearOnFocus="true"
                                    :items="filteredBarangays"
                                    itemLabelKey="name"
                                    itemKeyProp="code"
                                    v-model:search="searchState.barangay_code"
                                    :modelValue="form.barangay_code"
                                    v-model:open="openState.barangay_code"
                                    :disabled="isBarangayLocked || !form.city_code"
                                    @update:modelValue="val => { onLocationModelUpdate('barangay_code', val); clearFieldError('barangay_code') }"
                                    @select="val => { onSelect('barangay_code', val); clearFieldError('barangay_code') }"
                                    name="barangay"
                                />
                            </div>
                            <p v-if="fieldError('barangay_code')" class="form-error">{{ fieldError('barangay_code') }}</p>
                        </div>
                    </div>
                </div>

                <div id="inventory-items">
                    <div v-for="category in categories" :key="category.value" class="form-card">
                        <div class="section-header">
                            <h2 class="section-title">{{ category.label }}</h2>
                        </div>

                        <div
                            v-for="({ item, originalIndex }, index) in getItemsByCategory(category.value)"
                            :key="item.id ?? `${category.value}-${originalIndex}`"
                            class="item-card"
                            :id="`item-${originalIndex}`"
                        >
                            <button type="button" class="remove-x-btn" @click="removeItemByOriginalIndex(originalIndex)">
                                ✕
                            </button>

                            <div class="form-grid">
                                <label class="form-label" style="grid-column: 1 / -1;">
                                    {{ category.label }} #{{ index + 1 }}: {{ item.name }}
                                </label>

                                <div>
                                    <label class="form-label">
                                        Name
                                        <span v-if="fieldErrors[`item-${originalIndex}-name`]" class="error-star">*</span>
                                    </label>

                                    <div :class="{ 'error-border': fieldErrors[`item-${originalIndex}-name`] }" @click="clearFieldError(`item-${originalIndex}-name`)">
                                        <SelectSearch
                                            :items="getNameOptions(category.value)"
                                            itemLabelKey="name"
                                            itemKeyProp="name"
                                            :search="item.name_search"
                                            :modelValue="item.name"
                                            v-model:open="nameOpenState[item.id ?? `${category.value}-${originalIndex}`]"
                                            @update:search="val => {
                                                onItemNameInput(item, val)
                                                clearFieldError(`item-${originalIndex}-name`)
                                            }"
                                            @update:modelValue="val => {
                                                onItemNameInput(item, val)
                                                clearFieldError(`item-${originalIndex}-name`)
                                            }"
                                            @select="val => {
                                                const picked = String(val?.name ?? '').trim()
                                                item.name = picked
                                                item.name_search = picked
                                                clearFieldError(`item-${originalIndex}-name`)
                                            }"
                                            name="item_name"
                                        />
                                    </div>

                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.name`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.name`) }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Granting Agency
                                        <span v-if="fieldErrors[`item-${originalIndex}-granting_agency`]" class="error-star">*</span>
                                    </label>

                                    <div :class="{ 'error-border': fieldErrors[`item-${originalIndex}-granting_agency`] }" @click="clearFieldError(`item-${originalIndex}-granting_agency`)">
                                        <SelectSearch
                                            :items="getGrantingAgencyOptions()"
                                            itemLabelKey="name"
                                            itemKeyProp="name"
                                            :modelValue="item.granting_agency"
                                            :freeInput="true"
                                            :clearOnFocus="false"
                                            v-model:open="grantingAgencyOpenState[item.id ?? `${category.value}-${originalIndex}`]"
                                            @update:search="val => {
                                                onGrantingAgencyInput(item, val)
                                                clearFieldError(`item-${originalIndex}-granting_agency`)
                                            }"
                                            @update:modelValue="val => {
                                                onGrantingAgencyInput(item, val)
                                                clearFieldError(`item-${originalIndex}-granting_agency`)
                                            }"
                                            @select="val => {
                                                item.granting_agency = normalizeGrantingAgencyValue(String(val?.name ?? '').trim(), true)
                                                clearFieldError(`item-${originalIndex}-granting_agency`)

                                                if (isSelfAgency(item)) {
                                                    item.granting_agency = 'Self'
                                                    item.moa_file = null
                                                }
                                            }"
                                            name="item_granting_agency"
                                        />
                                    </div>

                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.granting_agency`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.granting_agency`) }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Location
                                        <span v-if="fieldErrors[`item-${originalIndex}-location`]" class="error-star">*</span>
                                    </label>
                                    <Input
                                        class="form-input"
                                        :class="{ 'error-border': fieldErrors[`item-${originalIndex}-location`] }"
                                        v-model="item.location"
                                        name="item_location"
                                        @input="clearFieldError(`item-${originalIndex}-location`)"
                                    />
                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.location`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.location`) }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Value
                                        <span v-if="fieldErrors[`item-${originalIndex}-value`]" class="error-star">*</span>
                                    </label>
                                    <Input
                                        class="form-input"
                                        :class="{ 'error-border': fieldErrors[`item-${originalIndex}-value`] }"
                                        type="number"
                                        step="0.01"
                                        v-model="item.value"
                                        name="item_value"
                                        @input="clearFieldError(`item-${originalIndex}-value`)"
                                    />
                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.value`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.value`) }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Quantity
                                        <span v-if="fieldErrors[`item-${originalIndex}-quantity`]" class="error-star">*</span>
                                    </label>
                                    <Input
                                        class="form-input"
                                        :class="{ 'error-border': fieldErrors[`item-${originalIndex}-quantity`] }"
                                        type="number"
                                        v-model="item.quantity"
                                        name="item_quantity"
                                        @input="clearFieldError(`item-${originalIndex}-quantity`)"
                                        @change="item.status = null"
                                    />
                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.quantity`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.quantity`) }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Status
                                        <span v-if="fieldErrors[`item-${originalIndex}-status`]" class="error-star">*</span>
                                    </label>
                                    <select
                                        v-model.number="item.status"
                                        class="form-select"
                                        :class="{ 'error-border': fieldErrors[`item-${originalIndex}-status`] }"
                                        :disabled="Number(item.quantity) === 0"
                                        name="item_status"
                                        @change="clearFieldError(`item-${originalIndex}-status`)"
                                    >
                                        <option :value="null" disabled>Select Status</option>
                                        <option
                                            v-for="option in getStatusOptions(item.quantity)"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.status`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.status`) }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">
                                        Acquired Date
                                        <span v-if="fieldErrors[`item-${originalIndex}-acquired_date`]" class="error-star">*</span>
                                    </label>
                                    <Input
                                        class="form-input"
                                        :class="{ 'error-border': fieldErrors[`item-${originalIndex}-acquired_date`] }"
                                        type="date"
                                        v-model="item.acquired_date"
                                        :max="today"
                                        name="item_acquired_date"
                                        @input="clearFieldError(`item-${originalIndex}-acquired_date`)"
                                        @change="clearFieldError(`item-${originalIndex}-acquired_date`)"
                                    />
                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.acquired_date`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.acquired_date`) }}
                                    </p>
                                </div>

                                <div>
                                    <label class="form-label">Item Picture</label>

                                    <div v-if="item.item_picture_meta" class="file-name">
                                        Current file:
                                        <a :href="getPreviewUrl(item.item_picture_meta)" target="_blank">
                                            {{ item.item_picture_meta.file_name }}
                                        </a>
                                    </div>

                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="form-input"
                                        @change="onFileChange($event, item, 'item_picture')"
                                    />

                                    <div v-if="item.item_picture?.name" class="file-name">
                                        New file: {{ item.item_picture.name }}
                                    </div>

                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.item_picture`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.item_picture`) }}
                                    </p>
                                </div>

                                <div v-if="!isSelfAgency(item)">
                                    <label class="form-label">MOA File</label>

                                    <div v-if="item.moa_file_meta" class="file-name">
                                        Current file:
                                        <a :href="getPreviewUrl(item.moa_file_meta)" target="_blank">
                                            {{ item.moa_file_meta.file_name }}
                                        </a>
                                    </div>

                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="form-input"
                                        @change="onFileChange($event, item, 'moa_file')"
                                    />

                                    <div v-if="item.moa_file?.name" class="file-name">
                                        New file: {{ item.moa_file.name }}
                                    </div>

                                    <p v-if="fieldError(`inventoryItem.${originalIndex}.moa_file`)" class="form-error">
                                        {{ fieldError(`inventoryItem.${originalIndex}.moa_file`) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="button-container">
                    <button type="submit" class="edit-submit-btn" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Records' }}
                    </button>

                    <button
                        type="button"
                        @click="router.visit(`${baseDashboardPath}/${props.cooperative.id}`)"
                        class="edit-cancel-btn"
                    >
                        Cancel
                    </button>
                </div>
            </form>
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

.locked-field {
    position: relative;
    background-color: #f1f5f9; /* light gray */
    border: 1px dashed #94a3b8 !important;
    cursor: not-allowed;
    pointer-events: none; /* fully block interaction */
}

.locked-field :deep(input),
.locked-field :deep(.select-search-input) {
    background-color: #f1f5f9 !important;
    color: #64748b !important;
    cursor: not-allowed;
}

.locked-field::after {
    content: "🔒 Locked";
    position: absolute;
    top: 6px;
    right: 10px;
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
}

.file-name {
    margin-top: 6px;
    font-size: 13px;
    color: #475569;
    word-break: break-word;
}

.file-name a {
    color: #2563eb;
    text-decoration: underline;
}

.form-error {
    margin-top: 6px;
    font-size: 12px;
    color: #dc2626;
}
</style>