<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import { computed, watch, ref, nextTick, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import PdfViewer from '@/components/PdfViewer.vue'

// Interfaces
interface ChecklistItem {
  id: number
  name: string
  upload?: { id: number; file_name: string; mime_type: string } | null
}

interface CoopProgram {
  id: number
  cooperative: { id: number; name: string }
  program?: { id: number; name: string; min_amount: number; max_amount: number } | null
  loan_amount?: number | null
  with_grace?: number | null
  consenter?: string | null
  has_amortization?: boolean
}

// Loan form
interface LoanFormData {
  loan_amount: number | null
  with_grace: number
  start_date: string
}

// Props
const props = defineProps<{
  cooperative: CoopProgram
  checklistItems: ChecklistItem[]
}>()

const filteredChecklist = computed(() =>
  props.checklistItems.filter(item => item.name.toLowerCase() !== 'bio data')
)

// All uploads must be completed before finalizing loan
const allUploadsDone = computed(() =>
  props.checklistItems
    .filter(item => item.name.toLowerCase() !== 'bio data')
    .every(item => item.upload)
)

const canFinalizeLoan = computed(() => allUploadsDone.value && !!props.cooperative.consenter)

// Disable upload if previous checklist not uploaded
const isDisabled = (index: number) =>
  index > 0 && !filteredChecklist.value[index - 1].upload

// Forms
const forms = filteredChecklist.value.map(item =>
  useForm({
    file: null as File | null,
    program_checklist_id: item.id,
  })
)

const loanForm = useForm<LoanFormData>({
  loan_amount: props.cooperative.loan_amount || null,
  with_grace: props.cooperative.with_grace || 0,
  start_date: '',
})

const selectedFile = ref<{ id: number; name: string; url: string } | null>(null)
const isConsented = ref(false);
const showPreviewModal = ref(false)

const finalizeLoanSection = ref<HTMLElement | null>(null)
const isChecklistRemade = ref(false)
const pdfFailed = ref(false)

watch(showPreviewModal, (isOpen) => {
  if (isOpen) {
    const firstUploaded = props.checklistItems.find(item => item.upload)
    if (firstUploaded?.upload) {
      selectedFile.value = {
        id: firstUploaded.upload.id,
        name: firstUploaded.upload.file_name,
        url: `/programs/${props.cooperative.program?.id}/cooperatives/${props.cooperative.cooperative.id}/checklist/${firstUploaded.upload.id}/preview`
      }
    }
  } else {
    selectedFile.value = null
    isConsented.value = false
  }
})

//Saves the Consent
function saveConsent() {
  if (!isConsented.value) return

  router.post(
    `/programs/${props.cooperative.program?.id}/cooperatives/${props.cooperative.cooperative.id}/consent`,
    {},
    {
      onSuccess: () => {
        toast.success('Consent recorded successfully!')
        isConsented.value = false
        showPreviewModal.value = false
        isChecklistRemade.value = false
        router.reload({ only: ['checklistItems', 'cooperative'] })
      },
      onError: () => {
        toast.error('Failed to record consent.')
      },
    }
  )
}

// When clicking a file in the modal
function openFilePreview(item: any) {
  selectedFile.value = {
    id: item.upload.id,
    name: item.upload.file_name,
    url: `/programs/${props.cooperative.program?.id}/cooperatives/${props.cooperative.cooperative.id}/checklist/${item.upload.id}/preview`
  }
}

// Handle file upload
function handleUpload(index: number, item: ChecklistItem) {
  const uploadedFile = forms[index].file

  forms[index].post(
    `/programs/${props.cooperative.program?.id}/cooperatives/${props.cooperative.cooperative.id}/checklist/upload`,
    {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        const fileName = uploadedFile?.name || 'File'
        const isChecklistRemade = ref(true)
        forms[index].reset()
        router.visit(window.location.href, {
          only: ['checklistItems'],
          preserveScroll: true,
          preserveState: true,
          replace: true,
        })
        const displayName = fileName.length > 20 ? fileName.slice(0, 17) + '...' : fileName
        toast.success(`"${displayName}" uploaded successfully!`)

      },
      onError: () => toast.error('Failed to upload file. Please try again.')
    }
  )
}

// Submit loan
function submitLoan() {
  if (!props.cooperative.program) return

  loanForm.post(
    `/programs/${props.cooperative.program.id}/cooperatives/${props.cooperative.cooperative.id}/finalize-loan`,
    {
      onSuccess: () => {
        toast.success('Loan finalized and amortization schedule generated successfully!')
        router.reload({
          only: ['cooperative', 'checklistItems'],
          onFinish: () => {
            nextTick(() => {
              finalizeLoanSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
            })
          }
        })
      },
      onError: () => {
        toast.error('Failed to finalize loan. Please try again.')
      }
    }
  )
}

