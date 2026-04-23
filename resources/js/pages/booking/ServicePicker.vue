<script setup lang="ts">
import type { BookingService, BookingTenant } from '@/types/booking'
import { selectStaff } from '@/actions/App/Http/Controllers/Booking/BookingController'
import BookingLayout from '@/components/booking/BookingLayout.vue'
import ServiceCard from '@/components/booking/ServiceCard.vue'

defineProps<{
  tenant: BookingTenant
  services: BookingService[]
}>()
</script>

<template>
  <BookingLayout :tenant="tenant">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">
      Choose a service
    </h1>
    <p v-if="services.length === 0" class="text-gray-500">
      No services available at this time.
    </p>
    <div class="grid gap-3">
      <ServiceCard
        v-for="service in services"
        :key="service.id"
        :service="service"
        :href="selectStaff.url({ tenant, service })"
      />
    </div>
  </BookingLayout>
</template>
