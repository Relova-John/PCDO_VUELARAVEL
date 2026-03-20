<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
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
}>()

const today = new Date().toISOString().split('T')[0]

const form = useForm({
    name: props.cooperative.name,
    email: props.cooperative.email,
    number: props.cooperative.number,
    region_code: props.cooperative.region_code,
    province_code: props.cooperative.province_code,
    city_code: props.cooperative.city_code,
    barangay_code: props.cooperative.barangay_code,
    inventoryItem: (props.inventoryItem ?? []).map((item) => ({
        ...item,
        item_picture: null,
        moa_file: null,
        name_search: item.name_search ?? item.name ?? '',
    }))
})

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

function onSelect(field: LocationFields, payload: { id: string; name: string }) {
    form[field] = String(payload.id)
    searchState[field] = payload.name
    openState[field] = false

    dependencyMap[field].forEach(dep => {
        form[dep] = ''
        searchState[dep] = ''
    })
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

/**
 * Inventory Item Logic
 */
const categories = [
    { label: 'Equipment', value: 'Equipment' },
    { label: 'Machinery', value: 'Machinery' },
    { label: 'Facilities', value: 'Facilities' }
]

const nameOpenState = reactive<Record<string | number, boolean>>({})

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
}

function onFileChange(
    event: Event,
    item: any,
    field: 'item_picture' | 'moa_file'
) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0] ?? null
    item[field] = file
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

/**
 * Form Submission
 */
const page = usePage<any>()
const user = page.props.auth.user

const baseDashboardPath = computed(() => {
    return user.role === 'officer' ? '/officer/dashboard' : '/admin/dashboard'
})

function submit() {
    form
        .transform((data) => ({
            ...data,
            _method: 'put',
        }))
        .post(`${baseDashboardPath.value}/${props.cooperative.id}`, {
            forceFormData: true,
            onSuccess: () => toast.success('Cooperative updated successfully'),
            onError: () => toast.error('Check fields for errors'),
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
                            <label class="form-label">Cooperative Name</label>
                            <Input class="form-input" v-model="form.name" />
                            <p v-if="fieldError('name')" class="form-error">{{ fieldError('name') }}</p>
                        </div>

                        <div>
                            <label class="form-label">Email</label>
                            <Input class="form-input" type="email" v-model="form.email" />
                            <p v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</p>
                        </div>

                        <div>
                            <label class="form-label">Contact Number</label>
                            <Input class="form-input" v-model="form.number" />
                            <p v-if="fieldError('number')" class="form-error">{{ fieldError('number') }}</p>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">Region</label>
                            <SelectSearch
                                :items="regions"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="searchState.region_code"
                                :modelValue="form.region_code"
                                v-model:open="openState.region_code"
                                @select="val => onSelect('region_code', val)"
                            />
                            <p v-if="fieldError('region_code')" class="form-error">{{ fieldError('region_code') }}</p>
                        </div>

                        <div>
                            <label class="form-label">Province</label>
                            <SelectSearch
                                :items="filteredProvinces"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="searchState.province_code"
                                :modelValue="form.province_code"
                                v-model:open="openState.province_code"
                                @select="val => onSelect('province_code', val)"
                            />
                            <p v-if="fieldError('province_code')" class="form-error">{{ fieldError('province_code') }}</p>
                        </div>

                        <div>
                            <label class="form-label">City</label>
                            <SelectSearch
                                :items="filteredCities"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="searchState.city_code"
                                :modelValue="form.city_code"
                                v-model:open="openState.city_code"
                                @select="val => onSelect('city_code', val)"
                            />
                            <p v-if="fieldError('city_code')" class="form-error">{{ fieldError('city_code') }}</p>
                        </div>

                        <div>
                            <label class="form-label">Barangay</label>
                            <SelectSearch
                                :items="filteredBarangays"
                                itemLabelKey="name"
                                itemKeyProp="code"
                                v-model:search="searchState.barangay_code"
                                :modelValue="form.barangay_code"
                                v-model:open="openState.barangay_code"
                                @select="val => onSelect('barangay_code', val)"
                            />
                            <p v-if="fieldError('barangay_code')" class="form-error">{{ fieldError('barangay_code') }}</p>
                        </div>
                    </div>
                </div>

                <div v-for="category in categories" :key="category.value" class="form-card">
                    <div class="section-header">
                        <h2 class="section-title">{{ category.label }}</h2>
                    </div>

                    <div
                        v-for="({ item, originalIndex }, index) in getItemsByCategory(category.value)"
                        :key="item.id ?? `${category.value}-${originalIndex}`"
                        class="item-card"
                    >
                        <button
                            type="button"
                            class="remove-x-btn"
                            @click="removeItemByOriginalIndex(originalIndex)"
                        >
                            ✕
                        </button>

                        <div class="form-grid">
                            <label class="form-label" style="grid-column: 1 / -1;">
                                {{ category.label }} #{{ index + 1 }}
                            </label>

                            <div>
                                <label class="form-label">Name</label>
                                <SelectSearch
                                    :items="getNameOptions(category.value)"
                                    itemLabelKey="name"
                                    itemKeyProp="name"
                                    v-model:search="item.name_search"
                                    :modelValue="item.name"
                                    v-model:open="nameOpenState[item.id ?? `${category.value}-${originalIndex}`]"
                                    @select="val => { item.name = val.name; item.name_search = val.name }"
                                />
                                <p v-if="fieldError(`inventoryItem.${originalIndex}.name`)" class="form-error">
                                    {{ fieldError(`inventoryItem.${originalIndex}.name`) }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label">Guaranteeing Agency</label>
                                <Input class="form-input" v-model="item.granting_agency" />
                                <p v-if="fieldError(`inventoryItem.${originalIndex}.granting_agency`)" class="form-error">
                                    {{ fieldError(`inventoryItem.${originalIndex}.granting_agency`) }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label">Location</label>
                                <Input class="form-input" v-model="item.location" />
                                <p v-if="fieldError(`inventoryItem.${originalIndex}.location`)" class="form-error">
                                    {{ fieldError(`inventoryItem.${originalIndex}.location`) }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label">Value</label>
                                <Input class="form-input" type="number" step="0.01" v-model="item.value" />
                                <p v-if="fieldError(`inventoryItem.${originalIndex}.value`)" class="form-error">
                                    {{ fieldError(`inventoryItem.${originalIndex}.value`) }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label">Quantity</label>
                                <Input class="form-input" type="number" v-model="item.quantity" />
                                <p v-if="fieldError(`inventoryItem.${originalIndex}.quantity`)" class="form-error">
                                    {{ fieldError(`inventoryItem.${originalIndex}.quantity`) }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label">Status</label>
                                <select
                                    v-model="item.status"
                                    class="form-select"
                                    :disabled="Number(item.quantity) === 0"
                                >
                                    <option value="">Select Status</option>
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
                                <label class="form-label">Acquired Date</label>
                                <Input
                                    class="form-input"
                                    type="date"
                                    v-model="item.acquired_date"
                                    :max="today"
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

                                <p v-if="fieldError(`inventoryItem.${originalIndex}.item_picture`)" class="form-error">
                                    {{ fieldError(`inventoryItem.${originalIndex}.item_picture`) }}
                                </p>
                            </div>

                            <div>
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