<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import SelectSearch from '@/components/SelectSearch.vue';
import { toast } from "vue-sonner"
import { useDrafts } from '@/composables/useDrafts'

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string }
}>()

const form = useForm({
    coop_id: props.cooperative.id,
    position: '',
    contact: '',
    email: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    marital_status: '',
    street: '',
    city: '',
    telephone: '',
    birthdate: '',
    age: '',
    sex: '',
    citizenship: '',
    birthplace: '',
    height: '',
    weight: '',
    religion: '',
    spouse_name: '',
    spouse_occupation: '',
    spouse_age: '',
    father_name: '',
    father_occupation: '',
    father_age: '',
    mother_name: '',
    mother_occupation: '',
    mother_age: '',
    parent_address: '',
    emergency_name: '',
    emergency_contact: '',
    dependent1_name: '',
    dependent1_relationship: '',
    dependent1_birthdate: '',
    dependent1_age: '',
    dependent2_name: '',
    dependent2_relationship: '',
    dependent2_birthdate: '',
    dependent2_age: '',
    elementary_start: '',
    elementary_end: '',
    elementary_name: '',
    elementary_degree: '',
    hs_start: '',
    hs_end: '',
    hs_name: '',
    hs_degree: '',
    college_start: '',
    college_end: '',
    college_name: '',
    college_degree: '',
    course_start: '',
    course_end: '',
    course_name: '',
    course_degree: '',
    others_start: '',
    others_end: '',
    others_name: '',
    others_degree: '',
    company1_start: '',
    company1_end: '',
    company1_name: '',
    company1_position: '',
    company1_rfl: '',
    company2_start: '',
    company2_end: '',
    company2_name: '',
    company2_position: '',
    company2_rfl: '',
    company3_start: '',
    company3_end: '',
    company3_name: '',
    company3_position: '',
    company3_rfl: '',
    company4_start: '',
    company4_end: '',
    company4_name: '',
    company4_position: '',
    company4_rfl: '',
    company5_start: '',
    company5_end: '',
    company5_name: '',
    company5_position: '',
    company5_rfl: '',
    ref1_name: '',
    ref1_company: '',
    ref1_position: '',
    ref1_contact: '',
    ref2_name: '',
    ref2_company: '',
    ref2_position: '',
    ref2_contact: '',
    is_representative: false,
    active_year: new Date().getFullYear(),
    files: [] as File[],
})

const positions = [
    { id: 'Treasurer', name: 'Treasurer' },
    { id: 'Chairman', name: 'Chairman' },
    { id: 'Manager', name: 'Manager' },
    { id: 'Member', name: 'Member' },
]

const { drafts, useDraft, deleteDraft, clearDrafts, post } = useDrafts(form, 'members')

const searchPosition = ref('')
const dropDownPositionOpen = ref(false)

const file = ref<File[]>([])

const allowedFileTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/jpg',
];

function onDrop(e: DragEvent) {
    e.preventDefault();
    const dt = e.dataTransfer;
    if (dt && dt.files.length > 0) {
        const validFiles = validateFiles(Array.from(dt.files));
        file.value = [...file.value, ...validFiles];
        form.files = file.value;
    }
}

function validateFiles(selectedFiles: File[]) {
    return selectedFiles.filter(f => {
        if (!allowedFileTypes.includes(f.type)) {
            toast.error(`File type not allowed: ${f.name}`);
            return false;
        }
        return true;
    });
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
        const validFiles = validateFiles(Array.from(target.files));
        file.value = [...file.value, ...validFiles];
        form.files = file.value;
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
    form.files = file.value.length > 0 ? file.value : [];
    post(`/cooperatives/${props.cooperative.id}/members`, {
        forceFormData: true,
        onError: (errors: Record<string, string | string[]>) => {
            const values = Object.values(errors);
            const messages = values.flatMap(v => Array.isArray(v) ? v.map(String) : [String(v)]);
            if (messages.length) {
                toast.error(messages.join('\n'));
            }
        },
        onSuccess: () => {
            toast.success(`${form.first_name} ${form.last_name} has been added successfully!`)
            if (typeof form.reset === 'function') form.reset()
        }
    });
}

