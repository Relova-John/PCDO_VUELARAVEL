<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import { reactive, computed, watch } from 'vue'
import type { Regions, Provinces, Cities } from '@/types/locations'

type EntryType = 'municipality'
type AccessType = 'access'

interface GeneratedEntry {
    id?: number
    token?: string
    code: string
    expires_at: string
    one_time: boolean
    max_uses: number | null
}

interface ExistingAccessControl {
    id: number
    type: AccessType
    token: string
    region_code: string | null
    province_code: string | null
    city_code: string | null
    barangay_code: string | null
    code: string
    expires_at: string | null
    one_time: boolean
    max_uses: number | null
}

const props = defineProps<{
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    accessControls: ExistingAccessControl[]
}>()

const filters = reactive({
    region_code: '1700000000',
    province_code: '1705300000',
    city_code: ''
})

const searchState = reactive({
    region_code: '',
    province_code: '',
    city_code: ''
})

const openState = reactive({
    region_code: false,
    province_code: false,
    city_code: false
})

const dependencyMap = {
    region_code: ['province_code', 'city_code'],
    province_code: ['city_code'],
    city_code: []
} as const

type LocationFields = 'region_code' | 'province_code' | 'city_code'

function onSelect(field: LocationFields, payload: { id: string; name: string }) {
    filters[field] = String(payload.id)
    searchState[field] = payload.name
    openState[field] = false

    dependencyMap[field].forEach(dep => {
        filters[dep] = ''
        searchState[dep] = ''
    })
}

const filteredProvinces = computed(() =>
    props.provinces.filter(p => String(p.region_code) === String(filters.region_code))
)

const filteredMunicipalities = computed(() =>
    props.cities.filter(c => String(c.province_code) === String(filters.province_code))
)

const accessForm = useForm({
    type: '',
    code: '',
    one_time: false,
    max_uses: null as number | null,
    expires_at: '',
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: '',
})

const generatedEntries = reactive<Record<string, GeneratedEntry>>({})

function makeKey(accessType: AccessType, entryType: EntryType, id: string | number) {
    return `${accessType}-${entryType}-${id}`
}

function generateRandomCode(prefix = 'QR') {
    const random = Math.random().toString(36).slice(2, 8).toUpperCase()
    return `${prefix}-${Date.now().toString().slice(-6)}-${random}`
}

function formatForDatetimeLocal(date: Date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')

    return `${year}-${month}-${day}T${hours}:${minutes}`
}

function oneWeekFromNow() {
    const date = new Date()
    date.setDate(date.getDate() + 7)
    return formatForDatetimeLocal(date)
}

function ensureEntry(accessType: AccessType, entryType: EntryType, id: string | number) {
    const key = makeKey(accessType, entryType, id)

    if (!generatedEntries[key]) {
        generatedEntries[key] = {
            code: '',
            expires_at: '',
            one_time: false,
            max_uses: null
        }
    }

    return generatedEntries[key]
}

function hydrateExistingEntries() {
    props.accessControls.forEach(item => {
        if (item.type === 'access' && item.city_code && !item.barangay_code) {
            const key = makeKey('access', 'municipality', item.city_code)

            generatedEntries[key] = {
                id: item.id,
                token: item.token,
                code: item.code ?? '',
                expires_at: item.expires_at ?? '',
                one_time: !!item.one_time,
                max_uses: item.max_uses ?? null
            }
        }
    })
}

watch(
    () => props.accessControls,
    () => {
        Object.keys(generatedEntries).forEach(k => delete generatedEntries[k])
        hydrateExistingEntries()
    },
    { immediate: true, deep: true }
)

async function copyCode(accessType: AccessType, entryType: EntryType, id: string | number) {
    const entry = ensureEntry(accessType, entryType, id)

    if (!entry.code) return

    await navigator.clipboard.writeText(entry.code)

    alert('Code copied.')
}

