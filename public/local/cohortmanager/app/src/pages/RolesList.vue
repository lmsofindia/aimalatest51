<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { useStringsStore } from '../stores/strings';
import { getUserContextRoles, deleteRole, showRoleForm } from '../utils/moodle';
import Notification from 'core/notification';
import { add } from 'core/toast';
import { deleteCancel } from 'core/notification';
import type { RoleFormData, Pagination } from '../types/interfaces';
import { useRouter } from 'vue-router';
import TablePagination from '../components/TablePagination.vue';

const router = useRouter();

const stringsStore = useStringsStore();

const roles = ref<RoleFormData[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const pagination = reactive<Pagination>({
  page: 1,
  perpage: 10,
  total: 0
});

onMounted(async () => {
  await loadRoles();
});

const loadRoles = async () => {
  loading.value = true;

  try {
    const rolesResponse = await getUserContextRoles({
      query: searchQuery.value,
      page: (pagination.page - 1),
      perpage: pagination.perpage
    });
    roles.value = rolesResponse?.roles || [];
    pagination.total = rolesResponse?.total || 0;
  } catch (err) {
    Notification.exception(err);
  } finally {
    loading.value = false;
  }
};

const searchRoles = () => {
  pagination.page = 1;
  loadRoles();
};

const goToPage = (page: number) => {
  pagination.page = page;
  loadRoles();
};

const changePerPage = (value: number) => {
  pagination.perpage = value;
  pagination.page = 1;
  loadRoles();
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

const openCreateModal = async () => {
  const modalForm = showRoleForm(
    0,
    stringsStore.getString('createnewrole'),
    stringsStore.getString('rolecreate'),
  );

  modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
    add(stringsStore.getString('rolecreatedsuccessfully'), {
      type: 'success'
    });
    loadRoles();
  });

  await modalForm.show();
};

const openEditModal = async (role: RoleFormData) => {
  const modalForm = showRoleForm(
    role.id,
    stringsStore.getString('editrole'),
    stringsStore.getString('roleupdate'),
  );

  modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
    add(stringsStore.getString('roleupdatedsuccessfully'), {
      type: 'success'
    });
    loadRoles();
  });

  await modalForm.show();
};

const deleteRoleConfirm = async (role: RoleFormData) => {
  const title = stringsStore.getString('deleterole');
  const message = stringsStore.getString('roledeleteconfirmation').replace('%s', role.name);
  const deleteLabel = stringsStore.getString('delete');

  await deleteCancel(
    title,
    message,
    deleteLabel,
    async () => {
      try {
        await deleteRole({ roleid: role.id });
        add(stringsStore.getString('roledeletedsuccessfully'), {
          type: 'success'
        });
        await loadRoles();
      } catch (err) {
        Notification.exception(err);
      }
    }
  );
};

const paginationInfo = computed(() => {
  const start = (pagination.page - 1) * pagination.perpage + 1;
  const end = Math.min(pagination.page * pagination.perpage, pagination.total);
  return `${start}-${end} of ${pagination.total}`;
});

const totalPages = computed(() => Math.ceil(pagination.total / pagination.perpage));

const goBack = () => {
  router.push('/');
};
</script>

<template>
  <div class="container p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <h1 class="h2 mb-0">{{ stringsStore.getString('rolesmanagement') }}</h1>
      <div class="d-flex">
        <button @click="goBack" class="btn btn-outline-secondary"
          :title="stringsStore.getString('back')">
          <i class="fa fa-arrow-left"></i> {{ stringsStore.getString('back') }}
        </button>
      </div>
    </div>

    <!-- Add role button -->
    <div v-if="!loading" class="mb-3">
      <button class="btn btn-primary" @click="openCreateModal"
        :title="stringsStore.getString('addnewrole')">
        {{ stringsStore.getString('addnewrole') }}
      </button>
    </div>

    <div class="mb-4">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="input-group mb-3">
            <input v-model="searchQuery" type="text" class="form-control" :placeholder="stringsStore.getString('rolesearchplaceholder')" @keyup.enter="searchRoles" />
            <div class="input-group-append">
              <button class="btn btn-secondary" type="button" @click="searchRoles"
                :title="stringsStore.getString('search')">
                <i class="fa fa-search"></i> {{ stringsStore.getString('search') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center p-4">
      {{ stringsStore.getString('loadingroles') }}
    </div>

    <div v-else-if="roles.length === 0" class="text-center py-5 text-muted">
      <i class="fa fa-user-tag fa-3x mb-3"></i>
      <h3 class="mb-2">{{ stringsStore.getString('norolesfound') }}</h3>
      <p>{{ stringsStore.getString('norolesfounddesc') }}</p>
    </div>

    <div v-else>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">{{ stringsStore.getString('roleid') }}</th>
                <th>{{ stringsStore.getString('rolename') }}</th>
                <th>{{ stringsStore.getString('roleshortname') }}</th>
                <th style="width: 160px;">{{ stringsStore.getString('rolearchetype') }}</th>
                <th class="text-center" style="width: 120px;">{{ stringsStore.getString('roleactions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="role in roles" :key="role.id">
                <td>
                  <span class="badge badge-light text-muted">{{ role.id }}</span>
                </td>
                <td>
                  <span class="font-weight-bold">{{ role.name }}</span>
                </td>
                <td>
                  <code class="p-1 rounded text-monospace"> {{ role.shortname }}</code>
                </td>
                <td>
                  <span v-if="role.archetype" class="badge badge-info">{{ role.archetype }}</span>
                  <span v-else class="text-muted">&mdash;</span>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" @click="openEditModal(role)"
                      :title="stringsStore.getString('editrole')">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-danger" @click="deleteRoleConfirm(role)"
                      :title="stringsStore.getString('deleterole')">
                      <i class="fa fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <TablePagination
      :visible="roles.length > 0"
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
