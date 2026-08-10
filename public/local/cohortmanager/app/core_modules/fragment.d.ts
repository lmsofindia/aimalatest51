/**
 * Type definitions for the core/fragment AMD module
 * @module core/fragment
 */

declare module 'core/fragment' {
    /**
     * Parameters for the fragment callback
     */
    interface FragmentParams {
        [key: string]: any;
    }

    /**
     * Result from loading a fragment
     */
    interface FragmentResult {
        html: string;
        javascript: string;
    }

    /**
     * The core/fragment AMD module
     */
    export const fragmentAppend: (
        component: string,
        callback: string,
        contextid: number,
        params: FragmentParams
    ) => Promise<[string, string]>;

    /**
     * Process collected JavaScript from fragment requirements
     * @param js - JavaScript string to process
     * @return Processed JavaScript string
     */
    export const processCollectedJavascript: (js: string) => string;

    /**
     * Default export containing all fragment functionality
     */
    const fragment: {
        /**
         * Appends HTML and JavaScript fragments to specified nodes.
         * Callbacks called by this AMD module are responsible for doing the appropriate security checks
         * to access the information that is returned. This only does minimal validation on the context.
         *
         * @param component - Component where callback is located
         * @param callback - Callback function name
         * @param contextid - Context ID of the fragment
         * @param params - Parameters for the callback
         * @return Promise that resolves with the html and js
         */
        loadFragment: (
            component: string,
            callback: string,
            contextid: number,
            params: FragmentParams
        ) => Promise<[string, string]>;

        /**
         * Converts the JS that was received from collecting JS requirements on the $PAGE so it can be added to the existing page
         *
         * @param js - JavaScript string to process
         * @return Processed JavaScript string
         */
        processCollectedJavascript: (js: string) => string;
    };

    export default fragment;
}