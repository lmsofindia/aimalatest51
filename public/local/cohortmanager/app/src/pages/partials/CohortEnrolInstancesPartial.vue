<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useStringsStore } from '../../stores/strings';
import type { Cohort, CohortEnrolInstance, Pagination } from '../../types/interfaces';
import { getCohortEnrolInstances, toggleCohortEnrolInstanceStatus } from '../../utils/moodle';
import Notification from 'core/notification';
import { add } from 'core/toast';
import CohortEnrolInstancesAddModal from '../../components/CohortEnrolInstancesAddModal.vue';
import CohortEnrolInstancesEditModal from '../../components/CohortEnrolInstancesEditModal.vue';
import CohortEnrolInstancesDelete from '../../components/CohortEnrolInstancesDelete.vue';
import TablePagination from '../../components/TablePagination.vue';

const stringsStore = useStringsStore();

const enrolInstances = ref<CohortEnrolInstance[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const statusFilter = ref<'all' | 'active' | 'inactive'>('all');
const togglingIds = ref<Set<number>>(new Set());
const pagination = ref<Pagination>({
  page: 1,
  perpage: 10,
  total: 0
});

const filteredInstances = computed(() => {
  let result = enrolInstances.value;

  if (statusFilter.value === 'active') {
    result = result.filter(i => i.status);
  } else if (statusFilter.value === 'inactive') {
    result = result.filter(i => !i.status);
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase();
    result = result.filter(i =>
      (i.coursefullname || '').toLowerCase().includes(q) ||
      (i.courseshortname || '').toLowerCase().includes(q) ||
      (i.rolename || '').toLowerCase().includes(q) ||
      (i.groupname || '').toLowerCase().includes(q)
    );
  }

  return result;
});

const totalPages = computed(() => Math.ceil(pagination.value.total / pagination.value.perpage));

const paginationInfo = computed(() => {
  const start = (pagination.value.page - 1) * pagination.value.perpage + 1;
  const end = Math.min(pagination.value.page * pagination.value.perpage, pagination.value.total);
  return `${start}-${end} of ${pagination.value.total}`;
});

const loadEnrolInstances = async () => {
  loading.value = true;
  try {
    const response = await getCohortEnrolInstances({
      cohortid: props.cohort.id,
      page: (pagination.value.page - 1),
      perpage: pagination.value.perpage
    });
    enrolInstances.value = response?.instances || [];
    pagination.value.total = response?.total || 0;
  } catch (err) {
    Notification.exception(err);
  } finally {
    loading.value = false;
  }
};

const changePerPage = (value: number) => {
  pagination.value.perpage = value;
  pagination.value.page = 1;
  loadEnrolInstances();
};

const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--;
    loadEnrolInstances();
  }
};

const nextPage = () => {
  if (pagination.value.page < totalPages.value) {
    pagination.value.page++;
    loadEnrolInstances();
  }
};

const toggleStatus = async (instance: CohortEnrolInstance) => {
  togglingIds.value.add(instance.id);
  try {
    const response = await toggleCohortEnrolInstanceStatus({
      enrolinstanceid: instance.id
    });
    if (response.success) {
      add(response.message, { type: 'success' });
      await loadEnrolInstances();
    }
  } catch (err) {
    add(stringsStore.getString('errortogglingstatus'), { type: 'danger' });
    Notification.exception(err);
  } finally {
    togglingIds.value.delete(instance.id);
  }
};

const handleAddedEnrolInstances = (result: boolean) => {
  if (result) {
    loadEnrolInstances();
  }
};

const handleUpdatedEnrolInstance = (result: boolean) => {
  if (result) {
    loadEnrolInstances();
  }
};

const handleDeletedEnrolInstance = () => {
  loadEnrolInstances();
};

onMounted(() => {
  loadEnrolInstances();
});

let props = defineProps<{
  cohort: Cohort;
}>();
</script>

