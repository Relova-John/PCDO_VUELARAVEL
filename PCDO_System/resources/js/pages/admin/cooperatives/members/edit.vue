<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { BreadcrumbItem } from '@/types'
import SelectSearch from '@/components/SelectSearch.vue'
import { Member } from '@/types/cooperatives'
import { toast } from "vue-sonner"
import PdfViewer from '@/components/PdfViewer.vue'

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
    middle_name: props.member.middle_name ?? '',
    last_name: props.member.last_name ?? '',
    is_representative: ['1', 1, true, 'true'].includes(props.member.is_representative),
    contact: props.member.contact ?? '',
    email: props.member.email ?? '',
    marital_status: props.member.marital_status ?? '',
    street: props.member.street ?? '',
    city: props.member.city ?? '',
    telephone: props.member.telephone ?? '',
    birthdate: props.member.birthdate ?? '',
    age: props.member.age ?? '',
    sex: props.member.sex ?? '',
    citizenship: props.member.citizenship ?? '',
    birthplace: props.member.birthplace ?? '',
    height: props.member.height ?? '',
    weight: props.member.weight ?? '',
    religion: props.member.religion ?? '',
    spouse_name: props.member.spouse_name ?? '',
    spouse_occupation: props.member.spouse_occupation ?? '',
    spouse_age: props.member.spouse_age ?? '',
    father_name: props.member.father_name ?? '',
    father_occupation: props.member.father_occupation ?? '',
    father_age: props.member.father_age ?? '',
    mother_name: props.member.mother_name ?? '',
    mother_occupation: props.member.mother_occupation ?? '',
    mother_age: props.member.mother_age ?? '',
    parent_address: props.member.parent_address ?? '',
    emergency_name: props.member.emergency_name ?? '',
    emergency_contact: props.member.emergency_contact ?? '',
    dependent1_name: props.member.dependent1_name ?? '',
    dependent1_relationship: props.member.dependent1_relationship ?? '',
    dependent1_birthdate: props.member.dependent1_birthdate ?? '',
    dependent1_age: props.member.dependent1_age ?? '',
    dependent2_name: props.member.dependent2_name ?? '',
    dependent2_relationship: props.member.dependent2_relationship ?? '',
    dependent2_birthdate: props.member.dependent2_birthdate ?? '',
    dependent2_age: props.member.dependent2_age ?? '',
    elementary_name: props.member.elementary_name ?? '',
    elementary_start: props.member.elementary_start ?? '',
    elementary_end: props.member.elementary_end ?? '',
    elementary_degree: props.member.elementary_degree ?? '',
    hs_name: props.member.hs_name ?? '',
    hs_start: props.member.hs_start ?? '',
    hs_end: props.member.hs_end ?? '',
    hs_degree: props.member.hs_degree ?? '',
    college_name: props.member.college_name ?? '',
    college_start: props.member.college_start ?? '',
    college_end: props.member.college_end ?? '',
    college_degree: props.member.college_degree ?? '',
    course_name: props.member.course_name ?? '',
    course_start: props.member.course_start ?? '',
    course_end: props.member.course_end ?? '',
    course_degree: props.member.course_degree ?? '',
    others_name: props.member.others_name ?? '',
    others_start: props.member.others_start ?? '',
    others_end: props.member.others_end ?? '',
    others_degree: props.member.others_degree ?? '',
    company1_name: props.member.company1_name ?? '',
    company1_position: props.member.company1_position ?? '',
    company1_start: props.member.company1_start ?? '',
    company1_end: props.member.company1_end ?? '',
    company1_rfl: props.member.company1_rfl ?? '',
    company2_name: props.member.company2_name ?? '',
    company2_position: props.member.company2_position ?? '',
    company2_start: props.member.company2_start ?? '',
    company2_end: props.member.company2_end ?? '',
    company2_rfl: props.member.company2_rfl ?? '',
    company3_name: props.member.company3_name ?? '',
    company3_position: props.member.company3_position ?? '',
    company3_start: props.member.company3_start ?? '',
    company3_end: props.member.company3_end ?? '',
    company3_rfl: props.member.company3_rfl ?? '',
    company4_name: props.member.company4_name ?? '',
    company4_position: props.member.company4_position ?? '',
    company4_start: props.member.company4_start ?? '',
    company4_end: props.member.company4_end ?? '',
    company4_rfl: props.member.company4_rfl ?? '',
    company5_name: props.member.company5_name ?? '',
    company5_position: props.member.company5_position ?? '',
    company5_start: props.member.company5_start ?? '',
    company5_end: props.member.company5_end ?? '',
    company5_rfl: props.member.company5_rfl ?? '',
    ref1_name: props.member.ref1_name ?? '',
    ref1_company: props.member.ref1_company ?? '',
    ref1_position: props.member.ref1_position ?? '',
    ref1_contact: props.member.ref1_contact ?? '',
    ref2_name: props.member.ref2_name ?? '',
    ref2_company: props.member.ref2_company ?? '',
    ref2_position: props.member.ref2_position ?? '',
    ref2_contact: props.member.ref2_contact ?? '',
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
const selectedFile = ref<any>(null)
const showFileModal = ref(false)
const pdfFailed = ref(false)

const allowedFileTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/jpg',
]

function validateFiles(selectedFiles: File[]) {
    return selectedFiles.filter(f => allowedFileTypes.includes(f.type))
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
    if (input && file.value.length === 0) input.value = ''
}

function selectFileModal() {
    const fileInput = document.getElementById('fileInput')
    if (fileInput) fileInput.click()
}

function openFileModal(file: any) {
    selectedFile.value = file
    showFileModal.value = true
}

function closeFileModal() {
    selectedFile.value = null
    showFileModal.value = false
}

