<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import type { Cooperative, Details } from '@/types/cooperatives';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[],
    cooperative: Cooperative[],
    details: Details[]
}>()
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7x7 p-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                    Cooperative Details
                </h1>
                
                <!-- Display Fields -->
                <div class="space-y-6">
                    <div>
                        <span class="font-semibold">Register Number:</span>
                        <span class="ml-2">{{ cooperative[0].id }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Cooperative Name:</span>
                        <span class="ml-2">{{ cooperative[0].name }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Cooperative Holder:</span>
                        <span class="ml-2">{{ cooperative[0].holder || '-' }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Cooperative Type:</span>
                        <span class="ml-2">{{ details[0].coop_type }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Status Category:</span>
                        <span class="ml-2">{{ details[0].status_category }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Bond of Membership:</span>
                        <span class="ml-2">{{ details[0].bond_of_membership }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Area of Operation:</span>
                        <span class="ml-2">{{ details[0].area_of_operation }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Citizenship:</span>
                        <span class="ml-2">{{ details[0].citizenship }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Member Count:</span>
                        <span class="ml-2">{{ details[0].members_count }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Total Asset:</span>
                        <span class="ml-2">{{ details[0].total_asset }}</span>
                    </div>

                    <div>
                        <span class="font-semibold">Net Surplus:</span>
                        <span class="ml-2">{{ details[0].net_surplus }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>