const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7x7 p-6">
                <div
                    class="bg-gray-100/90 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3 pb-6">
                        <Plus class="w-10 h-10 text-blue-600 dark:text-blue-400 flex-shrink-0" /> Add Cooperative Member
                    </h1>
                    <!-- Drafts List -->
                    <div v-if="drafts.length" class="mt-10 border-t border-gray-200 dark:border-gray-700 pt-2 pb-1">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                                Saved Drafts
                            </h2>
                            <button @click="clearDrafts"
                                class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                Clear All
                            </button>
                        </div>

                        <ul class="space-y-2">
                            <li v-for="draft in drafts" :key="draft.id"
                                class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-750 transition">

                                <button @click="useDraft(draft)"
                                    class="text-left flex-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <p class="font-medium">{{ draft.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Saved on {{ draft.savedAt }}
                                    </p>
                                </button>

                                <button @click="deleteDraft(draft.id)"
                                    class="ml-3 px-2 py-1 text-red-500 hover:text-red-700 rounded-md transition">
                                    ✕
                                </button>
                            </li>
                        </ul>
                    </div>
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <div>
                            <label for="position" class="block mb-2">Position</label>
                            <SelectSearch id="position" :items="positions" itemKeyProp="id" itemLabelKey="name"
                                v-model:search="searchPosition" :modelValue="form.position" @select="(val: { id: string; name: string }) => {
                                    form.position = val.id
                                }" :open="dropDownPositionOpen" @update:open="val => dropDownPositionOpen = val"
                                placeholder="Select Position" />
                        </div>
                        <div>
                            <label for="active_year" class="block mb-2">Active Year</label>
                            <input v-model="form.active_year" id="active_year" type="number" min="2000"
                                :max="new Date().getFullYear() + 1"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <label for="first_name" class="block mb-2">First Name</label>
                            <input v-model="form.first_name" id="first_name" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="middle_name" class="block mb-2">Middle Name</label>
                            <input v-model="form.middle_name" id="middle_name" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="last_name" class="block mb-2">Last Name</label>
                            <input v-model="form.last_name" id="last_name" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="marital_status" class="block mb-2">Marital Status</label>
                            <input v-model="form.marital_status" id="marital_status" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="contact" class="block mb-2">Contact Number</label>
                            <input v-model="form.contact" id="contact" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="email" class="block mb-2">Email</label>
                            <input v-model="form.email" id="email" type="email"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="street" class="block mb-2">Street Address</label>
                            <input v-model="form.street" id="street" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="city" class="block mb-2">City Address</label>
                            <input v-model="form.city" id="city" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="telephone" class="block mb-2">Telephone</label>
                            <input v-model="form.telephone" id="telephone" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="birthdate" class="block mb-2">Birthdate</label>
                            <input v-model="form.birthdate" id="birthdate" type="date"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="age" class="block mb-2">Age</label>
                            <input v-model="form.age" id="age" type="number"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="sex" class="block mb-2">Sex</label>
                            <select v-model="form.sex" id="sex"
                                class="w-full rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div>
                            <label for="citizenship" class="block mb-2">Citizenship</label>
                            <select v-model="form.citizenship" id="citizenship"
                                class="w-full rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="Filipino">Filipino</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>

                        <div>
                            <label for="birthplace" class="block mb-2">Birthplace</label>
                            <input v-model="form.birthplace" id="birthplace" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="height" class="block mb-2">Height (cm)</label>
                            <input v-model="form.height" id="height" type="number"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="weight" class="block mb-2">Weight (kg)</label>
                            <input v-model="form.weight" id="weight" type="number"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="religion" class="block mb-2">Religion</label>
                            <input v-model="form.religion" id="religion" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="spouse_name" class="block mb-2">Spouse Name</label>
                            <input v-model="form.spouse_name" id="spouse_name" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="spouse_occupation" class="block mb-2">Spouse Occupation</label>
                            <input v-model="form.spouse_occupation" id="spouse_occupation" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="spouse_age" class="block mb-2">Spouse Age</label>
                            <input v-model="form.spouse_age" id="spouse_age" type="number"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="father_name" class="block mb-2">Father's Name</label>
                            <input v-model="form.father_name" id="father_name" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="father_occupation" class="block mb-2">Father's Occupation</label>
                            <input v-model="form.father_occupation" id="father_occupation" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="father_age" class="block mb-2">Father's Age</label>
                            <input v-model="form.father_age" id="father_age" type="number"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="mother_name" class="block mb-2">Mother's Name</label>
                            <input v-model="form.mother_name" id="mother_name" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="mother_occupation" class="block mb-2">Mother's Occupation</label>
                            <input v-model="form.mother_occupation" id="mother_occupation" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="mother_age" class="block mb-2">Mother's Age</label>
                            <input v-model="form.mother_age" id="mother_age" type="number"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="parent_address" class="block mb-2">Parent Address</label>
                            <input v-model="form.parent_address" id="parent_address" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="emergency_name" class="block mb-2">Emergency Contact Name</label>
                            <input v-model="form.emergency_name" id="emergency_name" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div>
                            <label for="emergency_contact" class="block mb-2">Emergency Contact Number</label>
                            <input v-model="form.emergency_contact" id="emergency_contact" type="text"
                                class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div class="mt-8 border-t pt-6">
                            <h2 class="text-lg font-semibold mb-4">Dependents</h2>

                            <div>
                                <label for="dependent1_name" class="block mb-2">Name</label>
                                <input v-model="form.dependent1_name" id="dependent1_name" type="text"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>

                            <div>
                                <label for="dependent1_relationship" class="block mb-2">Relationship</label>
                                <input v-model="form.dependent1_relationship" id="dependent1_relationship" type="text"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>

                            <div>
                                <label for="dependent1_birthdate" class="block mb-2">Birthdate</label>
                                <input v-model="form.dependent1_birthdate" id="dependent1_birthdate" type="date"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>

                            <div>
                                <label for="dependent1_age" class="block mb-2">Age</label>
                                <input v-model="form.dependent1_age" id="dependent1_age" type="number"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>

                            <div class="mt-4">
                                <label for="dependent2_name" class="block mb-2">Name</label>
                                <input v-model="form.dependent2_name" id="dependent2_name" type="text"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>

                            <div>
                                <label for="dependent2_relationship" class="block mb-2">Relationship</label>
                                <input v-model="form.dependent2_relationship" id="dependent2_relationship" type="text"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>

                            <div>
                                <label for="dependent2_birthdate" class="block mb-2">Birthdate</label>
                                <input v-model="form.dependent2_birthdate" id="dependent2_birthdate" type="date"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>

                            <div>
                                <label for="dependent2_age" class="block mb-2">Age</label>
                                <input v-model="form.dependent2_age" id="dependent2_age" type="number"
                                    class="w-full pl-9 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>

                        <div class="mt-8 border-t pt-6">
                            <h2 class="text-lg font-semibold mb-4">Educational Attainment</h2>

                            <div>
                                <label class="block mb-2">Elementary</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input v-model="form.elementary_name" placeholder="School Name" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.elementary_degree" placeholder="Degree / Level" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.elementary_start" placeholder="Start Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.elementary_end" placeholder="End Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 mt-4">High School</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input v-model="form.hs_name" placeholder="School Name" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.hs_degree" placeholder="Degree / Level" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.hs_start" placeholder="Start Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.hs_end" placeholder="End Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 mt-4">College</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input v-model="form.college_name" placeholder="School Name" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.college_degree" placeholder="Degree" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.college_start" placeholder="Start Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.college_end" placeholder="End Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 mt-4">Courses / Trainings</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input v-model="form.course_name" placeholder="Course Name" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.course_degree" placeholder="Degree / Certificate" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.course_start" placeholder="Start Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.course_end" placeholder="End Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 mt-4">Others</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input v-model="form.others_name" placeholder="Institution Name" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.others_degree" placeholder="Degree / Certificate" type="text"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.others_start" placeholder="Start Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                    <input v-model="form.others_end" placeholder="End Year" type="number"
                                        class="rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                </div>
                            </div>
                        </div>

                        <div class="pt-10">
                            <h2 class="text-xl font-semibold mb-4">Employment Records</h2>

                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <h3 class="font-medium mb-2">Company 1</h3>
                                <input v-model="form.company1_name" type="text" placeholder="Company Name"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company1_position" type="text" placeholder="Position"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company1_rfl" type="text" placeholder="Reason for Leaving"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company1_start" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company1_end" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                            </div>

                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <h3 class="font-medium mb-2">Company 2</h3>
                                <input v-model="form.company2_name" type="text" placeholder="Company Name"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company2_position" type="text" placeholder="Position"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company2_rfl" type="text" placeholder="Reason for Leaving"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company2_start" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company2_end" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                            </div>

                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <h3 class="font-medium mb-2">Company 3</h3>
                                <input v-model="form.company3_name" type="text" placeholder="Company Name"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company3_position" type="text" placeholder="Position"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company3_rfl" type="text" placeholder="Reason for Leaving"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company3_start" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company3_end" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                            </div>

                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <h3 class="font-medium mb-2">Company 4</h3>
                                <input v-model="form.company4_name" type="text" placeholder="Company Name"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company4_position" type="text" placeholder="Position"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company4_rfl" type="text" placeholder="Reason for Leaving"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company4_start" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company4_end" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                            </div>

                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <h3 class="font-medium mb-2">Company 5</h3>
                                <input v-model="form.company5_name" type="text" placeholder="Company Name"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company5_position" type="text" placeholder="Position"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company5_rfl" type="text" placeholder="Reason for Leaving"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company5_start" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.company5_end" type="date"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                            </div>
                        </div>

                        <div class="pt-10">
                            <h2 class="text-xl font-semibold mb-4">Character References</h2>

                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <h3 class="font-medium mb-2">Reference 1</h3>
                                <input v-model="form.ref1_name" type="text" placeholder="Name"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.ref1_company" type="text" placeholder="Company"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.ref1_position" type="text" placeholder="Position"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.ref1_contact" type="text" placeholder="Contact"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                            </div>

                            <div class="border-t border-gray-600 pt-4 mt-4">
                                <h3 class="font-medium mb-2">Reference 2</h3>
                                <input v-model="form.ref2_name" type="text" placeholder="Name"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.ref2_company" type="text" placeholder="Company"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.ref2_position" type="text" placeholder="Position"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                                <input v-model="form.ref2_contact" type="text" placeholder="Contact"
                                    class="w-full mb-2 rounded-xl border bg-white dark:bg-gray-700 border-gray-500 dark:border-gray-700 p-3" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_representative" v-model="form.is_representative" />
                            <label for="is_representative">
                                Is Representative?
                            </label>
                        </div>

                        <div>
                            <label class="block mb-2">Upload File</label>
                            <div class="border-2 border-dashed bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center text-gray-500 dark:text-gray-400 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition"
                                @dragover.prevent @drop="onDrop" @click="openFileModal">
                                <input id="fileInput" type="file" multiple @change="onFileChange" class="hidden"
                                    accept=".pdf,.doc,.docx,.jpg, .jpeg" />
                                <div v-if="file.length" class="mb-2 space-y-2">
                                    <div v-for="(f, index) in file" :key="index"
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
                                <div class="text-xs text-gray-400">Accepted formats: PDF, DOC, DOCX, JPG</div>
                            </div>
                        </div>
                        <div class="pt-6 md:col-span-2 flex justify-center md:justify-end">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>