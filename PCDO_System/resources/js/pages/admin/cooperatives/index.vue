<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { BreadcrumbItem } from '@/types'
import { router, usePage } from '@inertiajs/vue3';
import type { Cooperative } from '@/types/cooperatives';
import { usePolling } from '@/composables/usePolling';
import { toast } from "vue-sonner"

const props = defineProps<{
    cooperatives: Cooperative[];
    breadcrumbs?: BreadcrumbItem[];
}>();

const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const deletingId = ref<string | null>(null)
const showFilterModal = ref(false)

const page = usePage();

const statusFilter = ref<'ongoing' | 'inactive' | ''>('')

const filteredCooperatives = computed(() => {
    let cooperatives = props.cooperatives

    // Filter by search query first
    if (searchQuery.value) {
        cooperatives = cooperatives.filter(coop =>
            coop.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            coop.id.toString().includes(searchQuery.value) ||
            (coop.holder?.toLowerCase().includes(searchQuery.value.toLowerCase() ?? false))
        )
    }

    // Filter by status
    if (statusFilter.value) {
        cooperatives = cooperatives.filter(coop => {
            if (statusFilter.value === 'ongoing') return coop.has_ongoing_program
            if (statusFilter.value === 'inactive') return !coop.has_ongoing_program
            return true
        })
    }

    return cooperatives
})

const paginatedCooperatives = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredCooperatives.value.slice(start, start + itemsPerPage.value);
});

const totalPages = computed(() => {
    return Math.ceil(filteredCooperatives.value.length / itemsPerPage.value);
});

const showExportModal = ref(false);
const showImportModal = ref(false);
const openDropdown = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const exportType = ref<'csv' | 'xlsx' | null>(null);
const file = ref<File | null>(null);

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};


function openExportModal() {
    showExportModal.value = true;
}

function closeExportModal() {
    showExportModal.value = false;
    exportType.value = null;
}

function openImportModal() {
    showImportModal.value = true;
}

function closeImportModal() {
    showImportModal.value = false;
    file.value = null;
}

function confirmExport() {
    if (!exportType.value) return
    window.location.href = `/cooperatives/export/${exportType.value}`
    showExportModal.value = false
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    const dt = e.dataTransfer;
    if (dt && dt.files.length > 0) {
        file.value = dt.files[0];
    }
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        file.value = target.files[0];
    } else {
        file.value = null;
    }
}

function clearFile() {
    file.value = null;
    const input = document.getElementById('fileInput') as HTMLInputElement;
    if (input) {
        input.value = '';
    }
}

function confirmImport() {
    if (!file.value) return
    const form = new FormData();
    form.append('file', file.value);

    router.post('/cooperatives/import', form, {
        forceFormData: true,
        onSuccess: () => {
            file.value = null;
            showImportModal.value = false;
            toast.success("Data imported successfully!")
        },
        onError: () => {
            toast.error("Failed to import data. Please check the file format and try again.")
        }
    })
}

function goToCreatePage() {
    router.visit('/cooperatives/create');
}

function goToViewPage(id: string) {
    router.visit(`/cooperatives/${id}`);
}

function goToDeletePage(id: string) {
    deletingId.value = id;
    router.delete(`/cooperatives/${id}`), {
        onFinish: () => {
            deletingId.value = null;
        }
    };
}

function confirmDelete(id: string, name: string) {
    deletingId.value = id
    router.delete(`/cooperatives/${id}`, {
        onFinish: () => {
            deletingId.value = null
        },
        onSuccess: () => {
            toast.success(`${name} has been deleted successfully!`)
        },
        onError: () => {
            toast.error(`Failed to delete ${name}.`)
        }
    })
}

function closeDropdown() {
    openDropdown.value = false
}

function onDocumentClick(e: MouseEvent) {
    if (!dropdownRef.value) return
    if (dropdownRef.value.contains(e.target as Node)) return
    closeDropdown()
}

function onKeyDown(e: KeyboardEvent) {
    if (e.key === 'Escape') closeDropdown()
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick)
    document.addEventListener('keydown', onKeyDown)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick)
    document.removeEventListener('keydown', onKeyDown)
})

usePolling(["cooperatives"], 15000);

</script>

