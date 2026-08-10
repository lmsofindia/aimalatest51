<script setup lang="ts">
import { useStringsStore } from '../stores/strings';

const stringsStore = useStringsStore();

const perPageOptions = [5, 10, 25, 50, 100];

withDefaults(defineProps<{
    currentPage: number;
    totalPages: number;
    paginationInfo: string;
    perPage: number;
    visible: boolean;
}>(), {
    currentPage: 1,
    totalPages: 1,
    paginationInfo: '',
    perPage: 10,
    visible: true,
});

const emit = defineEmits<{
    (e: 'update:perPage', value: number): void;
    (e: 'prev'): void;
    (e: 'next'): void;
}>();

const onPerPageChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    emit('update:perPage', parseInt(target.value, 10));
};
</script>

<template>
    <div v-if="visible" class="d-flex justify-content-between align-items-center flex-wrap mt-3">
        <div class="input-group input-group-sm" style="width: auto;">
            <div class="input-group-prepend">
                <span class="input-group-text border-right-0 bg-white">
                    {{ stringsStore.getString('perpage') }}
                </span>
            </div>
            <select class="custom-select custom-select-sm border-left-0" style="max-width: 80px;"
                :value="perPage" @change="onPerPageChange">
                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>

        <nav v-if="totalPages > 1">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                    <button class="page-link" :disabled="currentPage <= 1" @click="$emit('prev')">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                </li>
                <li class="page-item active">
                    <span class="page-link">{{ paginationInfo }}</span>
                </li>
                <li class="page-item" :class="{ disabled: currentPage >= totalPages }">
                    <button class="page-link" :disabled="currentPage >= totalPages" @click="$emit('next')">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</template>
