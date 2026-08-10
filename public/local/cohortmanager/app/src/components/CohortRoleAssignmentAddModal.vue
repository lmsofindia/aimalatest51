<script setup lang="ts">
import { useStringsStore } from '../stores/strings';
import { showAddCohortRoleAssignmentForm } from '../utils/moodle';
import { add } from 'core/toast';

const props = defineProps<{
    cohortid: number;
}>();

const emit = defineEmits<{
    'added:assignment': [value: boolean];
}>();

const stringsStore = useStringsStore();

const openModal = async () => {
    const modalForm = showAddCohortRoleAssignmentForm(
        props.cohortid,
        stringsStore.getString('addroleassignment'),
        stringsStore.getString('save'),
    );

    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (event: any) => {
        if (event.detail) {
            add(stringsStore.getString('cohortroleassignmentcreated'), {
                type: 'success'
            });
            emit('added:assignment', event.detail);
        }
    });

    await modalForm.show();
}
</script>

<template>
    <button class="btn btn-primary" @click="openModal" :title="stringsStore.getString('addroleassignment')">{{ stringsStore.getString('addroleassignment') }}</button>
</template>
