<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import type { Cooperative, Details } from '@/types/cooperatives';
import { router, usePage } from '@inertiajs/vue3';
import FlashToast from '@/components/FlashToast.vue';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string; info?: string });

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[],
    cooperative: Cooperative,
    details: Details,
}>()

function goToEditPage(id: string) {
    router.visit(`/cooperatives/${id}/edit`);
}

function goToMemberPage(id: string) {
    router.visit(`/cooperatives/${id}/members`)
}

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div
                class="bg-white dark:bg-gray-900 shadow-lg rounded-2xl p-8 border border-gray-200 dark:border-gray-700">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        🏢 Cooperative Details
                    </h1>
                    
                    <!-- Right-side actions -->
                    <div class="flex items-center gap-3">
                    <!-- ID Badge -->
                        <span class="inline-flex gap-2 px-4 py-2 rounded-full text-sm font-medium
                        bg-indigo-100 text-indigo-700 dark:bg-indigo-800 dark:text-indigo-200">
                            ID: {{ cooperative.id }}
                        </span>
                        <!-- Edit Button -->
                        <Button
                            @click="goToEditPage(cooperative.id)"
                            class="inline-flex gap-2 px-4 py-2 rounded-lg 
                            bg-gray-200 dark:bg-green-700 
                            text-green-700 dark:text-gray-200 
                            hover:bg-gray-300 dark:hover:bg-green-600 
                            text-sm font-medium transition">
                            Edit
                        </Button>
                        <Button
                            @click="goToMemberPage(cooperative.id)"
                            class="inline-flex gap-2 px-4 py-2 rounded-lg 
                            bg-gray-200 dark:bg-blue-700 
                            text-blue-700 dark:text-gray-200 
                            hover:bg-gray-300 dark:hover:bg-blue-600 
                            text-sm font-medium transition">
                            Members
                        </Button>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6 text-gray-800 dark:text-gray-200">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cooperative Name</p>
                        <p class="text-lg font-semibold">{{ cooperative.name }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cooperative Holder</p>
                        <p class="text-lg font-semibold">{{ cooperative.holder || '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cooperative Type</p>
                        <p class="text-lg font-semibold">{{ details.coop_type }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status Category</p>
                        <p class="text-lg font-semibold">{{ details.status_category }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bond of Membership</p>
                        <p class="text-lg font-semibold">{{ details.bond_of_membership }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Area of Operation</p>
                        <p class="text-lg font-semibold">{{ details.area_of_operation }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Citizenship</p>
                        <p class="text-lg font-semibold">{{ details.citizenship }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Member Count</p>
                        <p class="text-lg font-semibold">{{ details.members_count }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Asset</p>
                        <p class="text-lg font-semibold">₱{{ details.total_asset.toLocaleString() }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Surplus</p>
                        <p class="text-lg font-semibold">₱{{ details.net_surplus.toLocaleString() }}</p>
                    </div>
                </div>
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