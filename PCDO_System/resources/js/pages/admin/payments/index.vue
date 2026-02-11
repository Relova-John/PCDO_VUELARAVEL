<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import PdfViewer from '@/components/PdfViewer.vue'

const breadcrumbs: BreadcrumbItem[] =
  [{ title: 'Payments', href: '#' }]

interface CoopProgram {
  id: number
  cooperative_name: string
  program_name: string
  loan_amount: number
  status: string
  has_schedule: boolean
  coop_program_id: number
  next_due_date: string | null
}

// Search + Filters
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedProgram = ref('')

const props = defineProps<{ coopPrograms: CoopProgram[] }>()

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(10)

const filteredCoopPrograms = computed(() => {
  if (!searchQuery.value) {
    return props.coopPrograms;
  }
  return props.coopPrograms.filter(coop =>
    coop.program_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    coop.id.toString().includes(searchQuery.value)
  );
});

const filteredPrograms = computed(() => {
  let list = props.coopPrograms

  // Search filter
  if (searchQuery.value) {
    list = list.filter(p =>
      p.cooperative_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.program_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.id.toString().includes(searchQuery.value)
    )
  }

  // Status filter
  if (selectedStatus.value) {
    list = list.filter(p => p.status.toLowerCase() === selectedStatus.value.toLowerCase())
  }

  // Program filter
  if (selectedProgram.value) {
    list = list.filter(p => p.program_name.toLowerCase() === selectedProgram.value.toLowerCase())
  }

  return list
})


const paginatedPrograms = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredPrograms.value.slice(start, start + itemsPerPage.value)
})

const totalPages = computed(() => Math.ceil(filteredPrograms.value.length / itemsPerPage.value))

function goToPage(page: number) {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page
}

function formatNextDue(date: string | null) {
  if (!date || date === 'N/A') return 'N/A'
  const due = new Date(date)
  return due.toLocaleDateString('en-US')
}

const showFileModal = ref(false)
const selectedFile = ref<string | null>(null)
const selectedMember = ref<string | null>(null)
const pdfFailed = ref(false)

function openFileModal(file: string, member: string) {
  selectedFile.value = file
  selectedMember.value = member
  showFileModal.value = true
}

function closeFileModal() {
  showFileModal.value = false
  selectedFile.value = null
  selectedMember.value = null
}

const isMobile = ref(false)

onMounted(() => {
  const uaCheck = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
  const sizeCheck = window.matchMedia('(max-width: 768px)').matches
  isMobile.value = uaCheck || sizeCheck
})
</script>

