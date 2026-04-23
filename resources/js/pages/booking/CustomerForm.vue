<script setup lang="ts">
import type { BookingTenant } from '@/types/booking'
import { useForm } from '@inertiajs/vue3'
import { store } from '@/actions/App/Http/Controllers/Booking/BookingController'
import BookingLayout from '@/components/booking/BookingLayout.vue'

const props = defineProps<{
  tenant: BookingTenant
  service: { id: number, name: string, duration_minutes: number }
  staff: { id: number, name: string }
  // eslint-disable-next-line vue/prop-name-casing
  starts_at: string
}>()

const form = useForm({
  starts_at: props.starts_at,
  customer_name: '',
  customer_email: '',
  customer_phone: '',
})

function submit() {
  form.post(
    store.url({
      tenant: props.tenant,
      service: props.service,
      staff: props.staff,
    }),
    {
      replace: true,
    },
  )
}
</script>

<template>
  <BookingLayout :tenant="tenant">
    <h1 class="mb-2 text-2xl font-bold text-gray-900">
      Your details
    </h1>
    <p class="mb-6 text-gray-500">
      {{ service.name }} · with {{ staff.name }}
    </p>

    <form class="space-y-4" @submit.prevent="submit">
      <input type="hidden" name="starts_at" :value="form.starts_at">

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">
          Full name
        </label>
        <input
          v-model="form.customer_name"
          type="text"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
          required
        >
        <p
          v-if="form.errors.customer_name"
          class="mt-1 text-xs text-red-600"
        >
          {{ form.errors.customer_name }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">
          Email
        </label>
        <input
          v-model="form.customer_email"
          type="email"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
          required
        >
        <p
          v-if="form.errors.customer_email"
          class="mt-1 text-xs text-red-600"
        >
          {{ form.errors.customer_email }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">
          Phone
          <span class="text-gray-400">(optional)</span>
        </label>
        <input
          v-model="form.customer_phone"
          type="tel"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
        >
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
      >
        {{ form.processing ? 'Booking...' : 'Confirm booking' }}
      </button>
    </form>
  </BookingLayout>
</template>
