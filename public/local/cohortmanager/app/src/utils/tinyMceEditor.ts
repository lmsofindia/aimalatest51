/**
 * TinyMCE Editor utilities for Cohort Manager
 *
 * This utility provides functions to initialize and manage TinyMCE editors
 * in a Vue-friendly way using Moodle's AMD require system.
 */

import { ref } from 'vue';
import { useAppStore } from '../stores/app';

// Type declaration for TinyMCE editor
declare global {
  interface Window {
    tinyMCE: {
      get: (id: string) => any;
      init: (config: any) => Promise<any[]>;
    };
    require: (modules: string[], callback: (...args: any[]) => void, error?: (error: any) => void) => void;
  }
}

// Import interfaces from the unified interfaces file
import type { TinyMceOptions } from '../types/interfaces';

/**
 * Initialize a TinyMCE editor for the specified element
 * 
 * @param elementId - The ID of the textarea element to convert to TinyMCE
 * @param options - Configuration options for the editor
 * @returns Promise that resolves when the editor is initialized
 */
export const initializeTinyMceEditor = async (elementId: string, options: TinyMceOptions = {}): Promise<void> => {
  try {
    // Get the app store to access TinyMCE configuration
    const appStore = useAppStore();
    
    // Get the default TinyMCE config from app store
    const defaultConfig = appStore.getTinyMCEConfig();

    // Use Moodle's AMD require to load the editor_tiny/editor module
    await new Promise<void>((resolve, reject) => {
      // @ts-ignore - Moodle's global require function
      window.require(['editor_tiny/editor'], (editor: any) => {
        editor.setupForElementId({
          elementId,
          options: {
            ...defaultConfig, // Override with default options
            ...options // Override with provided options
          }
        });
        resolve();
      }, (error: any) => {
        console.error('Failed to load editor_tiny/editor module:', error);
        reject(error);
      });
    });
  } catch (error) {
    console.error('Failed to initialize TinyMCE editor:', error);
    throw error;
  }
};

/**
 * Get the TinyMCE editor instance for the specified element ID
 * 
 * @param elementId - The ID of the editor element
 * @returns The TinyMCE editor instance or undefined
 */
export const getTinyMceEditor = (elementId: string): any => {
  return (window as any).tinyMCE?.get(elementId);
};

/**
 * Remove the TinyMCE editor instance for the specified element ID
 * 
 * @param elementId - The ID of the editor element
 */
export const removeTinyMceEditor = (elementId: string): void => {
  const editor = getTinyMceEditor(elementId);
  if (editor) {
    editor.remove();
  }
};

/**
 * Composable for using TinyMCE editor in Vue components
 * 
 * @param elementId - The ID of the textarea element to convert to TinyMCE
 * @param options - Configuration options for the editor
 * @returns Object with editor management functions and state
 */
export const useTinyMceEditor = (elementId: string, options: TinyMceOptions = {}) => {
  const editorInitialized = ref(false);

  /**
   * Initialize the editor
   */
  const initEditor = async (): Promise<void> => {
    if (editorInitialized.value) return;
    try {
      await initializeTinyMceEditor(elementId, options);
      editorInitialized.value = true;
    } catch (error) {
      console.error('Failed to initialize TinyMCE editor:', error);
      throw error;
    }
  };

  /**
   * Update editor content
   * 
   * @param content - The content to set in the editor
   */
  const setContent = (content: string): void => {
    if (!editorInitialized.value) return;
    
    const editor = getTinyMceEditor(elementId);
    if (editor) {
      editor.setContent(content);
    }
  };

  /**
   * Get editor content
   * 
   * @returns The current content of the editor
   */
  const getContent = (): string => {
    if (!editorInitialized.value) return '';
    
    const editor = getTinyMceEditor(elementId);
    return editor ? editor.getContent() : '';
  };

  /**
   * Remove the editor
   */
  const removeEditor = (): void => {
    if (!editorInitialized.value) return;
    
    removeTinyMceEditor(elementId);
    editorInitialized.value = false;
  };

  return {
    getTinyMceEditor,
    editorInitialized,
    initEditor,
    setContent,
    getContent,
    removeEditor
  };
};