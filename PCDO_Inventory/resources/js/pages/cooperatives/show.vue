<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps<{
    cooperative: any
    reportingDate: any
    reportingDateId: number
}>()

function groupByCategory(inventories: any[]) {
    const grouped: Record<string, any[]> = {}

    inventories.forEach(item => {
        if (!grouped[item.category]) {
            grouped[item.category] = []
        }
        grouped[item.category].push(item)
    })

    return grouped
}
</script>

<template>

    <Head :title="cooperative.coop_name" />

    <div class="max-w-7xl mx-auto p-6 space-y-6">

        <h1 class="text-2xl font-bold">
            {{ cooperative.name }}
        </h1>

        <p class="text-gray-500">
            Reporting Period: {{ reportingDate.reporting_month }}/{{ reportingDate.reporting_year }}
        </p>
        <div>
            <h2 class="text-xl font-semibold mb-4">
                Details
            </h2>
            <table class="w-full bg-white shadow rounded-xl">
                <tbody>
                    <tr class="border-t">
                        <td class="p-4 font-semibold text-gray-600">Address</td>
                        <td class="p-4">{{ cooperative.barangay.name }}, {{ cooperative.city.name }}, {{ cooperative.province.name }}, {{ cooperative.region.name }}</td>
                    </tr>
                    <tr class="border-t">
                        <td class="p-4 font-semibold text-gray-600">Email</td>
                        <td class="p-4">{{ cooperative.email }}</td>
                    </tr>
                    <tr class="border-t">
                        <td class="p-4 font-semibold text-gray-600">Contact Number</td>
                        <td class="p-4">{{ cooperative.number }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-for="instance in cooperative.instances"
            :key="instance.id"
            class="bg-white shadow rounded-xl p-6 space-y-4"
        >

            <details
                v-for="(items, category) in groupByCategory(instance.inventories)"
                :key="category"
                class="border rounded-lg"
                open
            >

                <summary
                    class="cursor-pointer bg-gray-100 px-4 py-3 font-semibold hover:bg-gray-200"
                >
                    {{ category }}
                </summary>

                <table class="w-full">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-left">Name</th>
                            <th class="p-3 text-left">Quantity</th>
                            <th class="p-3 text-left">Value</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Guarantor Agency</th>
                            <th class="p-3 text-left">Acquire Date</th>

                        </tr>
                    </thead>

                    <tbody>

                        <tr
                            v-for="item in items"
                            :key="item.id"
                            class="border-b"
                        >
                            <td class="p-3">{{ item.name }}</td>
                            <td class="p-3">{{ item.quantity }}</td>
                            <td class="p-3">{{ item.value }} ₱</td>
                            <td class="p-3">{{ item.status }}</td>
                            <td class="p-3">{{ item.guarantor_agency }}</td>
                            <td class="p-3">{{ item.acquired_date }}</td>
                        </tr>

                    </tbody>

                </table>

            </details>

        </div>

        <button
            @click="$inertia.visit(`/cooperatives?reporting_date_id=${reportingDateId}`)"
            class="text-blue-600 hover:underline"
        >
            Back to Cooperatives
        </button>

    </div>

</template>