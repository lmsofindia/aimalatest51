import { defineStore } from 'pinia';
import { getAllStrings } from '../utils/moodle';
import Notification from 'core/notification';

// Import interfaces from the unified interfaces file
import type {
  ExternalStringData,
  LanguageData,
  StringsState
} from '../types/interfaces';

export const useStringsStore = defineStore('strings', {
  state: (): StringsState => ({
    strings: {},
    loading: false,
    error: null,
    loadedComponents: new Set(),
  }),

  getters: {
    getString: (state: StringsState) => (key: string, fallback?: string) => {
      return state.strings[key] || fallback || key;
    },
    
    isComponentLoaded: (state: StringsState) => (component: string) => {
      return state.loadedComponents.has(component);
    },
    
    isLoading: (state: StringsState) => state.loading,
    
    hasError: (state: StringsState) => state.error !== null,
  },

  actions: {
    async loadAllStringsFromExternal() {
      this.loading = true;

      try {
        const response = await getAllStrings();
        
        // Process the response from external service
        // English is used only as fallback for missing keys
        if (response && Array.isArray(response)) {
          const enStrings: Record<string, string> = {};
          const langStrings: Record<string, string> = {};

          response.forEach((langData: LanguageData) => {
            if (langData.strings && Array.isArray(langData.strings)) {
              const target = langData.language === 'en' ? enStrings : langStrings;
              langData.strings.forEach((stringData: ExternalStringData) => {
                if (stringData.identifier && stringData.value) {
                  target[stringData.identifier] = stringData.value;
                }
              });
            }
          });

          // Apply English first as base, then overwrite with translated strings
          this.strings = { ...enStrings, ...langStrings };
        }
      } catch (err) {
        Notification.exception(err);
      } finally {
        this.loading = false;
      }
    }
  },
});