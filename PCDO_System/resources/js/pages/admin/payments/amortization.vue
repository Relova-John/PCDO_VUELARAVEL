<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'
import amortizations from '@/routes/amortizations'
import { toast } from "vue-sonner"
import { ref } from 'vue';


// Types
interface Schedule {
  id: number
  due_date: string
  installment: number
  penalty_amount?: number
  is_paid?: boolean
  date_paid?: string | null
  balance?: number
  amount_paid?: number
}

interface CoopProgram {
  id: number
  cooperative_name: string
  program_name: string
  loan_amount: number
  term_months?: number
  grace_period?: number
  schedules: Schedule[]
  expected_end_date?: string | null
  program_status?: string
  resolved?: boolean
}

const props = defineProps<{ coopProgram: CoopProgram }>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Payments', href: amortizations.index().url },
  { title: 'Amortization', href: '#' },
]

// Flash message on successful amortization generation
onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  if (params.get('flash') === 'amortization_success') {
    toast.success('Amortization generated successfully!')
  }
})

onMounted(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  props.coopProgram.schedules.forEach((s) => {
    const due = new Date(s.due_date)
    due.setHours(0, 0, 0, 0)
    if (!s.is_paid && due < today && (!s.penalty_amount || s.penalty_amount === 0)) {
      router.post(`/schedules/${s.id}/penalty`, { add: true }, { preserveScroll: true })
    }
  })
})

// Forms
const scheduleForms = props.coopProgram.schedules.map(() => useForm({ amount_paid: '' }))

// Format date
function formatDate(dateStr?: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

// Expected End Date
const expectedEndDate = computed(() => {
  if (!props.coopProgram?.schedules?.length) return null
  const lastSchedule = props.coopProgram.schedules[props.coopProgram.schedules.length - 1]
  return lastSchedule?.due_date || null
})

// Get form index skipping grace periods
function getFormIndex(rowIndex: number) {
  const grace = props.coopProgram.grace_period || 0
  const formIndex = rowIndex - grace
  return formIndex >= 0 ? formIndex : 0
}

// Compute all periods with carry-over dues
const allPeriods = computed(() => {
  const periods: { type: 'grace' | 'schedule'; label: string; data?: Schedule; totalDue?: number }[] = []
  let carryOver = 0
  const grace = props.coopProgram.grace_period || 0
  const today = new Date()

  // Grace periods
  for (let i = 1; i <= grace; i++) {
    periods.push({ type: 'grace', label: `Grace Period ${i}` })
  }

  // Schedule rows with dues including carry-over
  props.coopProgram.schedules.forEach((s, i) => {
    const installment = Number(s.installment) || 0
    let penalty = Number(s.penalty_amount) || 0

    const paid = Number(s.amount_paid) || 0
    const dues = installment + penalty + carryOver
    const unpaid = Math.max(0, Math.round(dues - paid))

    carryOver = unpaid > 0 ? unpaid : 0

    periods.push({
      type: 'schedule',
      label: `Period ${i + 1}`,
      data: s,
      totalDue: dues
    })
  })

  return periods
})

const isDelinquent = computed(() => {
  if (!props.coopProgram?.schedules?.length) return false;

  const now = new Date();
  const fourMonthsAgo = new Date();
  fourMonthsAgo.setMonth(now.getMonth() - 4);

  // find if there's any schedule due before 4 months ago that is unpaid
  return props.coopProgram.schedules.some(s => {
    const dueDate = new Date(s.due_date);
    return dueDate < fourMonthsAgo && !s.is_paid;
  });
});

// Status display
function getStatus(schedule: Schedule) {
  const dueDate = schedule.due_date ? new Date(schedule.due_date) : new Date()
  const today = new Date()

  let type = 'Pending'
  let label = 'Pending'

  if (schedule.is_paid || (schedule.balance ?? 0) === 0) {
    type = 'Paid'
    label = schedule.date_paid
      ? `Paid on ${formatDate(schedule.date_paid)}`
      : 'Paid'
  } else if ((schedule.balance || 0) > 0 && (schedule.amount_paid || 0) > 0) {
    type = 'Partial Paid'
    label = `Partially Paid (Balance: ₱${Math.round(schedule.balance || 0).toLocaleString()})`
  } else if (
    dueDate.getFullYear() === today.getFullYear() &&
    dueDate.getMonth() === today.getMonth() &&
    dueDate.getDate() === today.getDate()
  ) {
    type = 'Due Today'
    label = 'Due Today'
  } else if (today > dueDate) {
    type = 'Overdue'
    label = 'Overdue'
  }

  return { type, label }
}

const showPenaltyModal = ref(false)
const penaltyRemarks = ref('')
const selectedScheduleId = ref<number | null>(null)

const showPaymentReceiptModal = ref(false)
const selectedPaymentScheduleId = ref<number | null>(null)
const paymentReceiptFile = ref<File | null>(null)
const isUploadingPaymentReceipt = ref(false)

const showNotePaymentReceiptModal = ref(false)
const selectedNotePaymentScheduleId = ref<number | null>(null)
const notePaymentReceiptFile = ref<File | null>(null)
const isUploadingNotePaymentReceipt = ref(false)

function openPenaltyModal(scheduleId: number) {
  selectedScheduleId.value = scheduleId
  showPenaltyModal.value = true
}

function closePenaltyModal() {
  showPenaltyModal.value = false
  penaltyRemarks.value = ''
  selectedScheduleId.value = null
}

function submitPenaltyRemoval() {
  if (!penaltyRemarks.value.trim()) {
    toast.error('Please enter remarks before removing penalty.')
    return
  }

  router.post(`/schedules/${selectedScheduleId.value}/penalty`,
    { remove: true, remarks: penaltyRemarks.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Penalty removed successfully.')
        closePenaltyModal()
      },
      onError: () => {
        toast.error('Failed to remove penalty.')
      },
    }
  )
}

