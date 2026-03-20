<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { computed, ref } from 'vue'
import { SquarePen } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Details', href: '' }
]

const props = withDefaults(defineProps<{
    cooperative: any
    reportingDate: any
    reportingDateId: number
}>(), {
    cooperative: () => ({
        id: null,
        name: '',
        email: '',
        number: '',
        barangay: null,
        city: null,
        province: null,
        region: null,
        instances: [],
    }),
    reportingDate: () => ({
        reporting_month: '',
        reporting_year: '',
    }),
    reportingDateId: 0,
})

const searchFilter = ref('')
const statusFilter = ref('all')

const page = usePage<any>()
const user = page.props.auth?.user ?? {}

const dashboardBasePath = computed(() => {
    return user.role === 'officer' ? '/officer/dashboard' : '/admin/dashboard'
})

const instances = computed(() => props.cooperative?.instances ?? [])

const hasAnyInventory = computed(() => {
    return instances.value.some((instance: any) => (instance?.inventories ?? []).length > 0)
})

function groupByCategory(inventories: any[] = []) {
    const grouped: Record<string, any[]> = {}

    inventories.forEach(item => {
        const category = item?.category ?? 'Uncategorized'

        if (!grouped[category]) {
            grouped[category] = []
        }

        grouped[category].push(item)
    })

    return grouped
}

function filterItems(items: any[] = []) {
    return items.filter(item => {
        const name = String(item?.name ?? '')
        const quantity = Number(item?.quantity ?? 0)
        const servicable = Number(item?.status ?? 0)
        const nonServicable = quantity - servicable

        const matchesSearch =
            name.toLowerCase().includes(searchFilter.value.toLowerCase())

        let matchesStatus = true

        if (statusFilter.value === 'servicable') {
            matchesStatus = servicable > 0
        }

        if (statusFilter.value === 'non-servicable') {
            matchesStatus = nonServicable > 0
        }

        return matchesSearch && matchesStatus
    })
}

function totalItem(item: any) {
    return Number(item?.quantity ?? 0) * Number(item?.value ?? 0)
}

function servicableTotal(item: any) {
    return Number(item?.status ?? 0) * Number(item?.value ?? 0)
}

function nonServicableTotal(item: any) {
    return (Number(item?.quantity ?? 0) - Number(item?.status ?? 0)) * Number(item?.value ?? 0)
}

function rowTotal(item: any) {
    if (!item) return 0

    switch (statusFilter.value) {
        case 'servicable':
            return servicableTotal(item)
        case 'non-servicable':
            return nonServicableTotal(item)
        default:
            return totalItem(item)
    }
}

function categoryTotal(items: any[] = []) {
    return items.reduce((sum, item) => {
        if (statusFilter.value === 'servicable') {
            return sum + servicableTotal(item)
        }

        if (statusFilter.value === 'non-servicable') {
            return sum + nonServicableTotal(item)
        }

        return sum + totalItem(item)
    }, 0)
}

const grandTotal = computed(() => {
    let total = 0

    instances.value.forEach((instance: any) => {
        const inventories = instance?.inventories ?? []

        inventories.forEach((item: any) => {
            if (statusFilter.value === 'servicable') {
                total += servicableTotal(item)
            } else if (statusFilter.value === 'non-servicable') {
                total += nonServicableTotal(item)
            } else {
                total += totalItem(item)
            }
        })
    })

    return total
})

