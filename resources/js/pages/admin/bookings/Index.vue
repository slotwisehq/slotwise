<script setup lang="ts">
import type { AdminBookingFilters, AdminStaff, PaginatedAppointments } from '@/types/admin'
import { Link, router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  appointments: PaginatedAppointments
  staff: Pick<AdminStaff, 'id' | 'name'>[]
  filters: AdminBookingFilters
}>()

const filters = reactive({ ...props.filters })

watch(filters, (val) => {
  router.get('/admin/bookings', val, {
    preserveState: true,
    replace: true,
  })
})

function formatDatetime(isoStr: string): string {
  return new Date(isoStr).toLocaleString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">
      Bookings
    </h1>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap gap-3 rounded-xl bg-white p-4 shadow-sm">
      <input v-model="filters.date_from" type="date" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none">
      <input v-model="filters.date_to" type="date" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none">
      <select v-model="filters.staff_id" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none">
        <option :value="null">
          All staff
        </option>
        <option v-for="s in staff" :key="s.id" :value="s.id">
          {{ s.name }}
        </option>
      </select>
      <select v-model="filters.status" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none">
        <option :value="null">
          All statuses
        </option>
        <option value="pending">
          Pending
        </option>
        <option value="confirmed">
          Confirmed
        </option>
        <option value="cancelled">
          Cancelled
        </option>
        <option value="no_show">
          No Show
        </option>
      </select>
    </div>

    <div v-if="appointments.data.length === 0" class="rounded-xl bg-white p-8 text-center text-gray-500 shadow-sm">
      No bookings found.
    </div>

    <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm">
      <table class="min-w-full divide-y divide-gray-100">
        <thead>
          <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
            <th class="px-4 py-3">
              Date/Time
            </th>
            <th class="px-4 py-3">
              Customer
            </th>
            <th class="px-4 py-3">
              Service
            </th>
            <th class="px-4 py-3">
              Staff
            </th>
            <th class="px-4 py-3">
              Status
            </th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="appt in appointments.data" :key="appt.id">
            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
              {{ formatDatetime(appt.starts_at) }}
            </td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">
              {{ appt.customer.name }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ appt.service.name }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ appt.staff.name }}
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="appt.status" />
            </td>
            <td class="px-4 py-3 text-right">
              <Link :href="`/admin/bookings/${appt.id}`" class="text-sm text-indigo-600 hover:text-indigo-800">
                View
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="appointments.last_page > 1" class="mt-4 flex justify-center gap-1">
      <template v-for="link in appointments.links" :key="link.label">
        <Link
          v-if="link.url"
          :href="link.url"
          class="rounded px-3 py-1 text-sm"
          :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
        >
          <span v-html="link.label" />
        </Link>
        <span v-else class="rounded px-3 py-1 text-sm text-gray-400" v-html="link.label" />
      </template>
    </div>
  </div>
</template>