function openPaymentReceiptModal(scheduleId: number) {
  selectedPaymentScheduleId.value = scheduleId
  showPaymentReceiptModal.value = true
}

function closePaymentReceiptModal() {
  showPaymentReceiptModal.value = false
  paymentReceiptFile.value = null
  selectedPaymentScheduleId.value = null
}

function onPaymentReceiptChange(e: Event) {
  const target = e.target as HTMLInputElement | null
  const file = target?.files?.[0] || null
  paymentReceiptFile.value = file
}

async function submitPaymentReceipt() {
  if (!paymentReceiptFile.value || !selectedPaymentScheduleId.value) {
    toast.error('Please select a receipt before submitting.')
    return
  }

  isUploadingPaymentReceipt.value = true
  const formData = new FormData()
  formData.append('receipt_image', paymentReceiptFile.value)

  router.post(`/schedules/${selectedPaymentScheduleId.value}/upload-receipt`, formData, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Receipt uploaded successfully. Payment marked as paid!')
      closePaymentReceiptModal()
      router.reload()
    },
    onError: () => {
      toast.error('Failed to upload receipt. Please try again.')
    },
    onFinish: () => {
      isUploadingPaymentReceipt.value = false
    }
  })
}

function openNotePaymentReceiptModal(scheduleId: number) {
  selectedNotePaymentScheduleId.value = scheduleId
  showNotePaymentReceiptModal.value = true
}

function closeNotePaymentReceiptModal() {
  showNotePaymentReceiptModal.value = false
  selectedNotePaymentScheduleId.value = null
  notePaymentReceiptFile.value = null
}

function onNotePaymentReceiptChange(e: Event) {
  const target = e.target as HTMLInputElement | null
  const file = target?.files?.[0] || null
  notePaymentReceiptFile.value = file
}

async function submitNotePaymentReceipt() {
  if (!notePaymentReceiptFile.value || !selectedNotePaymentScheduleId.value) {
    toast.error('Please select a receipt before submitting.')
    return
  }

  // Get the amount for this schedule
  const scheduleIndex = props.coopProgram.schedules.findIndex(
    s => s.id === selectedNotePaymentScheduleId.value
  )

  // Ensure the amount is treated as a number
  const amount = Number(scheduleForms[getFormIndex(scheduleIndex)].amount_paid)

  if (isNaN(amount) || amount <= 0) {
    toast.error('Please enter a valid amount before submitting.')
    return
  }

  isUploadingNotePaymentReceipt.value = true

  const formData = new FormData()
  formData.append('receipt_image', notePaymentReceiptFile.value)
  formData.append('amount_paid', amount.toString()) // append as string for FormData

  router.post(
    `/schedules/${selectedNotePaymentScheduleId.value}/upload-note-receipt`,
    formData,
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Receipt uploaded successfully for noted payment!')
        closeNotePaymentReceiptModal()
        router.reload()
      },
      onError: () => {
        toast.error('Failed to upload receipt. Please try again.')
      },
      onFinish: () => {
        isUploadingNotePaymentReceipt.value = false
      },
    }
  )
}

