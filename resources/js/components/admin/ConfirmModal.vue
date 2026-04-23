<script setup lang="ts">
withDefaults(defineProps<{
  open: boolean
  title: string
  message: string
  confirmLabel?: string
  variant?: 'danger' | 'default'
}>(), {
  confirmLabel: 'Confirm',
  variant: 'danger',
})

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        @keydown.esc="emit('cancel')"
      >
        <div class="absolute inset-0 bg-black/50" @click="emit('cancel')" />

        <div class="relative w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
          <h2 class="mb-1 text-base font-semibold text-gray-900">
            {{ title }}
          </h2>
          <p class="mb-6 text-sm text-gray-500">
            {{ message }}
          </p>

          <div class="flex justify-end gap-3">
            <button
              type="button"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              @click="emit('cancel')"
            >
              Cancel
            </button>
            <button
              type="button"
              class="rounded-lg px-4 py-2 text-sm font-medium text-white"
              :class="variant === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700'"
              @click="emit('confirm')"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
