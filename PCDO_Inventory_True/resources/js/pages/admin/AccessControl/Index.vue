<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import SelectSearch from '@/components/SelectSearch.vue'
import Switch from '@/components/ui/switch/Switch.vue'
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from '@/components/ui/alert-dialog'
import type { BreadcrumbItem } from '@/types'
import { reactive, computed, watch, ref, onBeforeUnmount } from 'vue'
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

interface UserRole {
    id: number
    name: string
}

interface UserItem {
    id: number
    name: string
    email: string
    roles: UserRole[]
    created_at: string
    active: boolean
}

interface SyncLogItem {
    id: number
    user_name: string
    table_name: string
    operation: string
    user_id: string
    record_id: string | null
    executed_at: string
    changes?: string | null
}

interface AuthUser {
    id: number
    role?: string
    roles?: Array<{ id: number; name: string }>
    region_code?: string | null
    province_code?: string | null
    city_code?: string | null
    assigned_city_codes?: string[] | null
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Access Control', href: '#' }
]

const props = defineProps<{
    regions: Regions[]
    provinces: Provinces[]
    cities: Cities[]
    barangays?: Array<{ code: string; name: string; city_code: string }>
    users?: {
        data: UserItem[]
    }
    recentLogs?: {
        data: SyncLogItem[]
        current_page?: number
        last_page?: number
        next_page_url?: string | null
        prev_page_url?: string | null
        links?: Array<{ url: string | null; label: string; active: boolean }>
    }
    roles?: Array<{ id: number; name: string }>
    filters?: { search?: string }
}>()

const page = usePage<{
    auth?: {
        user?: AuthUser
    }
}>()

const currentUser = computed(() => page.props.auth?.user)
const currentRole = computed(() => {
    const explicitRole = currentUser.value?.role
    if (explicitRole) return explicitRole
    return currentUser.value?.roles?.[0]?.name ?? ''
})

function normalizeRole(value?: string | null) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[_-]/g, ' ')
        .replace(/\s+/g, ' ')
}

function isSuperadminRole(value?: string | null) {
    return normalizeRole(value) === 'superadmin'
}

function isAdminRole(value?: string | string | null) {
    return normalizeRole(value) === 'admin'
}

function isOfficerIRole(value?: string | null) {
    const role = normalizeRole(value)
    return role === 'officeri'
}

function isOfficerIIRole(value?: string | null) {
    const role = normalizeRole(value)
    return role === 'officerii'
}

const qrModal = reactive({
    open: false,
    title: '',
    svg: '',
    filename: 'qr-code.png'
})

const accessForm = useForm({
    type: '',
    code: '',
    one_time: false,
    max_uses: null as number | null,
    expires_at: '',
    region_code: '',
    province_code: '',
    city_code: '',
})

const generatedEntries = reactive<Record<string, GeneratedEntry>>({})

function makeKey(accessType: AccessType, entryType: EntryType, id: string | number) {
    return `${accessType}-${entryType}-${id}`
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
            expires_at: oneWeekFromNow(),
            one_time: false,
            max_uses: null
        }
    }

    return generatedEntries[key]
}

