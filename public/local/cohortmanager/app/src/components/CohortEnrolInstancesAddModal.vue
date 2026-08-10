<script setup lang="ts">
import { useStringsStore } from '../stores/strings';
import CohortEnrolInstancesSearchModal from './CohortEnrolInstancesSearchModal.vue';
import { add } from 'core/toast';
import { createCohortEnrolInstances } from '../utils/moodle';
import Notification from 'core/notification';

const props = defineProps<{
    cohortid: number;
}>();

const emit = defineEmits<{
    'added:enrolinstances': [value: boolean];
}>();

const stringsStore = useStringsStore();

// Handle selected courses from the search modal
const handleSelectedCourses = async (courses: any[]) => {
    try {
        // Transform the courses data to match the API expected format
        const coursesData = courses.map(course => ({
            courseid: course.courseid,
            roleid: course.roleid,
            status: course.status,
            groupid: course.groupid
        }));

        // Call the API to create cohort enrol instances
        const response = await createCohortEnrolInstances({
            cohortid: props.cohortid,
            courses: coursesData
        });

        // Show success message with count
        if (response.created_count > 0) {
            add(stringsStore.getString('enrolinstanceadded') + ' ' + response.created_count, {
                type: 'success'
            });
        }

        // Show error messages if any
        if (response.errors && response.errors.length > 0) {
            response.errors.forEach((error: string) => {
                add(error, {
                    type: 'warning'
                });
            });
        }

        // Emit success event
        emit('added:enrolinstances', true);
        
    } catch (error) {
        Notification.exception(error);
        add(stringsStore.getString('errorcreatingenrolinstances'), {
            type: 'error'
        });
    }
};
</script>

<template>
    <CohortEnrolInstancesSearchModal 
        :cohortid="cohortid" 
        @selected:courses="handleSelectedCourses" 
    />
</template>