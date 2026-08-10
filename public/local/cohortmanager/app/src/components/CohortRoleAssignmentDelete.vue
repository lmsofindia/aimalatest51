<script setup lang="ts">
import { ref } from 'vue';
import { deleteCohortRoleAssignment } from '../utils/moodle';
import { useStringsStore } from '../stores/strings';
import { add } from 'core/toast';
import { deleteCancel } from 'core/notification';
import type { CohortRoleAssignment } from '../types/interfaces';
import Notification from 'core/notification';

const emit = defineEmits<{
    (e: 'deleted:assignment'): void;
}>();

interface Props {
    assignment: CohortRoleAssignment;
}

const props = defineProps<Props>();

const loading = ref(false);
const stringsStore = useStringsStore();

const deleteAssignment = async () => {
    const confirmationMessage = stringsStore.getString('deleteassignmentconfirmation');

    try {
        await deleteCancel(
            stringsStore.getString('deleteassignment'),
            confirmationMessage,
            stringsStore.getString('delete'),
            async () => {
                try {
                    loading.value = true;
                    await deleteCohortRoleAssignment({ id: props.assignment.id });
                    add(stringsStore.getString('cohortroleassignmentdeleted'), { type: 'success' });
                    emit('deleted:assignment');
                } catch (err) {
                    Notification.exception(err);
                } finally {
                    loading.value = false;
                }
            },
            () => {}
        );
    } catch (err) {
        Notification.exception(err);
    }
};
</script>

<template>
    <button class="btn btn-sm btn-danger" @click="deleteAssignment" :disabled="loading"
        :title="stringsStore.getString('deletethisassignment')">
        <i v-if="!loading" class="fa fa-trash"></i>
        <i v-else class="fa fa-spinner fa-spin"></i>
    </button>
</template>
