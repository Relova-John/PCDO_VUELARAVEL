<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import PdfViewer from '@/components/PdfViewer.vue'

const showFileModal = ref(false)
const pdfUrl = ref('/programs/reports/monthly')
const closeFileModal = () => (showFileModal.value = false, pdfLoading.value = true, pdfFailed.value = false)
const pdfFailed = ref(false)

const props = defineProps<{
  programs: Array<{
    id: number
    name: string
    cooperatives_count: number
  }>
}>()

const programDescriptions: Record<string, string> = {
  USAD: 'Upgrading Support for Advancement and Development of Enterprises in Cooperative',
  LICAP: 'Livelihood Credit Assistance Program',
  COPSE: 'Cooperative Program For Sustainable Enterprise',
  SULONG: 'Sustained Livelihood Opportunities and Growth',
  PCLRP: 'Provincial Cooperative Livelihood Recovery Program'
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Programs', href: '#' },
]

// Dynamic gradients for each program
const programGradients: Record<number, string> = {
  1: 'from-yellow-400 to-orange-500',
  2: 'from-blue-500 to-indigo-500',
  3: 'from-emerald-500 to-teal-600',
  4: 'from-red-400 to-pink-500',
  5: 'from-green-300 to-green-600'
}

const selectedMonth = ref(new Date().toISOString().slice(0, 7))
const selectedProgram = ref('all')

const updateMonth = () => {
  pdfUrl.value = `/programs/reports/monthly?month=${selectedMonth.value}&program_id=${selectedProgram.value}`
  reloadPdf()
}

const pdfLoading = ref(true)

const reloadPdf = () => {
  pdfLoading.value = true
  setTimeout(() => {
    pdfLoading.value = false
  }, 500)
}

const isMobile = ref(false)

onMounted(() => {
  const uaCheck = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
  const sizeCheck = window.matchMedia('(max-width: 768px)').matches
  isMobile.value = uaCheck || sizeCheck
})
</script>

<template>

  <Head title="Programs" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
      <div class="px-5 md:px-8 pt-5">
        <div class="flex flex-col gap-6 p-6">
          <div class="flex flex-col sm:flex-row sm:justify-end sm:items-center gap-3 sm:gap-4 w-full">

            <!-- Button -->
            <button @click="showFileModal = true"
              class="w-full sm:w-auto px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium shadow transition">
              View Monthly Report
            </button>
          </div>

          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Link v-for="program in props.programs" :key="program.id" :href="`/programs/${program.id}`" class="rounded-2xl shadow-md border border-gray-300 dark:border-gray-700 
                     bg-gray-50 dark:bg-gray-800 
                     hover:shadow-2xl hover:-translate-y-1.5 transform transition-all block">
            <!-- Dynamic Gradient Top Bar -->
            <div
              :class="`h-2 rounded-t-2xl bg-gradient-to-r ${programGradients[program.id] || 'from-blue-500 to-indigo-500'}`">
            </div>

            <div class="p-5 flex flex-col h-full">
              <h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-100">
                {{ program.name }}
              </h2>

              <p class="text-gray-800 dark:text-gray-300 text-sm leading-relaxed mb-4">
                {{ programDescriptions[program.name] }}
              </p>

              <div class="mt-auto flex items-center justify-between gap-2">
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center gap-1">
                  <Handshake class="w-4 h-4 text-gray-600 dark:text-gray-400" /> Active Cooperatives
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold 
                               bg-blue-200 text-blue-800 
                               dark:bg-blue-900 dark:text-blue-200 shadow-sm">
                  {{ program.cooperatives_count }}
                </span>
              </div>
            </div>
            </Link>
          </div>
        </div>
      </div>
      <Transition name="fade">
        <div v-if="showFileModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 sm:p-0"
          @click.self="closeFileModal">
          <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg w-full max-w-4xl max-h-[90vh] overflow-hidden sm:m-0 m-auto">
            <!-- Header -->
            <header
              class="flex flex-wrap justify-between items-center border-b border-gray-200 dark:border-gray-700 p-4 gap-4">
              <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate">
                Monthly Program Report
              </h2>
              <button v-if="isMobile" @click="closeFileModal"
                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl leading-none">
                ✕
              </button>
              <div class="flex items-center gap-3 flex-wrap justify-end">
                <!-- Program Selector -->
                <select v-model="selectedProgram" @change="updateMonth" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
             text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                  <option value="all">All Programs</option>
                  <option v-for="program in props.programs" :key="program.id" :value="program.id">
                    {{ program.name }}
                  </option>
                </select>

                <!-- Month Selector -->
                <input type="month" v-model="selectedMonth" @change="updateMonth" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
             text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <button v-if="!isMobile" @click="closeFileModal"
                  class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl leading-none">
                  ✕
                </button>
              </div>
            </header>

            <!-- Content -->
            <div class="p-4 overflow-auto max-h-[80vh] bg-gray-50 dark:bg-gray-800 rounded-b-2xl">
              <div v-if="pdfLoading" class="flex justify-center items-center h-[80vh] text-gray-600 dark:text-gray-300">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-400 border-t-transparent"></div>
              </div>

              <!-- Desktop PDF -->
              <iframe v-if="!isMobile" :src="`${pdfUrl}`" class="w-full h-[75vh] rounded" key="pdfUrl"
                @load="pdfLoading = false"></iframe>

              <!-- Mobile PDF -->
              <template v-else>
                <PdfViewer v-if="!pdfFailed" :url="`${pdfUrl}`" type="report"
                  @error="pdfFailed = true; pdfLoading = false" :key="pdfUrl" @load="pdfLoading = false" />

                <div v-else class="text-center text-gray-600 dark:text-gray-400">
                  <p class="mb-2">PDF preview not supported on your device.</p>
                  <a :href="`${pdfUrl}?download=1`" class="text-blue-600 hover:underline font-medium">Download the PDF
                    file</a>
                </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </AppLayout>
</template>
