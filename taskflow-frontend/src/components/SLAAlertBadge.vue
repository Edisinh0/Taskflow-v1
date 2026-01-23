<template>
  <span v-if="alertType" :class="badgeClass">
    <span v-if="alertType === 'escalation'" class="flex items-center gap-1">
      <AlertOctagon :size="14" :stroke-width="2.5" />
      <span class="font-bold">CRÍTICA</span>
      <span v-if="daysOverdue" class="text-xs font-normal">
        (+{{ daysOverdue }}d)
      </span>
    </span>
    <span v-else-if="alertType === 'warning'" class="flex items-center gap-1">
      <AlertTriangle :size="14" :stroke-width="2.5" />
      <span class="font-bold">ALERTA</span>
      <span v-if="daysOverdue" class="text-xs font-normal">
        (+{{ daysOverdue }}d)
      </span>
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { AlertOctagon, AlertTriangle } from 'lucide-vue-next'

const props = defineProps({
  alertType: {
    type: String, // 'warning' | 'escalation' | null
    default: null,
    validator: (value) => [null, 'warning', 'escalation'].includes(value)
  },
  daysOverdue: {
    type: Number,
    default: 0
  }
})

const badgeClass = computed(() => {
  const baseClasses = 'px-2 py-1 rounded text-xs font-bold inline-flex items-center'

  if (props.alertType === 'escalation') {
    return `${baseClasses} bg-red-500 text-white animate-pulse shadow-lg shadow-red-500/50`
  }

  if (props.alertType === 'warning') {
    return `${baseClasses} bg-yellow-500 dark:bg-yellow-600 text-white`
  }

  return baseClasses
})
</script>

<style scoped>
/* Animación de pulso más intensa para críticas */
@keyframes pulse-intense {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.05);
  }
}

.animate-pulse {
  animation: pulse-intense 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
