<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog'
import Switch from '@/components/ui/switch/Switch.vue'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Admin', href: '/admin' }]

const props = defineProps<{
    users?: {
        data: Array<{
            id: number
            name: string
            email: string
            roles: { id: number; name: string }[]
            created_at: string
            active: boolean
        }>
    }
    recentLogs?: {
        data: Array<{
            id: number
            user_name: string
            table_name: string
            operation: string
            user_id: string
            record_id: string | null
            executed_at: string
            changes?: string | null
        }>
        current_page?: number
        last_page?: number
        next_page_url?: string | null
        prev_page_url?: string | null
        links?: Array<{ url: string | null; label: string; active: boolean }>
    }
    roles?: Array<{ id: number; name: string }>
    filters?: { search?: string }
}>()

const users = ref(props.users ?? { data: [] })
const recentLogs = ref(props.recentLogs ?? { data: [] })

const page = usePage()
const currentUser = page.props.auth?.user
const currentRole = currentUser?.roles?.[0]?.name ?? ''

const showPageSelector = ref(false)
const pageSelectorList = ref<number[]>([])

const roles = ref(
    (props.roles ?? []).filter((r) => {
        if (currentRole === 'superadmin') return ['admin', 'officer'].includes(r.name)
        if (currentRole === 'admin') return ['officer'].includes(r.name)
        return false
    })
)

const search = ref(props.filters?.search ?? '')

const filteredUsers = computed(() => {
    if (!search.value.trim()) return users.value.data
    const term = search.value.toLowerCase()
    return users.value.data.filter(
        (u) =>
            u.name.toLowerCase().includes(term) ||
            u.email.toLowerCase().includes(term) ||
            u.roles.some((r) => r.name.toLowerCase().includes(term))
    )
})

watch(search, () => {
    router.visit('/admin', {
        method: 'get',
        data: { search: search.value },
        preserveScroll: true,
        preserveState: true,
        replace: true,
    })
})

const name = ref('')
const email = ref('')
const role = ref<string>(roles.value?.[0]?.name ?? '')
const password = ref('')
const creating = ref(false)
const errors = ref<Record<string, string>>({})

function refreshPage(url?: string) {
    router.visit(url || '/admin', {
        method: 'get',
        data: { search: search.value },
        preserveScroll: true,
        preserveState: false,
    })
}

function resetForm() {
    name.value = ''
    email.value = ''
    role.value = roles.value?.[0]?.name ?? ''
    password.value = ''
    errors.value = {}
}

function createUser() {
    creating.value = true
    errors.value = {}
    if (!name.value) errors.value.name = 'Name is required'
    if (!email.value) errors.value.email = 'Email is required'
    if (!role.value) errors.value.role = 'Role is required'
    if (Object.keys(errors.value).length > 0) {
        creating.value = false
        return
    }

    router.post(
        '/admin/users',
        { name: name.value, email: email.value, role: role.value, password: password.value },
        {
            preserveScroll: true,
            onFinish: () => (creating.value = false),
            onSuccess: () => {
                resetForm()
                refreshPage()
            },
            onError: (serverErrors: any) => (errors.value = serverErrors || {}),
        }
    )
}

function changeRole(user: any, newRole: string) {
    const oldRoles = [...user.roles]

    user.roles = [{ id: 0, name: newRole }]

    router.post(
        `/admin/users/${user.id}/change-role`,
        { role: newRole },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                user.roles = oldRoles
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
}

function deactivateUser(id: number) {
    router.post(
        `/admin/users/${id}/deactivate`,
        {},
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => refreshPage(),
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
            onSuccess: () => refreshPage(),
        }
    )
}

function formatDate(dt?: string) {
    if (!dt) return '-'
    return new Date(dt).toLocaleString()
}

function goToLogsPage(url: string) {
    if (!url) return
    router.visit(url, {
        method: 'get',
        preserveScroll: true,
        preserveState: false,
        replace: true,
    })
}

function openPageSelector(start: number, end: number) {
    pageSelectorList.value = Array.from({ length: end - start - 1 }, (_, i) => start + i + 1)
    showPageSelector.value = true
}

const isMobile = ref(window.innerWidth < 640)

window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth < 640

})

