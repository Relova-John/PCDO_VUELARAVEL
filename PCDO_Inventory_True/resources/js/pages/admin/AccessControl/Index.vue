<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
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
import { reactive, computed, watch, ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import type { Regions, Provinces, Cities } from '@/types/locations'
import { toast } from 'vue-sonner'

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
    location_accesses?: Array<{
        region_code?: string | null
        province_code?: string | null
        city_code?: string | null
    }>
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

function isAdminRole(value?: string | null) {
    return normalizeRole(value) === 'admin'
}

function isOfficerIRole(value?: string | null) {
    return normalizeRole(value) === 'officeri'
}

function isOfficerIIRole(value?: string | null) {
    return normalizeRole(value) === 'officerii'
}

const qrModal = reactive({
    open: false,
    title: '',
    svg: '',
    filename: 'qr-code.png'
})

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
        if (isSuperadminRole(currentRole.value)) {
            return ['admin', 'officeri', 'officerii'].includes(normalizeRole(r.name))
        }

        if (isAdminRole(currentRole.value)) {
            return ['officeri', 'officerii'].includes(normalizeRole(r.name))
        }

        if (isOfficerIRole(currentRole.value)) {
            return ['officerii'].includes(normalizeRole(r.name))
        }

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
    region_code: String(currentUser.value?.region_code || '1700000000'),
    province_code: String(currentUser.value?.province_code || '1705300000'),
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

const regionNameMap = computed(() => {
    const map = new Map<string, string>()

    for (const region of props.regions) {
        map.set(String(region.code), region.name)
    }

    return map
})

const provinceNameMap = computed(() => {
    const map = new Map<string, string>()

    for (const province of props.provinces) {
        map.set(String(province.code), province.name)
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
        createLocation.city_codes = []
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
        createLocation.city_codes = []
        createSearchState.city_code = ''
        createOpenState.city_code = false
    }
)

watch(
    () => role.value,
    (newRole, oldRole) => {
        createLocation.city_code = ''
        createLocation.city_codes = []
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

        createLocation.city_codes = createLocation.city_codes.filter(code => allowed.has(String(code)))
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
    createLocation.region_code = String(currentUser.value?.region_code || '1700000000')
    createLocation.province_code = String(currentUser.value?.province_code || '1705300000')
    createLocation.city_code = ''
    createLocation.city_codes = []
    createSearchState.region_code = ''
    createSearchState.province_code = ''
    createSearchState.city_code = ''
    createOpenState.region_code = false
    createOpenState.province_code = false
    createOpenState.city_code = false
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

const roleChangeModal = reactive({
    open: false,
    userId: null as number | null,
    userName: '',
    role: '',
    region_code: String(currentUser.value?.region_code || '1700000000'),
    province_code: String(currentUser.value?.province_code || '1705300000'),
    city_code: '',
    city_codes: [] as string[],
    errors: {} as Record<string, string>,
})

const roleChangeSearchState = reactive({
    region_code: '',
    province_code: '',
    city_code: '',
})

const roleChangeOpenState = reactive({
    region_code: false,
    province_code: false,
    city_code: false,
})

const isInitializingRoleChange = ref(false)
const roleChangeAutofocusRef = ref<HTMLElement | null>(null)

function setRoleChangeBaseLocation() {
    roleChangeModal.region_code = String(currentUser.value?.region_code || '1700000000')
    roleChangeModal.province_code = String(currentUser.value?.province_code || '1705300000')
    roleChangeModal.city_code = ''
    roleChangeModal.city_codes = []
    roleChangeSearchState.region_code = ''
    roleChangeSearchState.province_code = ''
    roleChangeSearchState.city_code = ''
    roleChangeOpenState.region_code = false
    roleChangeOpenState.province_code = false
    roleChangeOpenState.city_code = false
}

const roleChangeFilteredProvinces = computed(() =>
    props.provinces.filter(p => String(p.region_code) === String(roleChangeModal.region_code))
)

const roleChangeFilteredMunicipalities = computed(() =>
    props.cities.filter(c => String(c.province_code) === String(roleChangeModal.province_code))
)

const roleChangeMunicipalityOptions = computed(() => {
    if (isOfficerIRole(roleChangeModal.role)) {
        return roleChangeFilteredMunicipalities.value
    }

    if (isOfficerIIRole(roleChangeModal.role)) {
        if (isSuperadminRole(currentRole.value) || isAdminRole(currentRole.value)) {
            return roleChangeFilteredMunicipalities.value
        }

        if (isOfficerIRole(currentRole.value)) {
            const allowed = new Set(creatorAssignedCityCodes.value)
            return roleChangeFilteredMunicipalities.value.filter(city => allowed.has(String(city.code)))
        }

        return []
    }

    return []
})

const selectedRoleChangeOfficerICityChips = computed(() =>
    roleChangeModal.city_codes.map(code => ({
        code: String(code),
        name: cityNameMap.value.get(String(code)) ?? String(code),
    }))
)

const selectedRoleChangeOfficerIICityName = computed(() =>
    roleChangeMunicipalityOptions.value.find(city => String(city.code) === String(roleChangeModal.city_code))?.name ?? ''
)

watch(
    () => roleChangeModal.region_code,
    () => {
        if (isInitializingRoleChange.value) return

        roleChangeModal.province_code = ''
        roleChangeModal.city_code = ''
        roleChangeModal.city_codes = []
        roleChangeSearchState.province_code = ''
        roleChangeSearchState.city_code = ''
        roleChangeOpenState.province_code = false
        roleChangeOpenState.city_code = false
    }
)

watch(
    () => roleChangeModal.province_code,
    () => {
        if (isInitializingRoleChange.value) return

        roleChangeModal.city_code = ''
        roleChangeModal.city_codes = []
        roleChangeSearchState.city_code = ''
        roleChangeOpenState.city_code = false
    }
)

watch(
    () => roleChangeModal.role,
    () => {
        if (isInitializingRoleChange.value) return

        roleChangeModal.city_code = ''
        roleChangeModal.city_codes = []
        roleChangeSearchState.city_code = ''
        roleChangeOpenState.city_code = false
        roleChangeModal.errors = {}
    }
)

watch(
    roleChangeMunicipalityOptions,
    (items) => {
        const allowed = new Set(items.map(item => String(item.code)))

        if (roleChangeModal.city_code && !allowed.has(String(roleChangeModal.city_code))) {
            roleChangeModal.city_code = ''
            roleChangeSearchState.city_code = ''
        }

        roleChangeModal.city_codes = roleChangeModal.city_codes.filter(code => allowed.has(String(code)))
    },
    { deep: true }
)

function onRoleChangeLocationSelect(field: 'region_code' | 'province_code' | 'city_code', payload: { id: string; name: string }) {
    if (field === 'region_code') {
        roleChangeModal.region_code = String(payload.id)
        roleChangeSearchState.region_code = payload.name
        roleChangeOpenState.region_code = false
        return
    }

    if (field === 'province_code') {
        roleChangeModal.province_code = String(payload.id)
        roleChangeSearchState.province_code = payload.name
        roleChangeOpenState.province_code = false
        return
    }

    roleChangeModal.city_code = String(payload.id)
    roleChangeSearchState.city_code = payload.name
    roleChangeOpenState.city_code = false
}

function toggleRoleChangeOfficerICity(cityCode: string) {
    const code = String(cityCode)

    if (roleChangeModal.city_codes.includes(code)) {
        roleChangeModal.city_codes = roleChangeModal.city_codes.filter(item => item !== code)
        return
    }

    roleChangeModal.city_codes = [...roleChangeModal.city_codes, code]
}

function removeRoleChangeOfficerICity(cityCode: string) {
    roleChangeModal.city_codes = roleChangeModal.city_codes.filter(code => String(code) !== String(cityCode))
}

function resetRoleChangeModal() {
    roleChangeModal.open = false
    roleChangeModal.userId = null
    roleChangeModal.userName = ''
    roleChangeModal.role = ''
    roleChangeModal.errors = {}

    isInitializingRoleChange.value = true
    setRoleChangeBaseLocation()

    nextTick(() => {
        isInitializingRoleChange.value = false
    })
}

async function changeRole(user: UserItem, newRole: string) {
    const previousRole = user.roles[0]?.name ?? ''

    if (!newRole || newRole === previousRole) return

    if (!isOfficerIRole(newRole) && !isOfficerIIRole(newRole)) {
        roleChangeModal.userId = user.id
        roleChangeModal.role = newRole
        submitRoleChange(previousRole)
        return
    }

    isInitializingRoleChange.value = true

    roleChangeModal.userId = user.id
    roleChangeModal.userName = user.name
    roleChangeModal.role = newRole
    roleChangeModal.errors = {}

    setRoleChangeBaseLocation()

    roleChangeModal.open = true

    await nextTick()
    roleChangeOpenState.region_code = false
    roleChangeOpenState.province_code = false
    roleChangeOpenState.city_code = false
    roleChangeAutofocusRef.value?.focus()

    await nextTick()
    isInitializingRoleChange.value = false
}

function submitRoleChange(previousRole?: string) {
    if (!roleChangeModal.userId) return

    roleChangeModal.errors = {}

    if (isOfficerIRole(roleChangeModal.role) && roleChangeModal.city_codes.length === 0) {
        roleChangeModal.errors.city_codes = 'Select at least one municipality for Officer I'
    }

    if (isOfficerIIRole(roleChangeModal.role) && !roleChangeModal.city_code) {
        roleChangeModal.errors.city_code = 'Select one municipality for Officer II'
    }

    if (Object.keys(roleChangeModal.errors).length > 0) {
        return
    }

    const user = users.value.data.find(u => u.id === roleChangeModal.userId)
    if (!user) return

    const oldRoles = [...user.roles]
    const oldRoleName = previousRole ?? oldRoles[0]?.name ?? ''

    user.roles = [{ id: 0, name: roleChangeModal.role }]

    router.post(
        `/admin/users/${roleChangeModal.userId}/change-role`,
        {
            role: roleChangeModal.role,
            city_code: isOfficerIIRole(roleChangeModal.role) ? roleChangeModal.city_code : null,
            city_codes: isOfficerIRole(roleChangeModal.role) ? roleChangeModal.city_codes : [],
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('User role updated.')
                resetRoleChangeModal()
            },
            onError: (serverErrors: any) => {
                user.roles = oldRoles
                roleChangeModal.errors = serverErrors || {}
                roleChangeModal.role = oldRoleName
                toast.error('Unable to update role.')
            }
        }
    )
}

const locationModal = reactive({
    open: false,
    userId: null as number | null,
    userName: '',
    role: '',
    region_code: String(currentUser.value?.region_code || '1700000000'),
    province_code: String(currentUser.value?.province_code || '1705300000'),
    city_code: '',
    city_codes: [] as string[],
    errors: {} as Record<string, string>,
})

const locationSearchState = reactive({
    region_code: '',
    province_code: '',
    city_code: '',
})

const locationOpenState = reactive({
    region_code: false,
    province_code: false,
    city_code: false,
})

const isInitializingLocationChange = ref(false)
const locationAutofocusRef = ref<HTMLElement | null>(null)

const locationFilteredProvinces = computed(() =>
    props.provinces.filter(p => String(p.region_code) === String(locationModal.region_code))
)

const locationFilteredMunicipalities = computed(() =>
    props.cities.filter(c => String(c.province_code) === String(locationModal.province_code))
)

const locationMunicipalityOptions = computed(() => {
    if (isOfficerIRole(locationModal.role)) {
        return locationFilteredMunicipalities.value
    }

    if (isOfficerIIRole(locationModal.role)) {
        if (isSuperadminRole(currentRole.value) || isAdminRole(currentRole.value)) {
            return locationFilteredMunicipalities.value
        }

        if (isOfficerIRole(currentRole.value)) {
            const allowed = new Set(creatorAssignedCityCodes.value)
            return locationFilteredMunicipalities.value.filter(city => allowed.has(String(city.code)))
        }

        return []
    }

    return []
})

const selectedLocationOfficerICityChips = computed(() =>
    locationModal.city_codes.map(code => ({
        code: String(code),
        name: cityNameMap.value.get(String(code)) ?? String(code),
    }))
)

const selectedLocationOfficerIICityName = computed(() =>
    locationMunicipalityOptions.value.find(city => String(city.code) === String(locationModal.city_code))?.name ?? ''
)

watch(
    () => locationModal.region_code,
    () => {
        if (isInitializingLocationChange.value) return

        locationModal.province_code = ''
        locationModal.city_code = ''
        locationModal.city_codes = []
        locationSearchState.province_code = ''
        locationSearchState.city_code = ''
        locationOpenState.province_code = false
        locationOpenState.city_code = false
    }
)

watch(
    () => locationModal.province_code,
    () => {
        if (isInitializingLocationChange.value) return

        locationModal.city_code = ''
        locationModal.city_codes = []
        locationSearchState.city_code = ''
        locationOpenState.city_code = false
    }
)

watch(
    locationMunicipalityOptions,
    (items) => {
        const allowed = new Set(items.map(item => String(item.code)))

        if (locationModal.city_code && !allowed.has(String(locationModal.city_code))) {
            locationModal.city_code = ''
            locationSearchState.city_code = ''
        }

        locationModal.city_codes = locationModal.city_codes.filter(code => allowed.has(String(code)))
    },
    { deep: true }
)

function setLocationModalBase(user?: UserItem) {
    const accesses = user?.location_accesses ?? []

    const firstAccess = accesses[0]

    const regionCode = String(
        firstAccess?.region_code ||
        currentUser.value?.region_code ||
        '1700000000'
    )

    const provinceCode = String(
        firstAccess?.province_code ||
        currentUser.value?.province_code ||
        '1705300000'
    )

    const cityCodes = accesses
        .map(access => String(access.city_code ?? ''))
        .filter(Boolean)

    const cityCode = cityCodes[0] ?? ''

    locationModal.region_code = regionCode
    locationModal.province_code = provinceCode
    locationModal.city_code = cityCode
    locationModal.city_codes = [...cityCodes]

    locationSearchState.region_code = regionNameMap.value.get(regionCode) ?? ''
    locationSearchState.province_code = provinceNameMap.value.get(provinceCode) ?? ''
    locationSearchState.city_code = cityNameMap.value.get(cityCode) ?? ''

    locationOpenState.region_code = false
    locationOpenState.province_code = false
    locationOpenState.city_code = false
}

async function openLocationModal(user: UserItem) {
    const userRole = user.roles[0]?.name ?? ''

    if (!isOfficerIRole(userRole) && !isOfficerIIRole(userRole)) {
        toast.error('Only Officer I and Officer II have location assignments.')
        return
    }

    isInitializingLocationChange.value = true

    locationModal.userId = user.id
    locationModal.userName = user.name
    locationModal.role = userRole
    locationModal.errors = {}

    setLocationModalBase(user)

    locationModal.open = true

    await nextTick()
    locationOpenState.region_code = false
    locationOpenState.province_code = false
    locationOpenState.city_code = false
    locationAutofocusRef.value?.focus()

    await nextTick()
    isInitializingLocationChange.value = false
}

function resetLocationModal() {
    locationModal.open = false
    locationModal.userId = null
    locationModal.userName = ''
    locationModal.role = ''
    locationModal.city_code = ''
    locationModal.city_codes = []
    locationModal.errors = {}

    isInitializingLocationChange.value = true

    locationSearchState.region_code = ''
    locationSearchState.province_code = ''
    locationSearchState.city_code = ''
    locationOpenState.region_code = false
    locationOpenState.province_code = false
    locationOpenState.city_code = false

    nextTick(() => {
        isInitializingLocationChange.value = false
    })
}

function onLocationSelect(field: 'region_code' | 'province_code' | 'city_code', payload: { id: string; name: string }) {
    if (field === 'region_code') {
        locationModal.region_code = String(payload.id)
        locationSearchState.region_code = payload.name
        locationOpenState.region_code = false
        return
    }

    if (field === 'province_code') {
        locationModal.province_code = String(payload.id)
        locationSearchState.province_code = payload.name
        locationOpenState.province_code = false
        return
    }

    locationModal.city_code = String(payload.id)
    locationSearchState.city_code = payload.name
    locationOpenState.city_code = false
}

function toggleLocationOfficerICity(cityCode: string) {
    const code = String(cityCode)

    if (locationModal.city_codes.includes(code)) {
        locationModal.city_codes = locationModal.city_codes.filter(item => item !== code)
        return
    }

    locationModal.city_codes = [...locationModal.city_codes, code]
}

function removeLocationOfficerICity(cityCode: string) {
    locationModal.city_codes = locationModal.city_codes.filter(code => String(code) !== String(cityCode))
}

function saveUserLocation() {
    if (!locationModal.userId) return

    locationModal.errors = {}

    if (isOfficerIRole(locationModal.role) && locationModal.city_codes.length === 0) {
        locationModal.errors.city_codes = 'Select at least one municipality for Officer I'
    }

    if (isOfficerIIRole(locationModal.role) && !locationModal.city_code) {
        locationModal.errors.city_code = 'Select one municipality for Officer II'
    }

    if (Object.keys(locationModal.errors).length > 0) return

    router.post(
        `/admin/users/${locationModal.userId}/change-location`,
        {
            city_code: isOfficerIIRole(locationModal.role) ? locationModal.city_code : null,
            city_codes: isOfficerIRole(locationModal.role) ? locationModal.city_codes : [],
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('User location updated.')
                resetLocationModal()
            },
            onError: (serverErrors: any) => {
                locationModal.errors = serverErrors || {}
                toast.error('Unable to update location.')
            },
        }
    )
}

/* FIXED TOGGLE FLOW */
const showDialog = ref(false)
const pendingAction = ref<'activate' | 'deactivate' | null>(null)
const pendingUserId = ref<number | null>(null)
const pendingUserName = ref<string>('')
const pendingValue = ref<boolean | null>(null)
const originalValue = ref<boolean | null>(null)
const toggleLoadingUserId = ref<number | null>(null)

function onToggle(user: { id: number; active: boolean; name: string }, _nextValue: boolean) {
    if (currentUser.value?.id === user.id) return
    if (toggleLoadingUserId.value !== null) return

    const current = Boolean(user.active)
    const next = !current

    pendingUserId.value = user.id
    pendingUserName.value = user.name
    originalValue.value = current
    pendingValue.value = next
    pendingAction.value = next ? 'activate' : 'deactivate'

    user.active = next
    showDialog.value = true
}

function confirmAction() {
    if (pendingUserId.value === null || pendingValue.value === null) return

    const user = users.value.data.find(u => u.id === pendingUserId.value)
    if (!user) {
        resetPending()
        return
    }

    toggleLoadingUserId.value = user.id

    if (pendingValue.value === true) {
        activateUser(user.id)
    } else {
        deactivateUser(user.id)
    }
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
    toggleLoadingUserId.value = null
}

function deactivateUser(id: number) {
    router.post(
        `/admin/users/${id}/deactivate`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('User deactivated.')
                resetPending()
            },
            onError: () => {
                const user = users.value.data.find(u => u.id === id)
                if (user && originalValue.value !== null) {
                    user.active = originalValue.value
                }

                toast.error('Unable to deactivate user.')
                resetPending()
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
            preserveState: true,
            onSuccess: () => {
                toast.success('User activated.')
                resetPending()
            },
            onError: () => {
                const user = users.value.data.find(u => u.id === id)
                if (user && originalValue.value !== null) {
                    user.active = originalValue.value
                }

                toast.error('Unable to activate user.')
                resetPending()
            }
        }
    )
}

function formatDate(dt?: string) {
    if (!dt) return '-'
    return new Date(dt).toLocaleString()
}

const isMobile = ref(false)

function handleResize() {
    isMobile.value = window.innerWidth < 640
}

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

onMounted(() => {
    handleResize()
    window.addEventListener('resize', handleResize)
})

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
                            <input v-model="name" type="text" placeholder="Name"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="{ 'border-red-500 ring-1 ring-red-200': errors.name }" />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
                        </div>

                        <div>
                            <input v-model="email" type="email" placeholder="Email"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="{ 'border-red-500 ring-1 ring-red-200': errors.email }" />
                            <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email }}</p>
                        </div>

                        <div>
                            <select v-model="role" class="w-full rounded-lg border px-3 py-2"
                                :class="{ 'border-red-500 ring-1 ring-red-200': errors.role }">
                                <option v-for="r in allowedRoles" :key="r.id" :value="r.name">
                                    {{ r.name }}
                                </option>
                            </select>
                            <p v-if="errors.role" class="mt-1 text-xs text-red-500">{{ errors.role }}</p>
                        </div>

                        <div v-if="shouldShowLocationAssignment"
                            class="space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
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
                                <SelectSearch :clearOnFocus="true" :items="regions" itemLabelKey="name"
                                    itemKeyProp="code" v-model:search="createSearchState.region_code"
                                    :modelValue="createLocation.region_code" v-model:open="createOpenState.region_code"
                                    @select="(val) => onCreateLocationSelect('region_code', val)" />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Province</label>
                                <div :class="{ 'opacity-50 pointer-events-none': !createLocation.region_code }">
                                    <SelectSearch :clearOnFocus="true" :items="createFilteredProvinces"
                                        itemLabelKey="name" itemKeyProp="code"
                                        v-model:search="createSearchState.province_code"
                                        :modelValue="createLocation.province_code"
                                        v-model:open="createOpenState.province_code"
                                        @select="(val) => onCreateLocationSelect('province_code', val)"
                                        :disabled="!createLocation.region_code" />
                                </div>
                            </div>

                            <div v-if="isOfficerIIRole(role)">
                                <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Municipality</label>
                                <div :class="{ 'opacity-50 pointer-events-none': !createLocation.province_code }">
                                    <SelectSearch :clearOnFocus="true" :items="municipalityOptionsForSelectedRole"
                                        itemLabelKey="name" itemKeyProp="code"
                                        v-model:search="createSearchState.city_code"
                                        :modelValue="createLocation.city_code" v-model:open="createOpenState.city_code"
                                        @select="(val) => onCreateLocationSelect('city_code', val)"
                                        :disabled="!createLocation.province_code" />
                                </div>
                                <p v-if="errors.city_code" class="mt-1 text-xs text-red-500">{{ errors.city_code }}</p>
                                <p v-if="selectedOfficerIICityName" class="mt-2 text-xs text-gray-600">
                                    Selected: <span class="font-medium">{{ selectedOfficerIICityName }}</span>
                                </p>
                            </div>

                            <div v-if="isOfficerIRole(role)">
                                <label
                                    class="mb-2 block text-xs font-bold uppercase text-gray-500">Municipalities</label>

                                <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3"
                                    :class="{ 'opacity-50 pointer-events-none': !createLocation.province_code }">
                                    <label v-for="city in municipalityOptionsForSelectedRole" :key="city.code"
                                        class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 hover:bg-gray-50">
                                        <input type="checkbox" class="h-4 w-4"
                                            :checked="createLocation.city_codes.includes(String(city.code))"
                                            @change="toggleOfficerICity(String(city.code))"
                                            :disabled="!createLocation.province_code" />
                                        <span class="text-sm text-gray-700">{{ city.name }}</span>
                                    </label>

                                    <div v-if="municipalityOptionsForSelectedRole.length === 0"
                                        class="text-sm text-gray-500">
                                        No municipalities available.
                                    </div>
                                </div>

                                <p v-if="errors.city_codes" class="mt-1 text-xs text-red-500">{{ errors.city_codes }}
                                </p>

                                <div v-if="selectedOfficerICityChips.length" class="mt-3 flex flex-wrap gap-2">
                                    <span v-for="city in selectedOfficerICityChips" :key="city.code"
                                        class="inline-flex items-center gap-2 rounded-full bg-black px-3 py-1 text-xs font-medium text-white">
                                        <span>{{ city.name }}</span>

                                        <button type="button"
                                            class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/20 text-[10px] leading-none hover:bg-white/30"
                                            @click="removeOfficerICity(city.code)">
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="createUser"
                            class="w-full rounded-lg bg-black px-4 py-2 font-medium text-white hover:opacity-90 disabled:opacity-50"
                            :disabled="creating">
                            {{ creating ? 'Creating...' : 'Create User' }}
                        </button>
                    </div>
                </div>

                <div class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">User List</h2>

                        <input v-model="search" type="text" placeholder="Search users..."
                            class="w-full rounded-lg border px-3 py-2 sm:w-72" />
                    </div>

                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="px-2 py-3">Name</th>
                                    <th class="px-2 py-3">Email</th>
                                    <th class="px-2 py-3">Roles</th>
                                    <th class="px-2 py-3">Location</th>
                                    <th class="px-2 py-3">Created</th>
                                    <th class="px-2 py-3 text-right">Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="user in filteredUsers" :key="user.id"
                                    class="border-b last:border-b-0 hover:bg-gray-50">
                                    <td class="px-2 py-3 font-medium text-gray-900">{{ user.name }}</td>
                                    <td class="px-2 py-3">{{ user.email }}</td>

                                    <td class="px-2 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <template
                                                v-if="isSuperadminRole(currentRole) && currentUser?.id !== user.id">
                                                <select
                                                    v-if="!user.roles.some(r => normalizeRole(r.name) === 'cooperative')"
                                                    class="h-10 rounded-lg border px-3 py-2 text-sm"
                                                    :value="user.roles[0]?.name"
                                                    @change="e => changeRole(user, (e.target as HTMLSelectElement).value)">
                                                    <option v-for="r in allowedRoles" :key="r.id" :value="r.name">
                                                        {{ r.name }}
                                                    </option>
                                                </select>

                                                <span v-else>
                                                    {{user.roles.map(r => r.name).join(', ') || '-'}}
                                                </span>
                                            </template>

                                            <template v-else>
                                                {{user.roles.map(r => r.name).join(', ') || '-'}}
                                            </template>
                                        </div>
                                    </td>

                                    <td class="px-2 py-3">
                                        <div class="flex flex-col gap-1">
                                            <template v-if="user.location_accesses?.length">
                                                <span v-for="(loc, i) in user.location_accesses" :key="i">
                                                    {{
                                                        [
                                                            regionNameMap.get(String(loc.region_code)),
                                                            provinceNameMap.get(String(loc.province_code)),
                                                            cityNameMap.get(String(loc.city_code))
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' / ')
                                                    }}
                                                </span>
                                            </template>

                                            <span v-else>N/A</span>
                                        </div>

                                        <div class="mt-2"
                                            v-if="isSuperadminRole(currentRole) || isAdminRole(currentRole) || (isOfficerIRole(currentRole) && user.roles.some(r => isOfficerIRole(r.name))) || (isOfficerIIRole(currentRole) && user.roles.some(r => isOfficerIIRole(r.name)))">
                                            <button
                                                v-if="user.roles.some(r => isOfficerIRole(r.name) || isOfficerIIRole(r.name))"
                                                type="button"
                                                class="inline-flex h-10 items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                                @click="openLocationModal(user)">
                                                Change Location
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-2 py-3">{{ formatDate(user.created_at) }}</td>

                                    <td class="px-2 py-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <span class="text-xs font-medium"
                                                :class="user.active ? 'text-green-600' : 'text-red-500'">
                                                {{ user.active ? 'Active' : 'Inactive' }}
                                            </span>

                                            <Switch v-if="!(
                                                isAdminRole(currentRole) &&
                                                user.roles.some(r => isSuperadminRole(r.name) || isAdminRole(r.name))
                                            )" :model-value="user.active"
                                                @update:modelValue="(value) => onToggle(user, value)"
                                                :disabled="currentUser?.id === user.id || toggleLoadingUserId === user.id" />
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="6" class="px-2 py-6 text-center text-gray-500">No users found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-4 md:hidden">
                        <div v-for="user in filteredUsers" :key="user.id"
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="space-y-2">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ user.name }}</p>
                                    <p class="break-all text-sm text-gray-500">{{ user.email }}</p>
                                </div>

                                <div class="text-sm text-gray-600">
                                    <p><span class="font-medium">Roles:</span> {{user.roles.map(r => r.name).join(', ')
                                        || '-' }}</p>
                                    <p><span class="font-medium">Created:</span> {{ formatDate(user.created_at) }}</p>
                                </div>

                                <div v-if="isSuperadminRole(currentRole) && currentUser?.id !== user.id"
                                    class="space-y-2">
                                    <select v-if="!user.roles.some(r => normalizeRole(r.name) === 'cooperative')"
                                        class="h-10 w-full rounded-lg border px-3 py-2 text-sm"
                                        :value="user.roles[0]?.name"
                                        @change="e => changeRole(user, (e.target as HTMLSelectElement).value)">
                                        <option v-for="r in allowedRoles" :key="r.id" :value="r.name">
                                            {{ r.name }}
                                        </option>
                                    </select>

                                    <button
                                        v-if="user.roles.some(r => isOfficerIRole(r.name) || isOfficerIIRole(r.name))"
                                        type="button"
                                        class="inline-flex h-10 w-full items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        @click="openLocationModal(user)">
                                        Change Location
                                    </button>
                                </div>

                                <div v-if="!(
                                    isAdminRole(currentRole) &&
                                    user.roles.some(r => isSuperadminRole(r.name) || isAdminRole(r.name))
                                )" class="flex items-center justify-between border-t pt-3">
                                    <span class="text-sm" :class="user.active ? 'text-green-600' : 'text-red-500'">
                                        {{ user.active ? 'Active' : 'Inactive' }}
                                    </span>

                                    <Switch :model-value="user.active"
                                        @update:modelValue="(value) => onToggle(user, value)"
                                        :disabled="currentUser?.id === user.id || toggleLoadingUserId === user.id" />
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

                                <div
                                    class="flex flex-col gap-1 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                                    <p>Record ID: {{ log.record_id ?? '-' }}</p>
                                    <p>Executed At: {{ formatDate(log.executed_at) }}</p>
                                </div>

                                <button type="button" @click="openChangesModal(log.id)"
                                    class="text-sm font-medium text-blue-600 hover:underline">
                                    View Changes
                                </button>
                            </div>
                        </div>
                    </li>

                    <li v-if="(recentLogs.data ?? []).length === 0" class="py-6 text-center text-gray-500">
                        No logs yet.
                    </li>
                </ul>
            </div>
        </div>

        <AlertDialog :open="showDialog"
            @update:open="(value) => { if (!value && toggleLoadingUserId === null) cancelAction() }">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ pendingAction === 'activate' ? 'Activate user: ' + pendingUserName : 'Deactivate user: ' +
                        pendingUserName }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        This will {{ pendingAction === 'activate' ? 'enable' : 'disable' }} the selected user account.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel type="button" @click="cancelAction" :disabled="toggleLoadingUserId !== null">
                        Cancel
                    </AlertDialogCancel>

                    <button type="button"
                        class="inline-flex h-10 items-center justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        @click="confirmAction" :disabled="toggleLoadingUserId !== null">
                        Confirm
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog :open="roleChangeModal.open" @update:open="(value) => { if (!value) resetRoleChangeModal() }">
            <AlertDialogContent class="max-w-2xl" @openAutoFocus="(e) => {
                e.preventDefault()
                roleChangeAutofocusRef?.focus()
            }">
                <button ref="roleChangeAutofocusRef" type="button" class="sr-only" tabindex="-1" aria-hidden="true">
                    modal focus target
                </button>

                <AlertDialogHeader>
                    <AlertDialogTitle>Assign location for {{ roleChangeModal.userName }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        <span v-if="isOfficerIRole(roleChangeModal.role)">
                            Officer I requires one or more municipality assignments.
                        </span>
                        <span v-else-if="isOfficerIIRole(roleChangeModal.role)">
                            Officer II requires one municipality assignment.
                        </span>
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Region</label>
                        <SelectSearch :clearOnFocus="true" :items="regions" itemLabelKey="name" itemKeyProp="code"
                            v-model:search="roleChangeSearchState.region_code" :modelValue="roleChangeModal.region_code"
                            v-model:open="roleChangeOpenState.region_code"
                            @select="(val) => onRoleChangeLocationSelect('region_code', val)" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Province</label>
                        <div :class="{ 'opacity-50 pointer-events-none': !roleChangeModal.region_code }">
                            <SelectSearch :clearOnFocus="true" :items="roleChangeFilteredProvinces" itemLabelKey="name"
                                itemKeyProp="code" v-model:search="roleChangeSearchState.province_code"
                                :modelValue="roleChangeModal.province_code"
                                v-model:open="roleChangeOpenState.province_code"
                                @select="(val) => onRoleChangeLocationSelect('province_code', val)"
                                :disabled="!roleChangeModal.region_code" />
                        </div>
                    </div>

                    <div v-if="isOfficerIIRole(roleChangeModal.role)">
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Municipality</label>
                        <div :class="{ 'opacity-50 pointer-events-none': !roleChangeModal.province_code }">
                            <SelectSearch :clearOnFocus="true" :items="roleChangeMunicipalityOptions"
                                itemLabelKey="name" itemKeyProp="code" v-model:search="roleChangeSearchState.city_code"
                                :modelValue="roleChangeModal.city_code" v-model:open="roleChangeOpenState.city_code"
                                @select="(val) => onRoleChangeLocationSelect('city_code', val)"
                                :disabled="!roleChangeModal.province_code" />
                        </div>
                        <p v-if="roleChangeModal.errors.city_code" class="mt-1 text-xs text-red-500">
                            {{ roleChangeModal.errors.city_code }}
                        </p>
                        <p v-if="selectedRoleChangeOfficerIICityName" class="mt-2 text-xs text-gray-600">
                            Selected: <span class="font-medium">{{ selectedRoleChangeOfficerIICityName }}</span>
                        </p>
                    </div>

                    <div v-if="isOfficerIRole(roleChangeModal.role)">
                        <label class="mb-2 block text-xs font-bold uppercase text-gray-500">Municipalities</label>

                        <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3"
                            :class="{ 'opacity-50 pointer-events-none': !roleChangeModal.province_code }">
                            <label v-for="city in roleChangeMunicipalityOptions" :key="city.code"
                                class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 hover:bg-gray-50">
                                <input type="checkbox" class="h-4 w-4"
                                    :checked="roleChangeModal.city_codes.includes(String(city.code))"
                                    @change="toggleRoleChangeOfficerICity(String(city.code))"
                                    :disabled="!roleChangeModal.province_code" />
                                <span class="text-sm text-gray-700">{{ city.name }}</span>
                            </label>

                            <div v-if="roleChangeMunicipalityOptions.length === 0" class="text-sm text-gray-500">
                                No municipalities available.
                            </div>
                        </div>

                        <p v-if="roleChangeModal.errors.city_codes" class="mt-1 text-xs text-red-500">
                            {{ roleChangeModal.errors.city_codes }}
                        </p>

                        <div v-if="selectedRoleChangeOfficerICityChips.length" class="mt-3 flex flex-wrap gap-2">
                            <span v-for="city in selectedRoleChangeOfficerICityChips" :key="city.code"
                                class="inline-flex items-center gap-2 rounded-full bg-black px-3 py-1 text-xs font-medium text-white">
                                <span>{{ city.name }}</span>

                                <button type="button"
                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/20 text-[10px] leading-none hover:bg-white/30"
                                    @click="removeRoleChangeOfficerICity(city.code)">
                                    ×
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="resetRoleChangeModal">Cancel</AlertDialogCancel>
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                        @click.prevent="submitRoleChange()">
                        Save
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog :open="locationModal.open" @update:open="(value) => { if (!value) resetLocationModal() }">
            <AlertDialogContent class="max-w-lg" @openAutoFocus="(e) => {
                e.preventDefault()
                locationAutofocusRef?.focus()
            }">
                <button ref="locationAutofocusRef" type="button" class="sr-only" tabindex="-1" aria-hidden="true">
                    modal focus target
                </button>

                <AlertDialogHeader>
                    <AlertDialogTitle>Change location for {{ locationModal.userName }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        <span v-if="isOfficerIRole(locationModal.role)">
                            Officer I can have multiple municipality assignments.
                        </span>
                        <span v-else-if="isOfficerIIRole(locationModal.role)">
                            Officer II can only have one municipality assignment.
                        </span>
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Region</label>
                        <SelectSearch :clearOnFocus="false" :items="regions" itemLabelKey="name" itemKeyProp="code"
                            v-model:search="locationSearchState.region_code" :modelValue="locationModal.region_code"
                            v-model:open="locationOpenState.region_code"
                            @select="(val) => onLocationSelect('region_code', val)" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Province</label>
                        <div :class="{ 'opacity-50 pointer-events-none': !locationModal.region_code }">
                            <SelectSearch :clearOnFocus="false" :items="locationFilteredProvinces" itemLabelKey="name"
                                itemKeyProp="code" v-model:search="locationSearchState.province_code"
                                :modelValue="locationModal.province_code" v-model:open="locationOpenState.province_code"
                                @select="(val) => onLocationSelect('province_code', val)"
                                :disabled="!locationModal.region_code" />
                        </div>
                    </div>

                    <div v-if="isOfficerIIRole(locationModal.role)">
                        <label class="mb-1 block text-xs font-bold uppercase text-gray-500">Municipality</label>
                        <div :class="{ 'opacity-50 pointer-events-none': !locationModal.province_code }">
                            <SelectSearch :clearOnFocus="false" :items="locationMunicipalityOptions" itemLabelKey="name"
                                itemKeyProp="code" v-model:search="locationSearchState.city_code"
                                :modelValue="locationModal.city_code" v-model:open="locationOpenState.city_code"
                                @select="(val) => onLocationSelect('city_code', val)"
                                :disabled="!locationModal.province_code" />
                        </div>

                        <p v-if="locationModal.errors.city_code" class="mt-1 text-xs text-red-500">
                            {{ locationModal.errors.city_code }}
                        </p>

                        <p v-if="selectedLocationOfficerIICityName" class="mt-2 text-xs text-gray-600">
                            Current: <span class="font-medium">{{ selectedLocationOfficerIICityName }}</span>
                        </p>
                    </div>

                    <div v-if="isOfficerIRole(locationModal.role)">
                        <label class="mb-2 block text-xs font-bold uppercase text-gray-500">Municipalities</label>

                        <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3"
                            :class="{ 'opacity-50 pointer-events-none': !locationModal.province_code }">
                            <label v-for="city in locationMunicipalityOptions" :key="city.code"
                                class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 hover:bg-gray-50">
                                <input type="checkbox" class="h-4 w-4"
                                    :checked="locationModal.city_codes.includes(String(city.code))"
                                    @change="toggleLocationOfficerICity(String(city.code))"
                                    :disabled="!locationModal.province_code" />
                                <span class="text-sm text-gray-700">{{ city.name }}</span>
                            </label>

                            <div v-if="locationMunicipalityOptions.length === 0" class="text-sm text-gray-500">
                                No municipalities available.
                            </div>
                        </div>

                        <p v-if="locationModal.errors.city_codes" class="mt-1 text-xs text-red-500">
                            {{ locationModal.errors.city_codes }}
                        </p>

                        <div v-if="selectedLocationOfficerICityChips.length" class="mt-3 flex flex-wrap gap-2">
                            <span v-for="city in selectedLocationOfficerICityChips" :key="city.code"
                                class="inline-flex items-center gap-2 rounded-full bg-black px-3 py-1 text-xs font-medium text-white">
                                <span>{{ city.name }}</span>

                                <button type="button"
                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/20 text-[10px] leading-none hover:bg-white/30"
                                    @click="removeLocationOfficerICity(city.code)">
                                    ×
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="resetLocationModal">Cancel</AlertDialogCancel>
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                        @click.prevent="saveUserLocation()">
                        Save
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog :open="qrModal.open" @update:open="(value) => { if (!value) closeQrModal() }">
            <AlertDialogContent class="max-w-md">
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ qrModal.title }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        Scan or download this QR code.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div class="flex justify-center">
                    <div class="max-w-full" v-html="qrModal.svg" />
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="closeQrModal">Close</AlertDialogCancel>
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                        @click="downloadQrAsPng">
                        Download PNG
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog :open="showValueModal" @update:open="showValueModal = $event">
            <AlertDialogContent class="max-w-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ modalTitle }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        Sync log details
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div v-if="loadingChanges" class="py-6 text-center text-sm text-gray-500">
                    Loading changes...
                </div>

                <div v-else class="max-h-[60vh] space-y-3 overflow-y-auto">
                    <div v-for="(item, index) in valueModalData" :key="index"
                        class="rounded-lg border border-gray-200 p-3">
                        <p class="text-xs font-semibold uppercase text-gray-500">{{ item.key }}</p>
                        <p class="mt-1 break-words text-sm text-gray-800">{{ item.value }}</p>
                    </div>

                    <div v-if="valueModalData.length === 0" class="py-4 text-center text-sm text-gray-500">
                        No changes available.
                    </div>
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="showValueModal = false">Close</AlertDialogCancel>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>