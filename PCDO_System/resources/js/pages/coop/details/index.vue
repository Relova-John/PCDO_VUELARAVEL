<script setup lang="ts">
import CoopLayout from '@/layouts/CoopLayout.vue'
import { BreadcrumbItem } from '@/types'
import type { Cooperative, Details } from '@/types/cooperatives'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

interface HistoryProgram {
  id: number | string
  program_name: string
  completed_at: string
  status: string
  has_delinquent: boolean
}

interface HistoryItem {
  year: number
  programs: HistoryProgram[]
  open: boolean
}

const props = defineProps<{
  breadcrumbs: BreadcrumbItem[]
  cooperative: Cooperative
  details: Details
  programs: { id: number; name: string }[]
  hasOngoingProgram: boolean
  history: HistoryItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Details', href: '/coop/details' },
]

const history = ref(props.history)

function goToProgramDocumentation(programId: string | number) {
  router.visit(`/documentation/cooperative/${programId}`)
}

</script>

<template>
  <CoopLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen p-6">
      <div class="bg-gray-50 dark:bg-gray-800/80 border rounded-xl px-6 py-5 mb-6">
        <h1 class="text-3xl font-bold mb-4">{{ cooperative.name }}</h1>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
          <div>
            <p class="text-sm text-gray-500">Cooperative Type</p>
            <p class="text-lg font-semibold">{{ details.coop_type || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Status Category</p>
            <p class="text-lg font-semibold">{{ details.status_category || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Bond of Membership</p>
            <p class="text-lg font-semibold">{{ details.bond_of_membership || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Area of Operation</p>
            <p class="text-lg font-semibold">{{ details.area_of_operation || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Citizenship</p>
            <p class="text-lg font-semibold">{{ details.citizenship || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Member Count</p>
            <p class="text-lg font-semibold">{{ details.members_count || 0 }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Asset</p>
            <p class="text-lg font-semibold">₱{{ details.total_asset?.toLocaleString() || '0.00' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Net Surplus</p>
            <p class="text-lg font-semibold">₱{{ details.net_surplus?.toLocaleString() || '0.00' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="text-lg font-semibold">{{ details.email || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Contact Number</p>
            <p class="text-lg font-semibold">{{ details.number || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Program History -->
      <section>
        <h2 class="text-2xl font-bold mb-4">Program History</h2>

        <div v-if="history.length">
          <div v-for="yearBlock in history" :key="yearBlock.year" class="mb-4 border rounded-lg">
            <button
              class="w-full px-4 py-2 text-left font-semibold bg-gray-200 dark:bg-gray-700 rounded-t-lg"
              @click="yearBlock.open = !yearBlock.open">
              {{ yearBlock.year }}
            </button>
            <div v-show="yearBlock.open" class="p-4 bg-white dark:bg-gray-800 border-t">
              <table v-if="yearBlock.programs.length" class="w-full text-left text-sm text-gray-700 dark:text-gray-200">
                <thead>
                  <tr>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Completed At</th>
                    <th>Delinquent?</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="program in yearBlock.programs" :key="program.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                      @click="goToProgramDocumentation(program.id)">
                    <td>{{ program.program_name }}</td>
                    <td>{{ program.status }}</td>
                    <td>{{ program.completed_at }}</td>
                    <td class="text-center">{{ program.has_delinquent ? '✔' : '—' }}</td>
                  </tr>
                </tbody>
              </table>
              <p v-else class="italic text-gray-500 dark:text-gray-400">No completed programs for this year.</p>
            </div>
          </div>
        </div>

        <p v-else class="italic text-gray-500 dark:text-gray-400">No historical program records found.</p>
      </section>
    </div>
  </CoopLayout>
</template>