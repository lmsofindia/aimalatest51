<script setup lang="ts">
import { ref } from 'vue';
import { deleteCohortEnrolInstance } from '../utils/moodle';
import { useStringsStore } from '../stores/strings';
import { add } from 'core/toast';
import { deleteCancel } from 'core/notification';
import type { CohortEnrolInstance } from '../types/interfaces';
import Notification from 'core/notification';

// Emits
const emit = defineEmits<{
  (e: 'deleted:enrolinstance'): void;
}>();

// Props
interface Props {
  enrolinstance: CohortEnrolInstance;
}

const props = defineProps<Props>();

// State
const loading = ref(false);
const stringsStore = useStringsStore();

// Delete enrol instance function
const deleteEnrolInstance = async () => {
  const courseName = props.enrolinstance.coursefullname || props.enrolinstance.courseshortname;
  const enroledCount = props.enrolinstance.enroled || 0;
  
  // Build confirmation message with enrolment count warning
  let confirmationMessage = stringsStore.getString('deleteenrolinstanceconfirmation', courseName);
  if (enroledCount > 0) {
    const warningMessage = stringsStore.getString('deleteenrolinstancewarning', enroledCount.toString());
    confirmationMessage += '<br><br><strong>' + warningMessage + '</strong>';
  }
  
  const deleteButtonText = stringsStore.getString('delete');
  const modalTitle = stringsStore.getString('deleteenrolinstance');

  try {
    await deleteCancel(
      modalTitle,
      confirmationMessage,
      deleteButtonText,
      async () => {
        try {
          loading.value = true;
          await deleteCohortEnrolInstance({
            enrolinstanceid: props.enrolinstance.id
          });

          // Show success toast
          add(stringsStore.getString('enrolinstancedeletedsuccessfully'), {
            type: 'success'
          });

          // Emit event to parent
          emit('deleted:enrolinstance');
        } catch (err) {
          Notification.exception(err);
        } finally {
          loading.value = false;
        }
      },
      () => {
        // User cancelled - do nothing
      }
    );
  } catch (err) {
    Notification.exception(err);
  }
};

// Expose function for external access
defineExpose({
  deleteEnrolInstance
});
</script>

<template>
  <button class="btn btn-danger btn-sm" @click="deleteEnrolInstance" :disabled="loading"
    :title="stringsStore.getString('deleteenrolinstance')">
    <i v-if="!loading" class="fa fa-trash"></i>
    <i v-else class="fa fa-spinner fa-spin"></i>
  </button>
</template>