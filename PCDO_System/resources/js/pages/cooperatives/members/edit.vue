<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import SelectSearch from '@/components/SelectSearch.vue'
import FlashToast from '@/components/FlashToast.vue'
import { Member, SingleChar } from '@/types/cooperatives'

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string }
    member: Member
}>()

const form = useForm({
    coop_id: props.cooperative.id,
    position: props.member.position ?? '',
    active_year: props.member.active_year ?? new Date().getFullYear(),
    first_name: props.member.first_name ?? '',
    middle_initial: props.member.middle_initial ?? '',
    last_name: props.member.last_name ?? '',
    suffix: props.member.suffix ?? '',
    is_representative: props.member.is_representative ?? false,
    files: [] as File[],
})

const positions = [
    { id: 'Treasurer', name: 'Treasurer' },
    { id: 'Chairman', name: 'Chairman' },
    { id: 'Manager', name: 'Manager' },
    { id: 'Member', name: 'Member' },
]

const searchPosition = ref('')
const dropDownPositionOpen = ref(false)
const file = ref<File[]>([])
const toastRef = ref<InstanceType<typeof FlashToast>>()

const allowedFileTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
    'image/jpg',
]

function setMiddleInitial(initial: string): SingleChar {
    return (initial.length > 0 ? initial.charAt(0) : ' ') as SingleChar
}

function validateFiles(selectedFiles: File[]) {
    return selectedFiles.filter(f => {
        if (!allowedFileTypes.includes(f.type)) {
            toastRef.value?.showToast({
                type: 'error',
                message: `File type not allowed: ${f.name}`,
            })
            return false
        }
        return true
    })
}

function onDrop(e: DragEvent) {
    e.preventDefault()
    const dt = e.dataTransfer
    if (dt && dt.files.length > 0) {
        const validFiles = validateFiles(Array.from(dt.files))
        file.value = [...file.value, ...validFiles]
        form.files = file.value
    }
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
        const validFiles = validateFiles(Array.from(target.files))
        file.value = [...file.value, ...validFiles]
        form.files = file.value
    }
}

function clearFile(index: number) {
    file.value.splice(index, 1)
    const input = document.getElementById('fileInput') as HTMLInputElement
    if (input && file.value.length === 0) {
        input.value = ''
    }
}

function openFileModal() {
    const fileInput = document.getElementById('fileInput')
    if (fileInput) {
        fileInput.click()
    }
}

function downloadFile(f: any) {
    window.open(
        `/cooperatives/${props.cooperative.id}/members/${props.member.id}/files/${f.id}/download`,
        '_blank'
    )
}

function deleteFile(f: any) {
    if (!confirm(`Delete file "${f.file_name}"?`)) return
    router.delete(
        `/cooperatives/${props.cooperative.id}/members/${props.member.id}/files/${f.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                toastRef.value?.showToast({
                    type: 'success',
                    message: 'File deleted successfully',
                })
            },
        }
    )
}

function handleSubmit() {
    form.active_year = Number(form.active_year)
    form.is_representative = !!form.is_representative
    form.files = [...file.value]
    console.log(form.data())
    form.post(`/cooperatives/${props.cooperative.id}/members/${props.member.id}?_method=PUT`, {
        forceFormData: true,
        onError: errors => {
            const messages = Object.values(errors)
            if (messages.length) {
                toastRef.value?.showToast({
                    type: 'error',
                    message: messages.join('\n'),
                })
            }
        },
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">        
        <div class="max-w-4xl mx-auto p-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
                <h1 class="text-2xl font-bold mb-6">Edit Cooperative Member</h1>

                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Position -->
                    <div>
                        <label for="position" class="block mb-1">Position</label>
                        <SelectSearch
                            id="position"
                            :items="positions"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model="form.position"
                            v-model:search="searchPosition"
                            v-model:open="dropDownPositionOpen"
                            placeholder="Select Position"
                        />
                    </div>

                    <!-- Active Year -->
                    <div>
                        <label for="active_year" class="block mb-1">Active Year</label>
                        <input v-model="form.active_year" id="active_year" type="number" min="2000"
                            :max="new Date().getFullYear() + 1" class="w-full border rounded p-2" />
                    </div>

                    <!-- Names (conditional) -->
                    <div v-if="['Chairman','Treasurer','Manager'].includes(form.position)">
                        <label for="first_name" class="block mb-1">First Name</label>
                        <input v-model="form.first_name" id="first_name" type="text"
                            class="w-full border rounded p-2" />
                    </div>

                    <div v-if="['Chairman','Treasurer','Manager'].includes(form.position)">
                        <label for="middle_initial" class="block mb-1">Middle Initial</label>
                        <input v-model="form.middle_initial" id="middle_initial" type="text" maxlength="1"
                            @input="form.middle_initial = setMiddleInitial(($event.target as HTMLInputElement).value)"
                            class="w-full border rounded p-2" />
                    </div>

                    <div v-if="['Chairman','Treasurer','Manager'].includes(form.position)">
                        <label for="last_name" class="block mb-1">Last Name</label>
                        <input v-model="form.last_name" id="last_name" type="text"
                            class="w-full border rounded p-2" />
                    </div>

                    <div v-if="['Chairman','Treasurer','Manager'].includes(form.position)">
                        <label for="suffix" class="block mb-1">Suffix</label>
                        <input v-model="form.suffix" id="suffix" type="text"
                            class="w-full border rounded p-2" />
                    </div>

                    <div v-if="['Chairman','Treasurer','Manager'].includes(form.position)" class="flex items-center gap-2">
                        <input type="checkbox" id="is_representative" v-model="form.is_representative" />
                        <label for="is_representative">Is Representative?</label>
                    </div>

                    <!-- Existing Files -->
                    <div v-if="props.member.files.length">
                        <label class="block mb-2">Existing Files</label>
                        <ul class="space-y-2">
                            <li v-for="f in props.member.files" :key="f.id"
                                class="flex justify-between items-center bg-gray-100 p-2 rounded">
                                <span>{{ f.file_name }}</span>
                                <div class="flex gap-3">
                                    <button type="button" class="text-blue-600 underline text-sm"
                                        @click="downloadFile(f)">Download</button>
                                    <button type="button" class="text-red-600 underline text-sm"
                                        @click="deleteFile(f)">Delete</button>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Upload new files -->
                    <div>
                        <label class="block mb-1">Upload New Files</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer"
                            @dragover.prevent @drop="onDrop" @click="openFileModal">
                            <input id="fileInput" type="file" multiple @change="onFileChange" class="hidden"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                            <div v-if="file.length" class="mb-2 space-y-2">
                                <div v-for="(f,index) in file" :key="index"
                                    class="flex items-center justify-between bg-gray-100 p-2 rounded">
                                    <p class="text-gray-700 text-sm truncate">{{ f.name }}</p>
                                    <button type="button" @click.stop="clearFile(index)"
                                        class="text-red-500 underline text-xs">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <div v-else class="mb-2">
                                <p class="text-gray-500">Drag & drop files here, or click to select</p>
                            </div>
                            <div class="text-xs text-gray-400">Accepted formats: PDF, DOC, DOCX, JPG, PNG</div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Update</button>
                    </div>
                </form>
            </div>
            <FlashToast ref="toastRef" />
        </div>
    </AppLayout>
</template>