<template>

  <Head title="Amortization List" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
      <div class="px-5 md:px-5 pt-5">
        <!-- Filter + Actions Card -->
        <div
          class="bg-gray-200 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <!-- Search bar -->
            <div class="flex-1 relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
              <InputField v-model="searchQuery" placeholder="Search cooperatives programs..."
                class="pl-9 pr-3 w-full rounded-sm border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200" />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
              <!-- Program Filter -->
              <select v-model="selectedProgram"
                class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">All Programs</option>
                <option v-for="program in [...new Set(props.coopPrograms.map(p => p.program_name))]" :key="program"
                  :value="program">
                  {{ program }}
                </option>
              </select>

              <!-- Status Filter -->
              <select v-model="selectedStatus"
                class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">All Status</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Finished">Finished</option>
                <option value="Resolved">Resolved</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="px-5 pb-2 hidden md:block">
        <div
          class="bg-white/90 dark:bg-gray-800/80 shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-gray-700">
          <Table class="min-w-full border-separate border-spacing-0 text-sm">
            <TableCaption class="text-lg font-semibold p-4 text-gray-900 dark:text-gray-100">
              List of Cooperatives
            </TableCaption>
            <TableHeader class="bg-gray-200/90 dark:bg-gray-700/50 border-b border-gray-500 dark:border-gray-500">
              <TableRow
                class="text-left text-gray-800 dark:text-gray-200 uppercase tracking-wide text-xs font-semibold">
                <TableHead class="py-3 pl-6">ID</TableHead>
                <TableHead class="pl-13 py-3">COOPERATIVES</TableHead>
                <TableHead class="pl-13 py-3">PROGRAMS</TableHead>
                <TableHead class="pl-13 py-3">LOAN AMOUNT</TableHead>
                <TableHead class="pl-13 py-3">NEXT DUE DATE</TableHead>
                <TableHead class="pl-13 py-3">STATUS</TableHead>
                <TableHead class="pl-13 py-3">ACTIONS</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody class="divide-y divide-gray-100 dark:divide-gray-700 bg-gray-100/70 dark:bg-gray-900">
              <TableRow v-for="program in paginatedPrograms" :key="program.id"
                class="transition-colors duration-150 hover:bg-gray-200/80 dark:hover:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 last:border-0">
                <TableCell class="py-3 pl-6 font-medium text-gray-700 dark:text-gray-400">
                  {{ program.id }}
                </TableCell>
                <TableCell class="pl-13 font-semibold text-gray-900 dark:text-gray-100">
                  {{ program.cooperative_name }}
                </TableCell>
                <TableCell class="pl-13 text-gray-600 dark:text-gray-300">
                  {{ program.program_name }}
                </TableCell>
                <TableCell v-if="program.status === 'Ongoing'" class="pl-13 text-gray-600 dark:text-gray-300">
                  ₱{{ (program.loan_amount ?? 0).toLocaleString() }}
                </TableCell>
                <TableCell v-else class="pl-13 text-gray-600 dark:text-gray-300">
                  Documented
                </TableCell>
                <TableCell class="pl-13 text-gray-600 dark:text-gray-300">
                  {{ formatNextDue(program.next_due_date) }}
                </TableCell>
                <!-- STATUS -->
                <TableCell class="pl-13 text-gray-600 dark:text-gray-300">
                  <!-- Finished -->
                  <template v-if="program.status === 'Finished'">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full
                  bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                      <CheckCircle class="w-3 h-3" />
                      Finished
                    </span>
                  </template>

                  <!-- Resolved -->
                  <template v-else-if="program.status === 'Resolved'">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full
                  bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                      <CheckCircle class="w-3 h-3" />
                      Resolved
                    </span>
                  </template>

                  <!-- Ongoing -->
                  <template v-else-if="program.status === 'Ongoing' && !program.has_schedule">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full
                  bg-yellow-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300">
                      <CircleDashed class="w-3 h-3 animate-spin" />
                      Ongoing - Checklist
                    </span>
                  </template>

                  <template v-else-if="program.status === 'Ongoing' && program.has_schedule">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full
                  bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                      <CircleDashed class="w-3 h-3 animate-spin" />
                      Ongoing - Program
                    </span>
                  </template>

                  <!-- No Amortization -->
                  <template v-else-if="!program.has_schedule">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full
                  bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                      <XCircle class="w-3 h-3" />
                      No Amortization
                    </span>
                  </template>

                  <!-- Fallback -->
                  <template v-else>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full
                  bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">
                      <XCircle class="w-3 h-3" />
                      {{ program.status }}
                    </span>
                  </template>
                </TableCell>
                <TableCell class="pl-13 py-4">
                  <template v-if="program.has_schedule">
                    <Link :href="`/amortizations/${program.coop_program_id}`"
                      class="inline-block px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700 transition">
                    View Amortization
                    </Link>
                  </template>
                  <template v-else-if="program.status === 'Finished' || program.status === 'Resolved'">
                    <Button
                      @click="openFileModal(`/amortization/${program.coop_program_id}/view`, program.cooperative_name)"
                      class="inline-block px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700 transition">
                      View Amortization File
                    </Button>
                  </template>
                  <template v-else>
                    <span
                      class="inline-block px-4 py-2 rounded-lg bg-gray-300 text-gray-700 dark:bg-gray-700 dark:text-gray-400 text-sm font-medium cursor-not-allowed">
                      No Amortization
                    </span>
                  </template>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row justify-between items-center mt-8 px-6 gap-4 pb-5">
          <!-- Showing text -->
          <span class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} -
            {{ Math.min(currentPage * itemsPerPage, filteredCoopPrograms.length) }}
            of {{ filteredCoopPrograms.length }} results
          </span>
          <div
            class="flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full shadow-md px-4 py-2">
            <!-- Previous Button -->
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
              class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 disabled:opacity-40 disabled:cursor-not-allowed px-3">
              <ChevronLeft class="w-5 h-5 ml-1" />
              <span>Previous</span>
            </button>

            <!-- Page Numbers -->
            <div class="flex items-center gap-1 mx-2">
              <template v-for="page in totalPages" :key="page">
                <button v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                  @click="goToPage(page)" :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all',
                    currentPage === page
                      ? 'bg-indigo-600 text-white shadow'
                      : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                  ]">
                  {{ page }}
                </button>
                <span v-else-if="page === currentPage - 2 || page === currentPage + 2"
                  class="text-gray-400 px-1">…</span>
              </template>
            </div>

            <!-- Next Button -->
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
              class="flex items-center text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 disabled:opacity-40 disabled:cursor-not-allowed px-3">
              <span>Next</span>
              <ChevronRight class="w-5 h-5 mr-1" />
            </button>
          </div>
        </div>
      </div>
      <!-- Mobile View -->
      <div class="md:hidden space-y-4 px-5 pb-4">
        <div v-for="program in paginatedPrograms" :key="program.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4 flex flex-col gap-3">
          <!-- Header -->
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                {{ program.cooperative_name }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ program.program_name }}</p>
            </div>
            <!-- Status Badge -->
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full" :class="{
              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300': program.status === 'Ongoing' && program.has_schedule,
              'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300': program.status === 'Ongoing' && !program.has_schedule,
              'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': program.status === 'Finished',
              'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300': program.status === 'Resolved',
              'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': !['Ongoing', 'Finished', 'Resolved'].includes(program.status)
            }">
              <template v-if="program.status === 'Ongoing' && program.has_schedule">
                <CircleDashed class="w-3 h-3 animate-spin" />
                Ongoing - Program
              </template>
              <template v-else-if="program.status === 'Ongoing' && !program.has_schedule">
                <CircleDashed class="w-3 h-3 animate-spin" />
                Ongoing - Checklist
              </template>
              <template v-else-if="program.status === 'Finished'">
                <CheckCircle class="w-3 h-3" />
                Finished
              </template>
              <template v-else-if="program.status === 'Resolved'">
                <CheckCircle class="w-3 h-3" />
                Resolved
              </template>
              <template v-else-if="!program.has_schedule && program.status !== 'Ongoing'">
                <XCircle class="w-3 h-3" />
                No Amortization
              </template>
              <template v-else>
                <XCircle class="w-3 h-3" />
                {{ program.status || 'Unknown' }}
              </template>
            </span>
          </div>

          <!-- Body -->
          <div class="flex flex-col gap-2 text-sm text-gray-600 dark:text-gray-300">
            <p><span class="font-medium text-gray-800 dark:text-gray-200">Loan Amount:</span>
              <span v-if="program.status === 'Ongoing'">₱{{ (program.loan_amount ?? 0).toLocaleString() }}</span>
              <span v-else>Documented</span>
            </p>
            <p><span class="font-medium text-gray-800 dark:text-gray-200">Next Due:</span>
              {{ formatNextDue(program.next_due_date) }}
            </p>
          </div>

          <!-- Action -->
          <div class="mt-2">
            <template v-if="program.has_schedule">
              <Link :href="`/amortizations/${program.coop_program_id}`"
                class="w-full block text-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700 transition">
              View Amortization
              </Link>
            </template>

            <template v-else-if="program.status === 'Finished' || program.status === 'Resolved'">
              <button @click="openFileModal(`/amortization/${program.coop_program_id}/view`, program.cooperative_name)"
                class="w-full block text-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700 transition">
                View Amortization File
              </button>
            </template>

            <template v-else>
              <button disabled
                class="w-full block text-center px-4 py-2 rounded-lg bg-gray-300 text-gray-600 dark:bg-gray-700 dark:text-gray-400 text-sm font-medium cursor-not-allowed">
                No Amortization
              </button>
            </template>
          </div>
        </div>
      </div>
      <!-- File Modal -->
      <Transition name="fade">
        <div v-if="showFileModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 sm:p-0"
          @click.self="closeFileModal">
          <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg w-full max-w-3xl max-h-[90vh] overflow-hidden sm:m-0 m-auto">
            <!-- Header -->
            <header class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 p-4">
              <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate">
                {{ selectedMember || 'Amortization Schedule' }}
              </h2>
              <button @click="closeFileModal" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                ✕
              </button>
            </header>

            <!-- Content -->
            <div class="p-4 overflow-auto max-h-[80vh] bg-gray-50 dark:bg-gray-800 rounded-b-2xl">
              <!-- PDF Preview -->
              <template v-if="selectedFile">
                <iframe v-if="!isMobile" :src="selectedFile" class="w-full h-[70vh] rounded"></iframe>

                <template v-else>
                  <!-- Show PdfViewer first, fallback if error -->
                  <PdfViewer v-if="!pdfFailed" :url="selectedFile" @error="pdfFailed = true" />
                  <div v-else class="text-center text-gray-600 dark:text-gray-400">
                    <p class="mb-2">PDF preview not supported on mobile.</p>
                    <a :href="selectedFile" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"
                      @click="closeFileModal">
                      Open PDF
                    </a>
                  </div>
                </template>
              </template>

              <!-- Fallback (no file) -->
              <div v-else class="text-center text-gray-600 dark:text-gray-400 py-10 text-sm">
                <p>No amortization file available for this program.</p>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </AppLayout>
</template>