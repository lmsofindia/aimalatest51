<template>
    <div>
        <!-- Search and filters -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="input-group" style="max-width: 300px;">
                <input type="text" class="form-control" :placeholder="stringsStore.getString('searchmembers')" v-model="searchQuery"
                    @input="handleSearch">
            </div>

            <!-- Letter filters -->
            <div class="col-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ stringsStore.getString('first') }}</span>
                    <input type="text" class="form-control" maxlength="1" v-model="firstInitial"
                        @input="handleLetterFilter">
                </div>

                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ stringsStore.getString('last') }}</span>
                    <input type="text" class="form-control" maxlength="1" v-model="lastInitial"
                        @input="handleLetterFilter">
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-3 flex-wrap">
            <button class="btn btn-sm btn-outline-secondary" @click="toggleColumnVisibility"
                :title="stringsStore.getString('columns')">
                <i class="bi bi-grid-3x3"></i> {{ stringsStore.getString('columns') }}
            </button>

            <!-- Delete button - shown when users are selected -->
            <button v-if="selectedMembers.length > 0" class="btn btn-sm btn-outline-danger"
                @click="showDeleteModal = true"
                :title="stringsStore.getString('delete') + ' (' + selectedMembers.length + ')'">
                <i class="bi bi-trash"></i> {{ stringsStore.getString('delete') }} ({{ selectedMembers.length }})
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-4">
            <i class="fa fa-spinner fa-spin"></i> {{ stringsStore.getString('loading') }}
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger">
            {{ error }}
        </div>

        <!-- Content -->
        <div v-else>
            <!-- Empty State -->
            <div v-if="members.length === 0" class="text-center py-4">
                <i class="fa fa-users fa-3x text-muted mb-3"></i>
                <h5>{{ stringsStore.getString('nomembersfound') }}</h5>
                <p class="text-muted">{{ stringsStore.getString('nomembersfounddescription') }}</p>
            </div>

            <!-- Members Table -->
            <div v-else class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                        <tr>
                            <th v-if="!isColumnHidden('select')">
                                <input type="checkbox" @change="toggleSelectAll" :checked="allSelected"
                                    :indeterminate="partialSelected">
                            </th>

                            <th v-if="!isColumnHidden('fullname')" @click="toggleSort('fullname')">
                                {{ stringsStore.getString('name') }}
                                <span v-if="currentSortBy === 'fullname'">
                                    {{ currentSortOrder === 1 ? '↑' : '↓' }}
                                </span>
                            </th>

                            <th v-if="!isColumnHidden('username')" @click="toggleSort('username')">
                                {{ stringsStore.getString('username') }}
                                <span v-if="currentSortBy === 'username'">
                                    {{ currentSortOrder === 1 ? '↑' : '↓' }}
                                </span>
                            </th>

                            <th v-if="!isColumnHidden('email')" @click="toggleSort('email')">
                                {{ stringsStore.getString('email') }}
                                <span v-if="currentSortBy === 'email'">
                                    {{ currentSortOrder === 1 ? '↑' : '↓' }}
                                </span>
                            </th>

                            <th v-if="!isColumnHidden('city')" @click="toggleSort('city')">
                                {{ stringsStore.getString('city') }}
                                <span v-if="currentSortBy === 'city'">
                                    {{ currentSortOrder === 1 ? '↑' : '↓' }}
                                </span>
                            </th>

                            <th v-if="!isColumnHidden('country')" @click="toggleSort('country')">
                                {{ stringsStore.getString('country') }}
                                <span v-if="currentSortBy === 'country'">
                                    {{ currentSortOrder === 1 ? '↑' : '↓' }}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="member in members" :key="member.id">
                            <td v-if="!isColumnHidden('select')">
                                <input type="checkbox" :value="member.id" v-model="selectedMembers">
                            </td>

                            <td v-if="!isColumnHidden('fullname')">
                                {{ member.fullname }}
                            </td>

                            <td v-if="!isColumnHidden('username')">{{ member.username }}</td>
                            <td v-if="!isColumnHidden('email')">{{ member.email }}</td>
                            <td v-if="!isColumnHidden('city')">{{ member.city }}</td>
                            <td v-if="!isColumnHidden('country')">{{ member.country }}</td>
                        </tr>
                    </tbody>
                </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <TablePagination
                :visible="members.length > 0"
                :current-page="currentPage"
                :total-pages="totalPages"
                :pagination-info="paginationInfo"
                :per-page="pageSize"
                @update:per-page="changePerPage"
                @prev="prevPage"
                @next="nextPage"
            />
        </div>

        <!-- Column visibility modal -->
        <ColumnVisibilityModal
            :show="showColumnModal"
            :visible-columns="visibleColumns"
            @close="showColumnModal = false"
            @save="handleColumnVisibilitySave"
        />

        <!-- Delete confirmation modal -->
        <DeleteConfirmationModal
            :show="showDeleteModal"
            :selected-members="selectedMembers"
            :deleting="deleting"
            :get-member-fullname="getMemberFullname"
            @close="showDeleteModal = false"
            @confirm="deleteSelectedMembers"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import type { CohortMember } from '@/types/interfaces';
import { getCohortMembers, deleteCohortMembers } from '../utils/moodle';
import ColumnVisibilityModal from './ColumnVisibilityModal.vue';
import DeleteConfirmationModal from './DeleteConfirmationModal.vue';
import TablePagination from './TablePagination.vue';
import { useStringsStore } from '@/stores/strings';

// Define props
const props = defineProps<{
    cohortId: number;
}>();

