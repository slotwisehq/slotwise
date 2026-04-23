<script setup lang="ts">
import type { BookingStaff, BookingTenant } from '@/types/booking'
import { selectSlot } from '@/actions/App/Http/Controllers/Booking/BookingController'
import BookingLayout from '@/components/booking/BookingLayout.vue'
import StaffCard from '@/components/booking/StaffCard.vue'

defineProps<{
  tenant: BookingTenant
  service: { id: number, name: string }
  staff: BookingStaff[]
}>()
</script>

<template>
  <BookingLayout :tenant="tenant">
    <h1 class="mb-2 text-2xl font-bold text-gray-900">
      Choose a team member
    </h1>
    <p class="mb-6 text-gray-500">
      for {{ service.name }}
    </p>
    <div class="grid gap-3">
      <StaffCard
        v-for="member in staff"
        :key="member.id"
        :staff="member"
        :href="selectSlot.url({ tenant, service, staff: member })"
      />
    </div>
  </BookingLayout>
</template>
