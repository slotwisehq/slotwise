<script setup lang="ts">
import type { AdminStaff } from '@/types/admin'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{ staff?: AdminStaff }>()

const form = useForm({
  name: props.staff?.name ?? '',
  bio: props.staff?.bio ?? '',
  avatar: null as File | null,
})

const avatarPreview = ref<string | null>(props.staff?.avatar_url ?? null)

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) {
    return
  }
  form.avatar = file
  avatarPreview.value = URL.createObjectURL(file)
}

function submit() {
  if (props.staff) {
    form.post(`/admin/staff/${props.staff.id}?_method=PUT`, {
      forceFormData: true,
    })
  }
  else {
    form.post('/admin/staff', { forceFormData: true })
  }
}
</script>

<template>
  <div class="mx-auto max-w-lg">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">
      {{ staff ? 'Edit Staff Member' : 'New Staff Member' }}
    </h1>

    <form class="space-y-5 rounded-xl bg-white p-6 shadow-sm" @submit.prevent="submit">
      <!-- Avatar -->
      <div class="flex items-center gap-4">
        <div class="h-16 w-16 overflow-hidden rounded-full bg-gray-100">
          <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar preview" class="h-full w-full object-cover">
          <div v-else class="flex h-full w-full items-center justify-center text-2xl font-semibold text-gray-400">
            {{ form.name.charAt(0) || '?' }}
          </div>
        </div>
        <div>
          <label class="cursor-pointer rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">
            Upload photo
            <input type="file" accept="image/*" class="sr-only" @change="onFileChange">
          </label>
          <p v-if="form.errors.avatar" class="mt-1 text-xs text-red-600">
            {{ form.errors.avatar }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            JPG, PNG, GIF — max 2 MB
          </p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input
          v-model="form.name"
          type="text"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
          :class="{ 'border-red-500': form.errors.name }"
        >
        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
          {{ form.errors.name }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Bio</label>
        <textarea
          v-model="form.bio"
          rows="3"
          maxlength="500"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
          :class="{ 'border-red-500': form.errors.bio }"
        />
        <p v-if="form.errors.bio" class="mt-1 text-xs text-red-600">
          {{ form.errors.bio }}
        </p>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ staff ? 'Update' : 'Create' }}
        </button>
        <a href="/admin/staff" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
      </div>
    </form>
  </div>
</template>
