<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue';
import { useStringsStore } from '../stores/strings';
import { useAppStore } from '../stores/app';

// Define types
interface ContextOption {
  type: string;
  value: string;
  label: string;
}

// Initialize store
const stringsStore = useStringsStore();
const appStore = useAppStore();

// Props
const props = defineProps<{
  modelValue: {
    type: string;
    value: string;
  };
}>();

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: { type: string; value: string }];
}>();

// State for searchable select
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const selectedOption = ref<{ type: string; value: string; label: string } | null>(null);

// Computed properties
const contextType = computed({
  get: () => props.modelValue.type,
  set: (value: string) => {
    emit('update:modelValue', {
      type: value,
      value: props.modelValue.value
    });
  }
});

// Get context list from store
const contextList = computed<ContextOption[]>(() => {
  return appStore.getContextList() as ContextOption[];
});

// Filter context list based on search query
const filteredContextList = computed<ContextOption[]>(() => {
  if (!searchQuery.value.trim()) {
    return contextList.value;
  }
  
  const query = searchQuery.value.toLowerCase();
  return contextList.value.filter((context: ContextOption) => {
    const label = context.label || '';
    return label.toLowerCase().includes(query);
  });
});

// Handle option selection
const selectOption = (option: ContextOption) => {
  selectedOption.value = {
    type: option.type,
    value: option.value,
    label: option.label
  };
  
  // Directly emit the update event
  emit('update:modelValue', {
    type: option.type || 'id',
    value: option.value || ''
  });
  
  isDropdownOpen.value = false;
  searchQuery.value = '';
};

// Handle search input
const handleSearch = (event: Event) => {
  const target = event.target as HTMLInputElement;
  searchQuery.value = target.value;
};

// Handle dropdown toggle
const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
  if (isDropdownOpen.value && !selectedOption.value) {
    // Set default system context
    selectedOption.value = {
      type: 'system',
      value: '',
      label: stringsStore.getString('systemcontext')
    };
  }
};

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  const dropdown = document.querySelector('.context-select-dropdown');
  if (dropdown && !dropdown.contains(event.target as Node)) {
    isDropdownOpen.value = false;
  }
};

// Watch for changes in modelValue to update selected option
watch(() => props.modelValue, (newValue) => {
  if (newValue.type === 'system') {
    selectedOption.value = {
      type: 'system',
      value: '',
      label: stringsStore.getString('systemcontext')
    };
  } else if (contextList.value.length > 0) {
    const foundOption = contextList.value.find((context: ContextOption) =>
      context.value === newValue.value && context.type === newValue.type
    );
    if (foundOption) {
      selectedOption.value = {
        type: foundOption.type,
        value: foundOption.value,
        label: foundOption.label || ''
      };
    }
  }
}, { immediate: true });

// Initialize component
onMounted(() => {
  // Add event listener for clicks outside
  document.addEventListener('click', handleClickOutside);
  
  // Check if context is empty and set system as default
  if (props.modelValue.type == 'id' && !props.modelValue.value) {
    selectedOption.value = {
      type: 'system',
      value: '',
      label: stringsStore.getString('systemcontext')
    };
    
    // Emit the update to set system as the default
    emit('update:modelValue', {
      type: 'system',
      value: ''
    });
  }
  
  // Fetch context list if not already loaded
  if (contextList.value.length === 0) {
    // The context list should be loaded by the parent component or through app initialization
    console.log('Context list is empty, ensure it is loaded by the app store');
  }
});
</script>

<template>
  <div class="mb-3">
    <label class="form-label">{{ stringsStore.getString('context') }}</label>

    <div class="context-select-dropdown position-relative">
      <div
        class="custom-select d-flex justify-content-between align-items-center"
        @click="toggleDropdown"
        :class="{ 'active': isDropdownOpen }"
        style="cursor: pointer;"
      >
        <span v-if="selectedOption" class="text-truncate flex-grow-1">
          <i class="fa mr-1" :class="selectedOption.type !== 'system' ? 'fa-book text-info' : 'fa-desktop text-secondary'"></i>
          {{ selectedOption.label }}
        </span>
        <span v-else class="text-muted">
          {{ stringsStore.getString('selectcontext') }}
        </span>
        <i class="fa fa-chevron-down ml-2 small"></i>
      </div>

      <div v-if="isDropdownOpen" class="dropdown-menu show w-100 shadow-sm">
        <div class="px-3 py-2 border-bottom">
          <input
            type="text"
            class="form-control form-control-sm"
            :placeholder="stringsStore.getString('searchcontext')"
            @input="handleSearch"
            @click.stop
          />
        </div>

        <div class="dropdown-list" style="max-height: 200px; overflow-y: auto;">
          <template v-if="filteredContextList.length > 0">
            <button
              type="button"
              v-for="context in filteredContextList"
              :key="context.value"
              class="dropdown-item d-flex align-items-center"
              :class="{ 'active': selectedOption && selectedOption.value === context.value }"
              @click="selectOption(context)"
            >
              <i class="fa mr-2" :class="context.type !== 'system' ? 'fa-book text-info' : 'fa-desktop text-secondary'"></i>
              {{ context.label }}
            </button>
          </template>

          <div v-else class="dropdown-item text-muted text-center py-3">
            <i class="fa fa-search mr-1"></i> {{ stringsStore.getString('nocontextsfound') }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="contextType !== 'system'" class="form-text">
      {{ stringsStore.getString('entercoursecontextid') }}
    </div>
  </div>
</template>

<style scoped>
.context-select-dropdown .custom-select {
  padding: 0.375rem 0.75rem;
  height: auto;
  min-height: calc(1.5em + 0.75rem + 2px);
}

.context-select-dropdown .custom-select:hover {
  border-color: #80bdff;
}

.context-select-dropdown .custom-select.active {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.dropdown-item.active,
.dropdown-item:active {
  background-color: #e9ecef;
  color: #212529;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}
</style>