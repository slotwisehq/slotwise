<script setup lang="ts">
import type { AdminStaff } from '@/types/admin'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })
defineProps<{ staff: AdminStaff[] }>()

function confirmDelete(member: AdminStaff) {
  if (confirm(`Remove "${member.name}"?`)) {
    router.delete(`/admin/staff/${member.id}`)
  }
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Staff</h1>
      <Link
        href="/admin/staff/create"
        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
      >
        Add staff
      </Link>
    </div>

    <div v-if="staff.length === 0" class="rounded-xl bg-white p-8 text-center text-gray-500 shadow-sm">
      No staff yet.
    </div>

    <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm">
      <table class="min-w-full divide-y divide-gray-100">
        <thead>
          <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Bio</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="member in staff" :key="member.id">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img
                  v-if="member.avatar_url"
                  :src="member.avatar_url"
                  :alt="member.name"
                  class="h-8 w-8 rounded-full object-cover"
                >
                <div
                  v-else
                  class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700"
                >
                  {{ member.name.charAt(0) }}
                </div>
                <span class="text-sm font-medium text-gray-900">{{ member.name }}</span>
              </div>
            </td>
            <td class="max-w-xs px-4 py-3 text-sm text-gray-500">
              <span class="line-clamp-1">{{ member.bio ?? '—' }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <Link
                :href="`/admin/staff/${member.id}/schedule`"
                class="mr-3 text-sm text-gray-500 hover:text-gray-700"
              >
                Schedule
              </Link>
              <Link
                :href="`/admin/staff/${member.id}/edit`"
                class="mr-3 text-sm text-indigo-600 hover:text-indigo-800"
              >
                Edit
              </Link>
              <button
                type="button"
                class="text-sm text-red-600 hover:text-red-800"
                @click="confirmDelete(member)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
