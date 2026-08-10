<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { getCohorts } from '../utils/moodle';
import { useStringsStore } from '../stores/strings';
import { add } from 'core/toast';
import CohortDetailsPartial from './partials/CohortDetailsPartial.vue';
import CohortMembersPartial from './partials/CohortMembersPartial.vue';
import CohortEnrolInstancesPartial from './partials/CohortEnrolInstancesPartial.vue';
import CohortRoleAssignmentsPartial from './partials/CohortRoleAssignmentsPartial.vue';
import CohortDelete from '../components/CohortDelete.vue';
import type { Cohort } from '../types/interfaces';
import Notification from 'core/notification';

const stringsStore = useStringsStore();

const props = defineProps<{
  id: number;
}>();

const router = useRouter();
const cohort = ref<Cohort | null>(null);
const loading = ref(false);
const activeTab = ref('details');
const showDropdown = ref(false);
const cohortDeleteRef = ref<InstanceType<typeof CohortDelete> | null>(null);

const loadCohort = async () => {
  loading.value = true;

  try {
    const cohortsList = await getCohorts({
      cohortids: [props.id]
    });

    if (cohortsList && cohortsList.length > 0) {
      cohort.value = cohortsList[0];
    } else {
      add(stringsStore.getString('cohortnotfound'), {
        type: 'danger'
      });
    }
  } catch (err) {
    Notification.exception(err);
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.push('/');
};

const editCohort = () => {
  showDropdown.value = false;
  router.push(`/cohort/${props.id}/edit`);
};

const triggerDelete = () => {
  showDropdown.value = false;
  cohortDeleteRef.value?.deleteCohort();
};

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
};

const closeDropdown = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.cohort-actions-dropdown')) {
    showDropdown.value = false;
  }
};

onMounted(() => {
  loadCohort();
  document.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeDropdown);
});
</script>

<template>
  <div class="container">

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4">
      <i class="fa fa-spinner fa-spin"></i> {{ stringsStore.getString('loadingcohortdetails') }}
    </div>

    <!-- Content -->
    <div v-else-if="cohort" class="mt-4">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <h1 class="h2 mb-0">{{ cohort.name }}</h1>
        <button @click="goBack" class="btn btn-outline-secondary"
          :title="stringsStore.getString('back')">
          <i class="fa fa-arrow-left"></i> {{ stringsStore.getString('back') }}
        </button>
      </div>

      <!-- Tabs -->
      <div class="d-flex justify-content-between align-items-center">
        <ul class="nav nav-tabs flex-grow-1">
          <li class="nav-item">
            <button :class="['nav-link', { active: activeTab === 'details' }]" @click="activeTab = 'details'"
              :title="stringsStore.getString('details')">
              {{ stringsStore.getString('details') }}
            </button>
          </li>
          <li class="nav-item">
            <button :class="['nav-link', { active: activeTab === 'members' }]" @click="activeTab = 'members'"
              :title="stringsStore.getString('members')">
              {{ stringsStore.getString('members') }}
            </button>
          </li>
          <li class="nav-item">
            <button :class="['nav-link', { active: activeTab === 'enrolinstances' }]" @click="activeTab = 'enrolinstances'"
              :title="stringsStore.getString('enrolinstances')">
              {{ stringsStore.getString('enrolinstances') }}
            </button>
          </li>
          <li class="nav-item">
            <button :class="['nav-link', { active: activeTab === 'cohortroles' }]" @click="activeTab = 'cohortroles'"
              :title="stringsStore.getString('cohortroles')">
              {{ stringsStore.getString('cohortroles') }}
            </button>
          </li>
        </ul>
        <div class="dropdown cohort-actions-dropdown ml-2 mb-1">
          <button class="btn btn-outline-secondary" type="button"
            @click="toggleDropdown"
            :aria-expanded="showDropdown"
            :title="stringsStore.getString('actions')">
            <i class="fa fa-ellipsis-v"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-right" :class="{ show: showDropdown }">
            <button class="dropdown-item" type="button" @click="editCohort">
              <i class="fa fa-edit"></i> {{ stringsStore.getString('edit') }}
            </button>
            <button class="dropdown-item text-danger" type="button" @click="triggerDelete">
              <i class="fa fa-trash"></i> {{ stringsStore.getString('delete') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="tab-content mb-2">
        <!-- Details Tab -->
        <CohortDetailsPartial 
          v-if="activeTab === 'details'" 
          :cohort="cohort" 
          :id="props.id"
        />
        
        <!-- Hidden delete component -->
        <CohortDelete ref="cohortDeleteRef" :cohort="cohort" @success="goBack" class="d-none" />
        
        <!-- Members Tab -->
        <CohortMembersPartial v-if="activeTab === 'members'" :cohort="cohort" />
        
        <!-- Enrol Instances Tab -->
        <CohortEnrolInstancesPartial v-if="activeTab === 'enrolinstances'" :cohort="cohort" />

        <!-- Cohort Roles Tab -->
        <CohortRoleAssignmentsPartial v-if="activeTab === 'cohortroles'" :cohort="cohort" />
      </div>
    </div>

    <!-- Not Found State -->
    <div v-else class="text-center py-4">
      <i class="fa fa-exclamation-triangle fa-3x text-warning mb-3"></i>
      <h3>{{ stringsStore.getString('cohortnotfound') }}</h3>
      <p class="text-muted">{{ stringsStore.getString('cohortnotfounddetails') }}</p>
      <button @click="goBack" class="btn btn-primary"
        :title="stringsStore.getString('backtocohortlist')">
        {{ stringsStore.getString('backtocohortlist') }}
      </button>
    </div>
  </div>
</template>
