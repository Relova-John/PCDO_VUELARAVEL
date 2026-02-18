<script setup lang="ts">
import CoopLayout from '@/layouts/CoopLayout.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Coop, Member } from '@/types/cooperatives'
import { Head } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'
import { ref, computed } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: dashboard().url },
]

const { cooperative, members } = defineProps<{
    cooperative: Coop[]
    members: Member[]
}>()

const activeTab = ref<'overview' | 'status' | 'details' | 'members' | 'history'>('overview')

const coop = computed(() => cooperative[0])

const programStatuses = computed(() => {
    if (!coop.value?.programs?.length) return []

    return coop.value.programs.map(program => {
        const required = program.program.checklists.length
        const submitted = program.checklist.length

        if (submitted < required) {
            return { label: 'Registering', color: 'red' }
        }

        if (program.amortizationSchedules.length > 0) {
            return { label: 'Ongoing', color: 'yellow' }
        }

        return { label: 'Completed', color: 'green' }
    })
})

const formatDate = (date: string) =>
    useDateFormat(date, 'MM/DD/YYYY').value

const checklistProgress = (program: any) => {
    const total = program.program.checklists.length
    const current = program.checklist.length
    return { current, total }
}

const groupedMembers = computed(() => {
    const groups: Record<string, Member[]> = {
        Chairman: [],
        Treasurer: [],
        Manager: [],
        Members: [],
    }

    if (!coop.value?.id || !members?.length) return groups

    const coopMembers = members.filter(m => m.coop_id === coop.value.id)

    coopMembers.forEach((m: Member) => {
        switch (m.position) {
            case 'Chairman':
                groups.Chairman.push(m)
                break
            case 'Treasurer':
                groups.Treasurer.push(m)
                break
            case 'Manager':
                groups.Manager.push(m)
                break
            case 'Member':
            default:
                groups.Members.push(m)
                break
        }
    })

    return groups
})
</script>