const showValueModal = ref(false)
const valueModalData = ref<{ key: string; value: string }[]>([])
const modalTitle = ref('')
const loadingChanges = ref(false)

function formatReadableValue(field: string, value: any): string {
    if (value === null || value === undefined) return 'empty'

    // Boolean / active flag
    if (field === 'active') {
        return value ? 'Active' : 'Inactive'
    }

    // Date fields
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
            valueModalData.value = Object.entries(changes).map(([key, value]) => {
                let displayValue = ''

                // UPDATE format: { before, after }
                if (
                    typeof value === 'object' &&
                    value !== null &&
                    'before' in value &&
                    'after' in value
                ) {
                    const before = formatReadableValue(key, (value as any).before)
                    const after = formatReadableValue(key, (value as any).after)

                    displayValue = `Changed from ${before} to ${after}`
                }
                // CREATE format: simple value
                else {
                    displayValue = formatReadableValue(key, value)
                }

                // Hide sensitive fields
                if (['password'].includes(key)) {
                    return {
                        key: formatFieldName(key),
                        value: 'Hidden for security reasons'
                    }
                }

                return {
                    key: formatFieldName(key),
                    value: displayValue
                }
            })
        } else {
            valueModalData.value = [{ key: 'raw', value: data.changes }]
        }
    } catch (e) {
        valueModalData.value = [{ key: 'Error', value: 'Could not load changes.' }]
    } finally {
        loadingChanges.value = false
    }
}

</script>

