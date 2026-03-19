<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue'
import { SquarePen } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Details', href: `` }
]

const props = defineProps<{
    cooperative: any
    reportingDate: any
    reportingDateId: number
}>()

const searchFilter = ref('')
const statusFilter = ref('all')

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

function filterItems(items: any[]) {
    return items.filter(item => {

        const matchesSearch =
            item.name.toLowerCase().includes(searchFilter.value.toLowerCase())

        const servicable = item.status
        const nonServicable = item.quantity - item.status

        let matchesStatus = true

        if (statusFilter.value === 'servicable')
            matchesStatus = servicable > 0

        if (statusFilter.value === 'non-servicable')
            matchesStatus = nonServicable > 0

        return matchesSearch && matchesStatus
    })
}

function totalItem(item: any) {
    return item.quantity * item.value
}

function rowTotal(item: any) {
    if (statusFilter.value === 'servicable') return servicableTotal(item)
    if (statusFilter.value === 'non-servicable') return nonServicableTotal(item)
    return totalItem(item)
}

function servicableTotal(item: any) {
    return item.status * item.value
}

function nonServicableTotal(item: any) {
    return (item.quantity - item.status) * item.value
}

function categoryTotal(items: any[]) {

    return items.reduce((sum, item) => {

        if (statusFilter.value === 'servicable')
            return sum + servicableTotal(item)

        if (statusFilter.value === 'non-servicable')
            return sum + nonServicableTotal(item)

        return sum + totalItem(item)

    }, 0)
}

const grandTotal = computed(() => {

    let total = 0

    props.cooperative.instances.forEach((instance: any) => {
        instance.inventories.forEach((item: any) => {

            if (statusFilter.value === 'servicable')
                total += servicableTotal(item)

            else if (statusFilter.value === 'non-servicable')
                total += nonServicableTotal(item)

            else
                total += totalItem(item)

        })
    })

    return total
})


const page = usePage()
const user = page.props.auth.user

const dashboardBasePath = computed(() => {
    return user.role === 'officer' ? '/officer/dashboard' : '/admin/dashboard'
})
</script>

<template>

    <Head :title="cooperative.coop_name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="show-page-wrapper">
            <div class="coop-header">

                <!-- LEFT SIDE -->
                <div class="coop-header-left">
                    <h1 class="coop-title">
                        {{ cooperative.name }}
                    </h1>

                    <p class="coop-description">
                        Cooperative Inventory Details
                    </p>
                </div>

                <!-- RIGHT SIDE -->
                <div class="coop-header-right">
                    <button class="edit-btn" @click="$inertia.visit(`${dashboardBasePath}/${cooperative.id}/edit`)"
                        title="Edit Cooperative">
                        <SquarePen color="white" />
                    </button>
                    <span class="report-label">Reporting Period</span>

                    <span class="report-badge">
                        {{ reportingDate.reporting_month }}/{{ reportingDate.reporting_year }}
                    </span>
                </div>

            </div>

            <div class="instance-card">
                <h2>Details</h2>
                <table class="details-info-table">
                    <tbody>
                        <tr>
                            <td>Address</td>
                            <td>{{ cooperative.barangay.name }}, {{ cooperative.city.name }}, {{
                                cooperative.province.name }}, {{ cooperative.region.name }}</td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>{{ cooperative.email }}</td>
                        </tr>
                        <tr>
                            <td>Contact Number</td>
                            <td>{{ cooperative.number }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="inventory-filters">

                <input v-model="searchFilter" placeholder="Search item..." class="coop-search" />

                <select v-model="statusFilter" class="coop-select">
                    <option value="all">All</option>
                    <option value="servicable">Servicable</option>
                    <option value="non-servicable">Non-Servicable</option>
                </select>

            </div>
            <!-- Instances -->
            <div v-for="instance in cooperative.instances" :key="instance.id" class="instance-card">
                <details v-for="(items, category) in groupByCategory(filterItems(instance.inventories))" :key="category"
                    open>
                    <summary>{{ category }}</summary>
                    <table class="inventory-data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Quantity</th>
                                <th>Value</th>
                                <th>Status</th>
                                <th>Granting Agency</th>
                                <th>Acquire Date</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in items" :key="item.id">
                                <td data-label="Name">{{ item.name }}</td>
                                <td data-label="Quantity">{{ item.quantity }}</td>
                                <td data-label="Value">₱ {{ item.value }}</td>

                                <td data-label="Status">
                                    <span v-if="statusFilter === 'all'">
                                        Servicable: {{ item.status }} out of {{ item.quantity }}
                                    </span>
                                    <span v-else-if="statusFilter === 'servicable'">
                                        Servicable: {{ item.status }}
                                    </span>
                                    <span v-else-if="statusFilter === 'non-servicable'">
                                        Non-Servicable: {{ item.quantity - item.status }}
                                    </span>
                                </td>

                                <td data-label="Granting Agency">{{ item.granting_agency }}</td>
                                <td data-label="Acquire Date">{{ item.acquired_date }}</td>
                                <td data-label="Total">₱ {{ rowTotal(item) }}</td>
                            </tr>
                        </tbody>
                        <tr class="category-total">
                            <td colspan="3"><strong>Total for {{ category }}</strong></td>
                            <td colspan="4">₱ {{ categoryTotal(items) }}</td>
                        </tr>
                    </table>
                </details>
            </div>
            <div class="grand-total">
                Grand Total: ₱ {{ grandTotal }}
            </div>
            <!-- Back Button -->
            <button @click="$inertia.visit(`/officer/dashboard?reporting_date_id=${reportingDateId}`)" class="back-btn">
                Back to Cooperatives
            </button>
        </div>
    </AppLayout>
</template>