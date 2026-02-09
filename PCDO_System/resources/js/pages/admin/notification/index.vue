<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

const props = defineProps<{
  notifications: Array<{
    id: number;
    subject: string;
    body: string;
    type: string;
    created_at: string;
    schedule?: {
      coop_program?: {
        cooperative?: {
          name: string;
        };
      };
    };
  }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Notifications', href: '/notifications' },
]

// Track read notifications in localStorage
const readNotifications = ref<number[]>([])

onMounted(() => {
  const saved = localStorage.getItem('readNotifications')
  if (saved) readNotifications.value = JSON.parse(saved)
})

function markAsRead(id: number) {
  if (!readNotifications.value.includes(id)) {
    readNotifications.value.push(id)
    localStorage.setItem('readNotifications', JSON.stringify(readNotifications.value))
  }
}

function isRead(id: number) {
  return readNotifications.value.includes(id)
}

// Helpers
function truncate(text: string, length = 80) {
  return text?.length > length ? text.substring(0, length) + '...' : text
}

function formatDateTime(dateStr: string) {
  const date = new Date(dateStr)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatDateOnly(dateStr: string) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const groupedNotifications = props.notifications.reduce((groups: Record<string, typeof props.notifications>, n) => {
  const date = formatDateOnly(n.created_at)
  if (!groups[date]) groups[date] = []
  groups[date].push(n)
  return groups
}, {})

const sortedDates = Object.keys(groupedNotifications).sort((a, b) => new Date(b).getTime() - new Date(a).getTime())
</script>

<template>
  <Head title="Notifications" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
      <div class="p-6 space-y-6">
        <p class="text-gray-700 dark:text-gray-200">All Notifications</p>

        <div class="border rounded-sm shadow-sm bg-gray-200 dark:bg-gray-800">
          <template v-for="date in sortedDates" :key="date">
            <!-- Date header -->
            <div class="bg-gray-200 dark:bg-gray-900/70 px-4 py-2 text-gray-700 dark:text-gray-300 font-semibold text-sm">
              {{ date }}
            </div>

            <!-- Notifications for this date -->
            <div v-for="notification in groupedNotifications[date]" :key="notification.id">
              <Link :href="`/notifications/${notification.id}`" @click="markAsRead(notification.id)">
                <div
                  class="p-4 flex items-start gap-4 transition rounded-md"
                  :class="[
                    isRead(notification.id)
                      ? 'bg-gray-50 dark:bg-gray-800/70 hover:bg-gray-100 dark:hover:bg-gray-700'
                      : 'bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-800/50 border-l-4 border-blue-500 shadow-inner'
                  ]"
                >
                  <!-- Indicator -->
                  <div class="mt-2">
                    <span
                      class="inline-block h-3 w-3 rounded-full"
                      :class="isRead(notification.id) ? 'bg-gray-400' : 'bg-blue-500 animate-pulse'"
                    ></span>
                  </div>

                  <!-- Content -->
                  <div class="flex-1">
                    <h4
                      class="font-medium"
                      :class="isRead(notification.id) ? 'text-gray-900 dark:text-gray-100' : 'text-blue-800 dark:text-blue-300'"
                    >
                      {{ notification.subject }}
                    </h4>
                    <p
                      class="text-sm"
                      :class="isRead(notification.id) ? 'text-gray-700 dark:text-gray-300' : 'text-blue-900 dark:text-blue-200 font-medium'"
                    >
                      {{ truncate(notification.body, 100) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ formatDateTime(notification.created_at) }}
                    </p>
                  </div>
                </div>
              </Link>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
