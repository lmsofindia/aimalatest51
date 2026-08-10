<script setup lang="ts">
import { ref } from 'vue';
import { useStringsStore } from '../stores/strings';
import { showEditEnrolInstancesForm } from '../utils/moodle';
import { add } from 'core/toast';
import type { CohortEnrolInstance } from '../types/interfaces';

const props = defineProps<{
    enrolinstance: CohortEnrolInstance;
}>();

const emit = defineEmits<{
    'updated:enrolinstance': [value: boolean];
}>();

const modal = ref();

const stringsStore = useStringsStore();

const openModal = async () => {
    modal.value = showEditEnrolInstancesForm(
        props.enrolinstance.id,
        stringsStore.getString('edit'),
        stringsStore.getString('editenrolinstance'),
    );

    modal.value.addEventListener(modal.value.events.FORM_SUBMITTED, (event: any) => {
        if (event.detail) {
            add(stringsStore.getString('enrolinstanceupdated'), {
                type: 'success'
            });
            emit('updated:enrolinstance', event.detail);
        }
        else {

        }
    });

    await modal.value.show();
}

</script>
<template>
    <button class="btn btn-outline-secondary" :title="stringsStore.getString('edit')" @click="openModal">
        <i class="fa fa-edit"></i>
    </button>
</template>