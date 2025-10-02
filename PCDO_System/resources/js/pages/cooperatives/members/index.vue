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
    years: { active_year: number | string, member_count: number }[]
}>()

const searchQuery = ref('')
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string; info?: string });

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
</script>

<template>
    <Head :title="`Members of ${cooperative.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Top Actions -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-4">
            <!-- Search -->
            <div class="relative w-full md:min-w-[200px] md:max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                <InputField
                    v-model="searchQuery"
                    placeholder="Search members..."
                    class="pl-9 pr-3 w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500"
                />
            </div>

            <!-- Create Button -->
            <Button
                @click="goToCreatePage"
                class="min-w-[120px] bg-indigo-600 text-white hover:bg-indigo-700 px-5 py-2 rounded-lg shadow"
            >
                Create
            </Button>
        </div>

        <!-- Year Filter -->
        <div v-if="years.length" class="flex flex-wrap gap-2 px-6 mb-6">
            <Button
                v-for="year in years"
                :key="year"
                @click="activeYear = year"
                class="px-4 py-2 rounded-lg"
                :class="activeYear === year ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
            >
                {{ year }}
            </Button>
        </div>

        <!-- Grouped Member List -->
        <div class="px-6 pb-6">
            <h2 class="text-xl font-bold mb-4">Member List ({{ activeYear }})</h2>

            <div v-for="(members, position) in groupedMembers" :key="position" class="mb-6">
                <h3 class="text-lg font-semibold text-indigo-700 mb-2">{{ position }}</h3>

                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <Table class="min-w-full">
                        <TableHeader class="bg-gray-100">
                            <TableRow>
                                <TableHead>Full Name</TableHead>
                                <TableHead>Representative</TableHead>
                                <TableHead>Files</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="mem in members"
                                :key="mem.id"
                                class="hover:bg-gray-50"
                            >
                                <TableCell>
                                    {{ mem.first_name }} {{ mem.middle_initial ? mem.middle_initial + '. ' : '' }}{{ mem.last_name }}
                                </TableCell>
                                <TableCell>
                                    <span v-if="mem.is_representative" class="text-green-600">Yes</span>
                                    <span v-else class="text-red-600">No</span>
                                </TableCell>
                                <TableCell>
                                    <ul v-if="mem.files && mem.files.length" class="list-disc list-inside">
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
                                    <span v-else class="text-gray-500">No files</span>
                                </TableCell>
                                <TableCell>
                                    <Button @click="goToViewPage(mem.id)" class="px-3 py-1 rounded-lg bg-blue-500 text-white">
                                        View
                                    </Button>
                                    <Button @click="goToDeletePage(mem.id)" class="px-3 py-1 rounded-lg bg-red-500 text-white">
                                        Delete
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="members.length === 0">
                                <TableCell colspan="4" class="text-center text-gray-500 py-4">
                                    No members for {{ position }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-gray-200">
                    <div
                        v-for="mem in members"
                        :key="mem.id"
                        class="p-4 flex flex-col gap-2"
                    >
                        <h3 class="font-semibold text-lg">{{ mem.first_name }} {{ mem.last_name }}</h3>
                        <p><span class="font-medium">Representative:</span> {{ mem.is_representative ? 'Yes' : 'No' }}</p>
                        <div v-if="mem.files && mem.files.length">
                            <span class="font-medium">Files:</span>
                            <ul>
                                <li v-for="file in mem.files" :key="file.id">
                                    <a :href="file.file_path" target="_blank" class="text-blue-600 hover:underline">
                                        {{ file.file_name }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <p v-else class="text-gray-500">No files</p>
                        <div class="flex gap-2 mt-2">
                            <Button @click="goToViewPage(mem.id)" class="flex-1 bg-blue-500 text-white">View</Button>
                            <Button @click="goToDeletePage(mem.id)" class="flex-1 bg-red-500 text-white">Delete</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <FlashToast v-if="flash.success" type="success" title="Success" :message="flash.success" />
        <FlashToast v-if="flash.error" type="error" title="Error" :message="flash.error" />
        <FlashToast v-if="flash.info" type="info" title="Info" :message="flash.info" />
    </AppLayout>
</template>
