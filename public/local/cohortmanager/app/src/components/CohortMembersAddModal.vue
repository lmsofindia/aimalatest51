<script setup lang="ts">
import { ref } from 'vue';
import { useStringsStore } from '../stores/strings';
import { showAddMembersForm } from '../utils/moodle';
import { add } from 'core/toast';

const props = defineProps<{
    cohortid: number;
}>();

const emit = defineEmits<{
    'added:members': [value: boolean];
}>();

const modal = ref();

const stringsStore = useStringsStore();

const openModal = async () => {
    modal.value = showAddMembersForm(
        props.cohortid,
        stringsStore.getString('addmembers'),
        stringsStore.getString('add'),
    );

    modal.value.addEventListener(modal.value.events.FORM_SUBMITTED, (event: any) => {
        if (event.detail) {
            add(stringsStore.getString('membersadded'), {
                type: 'success'
            });
            emit('added:members', event.detail);
        }
        else {

        }
    });

    await modal.value.show();
}

</script>
<template>
    <button class="btn btn-primary" @click="openModal" :title="stringsStore.getString('addmembers')">{{ stringsStore.getString('addmembers') }}</button>
</template>
