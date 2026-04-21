<script setup lang="ts">
import type { BookingSlot } from '@/types/booking'

defineProps<{
  slots: BookingSlot[]
  loading?: boolean
  selectedStartsAt?: string
}>()

const emit = defineEmits<{
  select: [startsAt: string]
}>()
</script>

<template>
  <div>
    <div v-if="loading" class="grid grid-cols-4 gap-2 sm:grid-cols-6">
      <div
        v-for="n in 8"
        :key="n"
        class="h-10 animate-pulse rounded-md bg-gray-200"
      />
    </div>
    <div
      v-else-if="slots.length === 0"
      class="py-8 text-center text-gray-500"
    >
      No availability for this date.
    </div>
    <div v-else class="grid grid-cols-4 gap-2 sm:grid-cols-6">
      <button
        v-for="slot in slots"
        :key="slot.starts_at"
        type="button"
        class="rounded-md border px-3 py-2 text-sm font-medium transition-colors"
        :class="[
          selectedStartsAt === slot.starts_at
            ? 'border-indigo-600 bg-indigo-600 text-white'
            : 'border-gray-200 bg-white text-gray-700 hover:border-indigo-400',
        ]"
        @click="emit('select', slot.starts_at)"
      >
        {{ slot.time }}
      </button>
    </div>
  </div>
</template>
