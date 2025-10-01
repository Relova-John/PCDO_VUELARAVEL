<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import SelectSearch from '@/components/SelectSearch.vue';
import FlashToast from '@/components/FlashToast.vue'

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string }
}>()

const form = useForm({
    coop_id: props.cooperative.id,
    position: '',
    first_name: '',
    middle_initial: '',
    last_name: '',
    is_representative: false,
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

function setMiddleInitial(initial: string) {
    if (initial.length > 1) {
        initial = initial.charAt(0);
    }
    return initial;
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    const dt = e.dataTransfer;
    if (dt && dt.files.length > 0) {
        file.value = [...file.value, ...Array.from(dt.files)]
    }
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
        file.value = [...file.value, ...Array.from(target.files)]
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
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.click();
    }
}

function handleSubmit() {
    form.post(`/cooperatives/${props.cooperative.id}/members`, {
        onError: (errors) => {
            const messages = Object.values(errors);

            if (messages.length) {
                FlashToast.show({
                    type: 'error',
                    message: messages[0]
                });
            }
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
                <h1 class="text-2xl font-bold mb-6">Add Cooperative Member</h1>

                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div>
                        <label for="position" class="block mb-1">Position</label>
                        <SelectSearch
                            id="position"
                            :items="positions"
                            itemKeyProp="id"
                            itemLabelKey="name"
                            v-model:search="searchPosition"
                            :modelValue="form.position"
                            @select="val => form.position = val.id"
                            :open="dropDownPositionOpen"
                            @update:open="val => dropDownPositionOpen = val"
                            placeholder="Select Position"
                        />
                    </div>
                    <div v-if="['Chairman', 'Secretary', 'Manager'].includes(form.position)">
                        <label for="first_name" class="block mb-1">First Name</label>
                        <input v-model="form.first_name" id="first_name" type="text" class="w-full border rounded p-2" />
                    </div>

                    <div v-if="['Chairman', 'Secretary', 'Manager'].includes(form.position)">
                        <label for="middle_initial" class="block mb-1">Middle Initial</label>
                        <input v-model="form.middle_initial" id="middle_initial" type="text" maxlength="1"
                            @input="form.middle_initial = setMiddleInitial(form.middle_initial)"
                            class="w-full border rounded p-2" />
                        <div v-if="form.middle_initial.length > 1" class="text-red-500 text-sm mt-1">
                            Middle initial must be a single character.
                        </div>
                    </div>

                    <div v-if="['Chairman', 'Secretary', 'Manager'].includes(form.position)">
                        <label for="last_name" class="block mb-1">Last Name</label>
                        <input v-model="form.last_name" id="last_name" type="text" class="w-full border rounded p-2" />
                    </div>

                    <div v-if="['Chairman', 'Secretary', 'Manager', 'Member'].includes(form.position)" class="flex items-center gap-2">
                        <input type="checkbox" id="is_representative" v-model="form.is_representative" />
                        <label for="is_representative">Is Representative?</label>
                    </div>

                    <div v-if="['Chairman', 'Secretary', 'Manager', 'Member'].includes(form.position)">
                        <label class="block mb-1">Upload File</label>
                        <div
                            class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer"
                            @dragover.prevent
                            @drop="onDrop"
                            @click="openFileModal"
                        >
                            <input
                                id="fileInput"
                                type="file"
                                multiple
                                @change="onFileChange"
                                class="hidden"
                                accept=".pdf,.doc,.docx,.jpg,.png"
                            />
                            <div v-if="file.length" class="mb-2 space-y-2">
                            <div v-for="(f, index) in file" :key="index" class="flex items-center justify-between bg-gray-100 p-2 rounded">
                                <p class="text-gray-700 text-sm truncate">{{ f.name }}</p>
                                <button
                                type="button"
                                @click.stop="clearFile(index)"
                                class="text-red-500 underline text-xs"
                                >
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
                    <div>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
