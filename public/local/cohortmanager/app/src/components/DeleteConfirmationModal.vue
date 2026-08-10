<template>
    <div v-if="false" class="modal show" style="display: none;">
    </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import { deleteCancel } from 'core/notification';
import Templates from 'core/templates';
import { useStringsStore } from '../stores/strings';

// Define props
const props = defineProps<{
    show: boolean;
    selectedMembers: number[];
    deleting?: boolean;
    getMemberFullname: (memberId: number) => string;
}>();

// Define emits
const emit = defineEmits<{
    close: [];
    confirm: [];
}>();

// Constants
const displayLimit = 15;

// Get strings store
const stringsStore = useStringsStore();

// Computed properties
const selectedMembersCount = computed(() => props.selectedMembers.length);
const displayedMembers = computed(() =>
    props.selectedMembers.slice(0, displayLimit)
);
const remainingCount = computed(() => Math.max(0, selectedMembersCount.value - displayLimit));

// Watch for show prop changes to show/hide the modal
watch(() => props.show, async (newShow) => {
    if (newShow) {
        await showDeleteConfirmationModal();
    }
});

// Function to show the delete confirmation modal using AMD module
const showDeleteConfirmationModal = async () => {
    const title = stringsStore.getString('deletecohort');
    const deleteLabel = stringsStore.getString('delete');

    // Prepare member data for the template
    const memberData = displayedMembers.value.map(id => ({
        id: id,
        fullname: props.getMemberFullname(id)
    }));

    // Create context for mustache template
    const context = {
        selectedMembersCount: selectedMembersCount.value,
        members: memberData,
        displayLimit: displayLimit,
        remainingCount: remainingCount.value
    };

    // Create HTML content for the question
    const template = await Templates.render(
        'local_cohortmanager/delete_membership_msg', context
    ) as any;

    await deleteCancel(
        title,
        template,
        deleteLabel,
        () => {
            // Delete callback - emit confirm event
            emit('confirm');
        },
        () => {
            // Cancel callback - emit close event
            emit('close');
        }
    );
};
</script>

<style scoped></style>