async function copyCode(accessType: AccessType, entryType: EntryType, id: string | number) {
    const entry = ensureEntry(accessType, entryType, id)

    if (!entry.code) return

    await navigator.clipboard.writeText(entry.code)
    toast.success('Access code copied to clipboard!')
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

const users = ref<{ data: UserItem[] }>(props.users ?? { data: [] })
const recentLogs = ref(props.recentLogs ?? { data: [] as SyncLogItem[] })

watch(
    () => props.users,
    (value) => {
        users.value = value ?? { data: [] }
    },
    { deep: true }
)

watch(
    () => props.recentLogs,
    (value) => {
        recentLogs.value = value ?? { data: [] }
    },
    { deep: true }
)

const allowedRoles = computed(() =>
    (props.roles ?? []).filter((r) => {
        if (isSuperadminRole(currentRole.value)) return true
        if (isAdminRole(currentRole.value)) return !isSuperadminRole(r.name)
        if (isOfficerIRole(currentRole.value)) return isOfficerIIRole(r.name)
        return false
    })
)

const search = ref(props.filters?.search ?? '')

const filteredUsers = computed(() => {
    const term = search.value.trim().toLowerCase()

    if (!term) return users.value.data

    return users.value.data.filter(
        (u) =>
            u.name.toLowerCase().includes(term) ||
            u.email.toLowerCase().includes(term) ||
            u.roles.some((r) => r.name.toLowerCase().includes(term))
    )
})

const name = ref('')
const email = ref('')
const role = ref<string>(allowedRoles.value?.[0]?.name ?? '')
const creating = ref(false)
const errors = ref<Record<string, string>>({})

const createLocation = reactive({
    region_code: currentUser.value?.region_code || '1700000000',
    province_code: currentUser.value?.province_code || '1705300000',
    city_code: '',
    city_codes: [] as string[],
})

const createSearchState = reactive({
    region_code: '',
    province_code: '',
    city_code: '',
})

const createOpenState = reactive({
    region_code: false,
    province_code: false,
    city_code: false,
})

watch(allowedRoles, (value) => {
    if (!value.some(r => r.name === role.value)) {
        role.value = value?.[0]?.name ?? ''
    }
}, { immediate: true })

const createFilteredProvinces = computed(() =>
    props.provinces.filter(p => String(p.region_code) === String(createLocation.region_code))
)

const createFilteredMunicipalities = computed(() =>
    props.cities.filter(c => String(c.province_code) === String(createLocation.province_code))
)

const creatorAssignedCityCodes = computed<string[]>(() => {
    const explicit = currentUser.value?.assigned_city_codes?.map(String).filter(Boolean) ?? []
    if (explicit.length > 0) return explicit

    if (currentUser.value?.city_code) {
        return [String(currentUser.value.city_code)]
    }

    return []
})

const municipalityOptionsForSelectedRole = computed(() => {
    if (isOfficerIRole(role.value)) {
        return createFilteredMunicipalities.value
    }

    if (isOfficerIIRole(role.value)) {
        if (isSuperadminRole(currentRole.value) || isAdminRole(currentRole.value)) {
            return createFilteredMunicipalities.value
        }

        if (isOfficerIRole(currentRole.value)) {
            const allowed = new Set(creatorAssignedCityCodes.value)
            return createFilteredMunicipalities.value.filter(city => allowed.has(String(city.code)))
        }

        return []
    }

    return []
})

const cityNameMap = computed(() => {
    const map = new Map<string, string>()

    for (const city of props.cities) {
        map.set(String(city.code), city.name)
    }

    return map
})

const selectedOfficerICityChips = computed(() =>
    createLocation.city_codes.map(code => ({
        code: String(code),
        name: cityNameMap.value.get(String(code)) ?? String(code),
    }))
)

const selectedOfficerIICityName = computed(() =>
    municipalityOptionsForSelectedRole.value.find(city => String(city.code) === String(createLocation.city_code))?.name ?? ''
)

watch(
    () => createLocation.region_code,
    () => {
        createLocation.province_code = ''
        createLocation.city_code = ''
        createSearchState.province_code = ''
        createSearchState.city_code = ''
        createOpenState.province_code = false
        createOpenState.city_code = false
    }
)

watch(
    () => createLocation.province_code,
    () => {
        createLocation.city_code = ''
        createSearchState.city_code = ''
        createOpenState.city_code = false
    }
)

watch(
    () => role.value,
    (newRole, oldRole) => {
        createLocation.city_code = ''
        createSearchState.city_code = ''
        createOpenState.city_code = false

        if (!isOfficerIRole(newRole)) {
            createLocation.city_codes = []
        }

        if (isOfficerIRole(oldRole) && isOfficerIIRole(newRole)) {
            createLocation.city_codes = []
        }
    }
)

watch(
    municipalityOptionsForSelectedRole,
    (items) => {
        const allowed = new Set(items.map(item => String(item.code)))

        if (createLocation.city_code && !allowed.has(String(createLocation.city_code))) {
            createLocation.city_code = ''
            createSearchState.city_code = ''
        }
    },
    { deep: true }
)

function onCreateLocationSelect(field: 'region_code' | 'province_code' | 'city_code', payload: { id: string; name: string }) {
    if (field === 'region_code') {
        createLocation.region_code = String(payload.id)
        createSearchState.region_code = payload.name
        createOpenState.region_code = false
        return
    }

    if (field === 'province_code') {
        createLocation.province_code = String(payload.id)
        createSearchState.province_code = payload.name
        createOpenState.province_code = false
        return
    }

    createLocation.city_code = String(payload.id)
    createSearchState.city_code = payload.name
    createOpenState.city_code = false
}

function toggleOfficerICity(cityCode: string) {
    const code = String(cityCode)

    if (createLocation.city_codes.includes(code)) {
        createLocation.city_codes = createLocation.city_codes.filter(item => item !== code)
        return
    }

    createLocation.city_codes = [...createLocation.city_codes, code]
}

function removeOfficerICity(cityCode: string) {
    createLocation.city_codes = createLocation.city_codes.filter(code => String(code) !== String(cityCode))
}

const shouldShowLocationAssignment = computed(() =>
    isOfficerIRole(role.value) || isOfficerIIRole(role.value)
)

function resetForm() {
    name.value = ''
    email.value = ''
    role.value = allowedRoles.value?.[0]?.name ?? ''
    createLocation.region_code = currentUser.value?.region_code || '1700000000'
    createLocation.province_code = currentUser.value?.province_code || '1705300000'
    createLocation.city_code = ''
    createLocation.city_codes = []
    createSearchState.region_code = ''
    createSearchState.province_code = ''
    createSearchState.city_code = ''
    errors.value = {}
}

function createUser() {
    creating.value = true
    errors.value = {}

    if (!name.value.trim()) errors.value.name = 'Name is required'
    if (!email.value.trim()) errors.value.email = 'Email is required'
    if (!role.value) errors.value.role = 'Role is required'

    if (isOfficerIRole(role.value) && createLocation.city_codes.length === 0) {
        errors.value.city_codes = 'Select at least one municipality for Officer I'
    }

    if (isOfficerIIRole(role.value) && !createLocation.city_code) {
        errors.value.city_code = 'Select one municipality for Officer II'
    }

    if (Object.keys(errors.value).length > 0) {
        creating.value = false
        return
    }

    router.post(
        '/admin/users',
        {
            name: name.value,
            email: email.value,
            role: role.value,
            city_code: isOfficerIIRole(role.value) ? createLocation.city_code : null,
            city_codes: isOfficerIRole(role.value) ? createLocation.city_codes : [],
        },
        {
            preserveScroll: true,
            onFinish: () => {
                creating.value = false
            },
            onSuccess: () => {
                toast.success('User created successfully.')
                resetForm()
            },
            onError: (serverErrors: any) => {
                errors.value = serverErrors || {}
                toast.error('Unable to create user.')
            },
        }
    )
}

function changeRole(user: UserItem, newRole: string) {
    const oldRoles = [...user.roles]
    user.roles = [{ id: 0, name: newRole }]

    router.post(
        `/admin/users/${user.id}/change-role`,
        { role: newRole },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('User role updated.')
            },
            onError: () => {
                user.roles = oldRoles
                toast.error('Unable to update role.')
            }
        }
    )
}

