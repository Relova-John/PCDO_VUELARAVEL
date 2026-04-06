<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import type { BreadcrumbItem } from '@/types'
import { reactive, computed, watch } from 'vue'
import type { Regions, Provinces, Cities } from '@/types/locations'
import { toast } from 'vue-sonner'

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Access Control', href: '#' }
]

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

const qrModal = reactive({
    open: false,
    title: '',
    svg: '',
    filename: 'qr-code'
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

    toast.success('Access code copied to clipboard!')
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

function slugify(value: string) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
}

async function openQrModal(accessType: AccessType, entryType: EntryType, id: string | number, label: string) {
    const entry = ensureEntry(accessType, entryType, id)

    if (!entry.id) {
        alert('Save the access code first before generating the QR.')
        return
    }

    try {
        const response = await fetch(`/admin/access-control/${entry.id}/qr`, {
            headers: {
                Accept: 'image/svg+xml'
            }
        })

        if (!response.ok) {
            throw new Error('Failed to load QR code.')
        }

        const svg = await response.text()

        qrModal.open = true
        qrModal.title = label
        qrModal.svg = svg
        qrModal.filename = `${slugify(label || 'qr-code')}.png`
    } catch {
        toast.error('Unable to load QR code.')
    }
}

async function openFormQrModal() {
    try {
        const response = await fetch('/admin/access-control/static-form-qr', {
            headers: {
                Accept: 'image/svg+xml'
            }
        })

        if (!response.ok) {
            throw new Error('Failed to load QR code.')
        }

        const svg = await response.text()

        qrModal.open = true
        qrModal.title = 'Form QR'
        qrModal.svg = svg
        qrModal.filename = 'form.png'
    } catch {
        toast.error('Unable to load QR code.')
    }
}

function closeQrModal() {
    qrModal.open = false
    qrModal.title = ''
    qrModal.svg = ''
    qrModal.filename = 'qr-code.png'
}

async function downloadQrAsPng() {
    if (!qrModal.svg) return

    const svgBlob = new Blob([qrModal.svg], { type: 'image/svg+xml;charset=utf-8' })
    const svgUrl = URL.createObjectURL(svgBlob)

    const img = new Image()

    img.onload = () => {
        const canvas = document.createElement('canvas')
        canvas.width = img.width || 300
        canvas.height = img.height || 300

        const ctx = canvas.getContext('2d')
        if (!ctx) {
            URL.revokeObjectURL(svgUrl)
            toast.error('Unable to prepare PNG download.')
            return
        }

        ctx.fillStyle = '#ffffff'
        ctx.fillRect(0, 0, canvas.width, canvas.height)
        ctx.drawImage(img, 0, 0)

        canvas.toBlob(blob => {
            URL.revokeObjectURL(svgUrl)

            if (!blob) {
                toast.error('Unable to prepare PNG download.')
                return
            }

            const pngUrl = URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = pngUrl
            link.download = qrModal.filename
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            URL.revokeObjectURL(pngUrl)

            toast.success('QR downloaded as PNG.')
        }, 'image/png')
    }

    img.onerror = () => {
        URL.revokeObjectURL(svgUrl)
        toast.error('Unable to prepare PNG download.')
    }

    img.src = svgUrl
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Access Control" />

        <div class="p-6 max-w-7xl space-y-6">
            <div class="coop-header-banner">
                <div class="header-content-left">
                    <h1 class="header-title">Access Control</h1>
                    <p class="header-subtitle">Manage municipality access codes and QR generation.</p>
                </div>

                <div class="header-content-right">
                    <button type="button" @click="openFormQrModal" class="btn-download-white">
                        Form QR
                    </button>
                </div>
            </div>

            <div class="coop-card-header" style="border-radius: 14px; border: 1.5px solid #e5e7eb;">
                <div class="coop-filter">
                    <div class="flex-1">
                        <label class="block mb-1 text-xs font-bold uppercase text-gray-500">Region</label>
                        <SelectSearch
                            :items="regions"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="searchState.region_code"
                            :modelValue="filters.region_code"
                            v-model:open="openState.region_code"
                            @select="(val) => onSelect('region_code', val)"
                        />
                    </div>

                    <div class="flex-1">
                        <label class="block mb-1 text-xs font-bold uppercase text-gray-500">Province</label>
                        <SelectSearch
                            :items="filteredProvinces"
                            itemLabelKey="name"
                            itemKeyProp="code"
                            v-model:search="searchState.province_code"
                            :modelValue="filters.province_code"
                            v-model:open="openState.province_code"
                            @select="(val) => onSelect('province_code', val)"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-lg p-4" style="background: #FFF8E1; border: 1px solid #FAD6A5;">
                <p class="text-sm text-gray-700">
                    <strong style="color: #D56C10;">Officer Unlock mode:</strong> expiry is automatically set to 1 week
                    and QR is always one-time use.
                </p>
            </div>

            <div class="rounded-xl overflow-hidden border border-gray-200">
                <table class="coop-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Municipality</th>
                            <th style="width: 25%;">Generated Code</th>
                            <th style="width: 35%; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="city in filteredMunicipalities" :key="city.code" class="coop-row">
                            <td class="font-semibold">{{ city.name }}</td>
                            <td style="font-family: monospace; color: #6b7280;">
                                {{ generatedEntries[`access-municipality-${city.code}`]?.code || '—' }}
                            </td>
                            <td style="text-align: right;">
                                <div class="flex justify-end gap-2">
                                    <button @click="regenerateOfficerMunicipality(city)" class="btn-past-white text-xs">
                                        {{ generatedEntries[`access-municipality-${city.code}`]?.code ? 'Regenerate' : 'Generate' }}
                                    </button>

                                    <button @click="copyCode('access', 'municipality', city.code)" class="btn-past-white text-xs">
                                        Copy
                                    </button>

                                    <button
                                        @click="openQrModal('access', 'municipality', city.code, `${city.name} QR`)"
                                        class="btn-past-black text-xs"
                                        :disabled="!(generatedEntries[`access-municipality-${city.code}`]?.code)"
                                    >
                                        QR
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredMunicipalities.length === 0">
                            <td colspan="3" class="coop-empty">
                                No municipalities found for the selected province.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="qrModal.open"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4"
            @click.self="closeQrModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ qrModal.title }}</h2>
                        <p class="text-sm text-gray-500">Preview QR code before downloading.</p>
                    </div>

                    <button
                        type="button"
                        class="rounded-full px-3 py-1 text-sm font-semibold text-gray-500 hover:bg-gray-100"
                        @click="closeQrModal"
                    >
                        ✕
                    </button>
                </div>

                <div class="p-6">
                    <div class="flex justify-center rounded-xl border border-gray-200 bg-gray-50 p-6">
                        <div class="h-[300px] w-[300px]" v-html="qrModal.svg"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                    <button
                        type="button"
                        class="btn-past-white text-sm"
                        @click="closeQrModal"
                    >
                        Close
                    </button>

                    <button
                        type="button"
                        class="btn-past-black text-sm"
                        @click="downloadQrAsPng"
                    >
                        Download PNG
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>