<template>

    <Head title="Admin Dashboard" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 rounded-3xl min-h-screen">
			<div class="px-5 md:px-8 pt-5 grid gap-6 md:grid-rows-[auto_1fr]">
                <div class="col-span-3 md:col-span-1 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3">Create User</h3>
                    <div v-if="roles.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                        You are not allowed to create users.
                    </div>
                    <div v-else class="space-y-3">
                        <input v-model="name" placeholder="Name" type="text"
                                class="w-full rounded-lg p-2 bg-white dark:bg-gray-700 border" />
                        <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name }}</p>
                        <input v-model="email" placeholder="Email" type="email"
                            class="w-full rounded-lg p-2 bg-white dark:bg-gray-700 border" />
                        <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
                        <select v-model="role" class="w-full rounded-lg p-2 bg-white dark:bg-gray-700 border">
                            <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                        </select>
                        <p v-if="errors.role" class="text-xs text-red-500 mt-1">{{ errors.role }}</p>

                        <button @click="createUser"
                            class="w-full sm:w-auto px-4 py-2 rounded-lg font-medium bg-blue-600 text-white shadow-md hover:opacity-95"
                            :disabled="creating">
                            <span v-if="!creating">Create</span>
                            <span v-else>Creating...</span>
                        </button>
                    </div>
                </div>

                <div
                    class="col-span-3 md:col-span-2 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-4 sm:p-6 overflow-hidden">

                    <!-- Header & Search -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-3">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-800 dark:text-gray-100">All Users</h2>
                        <input v-model="search" placeholder="Search users..."
                            class="rounded-lg px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-sm sm:text-base w-full sm:w-64 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr
                                    class="text-left text-gray-500 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 px-2">Name</th>
                                    <th class="py-2 px-2">Email</th>
                                    <th class="py-2 px-2">Roles</th>
                                    <th class="py-2 px-2">Created</th>
                                    <th class="py-2 px-2 text-right">Activated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in filteredUsers" :key="user.id"
                                    class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-750 transition-colors">
                                    <td class="py-2 px-2 font-medium text-gray-900 dark:text-gray-100">{{ user.name }}
                                    </td>
                                    <td class="py-2 px-2">{{ user.email }}</td>
                                    <td class="py-2 px-2">
                                        <template v-if="currentRole === 'superadmin' && currentUser.id !== user.id">
                                            <select v-if="!user.roles.some(r => r.name === 'cooperative')"
                                                class="rounded-md border px-2 py-1 text-sm bg-white dark:bg-gray-700"
                                                :value="user.roles[0]?.name"
                                                @change="e => changeRole(user, (e.target as HTMLSelectElement).value)">
                                                <option value="admin">admin</option>
                                                <option value="officer">officer</option>
                                            </select>
                                            <div v-else>
                                                {{ user.roles.map(r => r.name).join(', ') }}
                                            </div>
                                        </template>

                                        <template v-else>
                                            {{user.roles.map(r => r.name).join(', ') || '-'}}
                                        </template>
                                    </td>
                                    <td class="py-2 px-2">{{ formatDate(user.created_at) }}</td>
                                    <td class="py-2 px-2 text-right">
                                        <Switch v-if="!(
                                            currentRole === 'admin' &&
                                            user.roles.some(r => r.name === 'superadmin')
                                        )" :model-value="user.active" @update:modelValue="() => onToggle(user)" :disabled="currentUser.id === user.id" />
                                        User is {{ user.active ? 'active' : 'inactive' }}
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="5" class="py-6 text-center text-gray-500">No users found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="sm:hidden space-y-4">
                        <div v-for="user in filteredUsers" :key="user.id"
                            class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-col space-y-2 bg-white dark:bg-gray-800 shadow-sm">
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-100 text-base">{{ user.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 break-all">{{ user.email }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    Roles: {{user.roles.map(r => r.name).join(', ') || '-'}}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ formatDate(user.created_at) }}
                                </p>
                            </div>
                            deactivate
                        </div>

                        <div v-if="filteredUsers.length === 0" class="py-4 text-center text-gray-500">
                            No users found.
                        </div>
                    </div>
                </div>

                <div class="col-span-3 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-6 overflow-hidden">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Recent Sync Logs</h2>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li v-for="log in recentLogs.data ?? []" :key="log.id" class="py-3">
                            <div
                                class="p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                <div class="space-y-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Table
                                        </p>
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            User: {{ log.user_name }}
                                        </p>
                                    </div>

                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ log.table_name }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            — {{ log.operation }}
                                        </span>
                                    </p>

                                    <div class="flex justify-between items-start">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Record ID: {{ log.record_id ?? '-' }}
                                        </p>
                                        <p class="text-xs text-gray-400 whitespace-nowrap">
                                            Executed At: {{ formatDate(log.executed_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="text-xs mt-1 flex flex-wrap items-center gap-1 !pl-0 !ml-0">
                                    <button @click="openChangesModal(log.id)"
                                        class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                        View Changes
                                    </button>
                                </div>
                            </div>
                        </li>
                        <li v-if="(recentLogs.data ?? []).length === 0" class="py-4 text-center text-gray-500">No logs
                            yet.</li>
                    </ul>

                    <div v-if="(recentLogs?.last_page ?? 1) > 1"
                        class="flex items-center justify-center mt-4 flex-wrap gap-2">
                        <button v-if="recentLogs?.prev_page_url" @click="goToLogsPage(recentLogs.prev_page_url)"
                            class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90 flex items-center gap-1">
                            <span v-if="isMobile">←</span>
                            <span v-else>← Prev</span>
                        </button>

                        <template v-if="(recentLogs?.last_page ?? 1) <= 10">
                            <button v-for="pageNum in recentLogs?.last_page ?? 1" :key="pageNum"
                                @click="goToLogsPage(`/admin?logs_page=${pageNum}&search=${search}`)" :class="[
                                    'px-3 py-1 rounded-md border',
                                    (recentLogs?.current_page ?? 1) === pageNum
                                        ? 'bg-blue-600 text-white border-blue-600'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90'
                                ]">
                                {{ pageNum }}
                            </button>
                        </template>

                        <template v-else>
                            <button v-for="pageNum in (isMobile ? [1] : [1, 2, 3])" :key="'start-' + pageNum"
                                @click="goToLogsPage(`/admin?logs_page=${pageNum}&search=${search}`)" :class="[
                                    'px-3 py-1 rounded-md border',
                                    (recentLogs?.current_page ?? 1) === pageNum
                                        ? 'bg-blue-600 text-white border-blue-600'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90'
                                ]">
                                {{ pageNum }}
                            </button>

                            <button v-if="(recentLogs?.current_page ?? 1) > (isMobile ? 3 : 5)"
                                @click="openPageSelector(1, (recentLogs?.current_page ?? 1) - 1)"
                                class="px-3 py-1 rounded-md border bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90">
                                ...
                            </button>

                            <template
                                v-for="pageNum in [(recentLogs?.current_page ?? 1) - 1, (recentLogs?.current_page ?? 1), (recentLogs?.current_page ?? 1) + 1]">
                                <button
                                    v-if="pageNum > (isMobile ? 1 : 3) && pageNum < (recentLogs?.last_page ?? 1) - (isMobile ? 1 : 2)"
                                    :key="'mid-' + pageNum"
                                    @click="goToLogsPage(`/admin?logs_page=${pageNum}&search=${search}`)" :class="[
                                        'px-3 py-1 rounded-md border',
                                        (recentLogs?.current_page ?? 1) === pageNum
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90'
                                    ]">
                                    {{ pageNum }}
                                </button>
                            </template>

                            <button
                                v-if="(recentLogs?.current_page ?? 1) < (recentLogs?.last_page ?? 1) - (isMobile ? 2 : 4)"
                                @click="openPageSelector((recentLogs?.current_page ?? 1), (recentLogs?.last_page ?? 1))"
                                class="px-3 py-1 rounded-md border bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90">
                                ...
                            </button>

                            <button
                                v-for="pageNum in (isMobile
                                    ? [(recentLogs?.last_page ?? 1)]
                                    : [(recentLogs?.last_page ?? 1) - 2, (recentLogs?.last_page ?? 1) - 1, (recentLogs?.last_page ?? 1)])"
                                :key="'end-' + pageNum"
                                @click="goToLogsPage(`/admin?logs_page=${pageNum}&search=${search}`)" :class="[
                                    'px-3 py-1 rounded-md border',
                                    (recentLogs?.current_page ?? 1) === pageNum
                                        ? 'bg-blue-600 text-white border-blue-600'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90'
                                ]">
                                {{ pageNum }}
                            </button>
                        </template>

                        <button v-if="recentLogs?.next_page_url" @click="goToLogsPage(recentLogs.next_page_url)"
                            class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:opacity-90 flex items-center gap-1">
                            <span v-if="isMobile">→</span>
                            <span v-else>Next →</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Selector Modal -->
        <teleport to="body">
            <div v-if="showPageSelector" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[999]"
                @click.self="showPageSelector = false">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-xl max-h-[70vh] w-[90%] sm:w-64 overflow-y-auto">
                    <h3 class="text-lg font-semibold mb-2 text-center text-gray-800 dark:text-gray-100">
                        Jump to page
                    </h3>

                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="pageNum in pageSelectorList" :key="'select-' + pageNum"
                            @click="goToLogsPage(`/admin?logs_page=${pageNum}&search=${search}`); showPageSelector = false"
                            class="px-2 py-1 rounded-md border text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-blue-600 hover:text-white transition">
                            {{ pageNum }}
                        </button>
                    </div>

                    <div class="mt-3 text-center">
                        <button @click="showPageSelector = false"
                            class="mt-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
        <!-- Value Viewer Modal -->
        <teleport to="body">
            <div v-if="showValueModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[1000]"
                @click.self="showValueModal = false">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-xl w-[90%] sm:w-[500px] max-h-[80vh] overflow-auto">
                    <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-100">
                        {{ modalTitle }}
                    </h3>

                    <div v-if="loadingChanges" class="text-center text-gray-500 py-6">
                        Loading changes...
                    </div>

                    <div v-else-if="valueModalData.length"
                        class="space-y-2 text-sm text-gray-700 dark:text-gray-300 font-mono">
                        <div v-for="change in valueModalData" :key="change.key"
                            class="border-b border-gray-200 dark:border-gray-700 pb-2">
                            <p class="font-semibold text-blue-600 dark:text-blue-400">
                                {{ change.key }}
                            </p>
                            <p class="whitespace-pre-wrap break-words">
                                {{ change.value }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="text-gray-500 text-center py-4">No changes data available.</div>

                    <div class="mt-3 text-center">
                        <button @click="showValueModal = false"
                            class="px-4 py-1 rounded-md bg-blue-600 text-white hover:opacity-90">
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
                        {{ pendingAction === 'activate'
                            ? 'Activate User'
                            : 'Deactivate User' }}
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
    </AdminLayout>
</template>
