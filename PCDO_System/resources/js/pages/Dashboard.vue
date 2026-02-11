<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from "vue3-apexcharts";
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

// Props from controller
const {
    totalCoops,
    upcomingMonthlyDues,
    totalReleases,
    totalReceived,
    monthlyData,
    monthlyCategories,
    yearlyData,
    yearlyCategories,
    notifications
} = defineProps<{
    totalCoops: number
    upcomingMonthlyDues: number
    totalReleases: number
    totalReceived: number
    monthlyData: Array<{ name: string, data: number[] }>
    monthlyCategories: string[]
    yearlyData: Array<{ name: string, data: number[] }>
    yearlyCategories: number[] | string[]
    notifications: Array<{
        id: number
        subject: string
        body: string
        type: string
        created_at: string
        read: boolean
    }>
}>();

// Toggle state
const view = ref<'monthly' | 'yearly'>('monthly');

// Chart series & categories (computed)
const series = computed(() => (view.value === 'monthly' ? monthlyData : yearlyData));
const categories = computed(() => (view.value === 'monthly' ? monthlyCategories : yearlyCategories));

// Dark mode detection for label colors
const isDarkMode = ref(document.documentElement.classList.contains('dark'))
const textColor = computed(() => isDarkMode.value ? '#ffffff' : '#1f2937')

// Chart options
const chartOptions = computed(() => ({
    chart: { type: "bar", toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 6, horizontal: false } },
    dataLabels: { enabled: false },
    xaxis: {
        categories: categories.value,
        labels: { style: { colors: textColor.value, fontSize: '14px', fontWeight: 500 } }
    },
    yaxis: {
        labels: {
            style: { colors: textColor.value, fontSize: "14px", fontWeight: 400 },
            formatter: (value: number) => value + " coop"
        },
        title: { text: "Number of Cooperatives", style: { color: "#9ca3af", fontSize: "14px", fontWeight: 500 } }
    },
    colors: ["#3b82f6", "#10b981", "#f97316", "#8b5cf6", "#ef4444"],
    legend: { show: true, position: "top", horizontalAlign: "center", labels: { colors: textColor.value } },
    tooltip: { theme: isDarkMode.value ? "dark" : "light" }
}));

// Format numbers as ₱K, ₱M, ₱B
function formatCurrencyShort(value: number): string {
    if (!value) return "₱0.00";
    if (value >= 1_000_000_000) return `₱${(value / 1_000_000_000).toFixed(1)}B`;
    if (value >= 1_000_000) return `₱${(value / 1_000_000).toFixed(1)}M`;
    if (value >= 1_000) return `₱${(value / 1_000).toFixed(1)}K`;
    return `₱${value.toFixed(2)}`;
}

function goToNotification(id: number) {
    router.get(`/notifications/${id}`);
}

</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
            <div class="grid gap-4 md:grid-cols-4 mt-6 px-4 pb-6">
                <!-- Total Cooperatives -->
                <div
                    class="col-span-4 md:col-span-1 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center text-center space-y-2">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 break-words truncate">
                        Total Cooperatives
                    </h3>
                    <p class="text-3xl font-extrabold text-blue-600 break-words truncate">
                        {{ totalCoops }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 break-words truncate">
                        Registered cooperatives
                    </p>
                </div>

                <!-- Upcoming Monthly Dues -->
                <div
                    class="col-span-4 md:col-span-1 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center text-center space-y-2">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        Upcoming Monthly Dues
                    </h3>
                    <p class="text-3xl font-extrabold text-red-600">
                        {{ formatCurrencyShort(upcomingMonthlyDues) }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Due next month
                    </p>
                </div>

                <!-- Cash Flow -->
                <div
                    class="col-span-4 md:col-span-1 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center text-center space-y-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">
                        Cash Flow
                    </h3>
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <div
                            class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 text-center flex flex-col justify-center items-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Release</p>
                            <p class="text-2xl font-bold text-blue-600 break-all max-w-full">
                                {{ formatCurrencyShort(totalReleases) }}
                            </p>
                        </div>
                        <div
                            class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 text-center flex flex-col justify-center items-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Received</p>
                            <p class="text-2xl font-bold text-green-600 break-all max-w-full">
                                {{ formatCurrencyShort(totalReceived) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Notifications Card -->
                <div
                    class="col-span-4 md:col-span-1 row-span-2 order-last md:order-none bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-6 flex flex-col">
                    <div class="flex flex-wrap justify-between items-center mb-4 w-full">
                        <h2
                            class="text-xl font-semibold text-gray-800 dark:text-gray-100 truncate max-w-full sm:max-w-[70%]">
                            Recent Notifications
                        </h2>
                        <a href="/notifications"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 sm:mt-0 w-full sm:w-auto text-right">
                            See All
                        </a>
                    </div>
                    <ul class="flex-1 space-y-3 overflow-y-auto flex flex-col items-center text-center pt-3 pb-12
           [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                        <li v-for="notification in notifications" :key="notification.id" class="bg-gray-100 dark:bg-gray-700 w-full p-3 rounded-lg text-sm cursor-pointer 
             hover:bg-gray-300 dark:hover:bg-gray-600 transition break-words"
                            @click="goToNotification(notification.id)">
                            {{ notification.subject }}
                            <p
                                class="text-xs text-gray-500 dark:text-gray-300 mt-1 whitespace-nowrap overflow-hidden text-ellipsis">
                                {{ new Date(notification.created_at).toLocaleString() }}
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Chart Card -->
                <div class="col-span-4 md:col-span-3 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                                Program Statistics
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Overview of all enrolled cooperative programs.
                            </p>
                        </div>

                        <div class="hidden md:flex gap-3">
                            <button class="px-4 py-2 rounded-lg font-medium transition" :class="view === 'monthly'
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'bg-blue-100 text-blue-700 hover:bg-blue-200'" @click="view = 'monthly'">
                                Monthly
                            </button>
                            <button class="px-4 py-2 rounded-lg font-medium transition" :class="view === 'yearly'
                                ? 'bg-green-600 text-white shadow-md'
                                : 'bg-green-100 text-green-700 hover:bg-green-200'" @click="view = 'yearly'">
                                Yearly
                            </button>
                        </div>
                    </div>

                    <VueApexCharts type="bar" height="320" :options="chartOptions" :series="series" />
                    <div class="flex md:hidden justify-center gap-3 mt-4">
                        <button class="px-4 py-2 rounded-lg font-medium transition w-1/2" :class="view === 'monthly'
                            ? 'bg-blue-600 text-white shadow-md'
                            : 'bg-blue-100 text-blue-700 hover:bg-blue-200'" @click="view = 'monthly'">
                            Monthly
                        </button>
                        <button class="px-4 py-2 rounded-lg font-medium transition w-1/2" :class="view === 'yearly'
                            ? 'bg-green-600 text-white shadow-md'
                            : 'bg-green-100 text-green-700 hover:bg-green-200'" @click="view = 'yearly'">
                            Yearly
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