function deleteFile(f: any) {
    router.delete(`/cooperatives/${props.cooperative.id}/members/${props.member.id}/files/${f.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('File deleted successfully!'),
    })
}

function handleSubmit() {
    form.active_year = Number(form.active_year)
    form.is_representative = !!form.is_representative
    form.files = [...file.value]
    form.post(`/cooperatives/${props.cooperative.id}/members/${props.member.id}?_method=PUT`, {
        forceFormData: true,
        onError: errors => {
            const messages = Object.values(errors)
            if (messages.length) toast.error(messages.join('\n'))
        },
        onSuccess: () => toast.success(`${form.first_name} ${form.last_name} has been updated successfully!`)
    })
}

const isMobile = ref(false)

onMounted(() => {
    const uaCheck = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
    const sizeCheck = window.matchMedia('(max-width: 768px)').matches
    isMobile.value = uaCheck || sizeCheck
})
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7x7 p-6">
                <div
                    class="bg-gray-100/60 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
                    <h1 class="text-2xl font-bold mb-6">Edit Cooperative Member</h1>

                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Position -->
                        <div>
                            <label for="position" class="block mb-1">Position</label>
                            <SelectSearch id="position" :items="positions" itemKeyProp="id" itemLabelKey="name"
                                v-model="form.position" v-model:search="searchPosition"
                                v-model:open="dropDownPositionOpen" placeholder="Select Position" />
                        </div>

                        <!-- Active Year -->
                        <div>
                            <label for="active_year" class="block mb-1">Active Year</label>
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
                            <label for="is_representative">Is Representative?</label>
                        </div>

                        <!-- Existing Files -->
                        <Section title="Uploaded Files">
                            <div v-if="member.files?.length" class="space-y-2">
                                <div v-for="file in member.files" :key="file.id"
                                    class="flex justify-between items-center p-3 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                    @click="openFileModal(file)">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200"> <span
                                                class="truncate block max-w-[140px] md:max-w-[140px]"
                                                title="{{ file.file_name }}">
                                                {{ file.file_name }}
                                            </span></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ file.file_type }}</p>
                                    </div>

                                    <div class="flex gap-4" @click.stop>
                                        <a :href="`/cooperatives/${props.cooperative.id}/members/${member.id}/files/${file.id}/download`"
                                            class="text-blue-600 dark:text-blue-400 hover:underline text-sm">Download</a>

                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <button
                                                    class="text-red-600 dark:text-red-400 hover:underline text-sm">Delete</button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Delete Upload?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This will permanently remove <strong>{{ file.file_name
                                                        }}</strong>. This action cannot be
                                                        undone.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction @click="deleteFile(file)">Confirm Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-500 italic">No files uploaded</p>
                        </Section>
                        <!-- Upload new files -->
                        <div>
                            <label class="block mb-1">Upload New Files</label>
                            <div class="border-2 border-dashed bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center text-gray-500 dark:text-gray-400 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition"
                                @dragover.prevent @drop="onDrop" @click="selectFileModal">
                                <input id="fileInput" type="file" multiple @change="onFileChange" class="hidden"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg" />
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

                        <!-- Submit -->
                        <div class="pt-6 md:col-span-2 flex justify-center md:justify-end">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- File Modal -->
        <Transition name="fade">
            <div v-if="showFileModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 sm:p-0"
                @click.self="closeFileModal">
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg w-full max-w-3xl max-h-[90vh] overflow-hidden
             sm:rounded-xl sm:m-0 m-auto">
                    <header class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 p-4">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate">
                            {{ selectedFile?.file_name }}
                        </h2>
                        <button @click="closeFileModal"
                            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                            ✕
                        </button>
                    </header>

                    <div
                        class="p-4 overflow-auto max-h-[80vh] bg-gray-50 dark:bg-gray-800 rounded-b-2xl sm:rounded-b-xl">
                        <template v-if="selectedFile?.file_type === 'application/pdf'">
                            <iframe v-if="!isMobile"
                                :src="`/cooperatives/${props.cooperative.id}/members/${member.id}/files/${selectedFile.id}/view`"
                                class="w-full h-[70vh]"></iframe>

                            <!-- Mobile PDF fallback -->
                            <template v-else>
                                <!-- Show PdfViewer first, fallback if it errors -->
                                <PdfViewer v-if="!pdfFailed" type="member" :cooperative-id="props.cooperative.id"
                                    :member-id="member?.id" :file-id="selectedFile.id"
                                    :url="`/cooperatives/${props.cooperative.id}/members/${member?.id}/files/${selectedFile.id}/view`"
                                    @error="pdfFailed = true" />

                                <div v-else class="text-center text-gray-600 dark:text-gray-400">
                                    <p class="mb-2">PDF preview not supported on mobile.</p>
                                    <a :href="`/cooperatives/${props.cooperative.id}/members/${member.id}/files/${selectedFile.id}/view`"
                                        target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"
                                        @click="closeFileModal">
                                        Open PDF
                                    </a>
                                </div>
                            </template>
                        </template>

                        <!-- Image -->
                        <img v-else-if="selectedFile?.file_type?.startsWith('image/')"
                            :src="`/cooperatives/${props.cooperative.id}/members/${member.id}/files/${selectedFile.id}/view`"
                            alt="Preview" class="max-h-[70vh] mx-auto rounded-lg shadow" />

                        <!-- Other Files -->
                        <div v-else class="text-center text-gray-600 dark:text-gray-400">
                            <p>Preview not available for this file type.</p>
                            <a :href="`/cooperatives/${props.cooperative.id}/members/${member.id}/files/${selectedFile.id}/download`"
                                class="text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                                Download File
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>