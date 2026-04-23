<script setup lang="ts">
import type { AdminService } from '@/types/admin'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import ConfirmModal from '@/components/admin/ConfirmModal.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })
defineProps<{ services: AdminService[] }>()

const serviceToDelete = ref<AdminService | null>(null)

function toggleActive(service: AdminService) {
  router.patch(`/admin/services/${service.id}/toggle`, {}, {
    only: ['services'],
    preserveScroll: true,
  })
}

function confirmDelete(service: AdminService) {
  serviceToDelete.value = service
}

function handleDeleteConfirmed() {
  if (!serviceToDelete.value) {
    return
  }
  router.delete(`/admin/services/${serviceToDelete.value.id}`, {
    onFinish: () => { serviceToDelete.value = null },
  })
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">
        Services
      </h1>
      <Link
        href="/admin/services/create"
        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
      >
        Add service
      </Link>
    </div>

    <div v-if="services.length === 0" class="rounded-xl bg-white p-8 text-center text-gray-500 shadow-sm">
      No services yet.
    </div>

    <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm">
      <table class="min-w-full divide-y divide-gray-100">
        <thead>
          <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
            <th class="px-4 py-3">
              Name
            </th>
            <th class="px-4 py-3">
              Duration
            </th>
            <th class="px-4 py-3">
              Price
            </th>
            <th class="px-4 py-3">
              Active
            </th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="service in services" :key="service.id">
            <td class="px-4 py-3 text-sm font-medium text-gray-900">
              {{ service.name }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ service.duration_minutes }} min
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              €{{ service.price }}
            </td>
            <td class="px-4 py-3">
              <button
                type="button"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none"
                :class="service.is_active ? 'bg-indigo-600' : 'bg-gray-300'"
                @click="toggleActive(service)"
              >
                <span
                  class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                  :class="service.is_active ? 'translate-x-4' : 'translate-x-1'"
                />
              </button>
            </td>
            <td class="px-4 py-3 text-right">
              <Link
                :href="`/admin/services/${service.id}/edit`"
                class="mr-3 text-sm text-indigo-600 hover:text-indigo-800"
              >
                Edit
              </Link>
              <button
                type="button"
                class="text-sm text-red-600 hover:text-red-800"
                @click="confirmDelete(service)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <ConfirmModal
      :open="serviceToDelete !== null"
      title="Delete service"
      :message="serviceToDelete ? `Delete &quot;${serviceToDelete.name}&quot;? This cannot be undone.` : ''"
      confirm-label="Delete"
      @confirm="handleDeleteConfirmed"
      @cancel="serviceToDelete = null"
    />
  </div>
</template>
