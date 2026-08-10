<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useStringsStore } from '../stores/strings';
import { getPotentialCohortCourses, getCohortCourseRoles, getCourseGroups } from '../utils/moodle';
import type { Course, Role, SelectedCourse, Group } from '../types/interfaces';
import { add } from 'core/toast';
import Notification from 'core/notification';

const props = defineProps<{
    cohortid: number;
}>();

const emit = defineEmits<{
    'selected:courses': [courses: SelectedCourse[]];
}>();

const stringsStore = useStringsStore();

// Modal state
const showModal = ref(false);
const modalTitle = ref(stringsStore.getString('addenrolinstance'));

// Search state
const searchQuery = ref('');
const searchResults = ref<Course[]>([]);
const isSearching = ref(false);
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

// Selected courses state
const selectedCourses = ref<SelectedCourse[]>([]);

// Available roles for the selected course
const availableRoles = ref<Role[]>([]);
const selectedRole = ref<number | null>(null);
const selectedStatus = ref<number>(0);

// Available groups for the selected course
const availableGroups = ref<Group[]>([]);
const selectedGroup = ref<number | null>(null);
const showGroupOptions = ref<boolean>(false);

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    if (searchDebounce) {
        clearTimeout(searchDebounce);
    }
    
    if (newQuery.trim() === '') {
        searchResults.value = [];
        return;
    }
    
    searchDebounce = setTimeout(async () => {
        await searchCourses(newQuery);
    }, 300);
});

const getExcludeCourseIds = () => {
    return selectedCourses.value.map(course => course.courseid);
};

// Search for courses
const searchCourses = async (query: string) => {
    if (!query.trim()) {
        searchResults.value = [];
        return;
    }
    
    const excludeCourseIds = getExcludeCourseIds();
    isSearching.value = true;
    try {
        const response = await getPotentialCohortCourses({
            cohortid: props.cohortid,
            query: query.trim(),
            excludecourseids: excludeCourseIds
        });
        searchResults.value = response || [];
    } catch (error) {
        Notification.exception(error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

// Handle course selection
const handleCourseSelect = async (course: Course) => {
    // Check if course is already selected
    if (selectedCourses.value.some(selected => selected.courseid === course.id)) {
        add(stringsStore.getString('coursealreadyselected'), {
            type: 'warning'
        });
        return;
    }
    
    // Get available roles for this course
    try {
        const response = await getCohortCourseRoles({
            cohortid: props.cohortid,
            courseid: course.id
        });
        availableRoles.value = response || [];
        selectedRole.value = availableRoles.value.length > 0 ? availableRoles.value[0]?.id ?? null : null;
    } catch (error) {
        Notification.exception(error);
        availableRoles.value = [];
        selectedRole.value = null;
    }
    
    // Get available groups for this course
    try {
        const response = await getCourseGroups({
            courseid: course.id
        });
        availableGroups.value = response || [];
        selectedGroup.value = availableGroups.value.length > 0 ? availableGroups.value[0]?.id ?? null : null;
        showGroupOptions.value = true;
    } catch (error) {
        Notification.exception(error);
        availableGroups.value = [];
        selectedGroup.value = null;
        showGroupOptions.value = false;
    }
    
    // Add course to selected list with default values
    const newSelectedCourse: SelectedCourse = {
        courseid: course.id,
        coursename: course.fullname,
        status: selectedStatus.value,
        roleid: selectedRole.value || 0,
        rolename: selectedRole.value ? availableRoles.value.find(r => r.id === selectedRole.value)?.name || '' : '',
        groupid: selectedGroup.value || undefined,
        groupname: selectedGroup.value ? availableGroups.value.find(g => g.id === selectedGroup.value)?.name || '' : undefined
    };
    
    selectedCourses.value.push(newSelectedCourse);
    
    // Clear search
    searchQuery.value = '';
    searchResults.value = [];
};

// Remove selected course
const removeSelectedCourse = (index: number) => {
    selectedCourses.value.splice(index, 1);
};

// Update selected course status
const updateCourseStatus = (index: number, status: number) => {
    if (selectedCourses.value[index]) {
        selectedCourses.value[index].status = status;
    }
};

// Update selected course role
const updateCourseRole = (index: number, roleid: number) => {
    if (selectedCourses.value[index]) {
        const role = availableRoles.value.find(r => r.id === roleid);
        selectedCourses.value[index].roleid = roleid;
        selectedCourses.value[index].rolename = role?.name || '';
    }
};

// Update selected course group
const updateCourseGroup = (index: number, groupid: number | undefined) => {
    if (selectedCourses.value[index]) {
        const group = availableGroups.value.find(g => g.id === groupid);
        selectedCourses.value[index].groupid = groupid || undefined;
        selectedCourses.value[index].groupname = group?.name || undefined;
    }
};

// Submit selected courses
const submitSelectedCourses = () => {
    if (selectedCourses.value.length === 0) {
        add(stringsStore.getString('noselectedcourses'), {
            type: 'warning'
        });
        return;
    }
    
    emit('selected:courses', selectedCourses.value);
    closeModal();
};

// Open modal
const openModal = () => {
    showModal.value = true;
    selectedCourses.value = [];
    searchQuery.value = '';
    searchResults.value = [];
    availableRoles.value = [];
    selectedRole.value = null;
    selectedStatus.value = 0;
    availableGroups.value = [];
    selectedGroup.value = null;
    showGroupOptions.value = false;
};

// Close modal
const closeModal = () => {
    showModal.value = false;
    selectedCourses.value = [];
    searchQuery.value = '';
    searchResults.value = [];
    availableRoles.value = [];
    selectedRole.value = null;
    selectedStatus.value = 0;
    availableGroups.value = [];
    selectedGroup.value = null;
    showGroupOptions.value = false;
};

// Handle escape key
const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeModal();
    }
};

// Initialize
onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

// Cleanup
onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleEscape);
    if (searchDebounce) {
        clearTimeout(searchDebounce);
    }
});
</script>

