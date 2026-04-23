<script setup lang="ts">
import type { BookingAppointmentSummary, BookingTenant } from '@/types/booking'
import BookingLayout from '@/components/booking/BookingLayout.vue'

defineProps<{
  tenant: BookingTenant
  appointment: BookingAppointmentSummary
}>()

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('en-GB', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <BookingLayout :tenant="tenant">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
      <div class="mb-6 flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100"
        >
          <svg
            class="h-5 w-5 text-green-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4.5 12.75l6 6 9-13.5"
            />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">
          Booking confirmed!
        </h1>
      </div>
      <dl class="space-y-3 text-sm">
        <div class="flex justify-between">
          <dt class="text-gray-500">
            Service
          </dt>
          <dd class="font-medium text-gray-900">
            {{ appointment.service_name }}
          </dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-gray-500">
            With
          </dt>
          <dd class="font-medium text-gray-900">
            {{ appointment.staff_name }}
          </dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-gray-500">
            Date &amp; time
          </dt>
          <dd class="font-medium text-gray-900">
            {{ formatDate(appointment.starts_at) }}
          </dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-gray-500">
            Name
          </dt>
          <dd class="font-medium text-gray-900">
            {{ appointment.customer_name }}
          </dd>
        </div>
      </dl>
    </div>
  </BookingLayout>
</template>
