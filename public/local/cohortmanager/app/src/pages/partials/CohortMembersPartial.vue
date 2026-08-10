<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useStringsStore } from '../../stores/strings';
import type { Cohort } from '../../types/interfaces';
import { countCohortMembers } from '../../utils/moodle';
import CohortMembersAddModal from '../../components/CohortMembersAddModal.vue';
import DynamicCohortMembersTable from '../../components/DynamicCohortMembersTable.vue';

// Initialize strings store
const stringsStore = useStringsStore();

const totalMembers = ref(0);
const loading = ref(false);

const loadMembers = async () => {
  loading.value = true;
  try {
    totalMembers.value = await countCohortMembers(props.cohort.id);
  } catch (err) {
    console.error('Error loading members:', err);
  } finally {
    loading.value = false;
  }
};

// Handle added members event
const handleAddedMembers = (result: boolean) => {
  if (result) {
    loadMembers();
  }
};

// Handle refresh from dynamic table
const handleTableRefresh = () => {
  loadMembers();
};

// Initialize the component
onMounted(() => {
  loadMembers();
});

// Props
let props = defineProps<{
  cohort: Cohort;
}>();

</script>

<template>
  <div class="tab-pane fade show active">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">{{ stringsStore.getString('cohortmembers') }}</h5>
      <span class="badge bg-primary my-2 p-2"><i class="fa fa-users me-1"></i>{{ totalMembers }} {{ stringsStore.getString('memberscount') }}</span>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <CohortMembersAddModal :cohortid="cohort.id" @added:members="handleAddedMembers" />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4">
      <i class="fa fa-spinner fa-spin"></i> {{ stringsStore.getString('loading') }}
    </div>

    <!-- Content -->
    <div v-else>
      <DynamicCohortMembersTable :cohortId="cohort.id" @refresh="handleTableRefresh" />
    </div>
  </div>
</template>