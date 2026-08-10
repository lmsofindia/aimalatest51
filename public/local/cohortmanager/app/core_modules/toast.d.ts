declare module 'core/toast' {
    /**
     * Add a toast notification to the page.
     *
     * @param {string} message The message to display
     * @param {string|object} [type] The type of toast ('success', 'error', 'warning', 'info') or options object
     * @param {number} [delay=4000] The delay in milliseconds before the toast disappears
     * @return {void}
     */
    export function add(message: string, type?: string | object, delay?: number): void;

    /**
     * Add a toast region to the page.
     *
     * @param {string} selector The CSS selector for the region
     * @return {void}
     */
    export function addToastRegion(selector: string): void;

    /**
     * Remove a toast region from the page.
     *
     * @param {string} selector The CSS selector for the region
     * @return {void}
     */
    export function removeToastRegion(selector: string): void;
}