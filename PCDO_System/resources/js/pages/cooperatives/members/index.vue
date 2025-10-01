<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { BreadcrumbItem } from '@/types'
import { router, usePage } from '@inertiajs/vue3';
import type { Member } from '@/types/cooperatives';
import FlashToast from '@/components/FlashToast.vue';
import TableHead from '@/components/ui/table/TableHead.vue';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    cooperative: { id: string, name: string }
    members: Member[],
}>()

const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(10)

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string; info?: string });

const filteredMembers = computed(() => {
    if (!searchQuery.value) return props.members;
    return props.members.filter(mem =>
        mem.id.toString().includes(searchQuery.value) ||
        (mem.first_name && mem.first_name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
        (mem.last_name && mem.last_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const paginatedMembers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredMembers.value.slice(start, start + itemsPerPage.value);
});

const totalPages = computed(() => Math.ceil(filteredMembers.value.length / itemsPerPage.value));

function goToPage(page: number) {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
}

function goToCreatePage() {
    router.visit(`/cooperatives/${props.cooperative.id}/members/create`);
}

function goToViewPage(id: number) {
    router.visit(`/cooperatives/${props.cooperative.id}/members/${id}`);
}

function goToDeletePage(id: number) {
    router.delete(`/cooperatives/${props.cooperative.id}/members/${id}`);
}
</script>

<template>
    <Head :title="`Members of ${cooperative.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
                <!-- Top Actions -->
        <div class="flex flex-col md:flex-row md:items-center md:item-stretch md:justify-between gap-4 p-4">
            <!-- Search -->
            <div class="relative w-full md:min-w-[200px] md:max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                <InputField
                v-model="searchQuery"
                placeholder="Search members..."
                class="pl-9 pr-3 w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500"
                />
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-center gap-3">
                <Button
                    @click="goToCreatePage"
                    class="min-w-[120px] bg-indigo-600 text-white hover:bg-indigo-700 px-5 py-2 rounded-lg shadow text-center"
                >
                    Create
                </Button>
            </div>
        </div>

        <!-- Table / Mobile Cards -->
        <div class="px-6 pb-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <Table class="min-w-full">
                        <TableCaption class="text-lg font-semibold p-4">List of Cooperatives Members</TableCaption>
                        <TableHeader class="bg-gray-100 dark:bg-gray-700">
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Full Name</TableHead>
                                <TableHead>Position</TableHead>
                                <TableHead>Representative</TableHead>
                                <TableHead>Files</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="mem in paginatedMembers"
                                :key="mem.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <TableCell class="font-medium text-gray-600">{{ mem.id }}</TableCell>
                                <TableCell class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ mem.first_name }} {{ mem.middle_initial ? mem.middle_initial + '. ' : '' }}{{ mem.last_name }}
                                </TableCell>
                                <TableCell>{{ mem.position }}</TableCell>
                                <TableCell>
                                    <span v-if="mem.is_representative" class="text-green-600">Yes</span>
                                    <span v-else class="text-red-600">No</span>
                                </TableCell>
                                <TableCell>
                                    <ul class="list-disc list-inside">
                                        <li v-for="file in mem.files" :key="file.id">
                                            <a
                                                :href="file.file_path"
                                                target="_blank"
                                                class="text-blue-600 hover:underline"
                                            >
                                                {{ file.file_name }}
                                            </a>
                                        </li>
                                    </ul>
                                <Button
                                    @click="goToViewPage(mem.id)"
                                    class="px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500"
                                >
                                    View
                                </Button>
                                <Button
                                    @click="goToDeletePage(mem.id)"
                                    class="px-3 py-1 rounded-lg bg-red-500 text-white hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500"
                                >
                                    Delete
                                </Button>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="paginatedMembers.length === 0">
                                <TableCell colspan="7" class="text-center text-gray-500 py-6">
                                No Cooperatives found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="mem in paginatedMembers"
                        :key="mem.id"
                        class="p-4 flex flex-col gap-2"
                    >
                        <div class="flex justify-between items-center">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">
                                {{ mem.first_name }} {{ mem.middle_initial ? mem.middle_initial + '. ' : '' }}{{ mem.last_name }}
                            </h3>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <p><span class="font-medium">Position:</span> {{ mem.position }}</p>
                            <p>
                                <span class="font-medium">Representative:</span>
                                <span v-if="mem.is_representative" class="text-green-600">Yes</span>
                                <span v-else class="text-red-600">No</span>
                            </p>
                            <div v-if="mem.files.length">
                                <span class="font-medium">Files:</span>
                                <ul class="list-disc list-inside">
                                    <li v-for="file in mem.files" :key="file.id">
                                        <a
                                            :href="file.file_path"
                                            target="_blank"
                                            class="text-blue-600 hover:underline"
                                        >
                                            {{ file.file_name }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <Button
                                @click="goToViewPage(mem.id)"
                                class="flex-1 text-center px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600"
                            >
                                View
                            </Button>
                            <Button
                                @click="goToDeletePage(mem.id)"
                                class="flex-1 text-center px-3 py-1 rounded-lg bg-red-500 text-white hover:bg-red-600"
                            >
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-between items-center">
            <span class="text-sm">Page {{ currentPage }} of {{ totalPages }}</span>
            <div>
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">Prev</button>
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">Next</button>
            </div>
        </div>

        <FlashToast
            v-if="flash.success"
            type="success"
            title="Success"
            :message="flash.success"
        />
        <FlashToast
            v-if="flash.error"
            type="error"
            title="Error"
            :message="flash.error"
        />
        <FlashToast
            v-if="flash.info"
            type="info"
            title="Info"
            :message="flash.info"
        />
    </AppLayout>
</template>
