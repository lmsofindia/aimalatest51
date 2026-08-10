<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { useStringsStore } from '../stores/strings';
import { useAppStore } from '../stores/app';
import Notification from 'core/notification';

// Initialize stores
const stringsStore = useStringsStore();
const appStore = useAppStore();

// Props
const props = defineProps<{
  modelValue: string;
}>();

// Emits
const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

// Computed properties
const selectedTheme = computed({
  get: () => props.modelValue,
  set: (value: string) => emit('update:modelValue', value)
});

// Theme options
const themeOptions = ref<Array<{ value: string, label: string }>>([]);
const error = ref<string | null>(null);

// Fetch theme options from external API
const fetchThemeOptions = async () => {
  try {
    error.value = null;

    // Call the external API to get theme list using Moodle AJAX
    const data = appStore.getThemeList();

    // Transform the response to match the expected format
    themeOptions.value = data.map((item: any) => ({
      value: item.value,
      label: item.label
    }));

  } catch (err) {
    Notification.exception(err);
  }
};

// Load theme options when component is mounted
onMounted(() => {
  fetchThemeOptions();
});
</script>

<template>
  <div class="mb-3">
    <label for="theme" class="form-label">{{ stringsStore.getString('theme') }}</label>

    <div v-if="error" class="alert alert-warning py-2 small">
      {{ error }}
    </div>

    <select v-else id="theme" v-model="selectedTheme" class="custom-select">
      <option value="">-</option>
      <option v-for="option in themeOptions" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>

    <div class="form-text">{{ stringsStore.getString('themedescription') }}</div>
  </div>
</template>

<style scoped></style>