function persistEntry(payload: {
    access_type: AccessType
    region_code: string
    province_code: string
    city_code: string
    barangay_code: string
    code: string
    one_time: boolean
    max_uses: number | null
    expires_at: string
}) {
    accessForm.type = payload.access_type
    accessForm.code = payload.code
    accessForm.one_time = payload.one_time
    accessForm.max_uses = payload.max_uses
    accessForm.expires_at = payload.expires_at

    accessForm.region_code = payload.region_code || ''
    accessForm.province_code = payload.province_code || ''
    accessForm.city_code = payload.city_code || ''
    accessForm.barangay_code = payload.barangay_code || ''

    accessForm.post('/admin/access-control', {
        preserveScroll: true
    })
}

function regenerateOfficerMunicipality(city: Cities) {
    const entry = ensureEntry('access', 'municipality', city.code)

    entry.code = generateRandomCode('OFFICER')
    entry.one_time = true
    entry.max_uses = 1
    entry.expires_at = oneWeekFromNow()

    persistEntry({
        access_type: 'access',
        region_code: filters.region_code,
        province_code: filters.province_code,
        city_code: String(city.code),
        barangay_code: '',
        code: entry.code,
        one_time: true,
        max_uses: 1,
        expires_at: entry.expires_at
    })
}

function generateQrCode(accessType: AccessType, entryType: EntryType, id: string | number) {
    const entry = ensureEntry(accessType, entryType, id)

    if (!entry.id) {
        alert('Save the access code first before generating the QR.')
        return
    }

    window.open(`/admin/access-control/${entry.id}/qr`, '_blank')
}

function getFormQrCode() {
    window.open('/admin/access-control/static-form-qr', '_blank')
}
</script>

<template>
    <AppLayout>

        <Head title="Access Control" />

        <div class="p-6 max-w-6xl space-y-6">
            <div class="space-y-3 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Access Control</h1>

                <button type="button" @click="getFormQrCode"
                    class="px-4 py-2 rounded bg-black text-white hover:bg-gray-800">
                    Download Form QR
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 font-medium">Region</label>
                    <SelectSearch :items="regions" itemLabelKey="name" itemKeyProp="code"
                        v-model:search="searchState.region_code" :modelValue="filters.region_code"
                        v-model:open="openState.region_code"
                        @select="(val: { id: string; name: string }) => onSelect('region_code', val)" />
                </div>

                <div>
                    <label class="block mb-1 font-medium">Province</label>
                    <SelectSearch :items="filteredProvinces" itemLabelKey="name" itemKeyProp="code"
                        v-model:search="searchState.province_code" :modelValue="filters.province_code"
                        v-model:open="openState.province_code"
                        @select="(val: { id: string; name: string }) => onSelect('province_code', val)" />
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-lg border p-4 bg-gray-50">
                    <p class="text-sm text-gray-700">
                        Officer Unlock mode: expiry is automatically set to <strong>1 week</strong> and QR is always
                        <strong>one-time use</strong>.
                    </p>
                </div>

                <div class="rounded-xl border overflow-hidden">
                    <div class="grid grid-cols-12 bg-gray-100 px-4 py-3 font-semibold text-sm">
                        <div class="col-span-5">Municipality</div>
                        <div class="col-span-3">Generated Code</div>
                        <div class="col-span-4 text-right">Actions</div>
                    </div>

                    <div v-for="city in filteredMunicipalities" :key="city.code"
                        class="grid grid-cols-12 px-4 py-3 border-t items-center gap-2">
                        <div class="col-span-5">
                            {{ city.name }}
                        </div>

                        <div class="col-span-3 text-sm break-all">
                            {{ generatedEntries[`access-municipality-${city.code}`]?.code || '—' }}
                        </div>

                        <div class="col-span-4 flex justify-end gap-2 flex-wrap">
                            <button type="button" @click="regenerateOfficerMunicipality(city)"
                                class="px-3 py-2 rounded border bg-white hover:bg-gray-50">
                                Regenerate & Save
                            </button>

                            <button type="button" @click="copyCode('access', 'municipality', city.code)"
                                class="px-3 py-2 rounded border bg-white hover:bg-gray-50">
                                Copy
                            </button>

                            <button type="button" @click="generateQrCode('access', 'municipality', city.code)"
                                class="px-3 py-2 rounded bg-black text-white"
                                :disabled="!(generatedEntries[`access-municipality-${city.code}`]?.code)">
                                Generate QR
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredMunicipalities.length === 0" class="px-4 py-6 text-sm text-gray-500">
                        No municipalities found for the selected province.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>