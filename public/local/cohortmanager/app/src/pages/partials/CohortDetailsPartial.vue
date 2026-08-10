<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useStringsStore } from '../../stores/strings';
import { countCohortMembers, getCohortEnrolInstances, listCohortRoleAssignments } from '../../utils/moodle';
import type { Cohort, CohortEnrolInstance } from '../../types/interfaces';
import Notification from 'core/notification';

const stringsStore = useStringsStore();

const props = defineProps<{
  cohort: Cohort;
  id: number;
}>();

const totalMembers = ref(0);
const totalEnrolInstances = ref(0);
const totalEnrolments = ref(0);
const totalRoleAssignments = ref(0);
const loading = ref(false);

const loadStatistics = async () => {
  loading.value = true;
  try {
    const [membersCount, enrolInstancesData, roleAssignmentsData] = await Promise.all([
      countCohortMembers(props.cohort.id),
      getCohortEnrolInstances({ cohortid: props.cohort.id }),
      listCohortRoleAssignments({ cohortid: props.cohort.id })
    ]);

    totalMembers.value = membersCount || 0;
    totalEnrolInstances.value = enrolInstancesData?.total || 0;

    const enrolmentsSum = (enrolInstancesData?.instances || []).reduce((sum: number, instance: CohortEnrolInstance) => {
      return sum + (instance.enroled || 0);
    }, 0);
    totalEnrolments.value = enrolmentsSum;
    
    totalRoleAssignments.value = roleAssignmentsData?.total || 0;
  } catch (err) {
    Notification.exception(err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadStatistics();
});
</script>

<template>
  <div class="tab-pane fade show active mb-2">
    <!-- Cohort Portrait - Statistics Summary -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ stringsStore.getString('cohortportrait') }}</h5>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-3">
          <i class="fa fa-spinner fa-spin"></i> {{ stringsStore.getString('loading') }}
        </div>
        <div v-else class="row g-3">
          <div class="col-6 col-md-3">
            <div class="text-center p-3 bg-light rounded">
              <div class="h3 mb-1 text-primary">{{ totalMembers }}</div>
              <div class="small text-muted">{{ stringsStore.getString('members') }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-center p-3 bg-light rounded">
              <div class="h3 mb-1 text-success">{{ totalEnrolInstances }}</div>
              <div class="small text-muted">{{ stringsStore.getString('enrolinstances') }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-center p-3 bg-light rounded">
              <div class="h3 mb-1 text-info">{{ totalEnrolments }}</div>
              <div class="small text-muted">{{ stringsStore.getString('totalenrolments') }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-center p-3 bg-light rounded">
              <div class="h3 mb-1 text-warning">{{ totalRoleAssignments }}</div>
              <div class="small text-muted">{{ stringsStore.getString('roleassignments') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ stringsStore.getString('basicinformation') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">{{ stringsStore.getString('name') }}:</label>
            <p class="form-control-plaintext">{{ props.cohort.name }}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">{{ stringsStore.getString('idnumber') }}:</label>
            <p class="form-control-plaintext">{{ props.cohort.idnumber }}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">{{ stringsStore.getString('visibility') }}:</label>
            <span :class="['badge p-2 mx-2', props.cohort.visible ? 'bg-success' : 'bg-secondary']">
              <i :class="['fa me-1', props.cohort.visible ? 'fa-eye' : 'fa-eye-slash']"></i>
              {{ props.cohort.visible ? stringsStore.getString('visible') : stringsStore.getString('hidden') }}
            </span>
          </div>
          <div v-if="props.cohort.theme" class="col-md-6 mb-3">
            <label class="form-label">{{ stringsStore.getString('theme') }}:</label>
            <p class="form-control-plaintext">{{ props.cohort.theme }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ stringsStore.getString('description') }}</h5>
      </div>
      <div class="card-body">
        <div v-if="props.cohort.description" v-html="props.cohort.description"></div>
        <div v-else class="text-muted fst-italic">
          {{ stringsStore.getString('nodescriptionprovided') }}
        </div>
      </div>
    </div>

    <div v-if="props.cohort.customfields && props.cohort.customfields.length > 0" class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ stringsStore.getString('customfields') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div v-for="field in props.cohort.customfields" :key="field.shortname" class="col-md-6 mb-3">
            <label class="form-label">{{ field.name }}:</label>
            <p class="form-control-plaintext">{{ field.value }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>