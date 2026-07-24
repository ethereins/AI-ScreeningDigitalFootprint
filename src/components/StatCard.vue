<template>
  <div class="bg-white rounded-lg shadow-sm border p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500">{{ title }}</p>
        <p class="text-2xl font-bold mt-1">{{ value }}</p>
      </div>
      <div :class="['p-3 rounded-lg', color]">
        <component :is="iconComponent" class="w-5 h-5 text-white" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Search, AlertTriangle, CheckCircle } from 'lucide-vue-next'

const props = defineProps({
  title: String,
  value: [Number, String],
  icon: String,
  color: String
})

const iconComponent = computed(() => {
  const icons = {
    Search: Search,
    AlertTriangle: AlertTriangle,
    CheckCircle: CheckCircle
  }
  return icons[props.icon] || Search
})
</script>