<template>

    <Head title="Dashboard" />

    <CoopLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="px-5 md:px-5 pt-5">

                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ coop.name }}
                </h1>

                <div class="text-m text-gray-600 dark:text-gray-400 mb-4">
                    <p>Email: <strong>{{ coop.details.email }}</strong></p>
                    <p>Contact Number: <strong>{{ coop.details.number }}</strong></p>
                </div>

                <div class="flex gap-4 border-b border-gray-300 dark:border-gray-700 mb-3">
                    <button v-for="tab in ['status', 'details', 'members', 'history']" :key="tab"
                        @click="activeTab = tab as any" class="pb-2 text-sm font-medium" :class="activeTab === tab
                            ? 'border-b-2 border-blue-600 text-blue-600'
                            : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
                        {{tab.replace(/^\w/, c => c.toUpperCase())}}
                    </button>
                </div>

                <section v-if="activeTab === 'status'" class="space-y-4">
                    <div v-for="(program, index) in coop.programs" :key="program.id"
                        class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow space-y-2">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ program.program.name }}
                            </h2>

                            <span class="font-medium">
                                {{ program.id }}) {{ program.project }}
                            </span>

                            <span>
                                Created Date: {{ formatDate(program.created_at) }}
                            </span>

                            <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="{
                                'bg-red-100 text-red-700': programStatuses[index].color === 'red',
                                'bg-yellow-100 text-yellow-700': programStatuses[index].color === 'yellow',
                                'bg-green-100 text-green-700': programStatuses[index].color === 'green',
                                'bg-gray-200 text-gray-700': programStatuses[index].color === 'gray',
                            }">
                                {{ programStatuses[index].label }}
                            </span>
                        </div>

                        <div v-if="programStatuses[index].label === 'Registering'" class="text-sm text-gray-500">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="text-left text-gray-500">Checklist Item</th>
                                        <th class="text-left text-gray-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="checklist in program.program.checklists" :key="checklist.id">
                                        <td>{{ checklist.name }}</td>
                                        <td>
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="program.checklist.some(c => c.id === checklist.id)
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'">
                                                {{program.checklist.some(c => c.id === checklist.id)
                                                    ? 'Submitted'
                                                    : 'Pending'}}
                                            </span>
                                            <span>File: {{program.checklist.find(c => c.id === checklist.id)?.file_name
                                                || 'None'}}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <span class="text-sm text-gray-600">
                                {{ checklistProgress(program).current }}/{{ checklistProgress(program).total }}
                            </span>

                        </div>

                        <div v-else-if="programStatuses[index].label === 'Ongoing'" class="text-sm text-gray-500">
                            Your program is currently ongoing. Please check the amortization schedules for details.
                        </div>
                    </div>
                </section>

                <section v-if="activeTab === 'details'" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="grid grid-cols-2 gap-y-3 text-sm">
                        <div class="text-gray-500">Asset Size:</div>
                        <div>{{ coop.details.asset_size }}</div>

                        <div class="text-gray-500">Coop Type:</div>
                        <div>{{ coop.details.coop_type }}</div>

                        <div class="text-gray-500">Area:</div>
                        <div>{{ coop.details.area_of_operation }}</div>

                        <div class="text-gray-500">Members:</div>
                        <div>{{ coop.details.members_count }}</div>

                        <div class="text-gray-500">Total Asset</div>
                        <div>₱{{ coop.details.total_asset.toLocaleString() }}</div>
                    </div>
                </section>

                <section v-if="activeTab === 'members'"
                    class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">

                    <div v-for="(members, position) in groupedMembers" :key="position" class="mb-6">
                        <h3 class="text-lg font-semibold text-indigo-700 mb-2">{{ position }}</h3>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full text-sm border-separate border-spacing-0">
                                <TableHeader>
                                    <TableRow
                                        class="bg-gray-200 dark:bg-gray-700/50 border-b border-gray-500 dark:border-gray-500">
                                        <th class="py-2 pl-4 text-left">Full Name</th>
                                        <th class="py-2 text-left">Representative</th>
                                        <th class="py-2 text-left">Files</th>
                                        <th class="py-2 text-left">Action</th>
                                    </TableRow>
                                </TableHeader>
                                <TableBody class="bg-white dark:bg-gray-800">
                                    <TableRow v-for="mem in members" :key="mem.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-600/50">
                                        <TableCell class="pl-4 text-gray-600 dark:text-gray-300">
                                            {{ mem.first_name }} {{ mem.middle_name ? mem.middle_name + '. ' : '' }}{{
                                            mem.last_name }}
                                        </TableCell>
                                        <TableCell class="text-gray-600 dark:text-gray-300">
                                            <span :class="mem.is_representative ? 'text-green-600' : 'text-red-600'">
                                                {{ mem.is_representative ? 'Yes' : 'No' }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-gray-600 dark:text-gray-300">{{ mem.email || '-' }}</TableCell>
                                        <TableCell class="text-gray-600 dark:text-gray-300">{{ mem.contact || '-' }}</TableCell>
                                    </TableRow>

                                    <TableRow v-if="members.length === 0">
                                        <TableCell colspan="4" class="text-center text-gray-600 dark:text-gray-300 py-4">
                                            No members for {{ position }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-4">
                            <div v-for="mem in members" :key="mem.id"
                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                            {{ mem.first_name }} {{ mem.middle_name ? mem.middle_name + '. ' : '' }}{{
                                            mem.last_name }}
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ mem.position || 'Member'
                                            }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Email: {{ mem.email || '-'
                                            }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Contact: {{ mem.contact ||
                                            '-' }}</p>
                                    </div>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full" :class="mem.is_representative
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                                        {{ mem.is_representative ? 'Representative' : 'Not Representative' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <section v-if="activeTab === 'history'" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <ul class="text-sm space-y-2">
                    <li v-for="program in coop.programs" :key="program.id">
                        {{ program.project }} — {{ program.program_status }}
                    </li>
                </ul>
            </section>
        </div>
    </CoopLayout>
</template>
