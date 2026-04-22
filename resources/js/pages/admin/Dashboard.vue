<script setup lang="ts">
import type { AdminAppointmentGroup } from '@/types/admin'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })
defineProps<{ appointmentGroups: AdminAppointmentGroup[] }>()

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-GB', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })
}

function formatTime(isoStr: string): string {
  return new Date(isoStr).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">
      Upcoming Appointments
    </h1>

    <div v-if="appointmentGroups.length === 0" class="rounded-xl bg-white p-8 text-center text-gray-500 shadow-sm">
      No upcoming appointments in the next 7 days.
    </div>

    <div v-else class="space-y-6">
      <div v-for="group in appointmentGroups" :key="group.date">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
          {{ formatDate(group.date) }}
        </h2>
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
          <table class="min-w-full divide-y divide-gray-100">
            <tbody class="divide-y divide-gray-50">
              <tr v-for="appt in group.appointments" :key="appt.id">
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                  {{ formatTime(appt.starts_at) }}
                </td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                  {{ appt.customer.name }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ appt.service.name }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ appt.staff.name }}</td>
                <td class="px-4 py-3">
                  <StatusBadge :status="appt.status" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