<template>
  <div class="tab-pane fade show active">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">{{ stringsStore.getString('cohortenrolinstances') }}</h5>
      <span class="badge bg-primary my-2 p-2"><i class="fa fa-link me-1"></i>{{ pagination.total }} {{ stringsStore.getString('instancescount') }}</span>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <CohortEnrolInstancesAddModal :cohortid="cohort.id" @added:enrolinstances="handleAddedEnrolInstances" />
    </div>

    <div class="d-flex flex-wrap align-items-center mb-3" style="gap: 0.75rem;">
      <input type="text" class="form-control" style="max-width: 300px;" :placeholder="stringsStore.getString('searchenrolinstances')" v-model="searchQuery" />

      <div class="btn-group btn-group-sm" role="group">
        <button class="btn" :class="statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'" @click="statusFilter = 'all'">
          {{ stringsStore.getString('all') }}
        </button>
        <button class="btn" :class="statusFilter === 'active' ? 'btn-success' : 'btn-outline-success'" @click="statusFilter = 'active'">
          {{ stringsStore.getString('active') }}
        </button>
        <button class="btn" :class="statusFilter === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary'" @click="statusFilter = 'inactive'">
          {{ stringsStore.getString('inactive') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-4">
      <i class="fa fa-spinner fa-spin"></i> {{ stringsStore.getString('loadingenrolinstances') }}
    </div>

    <div v-else>
      <div v-if="filteredInstances.length === 0" class="text-center py-4">
        <i class="fa fa-link fa-3x text-muted mb-3"></i>
        <h5>{{ stringsStore.getString('noenrolinstancesfound') }}</h5>
        <p class="text-muted">{{ stringsStore.getString('noenrolinstancesfounddescription') }}</p>
      </div>

      <div v-else>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>{{ stringsStore.getString('course') }}</th>
                    <th>{{ stringsStore.getString('role') }}</th>
                    <th>{{ stringsStore.getString('status') }}</th>
                    <th>{{ stringsStore.getString('enroled') }}</th>
                    <th>{{ stringsStore.getString('group') }}</th>
                    <th>{{ stringsStore.getString('groupmembers') }}</th>
                    <th>{{ stringsStore.getString('actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="instance in filteredInstances" :key="instance.id">
                    <td>
                      <div>
                        <strong>{{ instance.coursefullname || instance.courseshortname }}</strong>
                        <br>
                        <small class="text-muted">{{ instance.courseshortname }}</small>
                      </div>
                    </td>
                    <td>
                      {{ instance.rolename }}
                    </td>
                    <td>
                      <button
                        class="btn btn-sm"
                        :class="instance.status ? 'btn-outline-success' : 'btn-outline-secondary'"
                        :disabled="togglingIds.has(instance.id)"
                        :title="stringsStore.getString('togglestatus')"
                        @click="toggleStatus(instance)"
                      >
                        <i v-if="togglingIds.has(instance.id)" class="fa fa-spinner fa-spin me-1"></i>
                        {{ instance.status ? stringsStore.getString('active') : stringsStore.getString('inactive') }}
                      </button>
                    </td>
                    <td>
                      {{ instance.enroled }}
                    </td>
                    <td>
                      {{ instance.groupname }}
                    </td>
                    <td>
                      {{ instance.groupmembers }}
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <CohortEnrolInstancesEditModal :enrolinstance="instance" @updated:enrolinstance="handleUpdatedEnrolInstance" />
                        <CohortEnrolInstancesDelete :enrolinstance="instance" @deleted:enrolinstance="handleDeletedEnrolInstance" />
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <TablePagination
          :visible="true"
          :current-page="pagination.page"
          :total-pages="totalPages"
          :pagination-info="paginationInfo"
          :per-page="pagination.perpage"
          @update:per-page="changePerPage"
          @prev="prevPage"
          @next="nextPage"
        />
      </div>
    </div>
  </div>
</template>
