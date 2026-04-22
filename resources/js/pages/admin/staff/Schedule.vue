<script setup lang="ts">
import type { AdminScheduleDay } from '@/types/admin'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  staff: { id: number; name: string }
  days: AdminScheduleDay[]
}>()

const DAY_NAMES = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const form = useForm({
  days: props.days.map(d => ({ ...d })),
})

function submit() {
  form.patch(`/admin/staff/${props.staff.id}/schedule`)
}
</script>

<template>
  <div class="mx-auto max-w-xl">
    <h1 class="mb-1 text-2xl font-bold text-gray-900">Schedule</h1>
    <p class="mb-6 text-sm text-gray-500">{{ staff.name }}</p>

    <form class="rounded-xl bg-white p-6 shadow-sm" @submit.prevent="submit">
      <div class="space-y-3">
        <div
          v-for="(day, index) in form.days"
          :key="day.day_of_week"
          class="grid grid-cols-[120px_1fr_1fr] items-center gap-4"
        >
          <!-- Enable toggle -->
          <label class="flex items-center gap-2">
            <input v-model="day.enabled" type="checkbox" class="rounded border-gray-300">
            <span class="text-sm font-medium text-gray-700">{{ DAY_NAMES[day.day_of_week] }}</span>
          </label>

          <!-- Start time -->
          <div>
            <input
              v-model="day.start_time"
              type="time"
              :disabled="!day.enabled"
              class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
              :class="{ 'border-red-500': form.errors[`days.${index}.start_time`] }"
            >
            <p v-if="form.errors[`days.${index}.start_time`]" class="mt-0.5 text-xs text-red-600">
              {{ form.errors[`days.${index}.start_time`] }}
            </p>
          </div>

          <!-- End time -->
          <div>
            <input
              v-model="day.end_time"
              type="time"
              :disabled="!day.enabled"
              class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
              :class="{ 'border-red-500': form.errors[`days.${index}.end_time`] }"
            >
            <p v-if="form.errors[`days.${index}.end_time`]" class="mt-0.5 text-xs text-red-600">
              {{ form.errors[`days.${index}.end_time`] }}
            </p>
          </div>
        </div>
      </div>

      <div class="mt-6 flex items-center gap-3">
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          Save schedule
        </button>
        <a :href="`/admin/staff/${staff.id}/edit`" class="text-sm text-gray-500 hover:text-gray-700">Back to staff</a>
      </div>
    </form>
  </div>
</template>
