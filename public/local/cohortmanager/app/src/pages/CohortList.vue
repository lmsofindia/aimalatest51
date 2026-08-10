<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { searchCohorts as searchCohortsApi } from '../utils/moodle';
import { useStringsStore } from '../stores/strings';
import Notification from 'core/notification';
import type { Cohort, Pagination } from '../types/interfaces';
import TablePagination from '../components/TablePagination.vue';

// Initialize strings store
const stringsStore = useStringsStore();

// Define types

// State management
const router = useRouter();
const cohorts = ref<Cohort[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const pagination = reactive<Pagination>({
  page: 1,
  perpage: 10,
  total: 0
});

// Initialize the component
onMounted(async () => {
  await loadCohorts();
});

// Load cohorts with pagination
const loadCohorts = async () => {
  loading.value = true;

  try {
    const cohortsResponse = await searchCohortsApi({
      query: searchQuery.value,
      page: (pagination.page - 1),
      perpage: pagination.perpage
    });
    cohorts.value = cohortsResponse?.cohorts || [];
    pagination.total = cohortsResponse?.total || 0;
  } catch (err) {
    Notification.exception(err);
  } finally {
    loading.value = false;
  }
};

// Search cohorts
const searchCohorts = () => {
  pagination.page = 1;
  loadCohorts();
};

// Pagination handlers
const goToPage = (page: number) => {
  pagination.page = page;
  loadCohorts();
};

const changePerPage = (value: number) => {
  pagination.perpage = value;
  pagination.page = 1;
  loadCohorts();
};

const prevPage = () => {
  if (pagination.page > 1) {
    goToPage(pagination.page - 1);
  }
};

const nextPage = () => {
  const totalPages = Math.ceil(pagination.total / pagination.perpage);
  if (pagination.page < totalPages) {
    goToPage(pagination.page + 1);
  }
};


// View cohort details
const viewCohort = (cohort: Cohort) => {
  // Navigate to cohort details page
  router.push(`cohort/${cohort.id}`);
};


// Calculate pagination info
const paginationInfo = computed(() => {
  const start = (pagination.page - 1) * pagination.perpage + 1;
  const end = Math.min(pagination.page * pagination.perpage, pagination.total);
  return `${start}-${end} of ${pagination.total}`;
});

const totalPages = computed(() => Math.ceil(pagination.total / pagination.perpage));
</script>

<template>
  <div class="container p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <h1 class="h2 mb-0">{{ stringsStore.getString('cohortmanager') }}</h1>
    </div>

    <!-- Add cohort button -->
    <div v-if="!loading" class="mb-3">
      <div class="d-flex justify-content-between">
        <div>
          <router-link to="cohort/create" class="btn btn-primary mr-2" :title="stringsStore.getString('addcohort')">
            {{ stringsStore.getString('addcohort') }}
          </router-link>
        </div>
        <div class="dropdown">
          <button class="btn btn-outline-secondary" type="button" id="managementDropdown"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
            :title="stringsStore.getString('actions')">
            <i class="fa fa-ellipsis-v"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="managementDropdown">
            <router-link to="roles" class="dropdown-item"
              :title="stringsStore.getString('rolesmanagement')">
              <i class="fa fa-user-tag mr-2"></i> {{ stringsStore.getString('rolesmanagement') }}
            </router-link>
            <router-link to="custom-fields" class="dropdown-item"
              :title="stringsStore.getString('customfieldsmanagement')">
              <i class="fa fa-cogs mr-2"></i> {{ stringsStore.getString('customfieldsmanagement') }}
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Search bar -->
    <div class="mb-4">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="input-group mb-3">
            <input v-model="searchQuery" type="text" class="form-control" :placeholder="stringsStore.getString('searchcohorts')" @keyup.enter="searchCohorts" />
            <div class="input-group-append">
              <button class="btn btn-secondary" type="button" @click="searchCohorts"
                :title="stringsStore.getString('search')">
                <i class="fa fa-search"></i> {{ stringsStore.getString('search') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center p-4">
      {{ stringsStore.getString('loading') }}
    </div>

    <!-- Empty state -->
    <div v-else-if="cohorts.length === 0" class="text-center py-5 text-muted">
      <i class="fa fa-users fa-3x mb-3"></i>
      <h3 class="mb-2">{{ stringsStore.getString('nocoortsfound') }}</h3>
      <p>{{ stringsStore.getString('nocoortsfounddesc') }}</p>
    </div>

    <!-- Cohort table -->
    <div v-if="cohorts.length > 0" class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>{{ stringsStore.getString('id') }}</th>
              <th>{{ stringsStore.getString('name') }}</th>
              <th>{{ stringsStore.getString('members') }}</th>
              <th>{{ stringsStore.getString('cohortenrolinstances') }}</th>
              <th class="text-center">{{ stringsStore.getString('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cohort in cohorts" :key="cohort.id">
              <td>{{ cohort.id }}</td>
              <td>
                <a href="#" class="text-decoration-none" @click.prevent="viewCohort(cohort)">
                  <strong>{{ cohort.name }}</strong>
                </a>
              </td>
              <td>{{ cohort.members }}</td>
              <td>{{ cohort.enrols }}</td>
              <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                  <button class="btn btn-outline-primary" @click="viewCohort(cohort)"
                    :title="stringsStore.getString('view') + ' ' + stringsStore.getString('Cohort')">
                    <i class="fa fa-info"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <TablePagination
      :visible="cohorts.length > 0"
      :current-page="pagination.page"
      :total-pages="totalPages"
      :pagination-info="paginationInfo"
      :per-page="pagination.perpage"
      @update:per-page="changePerPage"
      @prev="prevPage"
      @next="nextPage"
    />
  </div>
</template>

<style scoped></style>