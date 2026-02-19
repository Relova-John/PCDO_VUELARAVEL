<script setup lang="ts">
import CoopLayout from '@/layouts/CoopLayout.vue'
import { ref, computed } from 'vue'
import { BreadcrumbItem } from '@/types'
import { router } from '@inertiajs/vue3';
import type { Member } from '@/types/cooperatives'

const props = defineProps<{
    cooperative: { id: string, name: string }
    members: Member[]
}>()

const searchQuery = ref('')

const filteredMembers = computed(() => {
    if (!searchQuery.value) return props.members

    return props.members.filter(mem =>
        mem.first_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        mem.last_name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

function goToViewPage(id: number) {
    router.visit(`/coop/members/${id}`)
}

const breadcrumbs: BreadcrumbItem[] =
    [{ title: 'Members', href: '#' }]
</script>

<template>
    <CoopLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto p-6">

                <!-- Header Section -->
                <div class="bg-white dark:bg-gray-800/80 border 
                ring-1 ring-gray-300 dark:ring-gray-700 
                border-gray-300 dark:border-gray-700 
                rounded-xl shadow-sm px-6 py-5 mb-6">

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                        <h1 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100">
                            Members of {{ cooperative.name }}
                        </h1>

                        <!-- Search -->
                        <div class="relative w-full md:w-80">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                            <input v-model="searchQuery" placeholder="Search member..." class="pl-9 pr-3 py-2 w-full rounded-lg border 
                           border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-800
                           text-gray-800 dark:text-gray-200
                           focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                    </div>
                </div>


                <!-- Members List Container -->
                <div class="bg-white dark:bg-gray-800/80 border 
                ring-1 ring-gray-300 dark:ring-gray-700 
                border-gray-300 dark:border-gray-700 
                rounded-xl shadow-sm px-6 py-5">

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <Table class="min-w-full text-sm border-separate border-spacing-0">

                            <TableHeader class="bg-gray-200 dark:bg-gray-700/50 border-b border-gray-500">
                                <TableRow>
                                    <TableHead class="py-3 pl-6 text-left">Full Name</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Position</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Representative</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Contact</TableHead>
                                    <TableHead class="py-3 pl-6 text-left">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody class="bg-white dark:bg-gray-800">

                                <TableRow v-for="mem in filteredMembers" :key="mem.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">

                                    <TableCell class="pl-6 py-3 text-gray-700 dark:text-gray-300">
                                        {{ mem.first_name }}
                                        {{ mem.middle_name ? mem.middle_name + '. ' : '' }}
                                        {{ mem.last_name }}
                                    </TableCell>

                                    <TableCell class="pl-6 py-3 text-gray-700 dark:text-gray-300">
                                        {{ mem.position || 'Member' }}
                                    </TableCell>

                                    <TableCell class="pl-6 py-3">
                                        <span class="text-xs font-medium px-2 py-1 rounded-full" :class="mem.is_representative
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                            : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                                            {{ mem.is_representative ? 'Yes' : 'No' }}
                                        </span>
                                    </TableCell>

                                    <TableCell class="pl-6 py-3 text-gray-700 dark:text-gray-300">
                                        {{ mem.contact || '-' }}
                                    </TableCell>

                                    <TableCell class="pl-6 py-3 text-gray-700 dark:text-gray-300">
                                        <button @click="goToViewPage(mem.id)" class="px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition">
                                            View
                                        </button>
                                    </TableCell>

                                </TableRow>

                                <TableRow v-if="filteredMembers.length === 0">
                                    <TableCell colspan="5" class="text-center py-6 text-gray-500">
                                        No members found.
                                    </TableCell>
                                </TableRow>

                            </TableBody>

                        </Table>
                    </div>


                    <!-- Mobile Cards -->
                    <div class="md:hidden space-y-4">
                        <div v-for="mem in filteredMembers" :key="mem.id" class="bg-white dark:bg-gray-800 
                        border border-gray-200 dark:border-gray-700 
                        rounded-2xl shadow-sm p-4 
                        transition hover:shadow-md">

                            <div class="flex justify-between items-start">

                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                        {{ mem.first_name }} {{ mem.last_name }}
                                    </h3>

                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ mem.position || 'Member' }}
                                    </p>
                                </div>

                                <span class="text-xs font-medium px-2 py-1 rounded-full" :class="mem.is_representative
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                    : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                                    {{ mem.is_representative ? 'Representative' : 'Member' }}
                                </span>

                            </div>

                            <div
                                class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                <p>Contact: {{ mem.contact || '-' }}</p>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </CoopLayout>
</template>