// if all checklist they can go view the files they uploaded and it will preselect the first file
function handleSaveProgress() {
  if (allUploadsDone.value) {
    showPreviewModal.value = true
  } else {
    router.visit(`/programs/${props.cooperative.program?.id}`)
  }
}

function handleDelete(uploadId: number, fileName: string) {
  router.delete(
    `/programs/${props.cooperative.program?.id}/cooperatives/${props.cooperative.cooperative.id}/checklist/${uploadId}`,
    {
      onSuccess: () => {
        router.reload({ only: ['checklistItems'] })
        toast.success(`"${fileName}" has been deleted successfully!`)
      },
      onError: () => {
        toast.error('Failed to delete the file. Please try again.')
      }
    }
  )
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Programs', href: '/programs' },
  { title: props.cooperative.program?.name || 'N/A', href: `/programs/${props.cooperative.program?.id}` },
  { title: 'Checklist', href: '#' },
]

const isMobile = ref(false)
onMounted(() => {
  const uaCheck = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
  const sizeCheck = window.matchMedia('(max-width: 768px)').matches
  isMobile.value = uaCheck || sizeCheck
})
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs" :key="props.cooperative.id">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
      <div class="px-5 md:px-8 pt-5 p-6 space-y-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div
          class="bg-gray-50 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
          <h2 class="text-2xl font-semibold mb-2 text-gray-900 dark:text-gray-100">
            Checklist for {{ props.cooperative.cooperative.name }}
          </h2>
          <p class="text-gray-600 dark:text-gray-400">
            Program: {{ props.cooperative.program?.name || 'N/A' }}
          </p>
        </div>

        <!-- Loan Section -->
        <div ref="finalizeLoanSection" v-if="props.cooperative.program && !props.cooperative.has_amortization"
          class="bg-gray-50 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
          <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">
            Finalize Loan
          </h3>

          <!-- Already finalized -->
          <div v-if="typeof props.cooperative.loan_amount === 'number'">
            <p class="text-gray-800 dark:text-gray-200">
              <strong>Loan Amount:</strong>
              ₱{{ props.cooperative.loan_amount.toLocaleString() }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 mt-2">
              <strong>Grace Period:</strong>
              {{ props.cooperative.with_grace === 0
                ? 'No Grace Period'
                : props.cooperative.with_grace + '-Month Grace Period' }}
            </p>
          </div>

          <!-- Loan form -->
          <form v-if="canFinalizeLoan" @submit.prevent="submitLoan" class="space-y-4">
            <!-- Loan Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Loan Amount
              </label>
              <input type="number" v-model="loanForm.loan_amount" :min="props.cooperative.program?.min_amount"
                :max="props.cooperative.program?.max_amount" step="0.01" required
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                placeholder="Enter loan amount" />

              <div v-if="loanForm.errors.loan_amount" class="text-red-600 text-sm mt-1">
                {{ loanForm.errors.loan_amount }}
              </div>

              <div class="flex gap-2 mt-2">
                <button type="button" @click="loanForm.loan_amount = props.cooperative.program?.min_amount || 0"
                  class="bg-gray-100 dark:bg-[#334155] border border-gray-300 dark:border-[#475569]
                           text-gray-800 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-[#3b445c] px-3 py-1 rounded">
                  Min: ₱{{ props.cooperative.program?.min_amount || 0 }}
                </button>
                <button type="button" @click="loanForm.loan_amount = props.cooperative.program?.max_amount || 0"
                  class="bg-gray-100 dark:bg-[#334155] border border-gray-300 dark:border-[#475569]
                           text-gray-800 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-[#3b445c] px-3 py-1 rounded">
                  Max: ₱{{ props.cooperative.program?.max_amount || 0 }}
                </button>
              </div>
            </div>

            <!-- Grace Period -->
            <div class="mt-4 border-t pt-4 border-gray-300 dark:border-gray-600">
              <span class="block text-sm font-medium mb-2 text-gray-900 dark:text-gray-100">
                Grace Period
              </span>
              <div class="flex gap-4 flex-col sm:flex-row">

                <label class="flex-1 cursor-pointer">
                  <div :class="['p-3 rounded-lg border transition',
                    loanForm.with_grace === 0
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                      : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800']">
                    <input type="radio" v-model="loanForm.with_grace" :value="0" class="hidden" />
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No Grace Period</p>
                  </div>
                </label>

                <label class="flex-1 cursor-pointer">
                  <div :class="['p-3 rounded-lg border transition',
                    loanForm.with_grace === 4
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                      : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800']">
                    <input type="radio" v-model="loanForm.with_grace" :value="4" class="hidden" />
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">4-Month Grace Period</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- Start Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Loan Start Date
              </label>
              <input type="date" v-model="loanForm.start_date" required
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
              <div v-if="loanForm.errors.start_date" class="text-red-600 text-sm mt-1">
                {{ loanForm.errors.start_date }}
              </div>
            </div>

            <div class="flex justify-end mt-4">
              <AlertDialog>
                <AlertDialogTrigger as-child>
                  <button type="button"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow-md"
                    :disabled="loanForm.processing">
                    Finalize Loan
                  </button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>Finalize Loan?</AlertDialogTitle>
                    <AlertDialogDescription>
                      You are about to finalize the loan for
                      <strong>{{ props.cooperative.cooperative.name }}</strong>.<br />
                      Loan Amount: ₱{{ loanForm.loan_amount?.toLocaleString() }} <br />
                      Grace Period:
                      {{ loanForm.with_grace === 0 ? 'No Grace Period' : loanForm.with_grace + '-Month Grace Period' }}
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="submitLoan">
                      Confirm Finalize
                    </AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </form>

          <!-- When all files are uploaded but no consent yet -->
          <div v-else-if="allUploadsDone && !props.cooperative.consenter"
            class="bg-yellow-50 border border-yellow-200 p-4 rounded text-yellow-800">
            Please confirm the checklist consent before finalizing the loan.
          </div>

          <!-- When files are still missing -->
          <div v-else class="bg-yellow-50 border border-yellow-200 p-4 rounded text-yellow-800">
            Please upload all required documents before finalizing the loan.
          </div>
        </div>

        <!-- Checklist Items -->
        <div v-for="(item, index) in filteredChecklist" :key="item.id"
          class="bg-gray-50 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
          <h5 class="font-semibold text-lg mb-3 text-gray-900 dark:text-gray-100">
            {{ item.name }}
          </h5>

          <!-- Already uploaded -->
          <div v-if="item.upload" class="mb-3 flex items-center justify-between">
            <p class="text-sm text-gray-800 dark:text-gray-300">
              Uploaded File: <span class="truncate block max-w-[140px] md:max-w-[140px]" title="{{ file.file_name }}">
                <strong>{{ item.upload.file_name }}</strong>
              </span> </p>
            <div class="flex gap-4">
              <a :href="`/programs/${props.cooperative.program?.id}/cooperatives/${props.cooperative.cooperative.id}/checklist/${item.upload.id}/download`"
                class="text-blue-600 dark:text-blue-400 hover:underline">
                Download
              </a>
              <AlertDialog>
                <AlertDialogTrigger as-child>
                  <button class="text-red-600 dark:text-red-400 hover:underline">
                    Delete
                  </button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>Delete Upload?</AlertDialogTitle>
                    <AlertDialogDescription>
                      This will permanently remove <strong>{{ item.upload.file_name }}</strong>.
                      This action cannot be undone.
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="handleDelete(item.upload.id, item.upload.file_name)">
                      Confirm Delete
                    </AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </div>

          <!-- Upload form -->
          <form @submit.prevent="handleUpload(index, item)" class="flex items-center gap-4">
            <input type="file" name="file" class="border p-2 rounded-lg w-full bg-gray-50 dark:bg-[#1e293b]
           border-gray-300 dark:border-[#334155]
           text-gray-900 dark:text-gray-100 disabled:bg-gray-200 disabled:text-gray-400
           dark:disabled:bg-[#0f172a] dark:disabled:text-gray-500 disabled:cursor-not-allowed"
              @change="forms[index].file = ($event.target as HTMLInputElement).files?.[0] || null"
              :disabled="isDisabled(index)" :title="isDisabled(index) ? 'Upload previous document first' : ''"
              required />

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded disabled:bg-gray-400
           inline-flex items-center justify-center gap-2" :disabled="forms[index].processing || !forms[index].file">
              <template v-if="item.upload">
                <Replace class="w-4 h-4 relative top-[1px]" />
                <span>Replace</span>
              </template>
              <template v-else>
                <Upload class="w-4 h-4 relative top-[1px]" />
                <span>Upload</span>
              </template>
            </button>
          </form>
        </div>

        <Dialog v-model:open="showPreviewModal">
          <DialogContent
            class="!max-w-[95vw] md:!max-w-[85vw] bg-gray-100/90 dark:bg-gray-900 rounded-xl shadow-xl overflow-hidden p-0 md:p-4 max-h-[90vh]">
            <DialogHeader class="px-4 pt-4 md:px-0">
              <DialogTitle class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100">
                Uploaded Checklist Preview
              </DialogTitle>
            </DialogHeader>

            <div class="flex flex-col md:flex-row overflow-hidden md:h-[75vh] max-h-[70vh] md:max-h-none">
              <div
                class="w-full md:w-1/4 border-b md:border-b-0 md:border-r border-gray-300 dark:border-gray-700 p-3 md:p-4 space-y-2 overflow-y-auto max-h-[30vh] md:max-h-none">
                <h4 class="text-gray-700 dark:text-gray-300 mb-2 text-sm md:text-base">
                  Checklists
                </h4>
                <ul class="space-y-1">
                  <li v-for="(item, i) in props.checklistItems" :key="i">
                    <button v-if="item.upload" @click="openFilePreview(item)" :class="[
                      'block w-full text-left p-2 rounded-lg transition text-sm break-words whitespace-normal',
                      selectedFile?.id === item.upload.id
                        ? 'bg-indigo-700 text-white font-medium shadow-sm'
                        : 'text-gray-800 dark:text-gray-100 hover:bg-indigo-100 dark:hover:bg-gray-700'
                    ]">
                      {{ item.name }}
                    </button>
                  </li>
                </ul>
              </div>

              <div class="flex-1 p-3 md:p-4 overflow-y-auto max-h-[60vh] md:max-h-[80vh]">
                <div v-if="selectedFile">
                  <p class="text-gray-800 dark:text-gray-100 mb-3 text-sm md:text-base break-words whitespace-normal">
                    Viewing: <strong>{{ selectedFile.name }}</strong>
                  </p>

                  <div v-if="isMobile">
                    <template v-if="selectedFile.name.toLowerCase().endsWith('.pdf')">
                      <PdfViewer v-if="!pdfFailed" type="checklist" :url="selectedFile.url"
                        :cooperative-id="props.cooperative.cooperative.id" :program-id="props.cooperative.program?.id"
                        :file-id="selectedFile.id" @error="pdfFailed = true" />

                      <div v-else class="text-center text-gray-600 dark:text-gray-400">
                        <p class="mb-2">PDF preview not supported on this device.</p>
                        <a :href="selectedFile.url" target="_blank"
                          class="text-blue-600 dark:text-blue-400 hover:underline">
                          Open PDF in New Tab
                        </a>
                      </div>
                    </template>
                    <template v-else -if="selectedFile.name.toLowerCase().match(/\.(jpg|jpeg|png|gif|bmp|webp)$/)">
                      <img :src="selectedFile.url" alt="Preview"
                        class="w-full h-auto max-h-[40vh] object-contain rounded-lg border border-gray-300 dark:border-gray-700 mb-4" />
                    </template>
                  </div>

                  <iframe v-else-if="selectedFile.url" :src="selectedFile.url"
                    class="w-full h-[40vh] md:h-[60vh] border rounded-lg"></iframe>

                  <div
                    class="mt-6 bg-white/70 dark:bg-gray-800/70 rounded-lg p-4 border border-gray-300 dark:border-gray-700">
                    <p class="text-gray-800 dark:text-gray-200 mb-3 text-sm md:text-base">
                      Please review the checklist documents carefully and confirm if all
                      the files are correct.
                    </p>

                    <div class="flex items-center mb-4">
                      <input id="consentCheckbox" type="checkbox" v-model="isConsented"
                        class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600" />
                      <label for="consentCheckbox" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        I hereby give consent on behalf of the cooperative.
                      </label>
                    </div>

                    <div class="text-right">
                      <button type="button" @click="saveConsent" :disabled="!isConsented"
                        class="px-4 py-2 rounded-lg shadow-md text-white text-sm md:text-base"
                        :class="isConsented ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed'">
                        Save
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </DialogContent>
        </Dialog>

        <div class="flex justify-end mt-6">
          <button v-if="!allUploadsDone" type="button" @click="handleSaveProgress"
            class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2 rounded-lg shadow-md">
            Save Progress
          </button>

          <button v-else-if="isChecklistRemade" type="button" @click="showPreviewModal = true"
            class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg shadow-md">
            Remake Checklist
          </button>

          <button v-else-if="!props.cooperative.consenter" type="button" @click="showPreviewModal = true"
            class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-lg shadow-md">
            Confirm Checklist
          </button>

          <button v-else disabled class="bg-gray-400 text-white px-5 py-2 rounded-lg shadow-md cursor-not-allowed">
            Checklist Confirmed
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
