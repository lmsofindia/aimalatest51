<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import Templates from 'core/templates';
import Notification from 'core/notification';
import { useRouter } from 'vue-router';
import { getCustomfieldlist } from '@/utils/moodle';
import { useStringsStore } from '../stores/strings';

const stringsStore = useStringsStore();
const router = useRouter();

// State management
const loading = ref(false);
const template = ref({
  html: '',
  js: ''
});

// Load custom fields
const loadCustomFields = async () => {
  loading.value = true;

  try {
    template.value = await getCustomfieldlist();
    // Wait for DOM to update with HTML content
    await nextTick();
    // Execute JavaScript after HTML is rendered
    if (template.value.js) {
      Templates.runTemplateJS(template.value.js);
    }
  } catch (err) {
    Notification.exception(err);
  } finally {
    loading.value = false;
  }
};

// Navigate back to home
const goBack = () => {
  router.push('/');
};

// Initialize the component
onMounted(() => {
  loadCustomFields();
});
</script>

<template>
  <div class="container p-4" id="cohort-customfield-manager">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <h1 class="h2 mb-0">{{ stringsStore.getString('customfieldsmanagement') }}</h1>
      <div class="d-flex gap-2">
        <button @click="goBack" class="btn btn-outline-secondary"
          :title="stringsStore.getString('back')">
          <i class="fa fa-arrow-left"></i> {{ stringsStore.getString('back') }}
        </button>
      </div>
    </div>
    <div id="cohort-customfield-manager-html" v-html="template.html"></div>
  </div>
</template>

<style scoped>
</style>