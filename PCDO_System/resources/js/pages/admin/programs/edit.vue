<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { BreadcrumbItem } from '@/types'
import { ref, computed } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3'
import type { Program, Checklists } from 'cooperatives'
import { toast } from "vue-sonner"

const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => authUser.value?.roles?.map((r: any) => r.name) || []);

const props = defineProps<{
    program: Program,
    checklist: Checklists[],
    programChecklists: number[],
    breadcrumbs: BreadcrumbItem[]
}>()

const program = props.program
const submitting = ref(false);

const selectedChecklists = ref<number[]>(props.programChecklists || []);
const newChecklistName = ref('');
const editingChecklistId = ref<number | null>(null);
const editingChecklistName = ref('');

const form = useForm({
    name: program.name || '',
    details: program.details || '',
    term_months: program.term_months ? Number(program.term_months) : 0,
    grace_period: program.grace_period ? Number(program.grace_period) : 0,
    min_amount: program.min_amount ? Number(program.min_amount) : 0,
    max_amount: program.max_amount ? Number(program.max_amount) : 0,
    penalty: program.penalty ? Number(program.penalty) : 0,
    selected_checklists: selectedChecklists.value,
});

function handleSubmit() {
    if (!userRoles.value.includes('admin') && !userRoles.value.includes('superadmin')) {
        toast.error('You do not have permission to perform this action.');
        return;
    }

    const requiredFields = ['name', 'details', 'term_months', 'grace_period', 'min_amount', 'max_amount', 'penalty'];

    const emptyFields = requiredFields.filter(field => {
        return (
            form[field as keyof typeof form] === '' ||
            form[field as keyof typeof form] === null
        );
    });

    if (emptyFields.length) {
        toast.error(
            `Please fill in all required fields:\n${emptyFields
                .map((field, i) => `${i + 1}. ${field}`)
                .join('\n')}`
        )
        return
    }

    submitting.value = true;
    form.selected_checklists = selectedChecklists.value;

    form.put(`/admin/programs/${program.id}`, {
        preserveState: true,
        onError: (errors) => {
            submitting.value = false;
            const messages = Object.values(errors);
            if (messages.length) toast.error(messages.join('\n'));
        },
        onSuccess: () => {
            toast.success(`${form.name} updated successfully!`);
            submitting.value = false;
        },
    });
}

function addChecklist() {
    const name = newChecklistName.value.trim();
    if (!name) {
        toast.error('Checklist name is required');
        return;
    }

    router.post('/checklists', { name }, {
        preserveState: true,
        onSuccess: () => {
            newChecklistName.value = '';
            router.reload({ only: ['checklist'] });
            toast.success('Checklist "' + name + '" has been added.');
        }
    });
}

function startEdit(item: Checklists) {
    editingChecklistId.value = item.id;
    editingChecklistName.value = item.name;
}

function cancelEdit() {
    editingChecklistId.value = null;
    editingChecklistName.value = '';
}

function saveEdit(id: number) {
    if (!editingChecklistName.value.trim()) {
        toast.error('Name cannot be empty');
        return;
    }

    router.put(`/checklists/${id}`, { name: editingChecklistName.value }, {
        preserveState: true,
        onSuccess: () => {
            editingChecklistId.value = null;
            router.reload({ only: ['checklist'] });
            toast.success('Checklist updated successfully!');
        }
    });
}

function deleteChecklist(id: number, name: string) {
    const checklistName = name;
    router.delete(`/checklists/${id}`, {
        preserveState: true,
        onSuccess: () => {
            selectedChecklists.value = selectedChecklists.value.filter(c => c !== id);
            router.reload({ only: ['checklist'] });
            toast.success('Checklist "' + checklistName + '" has been deleted');
        }
    });
}

</script>

<template>

    <Head title="Programs" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 rounded-3xl min-h-screen px-4 py-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="mb-4">
                    <form @submit.prevent="handleSubmit" @keydown.enter.prevent class="space-y-6">

                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2">Program Name</label>
                        <input type="text" v-model="form.name"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" />

                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mt-4 mb-2">Details</label>
                        <textarea v-model="form.details"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                            rows="4">
                        </textarea>

                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mt-4 mb-2">Term
                            (Months)</label>
                        <input type="number" v-model="form.term_months"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" />

                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mt-4 mb-2">Grace Period
                            (Months)</label>
                        <input type="number" v-model="form.grace_period"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" />

                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mt-4 mb-2">Minimum
                            Amount</label>
                        <input type="number" v-model="form.min_amount"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" />

                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mt-4 mb-2">Maximum
                            Amount</label>
                        <input type="number" v-model="form.max_amount"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" />

                        <label class="block text-gray-700 dark:text-gray-300 font-semibold mt-4 mb-2">Penalty
                            (%)</label>
                        <input type="number" v-model="form.penalty"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" />

                        <div class="mt-4">
                            <h3 class="text-lg font-semibold mb-2 text-gray-700 dark:text-gray-300">
                                Associated Checklists
                            </h3>

                            <button type="button" @click="selectedChecklists = props.programChecklists"
                                class="mb-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                Reset to Current
                            </button>

                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-60 overflow-y-auto border p-4 rounded-lg bg-gray-50 dark:bg-gray-700">

                                <div v-for="item in checklist" :key="item.id" class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" :value="item.id" v-model="selectedChecklists"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />

                                        <template v-if="editingChecklistId === item.id">
                                            <input v-model="editingChecklistName"
                                                class="px-2 py-1 border rounded dark:bg-gray-600" />
                                        </template>
                                        <template v-else>
                                            <label class="ml-2 block text-gray-700 dark:text-gray-300">
                                                {{ item.name }}
                                            </label>
                                        </template>
                                    </div>

                                    <div class="flex gap-2">
                                        <button v-if="editingChecklistId !== item.id" type="button"
                                            @click="startEdit(item)" class="text-blue-500 text-sm">
                                            Edit
                                        </button>

                                        <button v-if="editingChecklistId === item.id" type="button"
                                            @click="saveEdit(item.id)" class="text-green-500 text-sm">
                                            Save
                                        </button>

                                        <button v-if="editingChecklistId === item.id" type="button" @click="cancelEdit"
                                            class="text-gray-500 text-sm">
                                            Cancel
                                        </button>

                                        <button type="button" @click="deleteChecklist(item.id, item.name)"
                                            class="text-red-500 text-sm">
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between opacity-50">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" disabled class="h-4 w-4 border-gray-300 rounded" />
                                        <input v-model="newChecklistName" placeholder="Add new checklist..."
                                            class="px-2 py-1 border rounded dark:bg-gray-600 w-full" />
                                    </div>
                                    <button type="button" @click="addChecklist" class="text-blue-500 text-sm">
                                        Add
                                    </button>
                                </div>

                            </div>

                            <button type="button" @click="selectedChecklists = []"
                                class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                Clear Selections
                            </button>
                        </div>

                        <div class="pt-6 md:col-span-2 flex justify-center md:justify-end">
                            <button type="submit" :disabled="submitting"
                                class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow hover:bg-indigo-700 transition">
                                <span v-if="submitting">Updating...</span>
                                <span v-else>Update Cooperative</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
