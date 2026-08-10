<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useStringsStore } from '../stores/strings';
import { useTinyMceEditor } from '../utils/tinyMceEditor';

const props = defineProps<{
  modelValue: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const stringsStore = useStringsStore();
const textareaRef = ref<HTMLTextAreaElement | null>(null);

// Use the TinyMCE editor composable
const {
  getTinyMceEditor,
  editorInitialized,
  initEditor,
  setContent,
  getContent,
  removeEditor
} = useTinyMceEditor('description');

// Method to set up TinyMCE event listeners
const setupEditorEventListeners = () => {
  // Set up TinyMCE change event listener after initialization
  const intervalId = setInterval(() => {
    const editor = getTinyMceEditor('description');
    if (editorInitialized.value && editor) {

      // Listen for TinyMCE change events
      editor.on('change', () => {
        const content = getContent();
        emit('update:modelValue', content);
      });

      // Also listen for input events as a fallback
      editor.on('input', () => {
        const content = getContent();
        emit('update:modelValue', content);
      });
      clearInterval(intervalId);
    }
  }, 500);
};

// Initialize editor when component is mounted
onMounted(async () => {
  await initEditor();
  setContent(props.modelValue);
  setupEditorEventListeners();
});

// Cleanup when component is unmounted
onBeforeUnmount(() => {
  removeEditor();
});
</script>

<template>
  <div class="mb-3">
    <label for="description" class="form-label">{{ stringsStore.getString('description') }}</label>
    <textarea ref="textareaRef" id="description" :value="modelValue" class="form-control" rows="4"
      :placeholder="stringsStore.getString('entercohortdescription')"></textarea>
    <div class="form-text">{{ stringsStore.getString('cohortdescription') }}</div>
  </div>
</template>