//Toggle Penalty
function togglePenalty(scheduleId: number, hasPenalty: boolean, row: Schedule) {
  router.post(
    `/schedules/${scheduleId}/penalty`,
    hasPenalty ? { remove: true } : { add: true },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(hasPenalty ? 'Penalty removed.' : 'Penalty added.')
        // Refresh the page so the new penalty_amount is loaded
        router.reload()
      },
      onError: () => {
        toast.error('Failed to toggle penalty. Please try again.')
      }
    }
  )
}

//Send Notification
function sendNotification(scheduleId: number) {
  router.post(
    `/schedules/${scheduleId}/send-notif`,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        const flash = usePage().props.flash as { success?: string; error?: string }

        if (flash.success) {
          toast.success(flash.success)
        } else if (flash.error) {
          toast.error(flash.error)
        } else {
          toast.success('Reminder sent successfully!')
        }
      },
      onError: () => {
        toast.error('Something went wrong while sending reminder.')
      }
    }
  )
}

// Receipt Modal States
const showReceiptModal = ref(false)
const isSubmitting = ref(false)
const receiptFile = ref<File | null>(null)
const isResolved = computed(() => props.coopProgram.resolved)

// Open Modal
function openReceiptModal() {
  showReceiptModal.value = true
}

// Close Modal
function closeReceiptModal() {
  showReceiptModal.value = false
  receiptFile.value = null
}

