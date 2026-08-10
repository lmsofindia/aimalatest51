<script setup lang="ts">
import { onMounted } from 'vue';
import { loadAllStrings, loadAppConfigs } from './utils/moodle';
import Notification from 'core/notification';

// Preload all strings and app config when the component is created
onMounted(async () => {
  try {
    await Promise.all([
      loadAllStrings(),
      loadAppConfigs()
    ]);
  } catch (err: any) {
    Notification.exception(err);
  }
});
</script>

<template>
  <div class="container-fluid">
    <!-- Router view to render current route component -->
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
  </div>
</template>

<style scoped>
/* Fade transition for router view */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