// Initialize strings store
const stringsStore = useStringsStore();

// State
const members = ref<CohortMember[]>([]);
const totalRows = ref(0);
const loading = ref(false);
const error = ref<string | null>(null);
const currentPage = ref(1);
const pageSize = ref(10);
const sortData = ref<{ sortby: string, sortorder: number }[]>([]);
const filters = ref<{ name: string, value: string }[]>([]);
const searchQuery = ref('');
const firstInitial = ref('');
const lastInitial = ref('');
const visibleColumns = ref<string[]>(['select', 'fullname', 'username', 'email', 'city', 'country']);
const selectedMembers = ref<number[]>([]);
const showColumnModal = ref(false);
const showDeleteModal = ref(false);
const deleting = ref(false);

// Computed properties
const currentSortBy = computed(() => sortData.value[0]?.sortby || 'lastname');
const currentSortOrder = computed(() => sortData.value[0]?.sortorder || 1);
const allSelected = computed(() =>
    members.value.length > 0 &&
    selectedMembers.value.length === members.value.length
);
const partialSelected = computed(() =>
    selectedMembers.value.length > 0 &&
    selectedMembers.value.length < members.value.length
);

const totalPages = computed(() => Math.ceil(totalRows.value / pageSize.value));
const paginationInfo = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value + 1;
    const end = Math.min(currentPage.value * pageSize.value, totalRows.value);
    return `${start}-${end} of ${totalRows.value} ${stringsStore.getString('memberscount')}`;
});

// Methods
const fetchMembers = async () => {
    try {
        loading.value = true;
        error.value = null;

        // Make API call to new webservice
        const response = await getCohortMembers({
            cohortid: props.cohortId,
            sortdata: sortData.value,
            filters: filters.value,
            pagenumber: currentPage.value,
            pagesize: pageSize.value,
            hiddencolumns: visibleColumns.value.filter(col => !['select', 'fullname'].includes(col))
        });

        members.value = response.members;
        totalRows.value = response.totalrows;

        if (!response) {
            throw new Error(stringsStore.getString('failedtofetchmembers'));
        }
        console.debug(response);
    } catch (err) {
        error.value = err instanceof Error ? err.message : stringsStore.getString('anerroroccurred');
    } finally {
        loading.value = false;
    }
};

const toggleSort = (column: string) => {
    if (currentSortBy.value === column) {
        sortData.value = [
            { sortby: column, sortorder: currentSortOrder.value === 1 ? -1 : 1 }
        ];
    } else {
        sortData.value = [{ sortby: column, sortorder: 1 }];
    }
    fetchMembers();
};

// Pagination handlers
const goToPage = (page: number) => {
    currentPage.value = page;
    fetchMembers();
};

const prevPage = () => {
    if (currentPage.value > 1) {
        goToPage(currentPage.value - 1);
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        goToPage(currentPage.value + 1);
    }
};

const changePerPage = (value: number) => {
    pageSize.value = value;
    currentPage.value = 1;
    fetchMembers();
};

const handleSearch = () => {
    buildFilters();
    fetchMembers();
};

const handleLetterFilter = () => {
    buildFilters();
    fetchMembers();
};

const buildFilters = () => {
    const newFilters = [];

    // Add search filter if search query is provided
    if (searchQuery.value.trim()) {
        newFilters.push({ name: 'search', value: searchQuery.value.trim() });
    }

    // Add first initial filter if provided
    if (firstInitial.value.trim()) {
        newFilters.push({ name: 'firstinitial', value: firstInitial.value.trim().toUpperCase() });
    }

    // Add last initial filter if provided
    if (lastInitial.value.trim()) {
        newFilters.push({ name: 'lastinitial', value: lastInitial.value.trim().toUpperCase() });
    }

    filters.value = newFilters;
};

const toggleColumnVisibility = () => {
    showColumnModal.value = !showColumnModal.value;
};

const handleColumnVisibilitySave = (columns: string[]) => {
    visibleColumns.value = columns;
};


const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedMembers.value = [];
    } else {
        selectedMembers.value = members.value.map(m => m.id);
    }
};

// Define isColumnHidden function
const isColumnHidden = (column: string): boolean => {
    // Select and fullname columns are always visible
    if (column === 'select' || column === 'fullname') {
        return false;
    }
    return !visibleColumns.value.includes(column);
};

// Helper function to get member fullname by ID
const getMemberFullname = (memberId: number): string => {
    const member = members.value.find(m => m.id === memberId);
    return member ? member.fullname : stringsStore.getString('unknown') + ` (${memberId})`;
};

const deleteSelectedMembers = async () => {
    try {
        deleting.value = true;

        // Format data according to the webservice requirements
        const membersData = selectedMembers.value.map(userId => ({
            cohortid: props.cohortId,
            userid: userId
        }));

        // Call the deleteCohortMembers function
        await deleteCohortMembers({
            members: membersData
        });

        // Clear selection and refresh data
        selectedMembers.value = [];
        showDeleteModal.value = false;
        fetchMembers();
    } catch (err) {
        error.value = err instanceof Error ? err.message : stringsStore.getString('errordeletingmembers');
    } finally {
        deleting.value = false;
    }
};

// Watchers
watch([currentPage, sortData, filters, firstInitial, lastInitial], fetchMembers);

// Watch for changes in visibleColumns and clean up always-visible columns
watch(visibleColumns, () => {
}, { deep: true });

// Initial load
onMounted(() => {
    fetchMembers();
});
</script>

<style scoped>
/* Modal styles have been moved to separate modal components */
</style>