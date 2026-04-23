<script setup lang="ts">
import type { AdminAppointment } from '@/types/admin'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import ConfirmModal from '@/components/admin/ConfirmModal.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{ appointment: AdminAppointment }>()

const cancellable = ['pending', 'confirmed']
const showCancelModal = ref(false)

function formatDatetime(isoStr: string): string {
  return new Date(isoStr).toLocaleString('en-GB', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function handleCancelConfirmed() {
  showCancelModal.value = false
  router.patch(`/admin/bookings/${props.appointment.id}/cancel`)
}
</script>

<template>
  <div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">
        Booking Detail
      </h1>
      <StatusBadge :status="appointment.status" />
    </div>

    <div class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
      <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
        <dt class="font-medium text-gray-500">
          Date &amp; Time
        </dt>
        <dd class="text-gray-900">
          {{ formatDatetime(appointment.starts_at) }}
        </dd>

        <dt class="font-medium text-gray-500">
          Service
        </dt>
        <dd class="text-gray-900">
          {{ appointment.service.name }}
        </dd>

        <dt class="font-medium text-gray-500">
          Staff
        </dt>
        <dd class="text-gray-900">
          {{ appointment.staff.name }}
        </dd>

        <dt class="font-medium text-gray-500">
          Customer
        </dt>
        <dd class="text-gray-900">
          {{ appointment.customer.name }}
        </dd>

        <dt class="font-medium text-gray-500">
          Email
        </dt>
        <dd class="text-gray-900">
          {{ appointment.customer.email }}
        </dd>

        <dt class="font-medium text-gray-500">
          Phone
        </dt>
        <dd class="text-gray-900">
          {{ appointment.customer.phone ?? '—' }}
        </dd>

        <template v-if="appointment.notes">
          <dt class="font-medium text-gray-500">
            Notes
          </dt>
          <dd class="text-gray-900">
            {{ appointment.notes }}
          </dd>
        </template>
      </dl>

      <div class="border-t border-gray-100 pt-4">
        <button
          v-if="cancellable.includes(appointment.status)"
          type="button"
          class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
          @click="showCancelModal = true"
        >
          Cancel appointment
        </button>
        <p v-else class="text-sm text-gray-400">
          This appointment cannot be cancelled.
        </p>
      </div>
    </div>

    <div class="mt-4">
      <a href="/admin/bookings" class="text-sm text-gray-500 hover:text-gray-700">← Back to bookings</a>
    </div>

    <ConfirmModal
      :open="showCancelModal"
      title="Cancel appointment"
      message="Cancel this appointment? The customer will not be notified automatically."
      confirm-label="Cancel appointment"
      @confirm="handleCancelConfirmed"
      @cancel="showCancelModal = false"
    />
  </div>
</template>
