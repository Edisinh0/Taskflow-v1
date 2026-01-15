<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 p-6 text-left align-middle shadow-xl transition-all border border-slate-200 dark:border-white/10">
              <DialogTitle as="h3" class="text-lg font-bold leading-6 text-slate-900 dark:text-white mb-4 flex items-center">
                <div class="p-2 bg-blue-50 dark:bg-blue-500/10 rounded-lg mr-3">
                  <User v-if="!isEditing" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                  <Edit2 v-else class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                {{ isEditing ? 'Editar Cliente' : 'Nuevo Cliente' }}
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="space-y-4">
                <!-- Nombre -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre / Empresa *</label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full rounded-xl border-slate-300 dark:border-white/10 dark:bg-slate-900/50 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-colors"
                    placeholder="Ej. Acme Corp"
                  />
                </div>

                <!-- Email -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email de Contacto</label>
                  <div class="relative">
                    <Mail class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    <input
                      v-model="form.email"
                      type="email"
                      class="pl-10 w-full rounded-xl border-slate-300 dark:border-white/10 dark:bg-slate-900/50 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-colors"
                      placeholder="contacto@empresa.com"
                    />
                  </div>
                </div>

                <!-- Teléfono -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
                  <div class="relative">
                    <Phone class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    <input
                      v-model="form.phone"
                      type="text"
                      class="pl-10 w-full rounded-xl border-slate-300 dark:border-white/10 dark:bg-slate-900/50 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-colors"
                      placeholder="+56 9 1234 5678"
                    />
                  </div>
                </div>

                <!-- Dirección -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dirección</label>
                  <div class="relative">
                    <MapPin class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    <input
                      v-model="form.address"
                      type="text"
                      class="pl-10 w-full rounded-xl border-slate-300 dark:border-white/10 dark:bg-slate-900/50 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-colors"
                      placeholder="Av. Providencia 1234, Santiago"
                    />
                  </div>
                </div>

                <!-- Industria -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Industria</label>
                  <div class="relative">
                    <Briefcase class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    <input
                      v-model="form.industry"
                      type="text"
                      class="pl-10 w-full rounded-xl border-slate-300 dark:border-white/10 dark:bg-slate-900/50 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-colors"
                      placeholder="Tecnología, Minería, Retail..."
                    />
                  </div>
                </div>

                <!-- SweetCRM ID (Opcional) -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    ID SweetCRM
                    <span class="text-xs text-slate-400 ml-1">(Opcional)</span>
                  </label>
                  <div class="relative">
                    <Database class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    <input
                      v-model="form.sweetcrm_id"
                      type="text"
                      class="pl-10 w-full rounded-xl border-slate-300 dark:border-white/10 dark:bg-slate-900/50 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-colors"
                      placeholder="ID externo"
                    />
                  </div>
                </div>

                <!-- Estado -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                  <div class="flex space-x-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                      <input type="radio" v-model="form.status" value="active" class="text-blue-600 focus:ring-blue-500" />
                      <span class="text-slate-700 dark:text-slate-300">Activo</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                      <input type="radio" v-model="form.status" value="inactive" class="text-blue-600 focus:ring-blue-500" />
                      <span class="text-slate-700 dark:text-slate-300">Inactivo</span>
                    </label>
                  </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-white/10 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all"
                    @click="closeModal"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                  >
                    <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
                    {{ isEditing ? 'Guardar Cambios' : 'Crear Cliente' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch } from 'vue'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'
import { User, Mail, Phone, MapPin, Briefcase, Database, Edit2, Loader2 } from 'lucide-vue-next'

const props = defineProps({
  isOpen: Boolean,
  client: Object,
  loading: Boolean
})

const emit = defineEmits(['close', 'save'])

const isEditing = ref(false)
const form = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  industry: '',
  sweetcrm_id: '',
  status: 'active'
})

// Resetear o cargar formulario cuando cambia el cliente o se abre el modal
watch(() => props.client, (newClient) => {
  if (newClient) {
    isEditing.value = true
    form.value = { ...newClient }
  } else {
    isEditing.value = false
    form.value = {
      name: '',
      email: '',
      phone: '',
      address: '',
      industry: '',
      sweetcrm_id: '',
      status: 'active'
    }
  }
}, { immediate: true })

const closeModal = () => {
  emit('close')
}

const handleSubmit = () => {
  emit('save', form.value)
}
</script>
