<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue';
import { useStringsStore } from '../stores/strings';
import { getCustomfieldDynamicForm } from '../utils/moodle';
import Notification from 'core/notification';

const props = defineProps<{
  cohortid: number;
  submitting: boolean
}>();

const emit = defineEmits<{
  'submit:customFields:result': [success: boolean, message: string];
}>();

const stringsStore = useStringsStore();

// Reactive references
const form = ref();
const formHtml = ref<string>('');
const isLoading = ref<boolean>(false);
const formContainer = ref<HTMLElement | null>(null);

// Load custom field form
const loadCustomFieldForm = async () => {
  if (!props.cohortid) return;

  try {
    isLoading.value = true;
    form.value = await getCustomfieldDynamicForm('#custom-field-form');
    form.value.load({
      id: props.cohortid
    });

    form.value.addEventListener(form.value.events.FORM_SUBMITTED, (event: Event) => {
      event.preventDefault();
      emit('submit:customFields:result', true, stringsStore.getString('customfieldssaved'));
    });

    form.value.addEventListener(form.value.events.CLIENT_VALIDATION_ERROR, (event: Event) => {
      event.preventDefault();
      emit('submit:customFields:result', false, stringsStore.getString('customfieldnotvalid'));
    });

    form.value.addEventListener(form.value.events.SERVER_VALIDATION_ERROR, (event: Event) => {
      event.preventDefault();
      emit('submit:customFields:result', false, stringsStore.getString('customfieldnotvalid'));
    });

    await nextTick();
    formContainer.value = document.querySelector('#custom-field-form form');
  } catch (error) {
    Notification.exception(error);
  } finally {
    isLoading.value = false;
  }
};

// Load form on component mount
onMounted(() => {
  if (props.cohortid) {
    loadCustomFieldForm();
  }
});

// Watch for submitting prop changes
watch(() => props.submitting, async (newSubmitting) => {
  if (newSubmitting && form.value) {
    try {
      await form.value.submitFormAjax();
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      emit('submit:customFields:result', false, stringsStore.getString('errorprocessingcustomfields') + ': ' + errorMessage);
    }
  }
});

</script>

<template>
  <div id="custom-field-form"></div>
  <div v-if="formHtml" class="border-top pt-3">
    <h2 class="h4">{{ stringsStore.getString('customfields') }}</h2>

    <div v-if="isLoading" class="text-center py-3">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

  </div>
</template>