function formatCurrency(value: number) {
    return Number(value ?? 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

function formatStatusText(item: any) {
    const quantity = Number(item?.quantity ?? 0)
    const servicable = Number(item?.status ?? 0)
    const nonServicable = quantity - servicable

    if (statusFilter.value === 'servicable') {
        return `Servicable: ${servicable}`
    }

    if (statusFilter.value === 'non-servicable') {
        return `Non-Servicable: ${nonServicable}`
    }

    return `Servicable: ${servicable} out of ${quantity}`
}

const formattedAddress = computed(() => {
    return [
        props.cooperative?.barangay?.name,
        props.cooperative?.city?.name,
        props.cooperative?.province?.name,
        props.cooperative?.region?.name,
    ].filter(Boolean).join(', ')
})

function hasVisibleItems(instance: any) {
    const inventories = instance?.inventories ?? []
    return Object.keys(groupByCategory(filterItems(inventories))).length > 0
}

const showFileModal = ref(false)
const selectedItem = ref<any | null>(null)

function openFileModal(item: any) {
    selectedItem.value = item
    showFileModal.value = true
}

function closeFileModal() {
    showFileModal.value = false
    selectedItem.value = null
}

/**
 * Normalize file relation because backend data may come as:
 * - single object
 * - array with one object
 * - null
 */
function resolveFile(file: any) {
    if (!file) return null

    if (Array.isArray(file)) {
        return file.length > 0 ? file[0] : null
    }

    return file
}

function getFileUrl(file: any) {
    const resolved = resolveFile(file)

    if (!resolved?.file_path) return ''

    if (String(resolved.file_path).startsWith('http://') || String(resolved.file_path).startsWith('https://')) {
        return resolved.file_path
    }

    return `/storage/${resolved.file_path}`
}

function isImageFile(file: any) {
    const resolved = resolveFile(file)

    const type = String(resolved?.file_type ?? '').toLowerCase()
    const path = String(resolved?.file_path ?? '').toLowerCase()

    return (
        type.startsWith('image/') ||
        path.endsWith('.png') ||
        path.endsWith('.jpg') ||
        path.endsWith('.jpeg') ||
        path.endsWith('.gif') ||
        path.endsWith('.webp') ||
        path.endsWith('.bmp') ||
        path.endsWith('.svg')
    )
}

function isPdfFile(file: any) {
    const resolved = resolveFile(file)

    const type = String(resolved?.file_type ?? '').toLowerCase()
    const path = String(resolved?.file_path ?? '').toLowerCase()

    return type.includes('pdf') || path.endsWith('.pdf')
}

const selectedItemPicture = computed(() => resolveFile(selectedItem.value?.item_pictures))
const selectedMoaFile = computed(() => resolveFile(selectedItem.value?.moa_files))

const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
</script>

<template>

    <Head :title="cooperative?.name || 'Cooperative Details'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="show-page-wrapper">
            <div class="coop-header">
                <div class="coop-header-left">
                    <h1 class="coop-title">
                        {{ cooperative?.name || 'Unnamed Cooperative' }}
                    </h1>

                    <p class="coop-description">
                        Cooperative Inventory Details
                    </p>
                </div>

                <div class="coop-header-right">
                    <button v-if="cooperative?.id" class="edit-btn"
                        @click="$inertia.visit(`${dashboardBasePath}/${cooperative.id}/edit`)" title="Edit Cooperative">
                        <SquarePen color="white" />
                    </button>

                    <span class="report-label">Reporting Period</span>

                    <span class="report-badge">
                        {{ reportingDate?.reporting_month || '-' }}/{{ reportingDate?.reporting_year || '-' }}
                    </span>
                </div>
            </div>

            <div class="instance-card">
                <h2>Details</h2>

                <table class="details-info-table">
                    <tbody>
                        <tr>
                            <td>Address</td>
                            <td>{{ formattedAddress || 'No address available' }}</td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>{{ cooperative?.email || '-' }}</td>
                        </tr>
                        <tr>
                            <td>Contact Number</td>
                            <td>{{ cooperative?.number || '-' }}</td>
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

            <div v-if="!hasAnyInventory" class="instance-card">
                <h2>Inventory</h2>
                <p>No inventory found for this cooperative in the selected reporting period.</p>
            </div>

            <template v-else>
                <div v-for="instance in instances" :key="instance?.id ?? Math.random()" class="instance-card">
                    <template v-if="hasVisibleItems(instance)">
                        <details v-for="(items, category) in groupByCategory(filterItems(instance?.inventories ?? []))"
                            :key="String(category)" open>
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
                                    <tr v-if="items.length === 0">
                                        <td colspan="7">No matching inventory items.</td>
                                    </tr>

                                    <tr v-for="item in items" :key="item?.id" class="clickable-row"
                                        @click="openFileModal(item)">
                                        <td data-label="Name">{{ item?.name || '-' }}</td>
                                        <td data-label="Quantity">{{ item?.quantity ?? 0 }}</td>
                                        <td data-label="Value">₱ {{ formatCurrency(item?.value ?? 0) }}</td>

                                        <td data-label="Status">
                                            {{ formatStatusText(item) }}
                                        </td>

                                        <td data-label="Granting Agency">{{ item?.granting_agency || '-' }}</td>
                                        <td data-label="Acquire Date">{{ item?.acquired_date || '-' }}</td>
                                        <td data-label="Total">₱ {{ formatCurrency(rowTotal(item)) }}</td>
                                    </tr>
                                </tbody>

                                <tfoot>
                                    <tr class="category-total">
                                        <td colspan="3"><strong>Total for {{ category }}</strong></td>
                                        <td colspan="4">₱ {{ formatCurrency(categoryTotal(items)) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </details>
                    </template>

                    <template v-else>
                        <h2>Inventory</h2>
                        <p>No matching inventory items for this instance.</p>
                    </template>
                </div>
            </template>

            <div class="grand-total">
                Grand Total: ₱ {{ formatCurrency(grandTotal) }}
            </div>

            <button @click="$inertia.visit(`${dashboardBasePath}?reporting_date_id=${reportingDateId}`)"
                class="back-btn">
                Back to Cooperatives
            </button>
        </div>

        <div v-if="showFileModal" class="file-modal-overlay" @click="closeFileModal">
            <div class="file-modal" @click.stop>
                <div class="file-modal-header">
                    <h2>{{ selectedItem?.name || 'Item Files' }}</h2>
                    <button type="button" class="file-modal-close" @click="closeFileModal">✕</button>
                </div>

                <div class="file-modal-body">
                    <div class="file-preview-section">
                        <h3>Item Picture:</h3>

                        <template v-if="selectedItemPicture">
                            <img v-if="isImageFile(selectedItemPicture)" :src="getFileUrl(selectedItemPicture)"
                                alt="Item Picture" class="file-preview-image" />

                            <template v-else-if="isPdfFile(selectedItemPicture)">
                                <iframe v-if="!isMobile" :src="getFileUrl(selectedItemPicture)"
                                    class="file-preview-frame"></iframe>

                                <a v-else :href="getFileUrl(selectedItemPicture)" target="_blank"
                                    class="file-preview-link">
                                    Open PDF
                                </a>
                            </template>

                            <div v-else class="file-preview-fallback">
                                Preview not available for this file type.
                            </div>
                        </template>

                        <p v-else>No item picture uploaded.</p>
                    </div>

                    <div class="file-preview-section">
                        <h3>MOA File:</h3>

                        <template v-if="selectedMoaFile">
                            <img v-if="isImageFile(selectedMoaFile)" :src="getFileUrl(selectedMoaFile)" alt="MOA File"
                                class="file-preview-image" />

                            <iframe v-else-if="isPdfFile(selectedMoaFile)" :src="getFileUrl(selectedMoaFile)"
                                class="file-preview-frame"></iframe>

                            <div v-else class="file-preview-fallback">
                                Preview not available for this file type.
                            </div>
                        </template>

                        <p v-else>No MOA file uploaded.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.item-link-btn {
    background: none;
    border: none;
    padding: 0;
    color: #2563eb;
    cursor: pointer;
    font: inherit;
    text-align: left;
}

.item-link-btn:hover {
    text-decoration: underline;
}

.file-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.file-modal {
    background: white;
    width: min(1000px, 100%);
    max-height: 90vh;
    overflow: auto;
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
}

.file-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.file-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
}

.file-modal-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 20px;
}

.file-preview-section h3 {
    margin-bottom: 12px;
}

.file-preview-image {
    width: 100%;
    height: auto;
    max-height: 70vh;
}

.file-preview-frame {
    width: 100%;
    height: 500px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: white;
}

.file-preview-fallback {
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #f8fafc;
    color: #6b7280;
}

.file-preview-link {
    color: #2563eb;
    font-weight: 600;
}

@media (max-width: 768px) {
    .file-modal-body {
        grid-template-columns: 1fr;
    }

    .file-preview-frame {
        height: 350px;
    }
}
</style>