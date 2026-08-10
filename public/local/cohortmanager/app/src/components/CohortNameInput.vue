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
    <label for="name" class="form-label">{{ stringsStore.getString('cohortname') }} <span class="text-danger">*</span></label>
    <input
      id="name"
      :value="modelValue"
      @input="updateValue"
      type="text"
      class="form-control"
      :placeholder="stringsStore.getString('entercohortname')"
      required
    />
    <div class="form-text">{{ stringsStore.getString('cohortnamedescription') }}</div>
  </div>
</template>