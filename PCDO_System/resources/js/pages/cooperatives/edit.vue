<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/vue3';
import type { Cooperative, Details } from '@/types/cooperatives';
import SelectSearch from '@/components/SelectSearch.vue';
import { ref } from 'vue';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[],
    cooperative: Cooperative[],
    details: Details[]
}>()

const form = useForm({
    id: '',
    name: '',
    holder_id: null as number | null,
    province_id: null as number | null,
    municipality_id: null as number | null,
    barangay_id: null as number | null,
    asset_size: '',
    coop_type: '',
    status_category: 'New',
    bond_of_membership: '',
    area_of_operation: '',
    citizenship: 'Filipino',
    members_count: 0,
    total_asset: 0,
    net_surplus: 0,
})

const showIdDropdown = ref(false);
const showNameDropdown = ref(false);

function hideIdDropdown() {
    setTimeout(() => (showIdDropdown.value = false), 200)
}
function hideNameDropdown() {
    setTimeout(() => (showNameDropdown.value = false), 200)
}

const searchHolder = ref('');
const dropDownHolderOpen = ref(false);
const selectedHolder = ref<number | ''>('');

function isIdFormatValid(query: string) {
    const regex = /^\d{4}-\d{4,}$/ // e.g. 2024-1234
    return regex.test(query)
}

function onHolderSelect(selected: { name: string; id: number }) {
    selectedHolder.value = selected.id;
    form.holder_id = selected.id;
    searchHolder.value = selected.name;
    dropDownHolderOpen.value = false;
}



function handleSubmit() {
    form.put(`/cooperatives/${props.cooperative[0].id}`, {
        onSuccess: () => {
            alert('Cooperative details updated successfully!');
        },
        onError: (errors) => {
            console.error('Failed to update cooperative details:', errors);
        }
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7x7 p-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                    Edit Cooperative Details
                </h1>
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Coop ID -->
                    <div>
                        <Label for="id">Register Number</Label>
                        <div v-if="!isIdFormatValid(form.id) && form.id" class="mt-1 text-red-500">
                            Invalid ID Format. Correct format is YYYY-XXXX (e.g., 2024-1234)
                        </div>
                        <div class="relative mt-1">
                            <input id="id" v-model="form.id" placeholder="Enter Cooperative Register Number"
                                @focus="showIdDropdown = true" @blur="hideIdDropdown"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                    </div>
                    <!-- Coop Name -->
                    <div>
                        <Label for="name">Cooperative Name</Label>
                        <div class="relative mt-1">
                            <input id="name" v-model="form.name" placeholder="Enter Cooperative Name"
                                @focus="showNameDropdown = true" @blur="hideNameDropdown"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                    </div>
                    <!-- Coop Holder -->
                    <div>
                        <Label for="holder" class="mb-2">Cooperative Holder</Label>
                        <SelectSearch 
                            id="holder" 
                            :items="props.cooperative" 
                            itemLabelKey="name" 
                            itemKeyProp="id"
                            v-model:search="searchHolder" 
                            :modelValue="selectedHolder"
                            :open="dropDownHolderOpen" 
                            @select="onHolderSelect"
                            @update:open="val => dropDownHolderOpen = val" 
                            placeholder="Search holder"
                        />
                    </div>
                    <!-- Coop Type -->
                    <div>
                        <Label for="type">Cooperative Type</Label>
                        <SelectSearch
                            id="coop_type"
                            :items="['Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose', 'Advocacy', 'Agrarian Reform', 'Bank', 'Diary', 'Education', 'Electric', 'Financial', 'Fishermen', 'Health Services', 'Housing', 'Insurance', 'Water Service', 'Workers', 'Others']"
                            v-model:search="form.coop_type"
                            :modelValue="form.coop_type"
                            :open="false"
                            @select="val => form.coop_type = val.id"
                            placeholder="Select Cooperative Type"
                        />
                    </div>
                    <!-- Status Category -->
                    <div>
                        <Label for="status_category" class="mb-2">Status Category</Label>
                        <SelectSearch
                            id="status_category"
                            :items="['Reporting', 'Non-Reporting', 'New']"
                            v-model:search="form.status_category"
                            :modelValue="form.status_category"
                            :open="false"
                            @select="val => form.status_category = val.id"
                            placeholder="Select Status Category"
                        />
                    </div>
                    <!-- Bond of Membership -->
                    <div>
                        <Label for="bond_of_membership" class="mb-2">Bond of Membership</Label>
                        <SelectSearch
                            id="bond_of_membership"
                            :items="['Residential', 'Insitutional', 'Associational', 'Occupational', 'Unspecified']"
                            v-model:search="form.bond_of_membership"
                            :modelValue="form.bond_of_membership"
                            :open="false"
                            @select="val => form.bond_of_membership = val.id"
                            placeholder="Select Bond of Membership"
                        />
                    </div>
                    <!-- Area of Operation -->
                    <div>
                        <Label for="area_of_operation" class="mb-2">Area of Operation</Label>
                        <SelectSearch
                            id="area_of_operation"
                            :items="['Municipal', 'Provincial']"
                            v-model:search="form.area_of_operation"
                            :modelValue="form.area_of_operation"
                            :open="false"
                            @select="val => form.area_of_operation = val.id"
                            placeholder="Select Area of Operation"
                        />
                    </div>
                    <!-- Citizenship -->
                    <div>
                        <Label for="citizenship" class="mb-2">Citizenship</Label>
                        <SelectSearch
                            id="citizenship"
                            :items="['Filipino', 'Foreign', 'Others']"
                            v-model:search="form.citizenship"
                            :modelValue="form.citizenship"
                            :open="false"
                            @select="val => form.citizenship = val.id"
                            placeholder="Select Citizenship"
                        />
                    </div>
                    <!-- Member Count -->
                    <div>
                        <Label class="mb-2">Member Count</Label>
                        <input v-model="form.members_count" type="number"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>

                    <!-- Total Asset -->
                    <div>
                        <Label class="mb-2">Total Asset</Label>
                        <input v-model="form.total_asset" type="number"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>
                    <!-- Net Surplus -->
                    <div>
                        <Label class="mb-2">Net Surplus</Label>
                        <input v-model="form.net_surplus" type="number"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>
                    <!-- Submit -->
                    <div class="pt-6 md:col-span-2">
                        <button type="submit"
                            class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow hover:bg-indigo-700 transition">
                            Edit Coop Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>