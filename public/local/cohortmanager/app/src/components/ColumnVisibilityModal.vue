<template>
    <div v-if="show" class="modal show" style="display: block;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Column Visibility</h5>
                    <button type="button" class="btn-close" @click="$emit('close')" :title="'Close'"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="username-column" 
                            v-model="localVisibleColumns"
                            value="username"
                        >
                        <label class="form-check-label" for="username-column">Username</label>
                    </div>
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="email-column" 
                            v-model="localVisibleColumns"
                            value="email"
                        >
                        <label class="form-check-label" for="email-column">Email</label>
                    </div>
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="city-column" 
                            v-model="localVisibleColumns"
                            value="city"
                        >
                        <label class="form-check-label" for="city-column">City</label>
                    </div>
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="country-column" 
                            v-model="localVisibleColumns"
                            value="country"
                        >
                        <label class="form-check-label" for="country-column">Country</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$emit('close')" :title="'Close'">
                        Close
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveColumnVisibility" :title="'Save Changes'">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

// Define props
const props = defineProps<{
    show: boolean;
    visibleColumns: string[];
}>();

// Define emits
const emit = defineEmits<{
    close: [];
    save: [columns: string[]];
}>();

// Local state
const localVisibleColumns = ref<string[]>([...props.visibleColumns]);

// Watch for prop changes
watch(() => props.visibleColumns, (newColumns) => {
    localVisibleColumns.value = [...newColumns];
}, { immediate: true });

// Methods
const saveColumnVisibility = () => {
    // Always ensure 'select' and 'fullname' columns are visible
    const alwaysVisibleColumns = ['select', 'fullname'];
    const updatedColumns = [...new Set([...localVisibleColumns.value, ...alwaysVisibleColumns])];
    
    emit('save', updatedColumns);
    emit('close');
};
</script>

<style scoped>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    width: 400px;
}
</style>