<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { BreadcrumbItem } from '@/types'
import { router, usePage } from '@inertiajs/vue3';
import type { Member } from '@/types/cooperatives';
import { toast } from 'vue-sonner';
import PdfViewer from '@/components/PdfViewer.vue';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string, name: string }
    members: Member[],
    years: { active_year: number | string, member_count: number }[]
}>()

const searchQuery = ref('')
const page = usePage();
const showFileModal = ref(false)
const selectedFile = ref<any>(null)
const selectedMember = ref<Member | null>(null)
const pdfFailed = ref(false)

const years = computed(() => props.years.map(y => y.active_year));
const activeYear = ref(
    years.value.length ? years.value[0] : new Date().getFullYear()
);

const filteredMembers = computed(() => {
    let result = props.members;

    if (activeYear.value) {
        result = result.filter(m => m.active_year === activeYear.value);
    }

    if (searchQuery.value) {
        result = result.filter(mem =>
            mem.id.toString().includes(searchQuery.value) ||
            (mem.first_name && mem.first_name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (mem.last_name && mem.last_name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (mem.position && mem.position.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }

    return result;
});

const deletingId = ref<number | null>(null)

const groupedMembers = computed(() => {
    const groups: Record<string, Member[]> = {
        Chairman: [],
        Treasurer: [],
        Manager: [],
        Members: [],
    };

    filteredMembers.value.forEach(m => {
        const pos = (m.position || '').trim().toLowerCase();

        switch (pos) {
            case 'chairman':
                groups.Chairman.push(m);
                break;
            case 'treasurer':
                groups.Treasurer.push(m);
                break;
            case 'manager':
                groups.Manager.push(m);
                break;
            case 'member':
            default:
                groups.Members.push(m);
                break;
        }
    });

    return groups;
});

function goToCreatePage() {
    router.visit(`/cooperatives/${props.cooperative.id}/members/create`);
}

function goToViewPage(id: number) {
    router.visit(`/cooperatives/${props.cooperative.id}/members/${id}`);
}

function goToDeletePage(id: number) {
    router.delete(`/cooperatives/${props.cooperative.id}/members/${id}`);
}

function confirmDelete(id: number, first_name: string, last_name: string) {
    deletingId.value = id

    router.delete(`/cooperatives/${props.cooperative.id}/members/${id}`, {
        onSuccess: () => {
            deletingId.value = null
            toast.success(`${first_name} ${last_name} has been deleted successfully!`)
        },
        onError: () => {
            deletingId.value = null
            toast.error('Failed to delete member. Please try again.')
        }
    })
}

function openFileModal(member: Member, file: any) {
    selectedMember.value = member
    selectedFile.value = file
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

    <Head :title="`Members of ${cooperative.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7x7 p-6">
                <!-- Top Actions -->
                <div
                    class="bg-gray-100 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <!-- Search -->
                        <div class="relative flex-1 md:w-96">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                            <InputField v-model="searchQuery" placeholder="Search cooperatives..."
                                class="pl-9 pr-3 w-full rounded-sm border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200" />
                        </div>

                        <!-- Create Button -->
                        <Button @click="goToCreatePage"
                            class="min-w-[120px] bg-indigo-600 text-white hover:bg-indigo-700 px-5 py-2 rounded-lg shadow">
                            <Plus class="w-4 h-4 text-green-300 dark:text-green-400" />
                            <span>Create</span>
                        </Button>
                    </div>

                    <!-- Year Filter -->
                    <div v-if="years.length" class="flex flex-wrap gap-2 px-6 mt-4">
                        <Button v-for="year in years" :key="year" @click="activeYear = year" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                        border border-gray-300 dark:border-gray-700
                        hover:scale-105 hover:shadow-sm" :class="activeYear === year
                            ? 'bg-indigo-600 text-white shadow-md dark:bg-indigo-500'
                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                            {{ year }}
                        </Button>
                    </div>
                </div>

                <!-- Grouped Member List -->
                <div
                    class="bg-gray-100/90 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
                    <h2 class="text-xl font-bold mb-4">Member List ({{ activeYear }})</h2>

                    <div v-for="(members, position) in groupedMembers" :key="position" class="mb-6">
                        <h3 class="text-lg font-semibold text-indigo-700 mb-2">{{ position }}</h3>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <Table class="min-w-full border-separate border-spacing-0 text-sm">
                                <TableHeader
                                    class="bg-gray-200 dark:bg-gray-700/50 border-b border-gray-500 dark:border-gray-500">
                                    <TableRow>
                                        <TableHead class="py-3 pl-6">Full Name</TableHead>
                                        <TableHead class="pl-16 py-3">Representative</TableHead>
                                        <TableHead class="pl-16 py-3">Files</TableHead>
                                        <TableHead class="pl-16 py-3">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody class="bg-white dark:bg-gray-800">
                                    <TableRow v-for="mem in members" :key="mem.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-600/50">
                                        <TableCell class="pl-16 text-gray-600 dark:text-gray-300">
                                            {{ mem.first_name }} {{ mem.middle_name ? mem.middle_name + '. ' :
                                                '' }}{{ mem.last_name }}
                                        </TableCell>
                                        <TableCell class="pl-16 text-gray-600 dark:text-gray-300">
                                            <span v-if="mem.is_representative" class="text-green-600">Yes</span>
                                            <span v-else class="text-red-600">No</span>
                                        </TableCell>
                                        <TableCell class="pl-16 text-gray-600 dark:text-gray-300">
                                            <ul v-if="mem.files && mem.files.length" class="list-disc list-inside">
                                                <li v-for="file in mem.files" :key="file.id">
                                                    <button @click="openFileModal(mem, file)"
                                                        class="text-blue-600 hover:underline">
                                                        <span class="truncate block max-w-[50px] md:max-w-[120px]"
                                                            title="{{ file.file_name }}">
                                                            {{ file.file_name }}
                                                        </span>
                                                    </button>
                                                </li>
                                            </ul>
                                            <span v-else class="text-gray-500">No files</span>
                                        </TableCell>
                                        <TableCell class="pl-16 text-gray-600 dark:text-gray-300">
                                            <div class="flex gap-2">
                                                <Button @click="goToViewPage(mem.id)"
                                                    class="px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition">
                                                    View
                                                </Button>
                                                <AlertDialog>
                                                    <AlertDialogTrigger as-child>
                                                        <Button :disabled="deletingId === mem.id"
                                                            class="px-3 py-2 rounded-lg text-xs font-medium bg-red-500 text-white hover:bg-red-600 dark:bg-red-700 dark:hover:bg-red-500 transition">
                                                            <span v-if="deletingId === mem.id">Deleting...</span>
                                                            <span v-else>Delete</span>
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>Confirm Deletion</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                Are you sure you want to delete <strong>{{
                                                                    mem.first_name }} {{ mem.last_name }}</strong>?
                                                                This action cannot be undone.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter class="flex justify-end gap-2">
                                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                            <AlertDialogAction
                                                                @click="confirmDelete(mem.id, mem.first_name, mem.last_name)">
                                                                Yes, Delete
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="members.length === 0">
                                        <TableCell colspan="4"
                                            class="text-center text-gray-600 dark:text-gray-300  py-4">
                                            No members for {{ position }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-4">
                            <div v-for="mem in members" :key="mem.id"
                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-4 transition-all duration-200 hover:shadow-md">

                                <!-- Header -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                            {{ mem.first_name }} {{ mem.middle_name ? mem.middle_name : '' }} {{
                                                mem.last_name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ mem.position || 'Member' }}
                                        </p>
                                    </div>

                                    <span class="text-xs font-medium px-2 py-1 rounded-full" :class="mem.is_representative
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                                        {{ mem.is_representative ? 'Representative' : 'Not Representative' }}
                                    </span>
                                </div>

                                <!-- Files -->
                                <div class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">Files</p>

                                    <ul v-if="mem.files && mem.files.length" class="space-y-1">
                                        <li v-for="file in mem.files" :key="file.id">
                                            <button @click="openFileModal(mem, file)"
                                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                                <FileText class="w-4 h-4" />
                                                <span class="truncate block max-w-[200px] md:max-w-[300px]"
                                                    title="{{ file.file_name }}">
                                                    {{ file.file_name }}
                                                </span>
                                            </button>
                                        </li>
                                    </ul>
                                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">No files uploaded</p>
                                </div>

                                <!-- Actions -->
                                <div class="mt-4 flex gap-2">
                                    <Button @click="goToViewPage(mem.id)"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-medium transition">
                                        View
                                    </Button>
                                    <Button @click="goToDeletePage(mem.id)"
                                        class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg text-sm font-medium transition">
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Transition name="fade">
                <div v-if="showFileModal"
                    class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 sm:p-0"
                    @click.self="closeFileModal">

                    <div
                        class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg w-full max-w-3xl max-h-[90vh] overflow-hidden sm:m-0 m-auto">

                        <!-- Header -->
                        <header
                            class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 p-4">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate">
                                {{ selectedFile?.file_name }}
                            </h2>
                            <button @click="closeFileModal"
                                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                ✕
                            </button>
                        </header>

                        <!-- Content -->
                        <div class="p-4 overflow-auto max-h-[80vh] bg-gray-50 dark:bg-gray-800 rounded-b-2xl">

                            <!-- PDF -->
                            <template v-if="selectedFile?.file_type === 'application/pdf'">
                                <iframe v-if="!isMobile"
                                    :src="`/cooperatives/${props.cooperative.id}/members/${selectedMember?.id}/files/${selectedFile.id}/view`"
                                    class="w-full h-[70vh] rounded">
                                </iframe>

                                <template v-else>
                                    <!-- Show PdfViewer first, fallback if it errors -->
                                    <PdfViewer v-if="!pdfFailed" type="member" :cooperative-id="props.cooperative.id"
                                        :member-id="selectedMember?.id" :file-id="selectedFile.id"
                                        :url="`/cooperatives/${props.cooperative.id}/members/${selectedMember?.id}/files/${selectedFile.id}/view`"
                                        @error="pdfFailed = true" />

                                    <div v-else class="text-center text-gray-600 dark:text-gray-400">
                                        <p class="mb-2">PDF preview not supported on mobile.</p>
                                        <a :href="`/cooperatives/${props.cooperative.id}/members/${selectedMember?.id}/files/${selectedFile.id}/view`"
                                            target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"
                                            @click="closeFileModal">
                                            Open PDF
                                        </a>
                                    </div>
                                </template>
                            </template>


                            <!-- Images -->
                            <img v-else-if="selectedFile?.file_type?.startsWith('image/')"
                                :src="`/cooperatives/${props.cooperative.id}/members/${selectedMember?.id}/files/${selectedFile.id}/view`"
                                alt="Preview" class="max-h-[70vh] mx-auto rounded-lg shadow" />

                            <!-- Others -->
                            <div v-else class="text-center text-gray-600 dark:text-gray-400">
                                <p>Preview not available for this file type.</p>
                                <a :href="`/cooperatives/${props.cooperative.id}/members/${selectedMember?.id}/files/${selectedFile.id}/download`"
                                    class="text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                                    Download File
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>