// Submit Receipt
async function submitReceipt() {
  if (!receiptFile.value) {
    toast.error("Please upload a receipt before submitting.")
    return
  }

  isSubmitting.value = true
  const formData = new FormData()
  formData.append('receipt', receiptFile.value)

  router.post(`/amortization/${props.coopProgram.id}/resolve`, formData, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success("Loan marked as resolved successfully!");
      closeReceiptModal();
      router.reload();
    },
    onError: () => {
      toast.error("Failed to resolve loan. Please try again.");
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement | null
  const file = target?.files?.[0] || null
  receiptFile.value = file
}

// Download Amortization even if it not finished
function downloadPdf() {
  window.location.href = `/amortization/${props.coopProgram.id}/download`
}

function canPayPeriod(index: number) {
  const schedules = props.coopProgram.schedules
  const current = schedules[index]
  if (!current) return false

  const status = getStatus(current).type

  if (status === 'Paid' || status === 'Partial Paid') return false

  const lastFinishedIndex = schedules.findLastIndex((s) => {
    const sStatus = getStatus(s).type
    return ['Paid', 'Partial Paid', 'Overdue'].includes(sStatus)
  })

  if (lastFinishedIndex === -1) {
    const firstUnpaidIndex = schedules.findIndex(
      (s) => !['Paid', 'Partial Paid'].includes(getStatus(s).type)
    )
    return index === firstUnpaidIndex
  }

  const firstAfterFinished = schedules.findIndex(
    (s, i) =>
      i > lastFinishedIndex &&
      !['Paid', 'Partial Paid'].includes(getStatus(s).type)
  )

  return index === firstAfterFinished
}


</script>

<template>

  <Head title="Loan Tracker" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
      <div class="px-5 md:px-5 pt-5">

        <!-- Loan Information Card -->
        <div
          class="bg-gray-200/40 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">

          <!-- Header -->
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              Loan Tracker
            </h2>
          </div>
          <div
            class="bg-gray-50 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
            <p class="text-m text-gray-500 dark:text-gray-400">
              Program: <span class="font-medium text-gray-800 dark:text-gray-200">
                {{ coopProgram.program_name }}
              </span>
            </p>
            <p class="text-m text-gray-500 dark:text-gray-400">
              Cooperative: <span class="font-medium text-gray-800 dark:text-gray-200">
                {{ coopProgram.cooperative_name }}
              </span>
            </p>
            <p class="mt-2 text-lg font-semibold text-indigo-600 dark:text-indigo-400">
              ₱{{ Math.round(coopProgram.loan_amount).toLocaleString() }}
            </p>


            <hr class="my-6 border-t border-gray-300 dark:border-gray-700" />

            <!-- Grid of Key Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
              <!-- Start Date -->
              <div
                class="bg-gray-200/50 dark:bg-gray-700 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Start Date</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                  {{ formatDate(coopProgram.schedules?.[0]?.due_date) }}
                </p>
              </div>

              <!-- Grace Period -->
              <div
                class="bg-gray-200/50 dark:bg-gray-700 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Grace Period</p>
                <p class="mt-1 text-lg font-semibold text-blue-600 dark:text-blue-300">
                  {{ coopProgram.grace_period || 0 }} months
                </p>
              </div>

              <!-- Loan Term -->
              <div
                class="bg-gray-200/50 dark:bg-gray-700 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Loan Term</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                  {{ coopProgram.term_months || 0 }} months
                </p>
              </div>

              <!-- Expected End Date -->
              <div
                class="bg-gray-200/50 dark:bg-gray-700 p-4 rounded-xl ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Expected End Date</p>
                <p class="mt-1 text-lg font-semibold text-purple-600 dark:text-purple-300">
                  {{ formatDate((coopProgram as any).expected_end_date) || 'N/A' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Payment Schedule Header -->
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Payment Schedule</h3>
            <button @click="downloadPdf"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition">
              Download PDF
            </button>
          </div>
          <div class="hidden md:block">
            <Table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700 shadow-lg">
              <!-- Header -->
              <TableHeader class="bg-gray-100 dark:bg-gray-700/50">
                <TableRow>
                  <TableHead class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Period
                  </TableHead>
                  <TableHead class="px-4 py-3">Due Date</TableHead>
                  <TableHead class="px-4 py-3">Amount</TableHead>
                  <TableHead class="px-4 py-3">Penalty</TableHead>
                  <TableHead class="px-4 py-3">Dues</TableHead>
                  <TableHead class="px-4 py-3">Status</TableHead>
                  <TableHead class="px-4 py-3">Actions</TableHead>
                  <TableHead class="px-4 py-3">Reminder</TableHead>
                </TableRow>
              </TableHeader>

              <!-- Body -->
              <TableBody>
                <TableRow v-for="(row, index) in allPeriods" :key="index" :class="[
                  'transition-colors',
                  row.data?.is_paid ? 'bg-green-50 dark:bg-green-900/20' : '',
                  index % 2 === 0 ? 'bg-gray-200 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-900'
                ]">

                  <!-- Grace Period Row -->
                  <template v-if="row.type === 'grace'">
                    <TableCell class="px-4 py-3 font-medium text-indigo-400">
                      {{ row.label }}
                    </TableCell>

                    <TableCell colspan="7" class="text-center font-semibold text-yellow-500">
                      <span class="inline-flex items-center gap-2 justify-center">
                        <Leaf class="w-4 h-4 text-green-400" />
                        <span>No payment due (Grace Period)</span>
                      </span>
                    </TableCell>
                  </template>

                  <!-- Regular Payment Row -->
                  <template v-else>
                    <!-- Period -->
                    <TableCell class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">
                      {{ row.label }}
                    </TableCell>

                    <!-- Due Date -->
                    <TableCell class="px-4 py-3">
                      {{ formatDate(row.data?.due_date) }}
                    </TableCell>

                    <!-- Amount -->
                    <TableCell class="px-4 py-3 font-medium">
                      ₱{{ Math.round(row.data?.installment || 0).toLocaleString() }}
                    </TableCell>

                    <!-- Penalty -->
                    <TableCell class="px-4 py-3">
                      <div v-if="row.data" class="flex items-center gap-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                          ₱{{ Math.round(row.data.penalty_amount || 0).toLocaleString() }}
                        </span>
                        <Button size="sm" :disabled="(row.data.penalty_amount! <= 0 && (new Date(row.data.due_date) >= new Date() || row.data.is_paid))
                          || (row.data.penalty_amount! > 0 && row.data.is_paid)
                          " @click="
                            row.data.penalty_amount! > 0
                              ? openPenaltyModal(row.data.id)
                              : togglePenalty(row.data.id, false, row.data!)
                            " :class="[
                              'flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold shadow transition',
                              row.data.penalty_amount! > 0
                                ? 'bg-red-600 hover:bg-red-700 text-white'
                                : 'bg-orange-500 hover:bg-orange-600 text-white'
                            ]">
                          <span v-if="row.data.penalty_amount! > 0">✕ Remove</span>
                          <span v-else class="inline-flex items-center gap-1">
                            <Plus class="w-3 h-3" />
                            <span>Add</span>
                          </span>
                        </Button>
                      </div>
                    </TableCell>

                    <!-- Dues -->
                    <TableCell class="font-semibold text-indigo-700 dark:text-indigo-300">
                      ₱{{ Number(row.data?.installment || 0).toLocaleString('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                      }) }}
                      <span v-if="row.data?.penalty_amount && row.data.penalty_amount > 0">
                        + ₱{{ Number(row.data.penalty_amount || 0).toLocaleString('en-PH', {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2
                        }) }}
                      </span>
                      = <strong>
                        ₱{{ row.totalDue !== undefined
                          ? Number(row.totalDue).toLocaleString('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                          })
                          : '0.00'
                        }}
                      </strong>
                    </TableCell>

                    <!-- Status -->
                    <TableCell class="px-4 py-3">
                      <template v-if="isResolved">
                        <span
                          class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200">
                          <Check class="w-4 h-4" />
                          <span>Resolved</span>
                        </span>
                      </template>

                      <template v-else>
                        <span :class="[
                          'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold',
                          row.data?.is_paid
                            ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200'
                            : getStatus(row.data!).type === 'Overdue'
                              ? 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200'
                              : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-700 dark:text-yellow-200'
                        ]">
                          {{ getStatus(row.data!).label }}
                        </span>
                      </template>
                    </TableCell>

                    <!-- Actions -->
                    <TableCell class="px-4 py-3">
                      <template v-if="isResolved">
                        <div class="inline-flex items-center gap-1 text-green-600 italic font-semibold">
                          <Check class="w-4 h-4" />
                          <span>Resolved</span>
                        </div>
                      </template>

                      <template v-else>
                        <div
                          v-if="row.data && !row.data.is_paid && !(row.data.amount_paid && (row.data.balance || 0) > 0)"
                          class="flex flex-col gap-2">

                          <Button size="sm" class="w-36 bg-green-600 hover:bg-green-700 text-white rounded-full"
                            @click="openPaymentReceiptModal(row.data!.id)" :disabled="!canPayPeriod(index)">
                            <ReceiptText class="w-3 h-3" /> Mark Paid
                          </Button>
                          <input type="number" v-model.number="scheduleForms[getFormIndex(index)].amount_paid"
                            placeholder="Enter amount"
                            class="w-36 px-3 py-2 border rounded-xl border-gray-400 dark:border-gray-700 text-sm focus:ring-2 focus:ring-indigo-500" />

                          <Button size="sm" class="w-36 bg-blue-600 hover:bg-blue-700 text-white rounded-full"
                            @click="openNotePaymentReceiptModal(row.data!.id)" :disabled="!canPayPeriod(index)">
                            <CircleDollarSign class="w-3 h-3 text-yellow-300" /> Pay
                          </Button>
                        </div>

                        <div v-else-if="row.data?.amount_paid && (row.data.balance || 0) > 0"
                          class="inline-flex items-center gap-1 text-yellow-600 font-semibold">
                          <TriangleAlert class="w-4 h-4" />
                          <span>Partially Paid</span>
                        </div>

                        <div v-else-if="row.data?.is_paid"
                          class="inline-flex items-center gap-1 text-emerald-400 italic font-semibold">
                          <Check class="w-5 h-5" />
                          <span>Paid</span>
                        </div>
                      </template>
                    </TableCell>

                    <!-- Reminder -->
                    <TableCell class="px-4 py-3">
                      <template v-if="isResolved">
                        <span class="inline-flex items-center gap-1 text-green-600 italic font-semibold">
                          <Check class="w-5 h-5" />
                          <span>Resolved</span>
                        </span>
                      </template>

                      <template v-else-if="row.data?.is_paid">
                        <span class="inline-flex items-center gap-1 text-emerald-400 italic font-semibold">
                          <Check class="w-5 h-5" />
                          <span>Paid</span>
                        </span>
                      </template>

                      <template v-else>
                        <Button size="sm"
                          class="px-4 py-1.5 rounded-full text-sm font-semibold bg-red-700 hover:bg-red-800 text-white shadow-sm"
                          @click="sendNotification(row.data!.id)">
                          🔔 Send Reminder
                        </Button>
                      </template>
                    </TableCell>
                  </template>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <!-- Mobile Card View -->
          <div class="space-y-4 md:hidden">
            <div v-for="(row, index) in allPeriods" :key="index"
              class="rounded-xl shadow-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
              <template v-if="row.type === 'grace'">
                <p class="font-semibold text-indigo-500 mb-2">{{ row.label }}</p>
                <div class="flex items-center justify-center text-yellow-600 dark:text-yellow-300 font-medium">
                  <Leaf class="w-4 h-4 mr-2" /> No payment due (Grace Period)
                </div>
              </template>

              <template v-else>
                <!-- Header -->
                <div class="flex justify-between items-center mb-2">
                  <p class="font-semibold text-gray-900 dark:text-gray-100">{{ row.label }}</p>
                  <span :class="[
                    'text-xs px-3 py-1 rounded-full font-semibold',
                    row.data?.is_paid
                      ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200'
                      : getStatus(row.data!).type === 'Overdue'
                        ? 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200'
                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-700 dark:text-yellow-200'
                  ]">
                    {{ getStatus(row.data!).label }}
                  </span>
                </div>

                <!-- Payment Details -->
                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300 mb-3">
                  <p><strong>Due Date:</strong> {{ formatDate(row.data?.due_date) }}</p>
                  <p><strong>Amount:</strong> ₱{{ Math.round(row.data?.installment || 0).toLocaleString() }}</p>
                  <p><strong>Penalty:</strong> ₱{{ Math.round(row.data?.penalty_amount || 0).toLocaleString() }}</p>
                  <p><strong>Dues:</strong>
                    ₱{{ row.totalDue !== undefined
                      ? Number(row.totalDue).toLocaleString('en-PH', {
                        minimumFractionDigits: 2, maximumFractionDigits: 2
                      })
                      : '0.00'
                    }}
                  </p>
                </div>

                <!-- Actions -->
                <div v-if="row.data" class="flex flex-col sm:flex-row sm:items-center gap-2">
                  <!-- Penalty Amount -->
                  <span class="font-medium text-gray-700 dark:text-gray-300 text-sm sm:text-base">
                    ₱{{ Math.round(row.data.penalty_amount || 0).toLocaleString() }}
                  </span>
                  <Button size="sm" :disabled="row.data.penalty_amount! <= 0
                    ? !canPayPeriod(index)
                    : ['Paid', 'Partial Paid'].includes(getStatus(row.data!).type)
                    " @click="
                      row.data.penalty_amount! > 0
                        ? openPenaltyModal(row.data.id)
                        : togglePenalty(row.data.id, false, row.data!)
                      " :class="[
                        'flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold shadow transition',
                        row.data.penalty_amount! > 0
                          ? 'bg-red-600 hover:bg-red-700 text-white'
                          : 'bg-orange-500 hover:bg-orange-600 text-white'
                      ]">
                    <span v-if="row.data.penalty_amount! > 0">✕ Remove</span>
                    <span v-else class="inline-flex items-center gap-1">
                      <Plus class="w-3 h-3" />
                      <span>Add</span>
                    </span>
                  </Button>

                </div>

                <div class="flex flex-wrap gap-2 justify-between">
                  <template v-if="isResolved">
                    <div class="inline-flex items-center gap-1 text-green-600 italic font-semibold">
                      <Check class="w-4 h-4" />
                      <span>Resolved</span>
                    </div>
                  </template>

                  <template v-else>
                    <!-- Payment buttons -->
                    <div v-if="row.data && !row.data.is_paid" class="flex flex-col gap-2 w-full">
                      <input type="number" v-model.number="scheduleForms[getFormIndex(index)].amount_paid"
                        placeholder="Enter amount"
                        class="w-full px-3 py-2 border rounded-lg border-gray-300 dark:border-gray-700 text-sm focus:ring-2 focus:ring-indigo-500" />
                      <Button size="sm"
                        class="bg-green-600 hover:bg-green-700 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="openPaymentReceiptModal(row.data!.id)" :disabled="!canPayPeriod(index)">
                        <ReceiptText class="w-4 h-4 mr-1" /> Mark Paid
                      </Button>
                      <Button size="sm"
                        class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="openNotePaymentReceiptModal(row.data!.id)" :disabled="!canPayPeriod(index)">
                        <CircleDollarSign class="w-4 h-4 text-yellow-300 mr-1" /> Pay
                      </Button>
                    </div>

                    <!-- Reminder -->
                    <Button size="sm"
                      class="w-full bg-red-700 hover:bg-red-800 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                      @click="sendNotification(row.data!.id)" :disabled="!canPayPeriod(index)">
                      🔔 Send Reminder
                    </Button>
                  </template>
                </div>
              </template>
            </div>
          </div>

          <div class="flex justify-end mt-6 p-4">
            <button v-if="!isResolved" type="button" @click="openReceiptModal"
              class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg shadow-md transition">
              Resolve / Nullify Amortization
            </button>
          </div>
          <!-- Receipt Upload Modal -->
          <div v-if="showReceiptModal"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-[90%] max-w-md p-6 relative">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Upload Receipt to Resolve Loan
              </h3>

              <div class="flex flex-col gap-3">
                <input type="file" accept="image/jpeg,image/jpg,application/pdf" @change="onFileChange"
                  class="border border-gray-300 dark:border-gray-700 rounded-lg p-2 text-sm bg-gray-50 dark:bg-gray-700" />
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Please upload the official receipt (image or PDF) before marking this loan as resolved.
                </p>
              </div>

              <div class="mt-6 flex justify-end gap-3">
                <button @click="closeReceiptModal"
                  class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">
                  Cancel
                </button>

                <button @click="submitReceipt" :disabled="isSubmitting"
                  class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">
                  <span v-if="isSubmitting">Submitting...</span>
                  <span v-else>Submit Receipt</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Penalty Removal Modal -->
          <div v-if="showPenaltyModal"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-[90%] max-w-md p-6 relative">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Remove Penalty
              </h3>

              <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                Please provide a reason for removing this penalty.
              </p>

              <textarea v-model="penaltyRemarks" rows="3"
                class="w-full p-3 border rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 text-sm"
                placeholder="Enter remarks here..."></textarea>

              <div class="mt-5 flex justify-end gap-3">
                <button @click="closePenaltyModal"
                  class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">
                  Cancel
                </button>
                <button @click="submitPenaltyRemoval"
                  class="px-5 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white">
                  Confirm Remove
                </button>
              </div>
            </div>
          </div>

          <!-- Payment Receipt Upload Modal -->
          <div v-if="showPaymentReceiptModal"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-[90%] max-w-md p-6 relative">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Upload Receipt for Payment
              </h3>

              <div class="flex flex-col gap-3">
                <input type="file" accept="image/*,application/pdf" @change="onPaymentReceiptChange"
                  class="border border-gray-300 dark:border-gray-700 rounded-lg p-2 text-sm bg-gray-50 dark:bg-gray-700" />
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Please upload the official receipt (image) to confirm this payment.
                </p>
              </div>

              <div class="mt-6 flex justify-end gap-3">
                <button @click="closePaymentReceiptModal"
                  class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">
                  Cancel
                </button>

                <button @click="submitPaymentReceipt" :disabled="isUploadingPaymentReceipt"
                  class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">
                  <span v-if="isUploadingPaymentReceipt">Uploading...</span>
                  <span v-else>Submit Receipt</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Note Payment Receipt Upload Modal -->
          <div v-if="showNotePaymentReceiptModal"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-[90%] max-w-md p-6 relative">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Upload Receipt for Noted Payment
              </h3>

              <div class="flex flex-col gap-3">
                <input type="file" accept="image/*,application/pdf" @change="onNotePaymentReceiptChange"
                  class="border border-gray-300 dark:border-gray-700 rounded-lg p-2 text-sm bg-gray-50 dark:bg-gray-700" />
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Please upload the official receipt (image) to confirm this noted payment.
                </p>
              </div>

              <div class="mt-6 flex justify-end gap-3">
                <button @click="closeNotePaymentReceiptModal"
                  class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">
                  Cancel
                </button>

                <button @click="submitNotePaymentReceipt" :disabled="isUploadingNotePaymentReceipt"
                  class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">
                  <span v-if="isUploadingNotePaymentReceipt">Uploading...</span>
                  <span v-else>Submit Receipt</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>