const showDialog = ref(false)
const pendingAction = ref<'activate' | 'deactivate' | null>(null)
const pendingUserId = ref<number | null>(null)
const pendingValue = ref<boolean | null>(null)
const originalValue = ref<boolean | null>(null)

function onToggle(user: { id: number; active: boolean }) {
    pendingUserId.value = user.id
    originalValue.value = user.active
    pendingAction.value = user.active ? 'deactivate' : 'activate'
    pendingValue.value = !user.active
    showDialog.value = true
}

function confirmAction() {
    if (pendingUserId.value === null || pendingValue.value === null) return

    const user = users.value.data.find(u => u.id === pendingUserId.value)
    if (!user) return

    if (pendingAction.value === 'activate') activateUser(user.id)
    else deactivateUser(user.id)

    resetPending()
}

function cancelAction() {
    const user = users.value.data.find(u => u.id === pendingUserId.value)

    if (user && originalValue.value !== null) {
        user.active = originalValue.value
    }

    resetPending()
}

function resetPending() {
    showDialog.value = false
    pendingUserId.value = null
    pendingValue.value = null
    pendingAction.value = null
    originalValue.value = null
}

function deactivateUser(id: number) {
    router.post(
        `/admin/users/${id}/deactivate`,
        {},
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                toast.success('User deactivated.')
            },
            onError: () => {
                toast.error('Unable to deactivate user.')
            }
        }
    )
}

