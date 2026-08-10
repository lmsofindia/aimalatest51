<script setup lang="ts">
import { ref } from 'vue';
import { deleteCohorts } from '../utils/moodle';
import { useStringsStore } from '../stores/strings';
import { add } from 'core/toast';
import { deleteCancel } from 'core/notification';
import type { Cohort } from '../types/interfaces';
import Notification from 'core/notification';

// Props
interface Props {
  cohort: Cohort;
  onSuccess?: () => void;
  onError?: (err: any) => void;
}

const props = withDefaults(defineProps<Props>(), {
  onSuccess: () => { },
  onError: (err: any) => {
    Notification.exception(err);
  }
});

// State
const loading = ref(false);
const stringsStore = useStringsStore();

// Delete cohort function
const deleteCohort = async () => {
  const confirmationMessage = stringsStore.getString('deleteconfirmation', props.cohort.name);
  const deleteButtonText = stringsStore.getString('delete');
  const modalTitle = stringsStore.getString('deletethiscohort');

  try {
    await deleteCancel(
      modalTitle,
      confirmationMessage,
      deleteButtonText,
      async () => {
        try {
          loading.value = true;
          await deleteCohorts({
            cohortids: [props.cohort.id]
          });

          // Show success toast
          add(stringsStore.getString('cohortdeletedsuccessfully'), {
            type: 'success'
          });

          // Call success callback
          props.onSuccess();
        } catch (err) {
          props.onError(err);
        } finally {
          loading.value = false;
        }
      },
      () => {
        // User cancelled - do nothing
      }
    );
  } catch (err) {
    props.onError(err);
  }
};

// Expose function for external access
defineExpose({
  deleteCohort
});
</script>

<template>
  <button class="btn btn-danger" @click="deleteCohort" :disabled="loading"
    :title="stringsStore.getString('deletecohort')">
    <i v-if="!loading" class="fa fa-trash"></i>
    <i v-else class="fa fa-spinner fa-spin"></i>
    <span v-if="$slots.default" class="ms-2">
      <slot></slot>
    </span>
  </button>
</template>