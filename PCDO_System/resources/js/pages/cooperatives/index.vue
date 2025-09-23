<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { BreadcrumbItem } from '@/types'
import { router } from '@inertiajs/vue3';
import type { Cooperative } from '@/types/cooperatives';

const props = defineProps<{
    cooperatives: Cooperative[];
    breadcrumbs?: BreadcrumbItem[];
}>();

const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(10)

const filteredCooperatives = computed(() => {
    if (!searchQuery.value) {
        return props.cooperatives;
    }
    return props.cooperatives.filter(coop =>
        coop.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        coop.id.toString().includes(searchQuery.value) ||
        (coop.holder?.toLowerCase().includes(searchQuery.value.toLowerCase() ?? false))
    );
});

const paginatedCooperatives = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredCooperatives.value.slice(start, start + itemsPerPage.value);
});

const totalPages = computed(() => {
    return Math.ceil(filteredCooperatives.value.length / itemsPerPage.value);
});

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

function goToCreatePage() {
    router.visit('/cooperatives/create');
}

function goToViewPage(id: string) {
    router.visit(`/cooperatives/${id}`);
}

function goToEditPage(id: string) {
    router.visit(`/cooperatives/${id}/edit`);
}

function goToDeletePage(id: string) {
    router.visit(`/cooperatives/${id}/delete`);
}
</script>

<template>
    <Head :title="`| ${$page.component}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Top Actions -->
        <div class="flex flex-col md:flex-row md:items-center md:item-stretch md:justify-between gap-4 p-4">
            <!-- Search -->
            <div class="relative w-full md:min-w-[200px] md:max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                <InputField
                v-model="searchQuery"
                placeholder="Search cooperatives..."
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

                <Link href="/cooperatives/import" class="inline-block">
                    <Button
                    class="min-w-[120px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg shadow text-center"
                    >
                    Import Data
                    </Button>
                </Link>

                <Link href="/cooperatives/export" class="inline-block">
                    <Button
                    class="min-w-[120px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg shadow text-center"
                    >
                    Export Data
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Table / Mobile Cards -->
        <div class="px-6 pb-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <Table class="min-w-full">
                        <TableCaption class="text-lg font-semibold p-4">List of Cooperatives</TableCaption>
                        <TableHeader class="bg-gray-100 dark:bg-gray-700">
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Holder</TableHead>
                                <TableHead>Member Count</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="coop in paginatedCooperatives"
                                :key="coop.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <TableCell class="font-medium text-gray-600">{{ coop.id }}</TableCell>
                                <TableCell class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ coop.name }}
                                </TableCell>
                                <TableCell>{{ coop.type }}</TableCell>
                                <TableCell>{{ coop.holder }}</TableCell>
                                <TableCell>{{ coop.member_count }}</TableCell>
                                <TableCell>
                                <span
                                    v-if="coop.has_ongoing_program"
                                    class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100"
                                >
                                    Ongoing
                                </span>
                                <span
                                    v-else
                                    class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100"
                                >
                                    Inactive
                                </span>
                                </TableCell>
                                <TableCell class="text-right space-x-2">
                                <Link
                                    :href="`/cooperatives/${coop.id}/show`"
                                    class="px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500"
                                >
                                    View
                                </Link>
                                <Link
                                    :href="`/cooperatives/${coop.id}/edit`"
                                    class="px-3 py-1 rounded-lg bg-green-500 text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500"
                                >
                                    Edit
                                </Link>
                                <Link
                                    :href="`/cooperatives/${coop.id}/delete`"
                                    class="px-3 py-1 rounded-lg bg-red-500 text-white hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500"
                                >
                                    Delete
                                </Link>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="paginatedCooperatives.length === 0">
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
                        v-for="coop in paginatedCooperatives"
                        :key="coop.id"
                        class="p-4 flex flex-col gap-2"
                    >
                        <div class="flex justify-between items-center">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">
                                {{ coop.name }}
                            </h3>
                            <span
                                v-if="coop.has_ongoing_program"
                                class="px-2 py-1 text-xs rounded bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100"
                            >
                                Ongoing
                            </span>
                            <span
                                v-else
                                class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100"
                            >
                                Inactive
                            </span>
                        </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">ID: {{ coop.id }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Type: {{ coop.type }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Holder: {{ coop.holder }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                            Members: {{ coop.member_count }}
                            </p>
                        <div class="flex gap-2 mt-2">
                            <Button
                                @click="goToViewPage(coop.id)"
                                class="flex-1 text-center px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600"
                            >
                                View
                        </Button>
                            <Link
                                @click="goToEditPage(coop.id)"
                                class="flex-1 text-center px-3 py-1 rounded-lg bg-green-500 text-white hover:bg-green-600"
                            >
                                Edit
                            </Link>
                            <Link
                                @click="goToDeletePage(coop.id)"
                                class="flex-1 text-center px-3 py-1 rounded-lg bg-red-500 text-white hover:bg-red-600"
                            >
                                Delete
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4">
            <span class="text-sm text-gray-600 dark:text-gray-300">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }}
            -
            {{ Math.min(currentPage * itemsPerPage, filteredCooperatives.length) }}
            of {{ filteredCooperatives.length }}
            </span>
            <div class="flex items-center gap-2">
                <Button
                    :disabled="currentPage === 1"
                    @click="goToPage(currentPage - 1)"
                    class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Previous
                </Button>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    Page {{ currentPage }} of {{ totalPages }}
                </span>
                <Button
                    :disabled="currentPage === totalPages"
                    @click="goToPage(currentPage + 1)"
                    class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Next
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
