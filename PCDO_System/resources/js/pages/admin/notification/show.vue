<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps<{
  notification: {
    id: number;
    subject: string;
    body: string;
    type: string;
    created_at: string;
    schedule?: {
      coop_program?: {
        email?: string;
        cooperative?: {
          name: string;
        };
      };
    };
  };
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Notifications', href: '/admin/notifications' },
  { title: props.notification.subject, href: `/admin/notifications/${props.notification.id}` },
]

// Detect due status from message
const getDueStatus = (body: string) => {
  if (body.toLowerCase().includes("overdue")) return "overdue"
  if (body.toLowerCase().includes("due today")) return "dueToday"
  if (body.toLowerCase().includes("before due")) return "beforeDue"
  return null
}

const canDownload = computed(() => {
  return ["overdue", "due_today", "before_due"].includes(
    props.notification.type
  );
});

</script>

<template>

  <Head :title="notification.subject" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="bg-gray-100/90 dark:bg-gray-900 min-h-screen">
      <div class="max-w-5xl mx-auto p-4 sm:p-6 md:p-10 space-y-6">
        <!-- Card -->
        <div
          class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">

          <!-- Header -->
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-6 sm:px-10 md:px-14 py-6 border-b border-gray-200 dark:border-gray-700
                   bg-gradient-to-r from-indigo-500/50 to-purple-500/50 dark:from-indigo-900/30 dark:to-purple-900/30">
            <div class="flex items-center space-x-4">
              <div
                class="p-3 rounded-full bg-indigo-200 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-300 text-xl shrink-0">
                💳
              </div>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-gray-100 break-words leading-snug">
                {{ notification.subject }}
              </h2>
            </div>

            <span class="px-4 py-1.5 rounded-full text-sm font-semibold uppercase tracking-wide" :class="{
              'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300': notification.type === 'info',
              'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300': notification.type === 'success',
              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300': notification.type === 'warning',
              'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300': notification.type === 'error',
            }">
              {{ notification.type }}
            </span>
          </div>

          <!-- Meta Info -->
          <div class="px-6 sm:px-10 md:px-14 py-5 text-sm text-gray-500 dark:text-gray-400 space-y-3">

            <!-- First row: Date + Download -->
            <div class="flex items-center justify-between flex-wrap gap-3">
              <p class="whitespace-nowrap">
                📅 {{ new Date(notification.created_at).toLocaleString() }}
              </p>

              <a v-if="canDownload" :href="`/notifications/${notification.id}/download`" class="px-4 py-2 text-sm sm:text-base bg-indigo-600 text-white rounded-lg 
             shadow hover:bg-indigo-700 transition whitespace-nowrap">
                Download PDF
              </a>
            </div>

            <!-- Second row: Cooperative info -->
            <p v-if="notification.schedule?.coop_program?.cooperative" class="flex items-start gap-1">
              🏢
              <span>
                Cooperative:
                <span class="font-medium text-gray-800 dark:text-gray-200">
                  {{ notification.schedule.coop_program.cooperative.name }}
                </span>
              </span>
            </p>

            <!-- Third row: Email info -->
            <p v-if="notification.schedule?.coop_program?.email" class="flex items-start gap-1 break-all">
              ✉️
              <span>
                Send to:
                <span class="font-medium text-indigo-600 dark:text-indigo-300 break-all">
                  {{ notification.schedule.coop_program.email }}
                </span>
              </span>
            </p>
          </div>

          <!-- Status Highlight -->
          <div v-if="getDueStatus(notification.body)"
            class="px-6 sm:px-10 md:px-14 py-4 text-center text-lg sm:text-xl font-bold" :class="{
              'bg-red-200 text-red-700 dark:bg-red-900/30 dark:text-red-300': getDueStatus(notification.body) === 'overdue',
              'bg-yellow-200 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300': getDueStatus(notification.body) === 'dueToday',
              'bg-green-200 text-green-700 dark:bg-green-900/30 dark:text-green-300': getDueStatus(notification.body) === 'beforeDue',
            }">
            {{ getDueStatus(notification.body) === 'overdue' ? '⚠️ Overdue Payment' :
              getDueStatus(notification.body) === 'dueToday' ? '📌 Payment Due Today' :
                '✅ Upcoming Payment' }}
          </div>

          <hr class="my-3 border-t border-gray-300 dark:border-gray-700" />

          <!-- Letter Body -->
          <div
            class="px-6 sm:px-10 md:px-14 py-4 text-gray-700 dark:text-gray-300 leading-relaxed space-y-6 text-base sm:text-lg">
            <div class="space-y-3 bg-gray-200/50 dark:bg-gray-700/50 rounded-lg p-4 sm:p-5 overflow-x-auto">
              <p
                v-for="(line, i) in (notification.body || '').split('\n')"
                :key="i"
                class="text-base sm:text-lg break-words"
                :class="{
                  'text-green-600 dark:text-green-400 font-semibold': line.toLowerCase().includes('amount to pay') && !line.toLowerCase().includes('penalty'),
                  'text-red-600 dark:text-red-400 font-semibold': line.toLowerCase().includes('penalty'),
                }"
              >
                {{ line }}
              </p>
            </div>

            <p class="pb-5 text-sm sm:text-base text-right">
              Thank you,<br />
              <span class="font-medium">PCDO Management</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