<template>
    <div>
        <!-- Trigger button -->
        <button class="btn btn-primary" @click="openModal"
            :title="stringsStore.getString('addenrolinstance')">
            {{ stringsStore.getString('addenrolinstance') }}
        </button>
        
        <!-- Modal -->
        <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ modalTitle }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"
                            :title="stringsStore.getString('close')"></button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Search Section -->
                        <div class="mb-4">
                            <label class="form-label">{{ stringsStore.getString('searchcourses') }}</label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        v-model="searchQuery"
                                        :placeholder="stringsStore.getString('searchcoursesplaceholder')"
                                        autocomplete="off"
                                    />
                                    
                                </div>
                                
                                <!-- Search Results -->
                                <div v-if="searchQuery && searchResults.length > 0" class="dropdown-menu show w-100">
                                    <div v-for="course in searchResults" :key="course.id" 
                                         class="dropdown-item cursor-pointer"
                                         @click="handleCourseSelect(course)">
                                        <div class="fw-bold">{{ course.fullname }}</div>
                                        <small class="text-muted">ID: {{ course.id }}</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Loading State -->
                            <div v-if="isSearching" class="text-center py-2">
                                <i class="fa fa-spinner fa-spin"></i> {{ stringsStore.getString('searching') }}
                            </div>
                            
                            <!-- No Results -->
                            <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="text-muted py-2">
                                {{ stringsStore.getString('nocoursesfound') }}
                            </div>
                        </div>
                        
                        <!-- Selected Courses Section -->
                        <div v-if="selectedCourses.length > 0">
                            <h6 class="mb-3">{{ stringsStore.getString('selectedcourses') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ stringsStore.getString('course') }}</th>
                                            <th>{{ stringsStore.getString('status') }}</th>
                                            <th>{{ stringsStore.getString('role') }}</th>
                                            <th>{{ stringsStore.getString('group') }}</th>
                                            <th>{{ stringsStore.getString('actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(course, index) in selectedCourses" :key="course.courseid">
                                            <td>
                                                <div class="fw-bold">{{ course.coursename }}</div>
                                                <small class="text-muted">ID: {{ course.courseid }}</small>
                                            </td>
                                            <td>
                                                <select 
                                                    class="form-select form-select-sm"
                                                    v-model="course.status"
                                                    @change="updateCourseStatus(index, course.status)"
                                                >
                                                    <option value=0>{{ stringsStore.getString('active') }}</option>
                                                    <option value=1>{{ stringsStore.getString('inactive') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select
                                                    class="form-select form-select-sm"
                                                    v-model="course.roleid"
                                                    @change="updateCourseRole(index, course.roleid)"
                                                >
                                                    <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                                                        {{ role.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                <select
                                                    class="form-select form-select-sm"
                                                    v-model="course.groupid"
                                                    @change="updateCourseGroup(index, course.groupid)"
                                                >
                                                    <option v-for="group in availableGroups" :key="group.id" :value="group.id">
                                                        {{ group.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                <button
                                                    class="btn btn-sm btn-outline-danger"
                                                    @click="removeSelectedCourse(index)"
                                                    :title="stringsStore.getString('remove')"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal"
                            :title="stringsStore.getString('cancel')">
                            {{ stringsStore.getString('cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" @click="submitSelectedCourses"
                            :title="stringsStore.getString('add')">
                            {{ stringsStore.getString('add') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dropdown-menu.show {
    max-height: 300px;
    overflow-y: auto;
}

.modal {
    display: block !important;
}

.modal-backdrop {
    display: none;
}
</style>