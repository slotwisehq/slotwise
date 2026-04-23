<script setup lang="ts">
import type { BookingSlot, BookingTenant } from '@/types/booking'
import { router, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { showCustomerForm } from '@/actions/App/Http/Controllers/Booking/BookingController'
import BookingLayout from '@/components/booking/BookingLayout.vue'
import SlotGrid from '@/components/booking/SlotGrid.vue'

const props = defineProps<{
  tenant: BookingTenant
  service: { id: number, name: string, duration_minutes: number }
  staff: { id: number, name: string }
  date: string
  slots: BookingSlot[]
}>()

const page = usePage()
const loadingSlots = ref(false)
const selectedStartsAt = ref<string | null>(null)

function changeDate(date: string) {
  loadingSlots.value = true
  router.reload({
    data: { date },
    only: ['slots', 'date'],
    onFinish: () => {
      loadingSlots.value = false
    },
  })
}

function selectSlot(startsAt: string) {
  selectedStartsAt.value = startsAt
  router.visit(
    showCustomerForm.url({
      tenant: props.tenant,
      service: props.service,
      staff: props.staff,
    }),
    {
      data: { starts_at: startsAt },
    },
  )
}
</script>

<template>
  <BookingLayout :tenant="tenant">
    <div
      v-if="page.flash.error"
      class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
      {{ page.flash.error }}
    </div>
    <h1 class="mb-1 text-2xl font-bold text-gray-900">
      Pick a time
    </h1>
    <p class="mb-6 text-gray-500">
      {{ service.name }} · {{ service.duration_minutes }} min · with
      {{ staff.name }}
    </p>

    <div class="mb-4 flex items-center gap-2">
      <button
        type="button"
        class="rounded border border-gray-200 bg-white px-3 py-1.5 text-sm hover:bg-gray-50"
        @click="changeDate(
          new Date(new Date(date).getTime() - 86400000)
            .toISOString()
            .slice(0, 10),
        )"
      >
        ‹
      </button>
      <input
        type="date"
        :value="date"
        class="rounded border border-gray-200 px-3 py-1.5 text-sm"
        @change="changeDate(($event.target as HTMLInputElement).value)"
      >
      <button
        type="button"
        class="rounded border border-gray-200 bg-white px-3 py-1.5 text-sm hover:bg-gray-50"
        @click="changeDate(new Date(new Date(date).getTime() + 86400000)
          .toISOString()
          .slice(0, 10),
        )"
      >
        ›
      </button>
    </div>

    <SlotGrid
      :slots="slots"
      :loading="loadingSlots"
      :selected-starts-at="selectedStartsAt ?? undefined"
      @select="selectSlot"
    />
  </BookingLayout>
</template>
