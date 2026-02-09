<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'
import SelectSearch from '@/components/SelectSearch.vue'
import { computed } from 'vue'
import { toast } from 'vue-sonner'

const props = defineProps<{
  program: { id: number; name: string }
  cooperatives: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Programs', href: '/programs' },
  { title: props.program.name, href: `/programs/${props.program.id}` },
  { title: 'Add Cooperative', href: '#' }
]

const query = new URLSearchParams(window.location.search)

const form = useForm({
  cooperative_id: query.get('cooperative_id') || '',
  project: '',
})

// Get selected cooperative details
const selectedCooperative = computed(() =>
  props.cooperatives.find(c => String(c.id) === String(form.cooperative_id))
)

//Project field validation
const validateProject = () => {
  form.clearErrors('project')
  if (!form.project) {
    form.setError('project', 'Project name is required.')
  }
}

// Full form validation (when saving)
const validateForm = () => {
  form.clearErrors()
  validateProject()

  return Object.keys(form.errors).length === 0
}

const handleSubmit = () => {
  if (validateForm()) {
    form.post(`/programs/${props.program.id}/cooperatives`, {
      onSuccess: () => {
        toast.success(`${selectedCooperative.value?.name} has been added to ${props.program.name}!`)
      },
      onError: () => {
        toast.error('Failed to add cooperative. Please try again.')
      }
    })
  }
}
</script>

<template>

  <Head :title="`Add Cooperative to ${program.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
      <div class="max-w-7x7 p-6">
        <div class="p-8 ml-6 mr-6 mt-6 bg-gray-200 dark:bg-gray-800 rounded-2xl shadow-md">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
            <Plus class="w-10 h-10 text-blue-600 dark:text-blue-400 flex-shrink-0" /> Add Cooperative
          </h1>

          <!-- FORM -->
          <form class="space-y-5 mt-4" @submit.prevent="handleSubmit">
            <!-- Cooperative -->
            <div v-if="!form.cooperative_id">
              <label class="block mb-1 font-medium">Select Cooperative</label>
              <SelectSearch id="cooperative" v-model="form.cooperative_id" :items="cooperatives"
                placeholder="Search cooperative..." item-label-key="name" item-key-prop="id"
                @select="({ id }) => form.cooperative_id = id" />
              <div v-if="form.errors.cooperative_id" class="text-red-500 text-sm">
                {{ form.errors.cooperative_id }}
              </div>
            </div>

            <!-- Show cooperative name if pre-filled -->
            <div v-else class="flex items-center justify-between">
              <input type="hidden" v-model="form.cooperative_id" />
              <p class="text-gray-700 dark:text-gray-300 font-medium">
                Cooperative:
                <span class="font-semibold">
                  {{ selectedCooperative?.name || 'Unknown' }}
                </span>
              </p>
              <!-- X button to reset -->
              <button type="button" @click="form.cooperative_id = ''"
                class="ml-3 text-red-400 hover:text-red-600 transition">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Project -->
            <div>
              <label class="block mb-1 font-medium">Project</label>
              <input type="text" v-model="form.project" placeholder="Enter Project Name"
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                @blur="validateProject" />
              <div v-if="form.errors.project" class="text-red-500 text-sm mt-1">
                {{ form.errors.project }}
              </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
              <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                Save
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
