<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import { ref } from 'vue'

const props = defineProps<{
  years: Array<{
    year: string
    cooperatives: Array<{
      id: number
      name: string
      program_name: string
      completed_at: string
    }>
  }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Documentation', href: '#' },
]

const openYear = ref<string | null>(null)
const toggleYear = (year: string) => {
  openYear.value = openYear.value === year ? null : year
}
</script>

<template>

  <Head title="Documentation" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen px-4 sm:px-6 py-8">
      <div class="mb-8 text-center">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">
          Documentation by Year
        </h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2 mx-auto max-w-md">
          Browse completed cooperative documentation organized by year.
        </p>
      </div>

      <div v-if="!props.years || props.years.length === 0" class="flex items-center justify-center py-32">
        <p class="text-gray-500 dark:text-gray-400 text-lg text-center">
          No documentation data available.
        </p>
      </div>

      <div v-else class="max-w-6xl mx-auto space-y-4">
        <div v-for="yearGroup in props.years" :key="yearGroup.year"
          class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
          <button
            class="w-full flex items-center justify-between px-5 sm:px-6 py-3 bg-indigo-600 text-white font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition text-base sm:text-lg"
            @click="toggleYear(yearGroup.year)">
            <span>{{ yearGroup.year }}</span>
            <ChevronDown class="w-5 h-5 transform transition-transform duration-200"
              :class="{ 'rotate-180': openYear === yearGroup.year }" />
          </button>

          <div v-show="openYear === yearGroup.year" class="transition-all duration-300">
            <div v-if="yearGroup.cooperatives.length > 0">
              <div class="hidden sm:block">
                <Table>
                  <TableCaption>
                    <span class="text-gray-600 dark:text-gray-400 text-sm">
                      Completed cooperatives for {{ yearGroup.year }}
                    </span>
                  </TableCaption>

                  <TableHeader>
                    <TableRow class="bg-gray-100 dark:bg-gray-700">
                      <TableHead class="text-gray-700 dark:text-gray-200">Name</TableHead>
                      <TableHead class="text-gray-700 dark:text-gray-200">Program</TableHead>
                      <TableHead class="text-gray-700 dark:text-gray-200">Completed At</TableHead>
                    </TableRow>
                  </TableHeader>

                  <TableBody>
                    <TableRow v-for="coop in yearGroup.cooperatives" :key="coop.id"
                      @click="$inertia.get(`/documentation/cooperative/${coop.id}`)"
                      class="cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                      <TableCell class="font-medium text-gray-800 dark:text-gray-100">{{ coop.name }}</TableCell>
                      <TableCell class="text-gray-700 dark:text-gray-300">{{ coop.program_name }}</TableCell>
                      <TableCell class="text-gray-600 dark:text-gray-400">{{ coop.completed_at }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>

              <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="coop in yearGroup.cooperatives" :key="coop.id"
                  @click="$inertia.get(`/documentation/cooperative/${coop.id}`)"
                  class="p-4 flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-3 active:bg-indigo-50 dark:active:bg-gray-700 transition">
                  <span class="font-semibold text-gray-900 dark:text-gray-100">{{ coop.name }}</span>
                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ coop.program_name }}</span>
                  <span class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ coop.completed_at }}</span>
                </div>
              </div>
            </div>

            <div v-else class="p-5 text-gray-500 dark:text-gray-400 text-center text-sm">
              No cooperatives completed yet for this year.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
