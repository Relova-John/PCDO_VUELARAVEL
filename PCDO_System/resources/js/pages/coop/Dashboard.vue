<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Coop } from '@/types/cooperatives'
import { Head } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'
import { ref, computed } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
]

const { cooperative } = defineProps<{
    cooperative: Coop[]
}>()

const activeTab = ref<'status' | 'details' | 'members' | 'history'>('status')

const coop = computed(() => cooperative[0])

const programStatuses = computed(() => {
    if (!coop.value?.programs?.length) return []

    return coop.value.programs.map(program => {
        const required = program.program.checklists.length
        const submitted = program.checklist.length

        if (submitted < required) {
            return { label: 'Registering', color: 'red' }
        }

        if (program.amortization_schedules.length > 0) {
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

</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen px-6 py-6 bg-gray-100 dark:bg-gray-900">

            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                {{ coop.name }}
            </h1>

            <div class="flex gap-4 border-b border-gray-300 dark:border-gray-700 mb-6">
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
                                            : 'Pending' }}
                                        </span>
                                        <span>File: {{ program.checklist.find(c => c.id === checklist.id)?.file_name || 'None' }}</span>
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
                    <div class="text-gray-500">Asset Size</div>
                    <div>{{ coop.details.asset_size }}</div>

                    <div class="text-gray-500">Coop Type</div>
                    <div>{{ coop.details.coop_type }}</div>

                    <div class="text-gray-500">Area</div>
                    <div>{{ coop.details.area_of_operation }}</div>

                    <div class="text-gray-500">Members</div>
                    <div>{{ coop.details.members_count }}</div>

                    <div class="text-gray-500">Total Asset</div>
                    <div>₱{{ coop.details.total_asset.toLocaleString() }}</div>
                </div>
            </section>

            <section v-if="activeTab === 'members'" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <p class="text-gray-600 dark:text-gray-400">
                    Members count: <strong>{{ coop.details.members_count }}</strong>
                </p>
            </section>

            <section v-if="activeTab === 'history'" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <ul class="text-sm space-y-2">
                    <li v-for="program in coop.programs" :key="program.id">
                        {{ program.project }} — {{ program.program_status }}
                    </li>
                </ul>
            </section>

        </div>
    </AppLayout>
</template>