<template>

    <Head title="Cooperatives" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="px-5 md:px-5 pt-5">
                <!-- Top Actions Card -->
                <div
                    class="bg-gray-200 dark:bg-gray-800/80 border ring-1 ring-gray-300 dark:ring-gray-700 border-gray-300 dark:border-gray-700 rounded-xl shadow-m px-6 py-5 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-4">

                        <!-- Left Side: Search + Filter -->
                        <div class="flex flex-col md:flex-row items-stretch gap-3 md:gap-4 flex-1 md:w-auto">
                            <!-- Search Bar -->
                            <div class="relative flex-1 md:w-80">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <InputField v-model="searchQuery" placeholder="Search cooperatives..."
                                    class="pl-9 pr-3 w-full rounded-sm border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200" />
                            </div>

                            <!-- Status Filter (Desktop only) -->
                            <div class="hidden md:block">
                                <select v-model="statusFilter"
                                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All Status</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Right Side: Filter (Mobile) + Actions -->
                        <div class="flex items-center justify-between md:justify-end gap-3 w-full md:w-auto">
                            <button @click="showFilterModal = true" class="md:hidden flex items-center justify-center
                                    w-36 h-10
                                    px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                                    bg-white dark:bg-gray-800
                                    text-gray-700 dark:text-gray-200 text-sm font-medium
                                    shadow-sm hover:bg-gray-100 dark:hover:bg-gray-700
                                    transition-colors duration-150 ease-in-out">

                                <div class="flex items-center justify-center gap-2">
                                    <template v-if="statusFilter === 'ongoing'">
                                        <CircleDashed class="w-4 h-4 text-green-600 animate-spin" />
                                        <span>Ongoing</span>
                                    </template>

                                    <template v-else-if="statusFilter === 'inactive'">
                                        <XCircle class="w-4 h-4 text-red-600" />
                                        <span>Inactive</span>
                                    </template>

                                    <template v-else>
                                        <span>All</span>
                                    </template>
                                </div>
                            </button>

                            <!-- Actions Dropdown -->
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button
                                        class="inline-flex items-center justify-between gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 text-sm font-medium transition w-36">
                                        <span class="flex items-center gap-2">
                                            <Plus class="w-4 h-4" /> Actions
                                        </span>
                                        <ChevronDown class="w-4 h-4" />
                                    </button>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent side="bottom" align="end"
                                    class="w-48 bg-white dark:bg-gray-900 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                                    <DropdownMenuItem asChild>
                                        <button @click="goToCreatePage()"
                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                            <Plus class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" />
                                            Create
                                        </button>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <button @click="openImportModal()"
                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                            <FileUp class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                            Import Data
                                        </button>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <button @click="openExportModal()"
                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                            <FileDown class="w-4 h-4 text-yellow-600 dark:text-yellow-400 shrink-0" />
                                            Export Data
                                        </button>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table / Mobile Cards -->
            <div class="px-5 pb-2">
                <div
                    class="bg-white/90 dark:bg-gray-800/80 shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-gray-700">
                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <Table class="min-w-full border-separate border-spacing-0 text-sm">
                            <TableCaption class="text-lg font-semibold p-4 text-gray-800 dark:text-gray-200">
                                List of Cooperatives
                            </TableCaption>

                            <!-- Table Header -->
                            <TableHeader
                                class="bg-gray-200/90 dark:bg-gray-700/50 border-b border-gray-500 dark:border-gray-500">
                                <TableRow
                                    class="text-left text-gray-800 dark:text-gray-200 uppercase tracking-wide text-xs font-semibold">
                                    <TableHead class="py-3 pl-6">ID</TableHead>
                                    <TableHead class="pl-16 py-3">Name</TableHead>
                                    <TableHead class="pl-16 py-3">Type</TableHead>
                                    <TableHead class="pl-16 py-3">Holder</TableHead>
                                    <TableHead class="pl-16 py-3">Members</TableHead>
                                    <TableHead class="pl-16 py-3">Status</TableHead>
                                    <TableHead class="pl-16 py-3">Total Delinquency / Programs</TableHead>
                                    <TableHead class="pl-16 py-3">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <!-- Table Body -->
                            <TableBody
                                class="divide-y divide-gray-100 dark:divide-gray-700 bg-gray-100/70 dark:bg-gray-900">
                                <TableRow v-for="coop in paginatedCooperatives" :key="coop.id"
                                    class="transition-colors duration-150 hover:bg-gray-200/80 dark:hover:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                    <TableCell class="py-4 pl-6 font-medium text-gray-700 dark:text-gray-400">
                                        {{ coop.id }}
                                    </TableCell>

                                    <TableCell class="pl-16 font-semibold text-gray-900 dark:text-gray-100">
                                        {{ coop.name }}
                                    </TableCell>

                                    <TableCell class="pl-16 text-gray-600 dark:text-gray-300">
                                        {{ coop.type }}
                                    </TableCell>

                                    <TableCell class="pl-16 text-gray-600 dark:text-gray-300">
                                        {{ coop.holder }}
                                    </TableCell>

                                    <TableCell class="pl-16 text-gray-600 dark:text-gray-300">
                                        {{ coop.member_count }}
                                    </TableCell>

                                    <TableCell class="pl-16">
                                        <span v-if="coop.has_ongoing_program"
                                            class="inline-flex items-center gap-1 px-3 py-1 text-m font-semibold rounded-xl bg-green-100 text-green-700 dark:bg-green-700/40 dark:text-green-300">
                                            <CircleDashed
                                                class="w-3 h-3 text-green-600 dark:text-green-300 animate-spin inline-block mr-1" />
                                            Ongoing
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center gap-1 px-3 py-1 text-m font-semibold rounded-xl bg-red-100 text-red-700 dark:bg-red-700/40 dark:text-red-300">
                                            <XCircle class="w-3 h-3 text-red-600 dark:text-red-300 inline-block mr-1" />
                                            Inactive
                                        </span>
                                    </TableCell>

                                    <TableCell class="pl-16 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ coop.delinquent_history_count ?? 0 }} / {{ coop.total_program_count ?? 0 }}

                                        <span v-if="(coop.delinquent_history_count ?? 0) > 0" class="inline-flex items-center gap-1 ml-2 px-3 py-1 text-m font-semibold rounded-xl
               bg-red-100 text-red-700 dark:bg-red-700/40 dark:text-red-300">
                                            <XCircle class="w-3 h-3 text-red-600 dark:text-red-300 inline-block mr-1" />
                                            Delinquent
                                        </span>
                                    </TableCell>


                                    <TableCell class="pl-16 space-x-2">
                                        <Button @click="goToViewPage(coop.id)"
                                            class="px-3 py-2 rounded-lg text-xs font-medium bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-500 transition">
                                            View
                                        </Button>
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button :disabled="deletingId === coop.id"
                                                    class="px-3 py-2 rounded-lg text-xs font-medium bg-red-500 text-white hover:bg-red-600 dark:bg-red-700 dark:hover:bg-red-500 transition">
                                                    <span v-if="deletingId === coop.id">Deleting...</span>
                                                    <span v-else>Delete</span>
                                                </Button>
                                            </AlertDialogTrigger>

                                            <AlertDialogContent @interact-outside="(event: Event) => {
                                                const target = event.target as HTMLElement
                                                if (target?.closest('[data-sonner-toaster]')) {
                                                    event.preventDefault()
                                                }
                                            }">
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Confirm Deletion</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Are you sure you want to delete <strong>{{ coop.name
                                                            }}</strong>?
                                                        This action cannot be undone.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction @click="confirmDelete(coop.id, coop.name)">
                                                        Yes, Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </TableCell>
                                </TableRow>

                                <!-- Empty State -->
                                <TableRow v-if="paginatedCooperatives.length === 0">
                                    <TableCell colspan="7" class="text-center text-gray-500 dark:text-gray-400 py-6">
                                        No Cooperatives found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="coop in paginatedCooperatives" :key="coop.id" class="p-4 flex flex-col gap-2">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">
                                    {{ coop.name }}
                                </h3>
                                <span v-if="coop.has_ongoing_program"
                                    class="flex items-center gap-1 px-2 py-1 text-xs rounded bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100">
                                    <CircleDashed class="w-3 h-3 animate-spin" />
                                    Ongoing
                                </span>
                                <span v-else
                                    class="flex items-center gap-1 px-2 py-1 text-xs rounded bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100">
                                    <XCircle class="w-3 h-3" />
                                    Inactive
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">ID: {{ coop.id }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Type: {{ coop.type }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Holder: {{ coop.holder }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Members: {{ coop.member_count }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 flex items-center flex-wrap gap-2">
                                Total Delinquency / Programs:
                                {{ coop.delinquent_history_count ?? 0 }} / {{ coop.total_program_count ?? 0 }}

                                <span v-if="(coop.delinquent_history_count ?? 0) > 0" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-lg
               bg-red-100 text-red-700 dark:bg-red-700/40 dark:text-red-300">
                                    <XCircle class="w-3 h-3 text-red-600 dark:text-red-300 inline-block" />
                                    Delinquent
                                </span>
                            </p>
                            <div class="flex justify-end gap-4 mt-4">
                                <Button @click="goToViewPage(coop.id)"
                                    class="w-24 h-9 text-sm rounded-lg bg-blue-500 text-white font-medium
                                        hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-600 transition-all duration-150">
                                    View
                                </Button>

                                <Button @click="goToDeletePage(coop.id)"
                                    class="w-24 h-9 text-sm rounded-lg bg-red-500 text-white font-medium
                                        hover:bg-red-600 dark:bg-red-700 dark:hover:bg-red-600 transition-all duration-150">
                                    Delete
                                </Button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Modal -->
            <div v-if="showExportModal"
                class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 transition duration-300 ease-in-out">
                <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-sm w-full">
                    <h2 class="text-lg text-center font-semibold mb-4">Do you want to export the file as?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 text-center">
                        Choose your preferred <br> file format to export the data.
                    </p>
                    <div class="flex gap-3">
                        <button
                            class="flex-1 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium shadow-sm"
                            @click="exportType = 'csv'; confirmExport()">
                            CSV
                        </button>
                        <button
                            class="flex-1 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium shadow-sm"
                            @click="exportType = 'xlsx'; confirmExport()">
                            Excel
                        </button>
                    </div>
                    <button
                        class="mt-4 w-full px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 font-medium shadow-sm"
                        @click="closeExportModal()">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Import Modal -->
            <div v-if="showImportModal"
                class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 transition duration-300 ease-in-out">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg max-w-md w-full">
                    <h2 class="text-lg text-center font-semibold mb-4">Import from</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 text-center">
                        Choose a file source or drag and drop your file below.
                    </p>

                    <!-- Local Storage-->
                    <div class="flex gap-3 mb-4">
                        <label
                            class="flex-1 cursor-pointer px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-center">
                            Local Storage
                            <input type="file" class="hidden" @change="onFileChange" />
                        </label>
                    </div>
                    <p class="text-sm text-center text-gray-500 mt-2 pb-2">or</p>
                    <!-- Drag & Drop Zone -->
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center text-gray-500 dark:text-gray-400 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition"
                        @dragover.prevent @drop="onDrop">
                        Drag & Drop File Here
                    </div>
                    <div class="mt-3">
                        <input id="fileInput" type="file" @change="onFileChange" class="w-full" />
                    </div>
                    <div v-if="file"
                        class="mt-2 flex items-center justify-between bg-gray-100 dark:bg-gray-700 p-2 rounded">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ file.name }}</span>
                        <button @click="clearFile" class="text-red-500 hover:text-red-700">&times;</button>
                    </div>
                    <p class="text-sm text-gray-500 text-center">
                        Supported formats: <span class="font-medium text-gray-700 dark:text-gray-300">.csv, .xlsx</span>
                    </p>
                    <div class="flex gap-3 mt-6">
                        <button
                            class="flex-1 px-4 py-2 rounded-lg bg-green-500 text-white font-medium hover:bg-green-600"
                            @click="confirmImport">
                            Import
                        </button>
                        <button
                            class="flex-1 px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                            @click="closeImportModal()">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Modal (Mobile only) -->
            <div v-if="showFilterModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 transition">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 w-80 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold mb-4 text-center text-gray-900 dark:text-gray-100">
                        Filter Cooperatives
                    </h2>

                    <div
                        class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-5 border border-gray-200 dark:border-gray-600">
                        <label class="block text-sm mb-3 text-gray-600 dark:text-gray-300 font-medium text-center">
                            Choose Status
                        </label>

                        <div class="grid grid-cols-1 gap-3">
                            <button @click="statusFilter = ''; showFilterModal = false" :class="[
                                'w-full px-4 py-2 rounded-lg text-sm font-medium border transition-all',
                                statusFilter === ''
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]">
                                All Status
                            </button>

                            <button @click="statusFilter = 'ongoing'; showFilterModal = false" :class="[
                                'w-full px-4 py-2 rounded-lg text-sm font-medium border transition-all',
                                statusFilter === 'ongoing'
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]">
                                Ongoing
                            </button>

                            <button @click="statusFilter = 'inactive'; showFilterModal = false" :class="[
                                'w-full px-4 py-2 rounded-lg text-sm font-medium border transition-all',
                                statusFilter === 'inactive'
                                    ? 'bg-red-600 text-white border-red-600'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]">
                                Inactive
                            </button>
                        </div>
                    </div>

                    <button
                        class="w-full mt-3 px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 font-medium shadow-sm"
                        @click="showFilterModal = false">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col md:flex-row justify-between items-center mt-8 px-6 gap-4 pb-5">
                <!-- Showing text -->
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ (currentPage - 1) * itemsPerPage + 1 }} -
                    {{ Math.min(currentPage * itemsPerPage, filteredCooperatives.length) }}
                    of {{ filteredCooperatives.length }} results
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
                            <button
                                v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
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
    </AppLayout>
</template>