function activateUser(id: number) {
    router.post(
        `/admin/users/${id}/activate`,
        {},
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                toast.success('User activated.')
            },
            onError: () => {
                toast.error('Unable to activate user.')
            }
        }
    )
}

function formatDate(dt?: string) {
    if (!dt) return '-'
    return new Date(dt).toLocaleString()
}

function goToLogsPage(url: string | null | undefined) {
    if (!url) return

    router.visit(url, {
        method: 'get',
        preserveScroll: true,
        preserveState: false,
        replace: true,
    })
}

const showPageSelector = ref(false)
const pageSelectorList = ref<number[]>([])

function openPageSelector(start: number, end: number) {
    pageSelectorList.value = Array.from({ length: end - start - 1 }, (_, i) => start + i + 1)
    showPageSelector.value = true
}

const isMobile = ref(window.innerWidth < 640)

function handleResize() {
    isMobile.value = window.innerWidth < 640
}

window.addEventListener('resize', handleResize)

const showValueModal = ref(false)
const valueModalData = ref<{ key: string; value: string }[]>([])
const modalTitle = ref('')
const loadingChanges = ref(false)

function formatReadableValue(field: string, value: any): string {
    if (value === null || value === undefined || value === '') return 'empty'

    if (field === 'active') {
        return value ? 'Active' : 'Inactive'
    }

    if (field.endsWith('_at')) {
        return new Date(value).toLocaleString()
    }

    return String(value)
}

