<script setup lang="ts">
import { useStringsStore } from '../stores/strings';

defineProps<{
  modelValue: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const stringsStore = useStringsStore();

const updateValue = (e: Event) => {
  const target = e.target as HTMLInputElement;
  emit('update:modelValue', target.value);
};
</script>

<template>
  <div class="mb-3">
    <label for="idnumber" class="form-label">{{ stringsStore.getString('idnumber') }} <span class="text-danger">*</span></label>
    <input
      id="idnumber"
      :value="modelValue"
      @input="updateValue"
      type="text"
      class="form-control"
      :placeholder="stringsStore.getString('enteridnumber')"
      required
    />
    <div class="form-text">{{ stringsStore.getString('idnumberdescription') }}</div>
  </div>
</template>