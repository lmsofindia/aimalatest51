import { defineStore } from 'pinia';
import { getAppConfig } from '../utils/moodle';
import Notification from 'core/notification';

// Import interfaces from the unified interfaces file
import type {
  AppConfig,
  AppState
} from '../types/interfaces';

export const useAppStore = defineStore('app', {
  state: (): AppState => ({
    appConfig: null,
    error: null,
  }),

  getters: {
    getAllowCohortThemes: (state: AppState) => (): boolean | null => {
      return state.appConfig?.allowcohortthemes || null;
    },

    getThemeList: (state: AppState) => (): Array<Object> => {
      return state.appConfig?.themelist || [];
    },

    getContextList: (state: AppState) => (): Array<Object> => {
      return state.appConfig?.contextlist || [];
    },

    getTinyMCEConfig: (state: AppState) => (): Object => {
      return JSON.parse(state.appConfig?.tinymceconfig || '') || {};
    },

    isAllowCohortThemesEnabled: (state: AppState) => (): boolean => {
      return state.appConfig?.allowcohortthemes === true;
    },

    hasError: (state: AppState) => state.error !== null,
  },

  actions: {
    async fetchAppConfig() {
      this.error = null;

      try {
        const result = await getAppConfig();
        this.appConfig = result;
      } catch (err) {
        Notification.exception(err);
        this.appConfig = null;
        this.error = 'Failed to fetch app configuration';
      }
    },

    setAppConfig(config: AppConfig) {
      this.appConfig = config;
    },

    clearError() {
      this.error = null;
    },
  },
});