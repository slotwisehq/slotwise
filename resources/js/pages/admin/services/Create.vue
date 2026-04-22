<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { store } from '@/routes/admin/services'

defineOptions({ layout: AdminLayout })
</script>

<template>
  <div class="mx-auto max-w-lg">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">
      New Service
    </h1>

    <Form
      v-slot="{ errors, processing }"
      v-bind="store.form()"
      class="space-y-5 rounded-xl bg-white p-6 shadow-sm"
    >
      <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input
          id="name"
          name="name"
          type="text"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
          :class="{ 'border-red-500': errors.name }"
        >
        <p v-if="errors.name" class="mt-1 text-xs text-red-600">
          {{ errors.name }}
        </p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
          <input
            id="duration_minutes"
            name="duration_minutes"
            type="number"
            min="5"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
            :class="{ 'border-red-500': errors.duration_minutes }"
          >
          <p v-if="errors.duration_minutes" class="mt-1 text-xs text-red-600">
            {{ errors.duration_minutes }}
          </p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Price (€)</label>
          <input
            id="price"
            name="price"
            type="number"
            step="0.01"
            min="0"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
            :class="{ 'border-red-500': errors.price }"
          >
          <p v-if="errors.price" class="mt-1 text-xs text-red-600">
            {{ errors.price }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300">
        <label for="is_active" class="text-sm font-medium text-gray-700">Active (visible to customers)</label>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="submit"
          :disabled="processing"
          class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          Create
        </button>
        <a href="/admin/services" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
      </div>
    </Form>
  </div>
</template>