function formatFieldName(field: string): string {
    const map: Record<string, string> = {
        active: 'Account Status',
        updated_at: 'Last Updated',
        created_at: 'Created At',
        email: 'Email',
        name: 'Name'
    }

    return map[field] ?? field.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

async function openChangesModal(logId: number) {
    showValueModal.value = true
    valueModalData.value = []
    loadingChanges.value = true
    modalTitle.value = `Log #${logId}`

    try {
        const res = await fetch(`/admin/logs/${logId}/changes`)
        if (!res.ok) throw new Error('Failed to fetch changes')

        const data = await res.json()
        const changes = data.changes ? JSON.parse(data.changes) : {}

        if (typeof changes === 'object' && changes !== null) {
            valueModalData.value = Object.entries(changes)
                .filter(([key]) => !['password', 'remember_token', 'file_content', 'created_by'].includes(key))
                .map(([key, value]) => {
                    let displayValue = ''

                    if (
                        typeof value === 'object' &&
                        value !== null &&
                        'before' in value &&
                        'after' in value
                    ) {
                        const before = formatReadableValue(key, (value as any).before)
                        const after = formatReadableValue(key, (value as any).after)
                        displayValue = `Changed from ${before} to ${after}`
                    } else {
                        displayValue = formatReadableValue(key, value)
                    }

                    if (displayValue.length > 500) {
                        displayValue = `${displayValue.slice(0, 500)}...`
                    }

                    return {
                        key: formatFieldName(key),
                        value: displayValue
                    }
                })
        } else {
            valueModalData.value = [{ key: 'Raw', value: String(data.changes ?? 'No changes data available.') }]
        }
    } catch {
        valueModalData.value = [{ key: 'Error', value: 'Could not load changes.' }]
    } finally {
        loadingChanges.value = false
    }
}

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize)
})
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Access Control" />

        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <div class="coop-header-banner">
                <div class="header-content-left">
                    <h1 class="header-title">Access Control</h1>
                    <p class="header-subtitle">Manage municipality access codes, users, and recent sync activity.</p>
                </div>

                <div class="header-content-right">
                    <button type="button" @click="openFormQrModal" class="btn-download-white">
                        Form QR
                    </button>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-1 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Create User</h2>

                    <div v-if="allowedRoles.length === 0" class="text-sm text-gray-500">
                        You are not allowed to create users.
                    </div>

                    <div v-else class="space-y-4">
                        <div>
                            <input
                                v-model="name"
                                type="text"
                                placeholder="Name"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="{ 'border-red-500 ring-1 ring-red-200': errors.name }"
                            />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
                        </div>

                        <div>
                            <input
                                v-model="email"
                                type="email"
                                placeholder="Email"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="{ 'border-red-500 ring-1 ring-red-200': errors.email }"
                            />
                            <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email }}</p>
                        </div>

                        <div>
                            <select
                                v-model="role"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="{ 'border-red-500 ring-1 ring-red-200': errors.role }"
                            >
                                <option v-for="r in allowedRoles" :key="r.id" :value="r.name">
                                    {{ r.name }}
                                </option>
                            </select>
                            <p v-if="errors.role" class="mt-1 text-xs text-red-500">{{ errors.role }}</p>
                        </div>

                        <div v-if="shouldShowLocationAssignment" class="space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Location Assignment</h3>
                                <p class="mt-1 text-xs text-gray-500">
                                    <span v-if="isOfficerIRole(role)">
                                        Officer I can be assigned to multiple municipalities.
                                    </span>
                                    <span v-else-if="isOfficerIIRole(role)">
                                        Officer II can only be assigned to one municipality from the allowed areas.
                                    </span>
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Region</label>
                                <SelectSearch
                                    :clearOnFocus="true"
                                    :items="regions"
                                    itemLabelKey="name"
                                    itemKeyProp="code"
                                    v-model:search="createSearchState.region_code"
                                    :modelValue="createLocation.region_code"
                                    v-model:open="createOpenState.region_code"
                                    @select="(val) => onCreateLocationSelect('region_code', val)"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Province</label>
                                <div :class="{ 'opacity-50 pointer-events-none': !createLocation.region_code }">
                                    <SelectSearch
                                        :clearOnFocus="true"
                                        :items="createFilteredProvinces"
                                        itemLabelKey="name"
                                        itemKeyProp="code"
                                        v-model:search="createSearchState.province_code"
                                        :modelValue="createLocation.province_code"
                                        v-model:open="createOpenState.province_code"
                                        @select="(val) => onCreateLocationSelect('province_code', val)"
                                        :disabled="!createLocation.region_code"
                                    />
                                </div>
                            </div>

                            <div v-if="isOfficerIIRole(role)">
                                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Municipality</label>
                                <div :class="{ 'opacity-50 pointer-events-none': !createLocation.province_code }">
                                    <SelectSearch
                                        :clearOnFocus="true"
                                        :items="municipalityOptionsForSelectedRole"
                                        itemLabelKey="name"
                                        itemKeyProp="code"
                                        v-model:search="createSearchState.city_code"
                                        :modelValue="createLocation.city_code"
                                        v-model:open="createOpenState.city_code"
                                        @select="(val) => onCreateLocationSelect('city_code', val)"
                                        :disabled="!createLocation.province_code"
                                    />
                                </div>
                                <p v-if="errors.city_code" class="mt-1 text-xs text-red-500">{{ errors.city_code }}</p>
                                <p v-if="selectedOfficerIICityName" class="mt-2 text-xs text-gray-600">
                                    Selected: <span class="font-medium">{{ selectedOfficerIICityName }}</span>
                                </p>
                            </div>

                            <div v-if="isOfficerIRole(role)">
                                <label class="mb-2 block text-xs font-bold uppercase text-gray-500">Municipalities</label>

                                <div
                                    class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3"
                                    :class="{ 'opacity-50 pointer-events-none': !createLocation.province_code }"
                                >
                                    <label
                                        v-for="city in municipalityOptionsForSelectedRole"
                                        :key="city.code"
                                        class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 hover:bg-gray-50"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4"
                                            :checked="createLocation.city_codes.includes(String(city.code))"
                                            @change="toggleOfficerICity(String(city.code))"
                                            :disabled="!createLocation.province_code"
                                        />
                                        <span class="text-sm text-gray-700">{{ city.name }}</span>
                                    </label>

                                    <div v-if="municipalityOptionsForSelectedRole.length === 0" class="text-sm text-gray-500">
                                        No municipalities available.
                                    </div>
                                </div>

                                <p v-if="errors.city_codes" class="mt-1 text-xs text-red-500">{{ errors.city_codes }}</p>

                                <div v-if="selectedOfficerICityChips.length" class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="city in selectedOfficerICityChips"
                                        :key="city.code"
                                        class="inline-flex items-center gap-2 rounded-full bg-black px-3 py-1 text-xs font-medium text-white"
                                    >
                                        <span>{{ city.name }}</span>

                                        <button
                                            type="button"
                                            class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/20 text-[10px] leading-none hover:bg-white/30"
                                            @click="removeOfficerICity(city.code)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="createUser"
                            class="w-full rounded-lg bg-black px-4 py-2 font-medium text-white hover:opacity-90 disabled:opacity-50"
                            :disabled="creating"
                        >
                            {{ creating ? 'Creating...' : 'Create User' }}
                        </button>
                    </div>
                </div>

                <div class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">User List</h2>

                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search users..."
                            class="w-full rounded-lg border px-3 py-2 sm:w-72"
                        />
                    </div>

                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="px-2 py-3">Name</th>
                                    <th class="px-2 py-3">Email</th>
                                    <th class="px-2 py-3">Roles</th>
                                    <th class="px-2 py-3">Created</th>
                                    <th class="px-2 py-3 text-right">Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="user in filteredUsers"
                                    :key="user.id"
                                    class="border-b last:border-b-0 hover:bg-gray-50"
                                >
                                    <td class="px-2 py-3 font-medium text-gray-900">{{ user.name }}</td>
                                    <td class="px-2 py-3">{{ user.email }}</td>

                                    <td class="px-2 py-3">
                                        <template v-if="isSuperadminRole(currentRole) && currentUser?.id !== user.id">
                                            <select
                                                v-if="!user.roles.some(r => normalizeRole(r.name) === 'cooperative')"
                                                class="rounded-md border px-2 py-1 text-sm"
                                                :value="user.roles[0]?.name"
                                                @change="e => changeRole(user, (e.target as HTMLSelectElement).value)"
                                            >
                                                <option
                                                    v-for="r in allowedRoles"
                                                    :key="r.id"
                                                    :value="r.name"
                                                >
                                                    {{ r.name }}
                                                </option>
                                            </select>

                                            <span v-else>
                                                {{ user.roles.map(r => r.name).join(', ') || '-' }}
                                            </span>
                                        </template>

                                        <template v-else>
                                            {{ user.roles.map(r => r.name).join(', ') || '-' }}
                                        </template>
                                    </td>

                                    <td class="px-2 py-3">{{ formatDate(user.created_at) }}</td>

                                    <td class="px-2 py-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <span class="text-xs font-medium" :class="user.active ? 'text-green-600' : 'text-red-500'">
                                                {{ user.active ? 'Active' : 'Inactive' }}
                                            </span>

                                            <Switch
                                                v-if="!(
                                                    isAdminRole(currentRole) &&
                                                    user.roles.some(r => isSuperadminRole(r.name) || isAdminRole(r.name))
                                                )"
                                                :model-value="user.active"
                                                @update:modelValue="() => onToggle(user)"
                                                :disabled="currentUser?.id === user.id"
                                            />
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="5" class="px-2 py-6 text-center text-gray-500">No users found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-4 md:hidden">
                        <div
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <div class="space-y-2">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ user.name }}</p>
                                    <p class="break-all text-sm text-gray-500">{{ user.email }}</p>
                                </div>

                                <div class="text-sm text-gray-600">
                                    <p><span class="font-medium">Roles:</span> {{ user.roles.map(r => r.name).join(', ') || '-' }}</p>
                                    <p><span class="font-medium">Created:</span> {{ formatDate(user.created_at) }}</p>
                                </div>

                                <div v-if="isSuperadminRole(currentRole) && currentUser?.id !== user.id">
                                    <select
                                        v-if="!user.roles.some(r => normalizeRole(r.name) === 'cooperative')"
                                        class="w-full rounded-md border px-2 py-1 text-sm"
                                        :value="user.roles[0]?.name"
                                        @change="e => changeRole(user, (e.target as HTMLSelectElement).value)"
                                    >
                                        <option
                                            v-for="r in allowedRoles"
                                            :key="r.id"
                                            :value="r.name"
                                        >
                                            {{ r.name }}
                                        </option>
                                    </select>
                                </div>

                                <div
                                    v-if="!(
                                        isAdminRole(currentRole) &&
                                        user.roles.some(r => isSuperadminRole(r.name) || isAdminRole(r.name))
                                    )"
                                    class="flex items-center justify-between border-t pt-3"
                                >
                                    <span class="text-sm" :class="user.active ? 'text-green-600' : 'text-red-500'">
                                        {{ user.active ? 'Active' : 'Inactive' }}
                                    </span>

                                    <Switch
                                        :model-value="user.active"
                                        @update:modelValue="() => onToggle(user)"
                                        :disabled="currentUser?.id === user.id"
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-if="filteredUsers.length === 0" class="py-4 text-center text-gray-500">
                            No users found.
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Recent Sync Logs</h2>

                <ul class="divide-y divide-gray-200">
                    <li v-for="log in recentLogs.data ?? []" :key="log.id" class="py-4">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <div class="space-y-2">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ log.table_name }}
                                        <span class="text-xs font-normal text-gray-500">— {{ log.operation }}</span>
                                    </p>

                                    <p class="text-sm text-gray-600">
                                        User: {{ log.user_name }}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-1 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                                    <p>Record ID: {{ log.record_id ?? '-' }}</p>
                                    <p>Executed At: {{ formatDate(log.executed_at) }}</p>
                                </div>

                                <button
                                    type="button"
                                    @click="openChangesModal(log.id)"
                                    class="text-sm font-medium text-blue-600 hover:underline"
                                >
                                    View Changes
                                </button>
                            </div>
                        </div>
                    </li>

                    <li v-if="(recentLogs.data ?? []).length === 0" class="py-6 text-center text-gray-500">
                        No logs yet.
                    </li>
                </ul>

                <div
                    v-if="(recentLogs?.last_page ?? 1) > 1"
                    class="mt-4 flex flex-wrap items-center justify-center gap-2"
                >
                    <button
                        v-if="recentLogs?.prev_page_url"
                        @click="goToLogsPage(recentLogs.prev_page_url)"
                        class="rounded-md bg-gray-200 px-3 py-1 text-gray-700 hover:opacity-90"
                    >
                        {{ isMobile ? '←' : '← Prev' }}
                    </button>

                    <template v-if="(recentLogs?.last_page ?? 1) <= 10">
                        <button
                            v-for="pageNum in recentLogs?.last_page ?? 1"
                            :key="pageNum"
                            @click="goToLogsPage(`/admin?logs_page=${pageNum}`)"
                            :class="[
                                'rounded-md border px-3 py-1',
                                (recentLogs?.current_page ?? 1) === pageNum
                                    ? 'border-black bg-black text-white'
                                    : 'bg-gray-200 text-gray-700 hover:opacity-90'
                            ]"
                        >
                            {{ pageNum }}
                        </button>
                    </template>

                    <template v-else>
                        <button
                            v-for="pageNum in (isMobile ? [1] : [1, 2, 3])"
                            :key="'start-' + pageNum"
                            @click="goToLogsPage(`/admin?logs_page=${pageNum}`)"
                            :class="[
                                'rounded-md border px-3 py-1',
                                (recentLogs?.current_page ?? 1) === pageNum
                                    ? 'border-black bg-black text-white'
                                    : 'bg-gray-200 text-gray-700 hover:opacity-90'
                            ]"
                        >
                            {{ pageNum }}
                        </button>

                        <button
                            v-if="(recentLogs?.current_page ?? 1) > (isMobile ? 3 : 5)"
                            @click="openPageSelector(1, (recentLogs?.current_page ?? 1) - 1)"
                            class="rounded-md border bg-gray-200 px-3 py-1 text-gray-700 hover:opacity-90"
                        >
                            ...
                        </button>

                        <template
                            v-for="pageNum in [
                                (recentLogs?.current_page ?? 1) - 1,
                                (recentLogs?.current_page ?? 1),
                                (recentLogs?.current_page ?? 1) + 1
                            ]"
                        >
                            <button
                                v-if="pageNum > (isMobile ? 1 : 3) && pageNum < (recentLogs?.last_page ?? 1) - (isMobile ? 1 : 2)"
                                :key="'mid-' + pageNum"
                                @click="goToLogsPage(`/admin?logs_page=${pageNum}`)"
                                :class="[
                                    'rounded-md border px-3 py-1',
                                    (recentLogs?.current_page ?? 1) === pageNum
                                        ? 'border-black bg-black text-white'
                                        : 'bg-gray-200 text-gray-700 hover:opacity-90'
                                ]"
                            >
                                {{ pageNum }}
                            </button>
                        </template>

                        <button
                            v-if="(recentLogs?.current_page ?? 1) < (recentLogs?.last_page ?? 1) - (isMobile ? 2 : 4)"
                            @click="openPageSelector((recentLogs?.current_page ?? 1), (recentLogs?.last_page ?? 1))"
                            class="rounded-md border bg-gray-200 px-3 py-1 text-gray-700 hover:opacity-90"
                        >
                            ...
                        </button>

                        <button
                            v-for="pageNum in (
                                isMobile
                                    ? [(recentLogs?.last_page ?? 1)]
                                    : [(recentLogs?.last_page ?? 1) - 2, (recentLogs?.last_page ?? 1) - 1, (recentLogs?.last_page ?? 1)]
                            )"
                            :key="'end-' + pageNum"
                            @click="goToLogsPage(`/admin?logs_page=${pageNum}`)"
                            :class="[
                                'rounded-md border px-3 py-1',
                                (recentLogs?.current_page ?? 1) === pageNum
                                    ? 'border-black bg-black text-white'
                                    : 'bg-gray-200 text-gray-700 hover:opacity-90'
                            ]"
                        >
                            {{ pageNum }}
                        </button>
                    </template>

                    <button
                        v-if="recentLogs?.next_page_url"
                        @click="goToLogsPage(recentLogs.next_page_url)"
                        class="rounded-md bg-gray-200 px-3 py-1 text-gray-700 hover:opacity-90"
                    >
                        {{ isMobile ? '→' : 'Next →' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="qrModal.open"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4"
            @click.self="closeQrModal"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
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
                    <button type="button" class="btn-past-white text-sm" @click="closeQrModal">
                        Close
                    </button>

                    <button type="button" class="btn-past-black text-sm" @click="downloadQrAsPng">
                        Download PNG
                    </button>
                </div>
            </div>
        </div>

        <teleport to="body">
            <div
                v-if="showPageSelector"
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50"
                @click.self="showPageSelector = false"
            >
                <div class="max-h-[70vh] w-[90%] overflow-y-auto rounded-lg bg-white p-4 shadow-xl sm:w-64">
                    <h3 class="mb-2 text-center text-lg font-semibold text-gray-800">Jump to page</h3>

                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="pageNum in pageSelectorList"
                            :key="'select-' + pageNum"
                            @click="goToLogsPage(`/admin?logs_page=${pageNum}`); showPageSelector = false"
                            class="rounded-md border bg-gray-100 px-2 py-1 text-gray-700 transition hover:bg-black hover:text-white"
                        >
                            {{ pageNum }}
                        </button>
                    </div>

                    <div class="mt-3 text-center">
                        <button
                            @click="showPageSelector = false"
                            class="mt-2 text-sm text-gray-500 hover:text-gray-800"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <teleport to="body">
            <div
                v-if="showValueModal"
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50"
                @click.self="showValueModal = false"
            >
                <div class="max-h-[80vh] w-[90%] overflow-auto rounded-lg bg-white p-4 shadow-xl sm:w-[500px]">
                    <h3 class="mb-2 text-lg font-semibold text-gray-800">
                        {{ modalTitle }}
                    </h3>

                    <div v-if="loadingChanges" class="py-6 text-center text-gray-500">
                        Loading changes...
                    </div>

                    <div v-else-if="valueModalData.length" class="space-y-2 font-mono text-sm text-gray-700">
                        <div
                            v-for="change in valueModalData"
                            :key="change.key"
                            class="border-b border-gray-200 pb-2"
                        >
                            <p class="font-semibold text-blue-600">
                                {{ change.key }}
                            </p>
                            <p class="whitespace-pre-wrap break-words">
                                {{ change.value }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="py-4 text-center text-gray-500">
                        No changes data available.
                    </div>

                    <div class="mt-3 text-center">
                        <button
                            @click="showValueModal = false"
                            class="rounded-md bg-black px-4 py-1 text-white hover:opacity-90"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <AlertDialog v-model:open="showDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ pendingAction === 'activate' ? 'Activate User' : 'Deactivate User' }}
                    </AlertDialogTitle>

                    <AlertDialogDescription>
                        Are you sure you want to
                        {{ pendingAction === 'activate' ? 'activate' : 'deactivate' }}
                        this user?
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="cancelAction">
                        Cancel
                    </AlertDialogCancel>

                    <AlertDialogAction @click="confirmAction">
                        {{ pendingAction === 'activate' ? 'Activate' : 'Deactivate' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>

<style scoped>
:disabled {
    cursor: not-allowed;
    opacity: 0.5;
    background-color: #f3f4f6;
    color: #9ca3af;
}
</style>