/**
 * Type declarations for core_form/dynamicform
 * Based on lib/form/amd/src/dynamicform.js
 */

declare module 'core_form/dynamicform' {
    /**
     * Various events that can be observed.
     */
    interface DynamicFormEvents {
        // Form was successfully submitted - the response is passed to the event listener.
        // Cancellable (in order to prevent default behavior to clear the container).
        FORM_SUBMITTED: 'core_form_dynamicform_formsubmitted';
        // Cancel button was pressed.
        // Cancellable (in order to prevent default behavior to clear the container).
        FORM_CANCELLED: 'core_form_dynamicform_formcancelled';
        // User attempted to submit the form but there was client-side validation error.
        CLIENT_VALIDATION_ERROR: 'core_form_dynamicform_clientvalidationerror';
        // User attempted to submit the form but server returned validation error.
        SERVER_VALIDATION_ERROR: 'core_form_dynamicform_validationerror';
        // Error occurred while performing request to the server.
        // Cancellable (by default calls Notification.exception).
        ERROR: 'core_form_dynamicform_error';
        // Right after user pressed no-submit button,
        // listen to this event if you want to add JS validation or processing for no-submit button.
        // Cancellable.
        NOSUBMIT_BUTTON_PRESSED: 'core_form_dynamicform_nosubmitbutton';
        // Right after user pressed submit button,
        // listen to this event if you want to add additional JS validation or confirmation dialog.
        // Cancellable.
        SUBMIT_BUTTON_PRESSED: 'core_form_dynamicform_submitbutton';
        // Right after user pressed cancel button,
        // listen to this event if you want to add confirmation dialog.
        // Cancellable.
        CANCEL_BUTTON_PRESSED: 'core_form_dynamicform_cancelbutton';
    }

    /**
     * Response object for form submission
     */
    interface FormSubmissionResponse {
        submitted: boolean;
        html: string;
        javascript: string;
        data?: string;
    }

    /**
     * DynamicForm class for displaying embedded forms
     */
    export default class DynamicForm {
        /**
         * Various events that can be observed.
         */
        events: DynamicFormEvents;

        /**
         * The form class name
         */
        formClass: string;

        /**
         * The container element for the form
         */
        container: Element;

        /**
         * Constructor
         *
         * Creates an instance
         *
         * @param {Element} container - the parent element for the form
         * @param {string} formClass full name of the php class that extends \core_form\modal , must be in autoloaded location
         */
        constructor(container: Element, formClass: string);

        /**
         * Loads the form via AJAX and shows it inside a given container
         *
         * @param {Object} args
         * @return {Promise}
         * @public
         */
        load(args?: Record<string, any>): Promise<void>;

        /**
         * Triggers a custom event
         *
         * @private
         * @param {String} eventName
         * @param {*} detail
         * @param {Boolean} cancelable
         * @return {CustomEvent<unknown>}
         */
        trigger(eventName: string, detail?: any, cancelable?: boolean): CustomEvent;

        /**
         * Add listener for an event
         *
         * @param {array} args
         */
        addEventListener(...args: any[]): void;

        /**
         * Get form body
         *
         * @param {String} formDataString form data in format of a query string
         * @private
         * @return {Promise}
         */
        getBody(formDataString: string): Promise<{html: string, js: any}>;

        /**
         * On form submit
         *
         * @param {*} response Response received from the form's "process" method
         */
        onSubmitSuccess(response: any): void;

        /**
         * On exception during form processing
         *
         * @private
         * @param {Object} exception
         */
        onSubmitError(exception: any): void;

        /**
         * Click on a "submit" button that is marked in the form as registerNoSubmitButton()
         *
         * @param {Element} button that was pressed
         */
        processNoSubmitButton(button: Element): void;

        /**
         * Get the form node from the Dialogue.
         *
         * @returns {HTMLFormElement}
         */
        getFormNode(): HTMLFormElement;

        /**
         * Notifies listeners that form dirty state should be reset.
         */
        notifyResetFormChanges(): void;

        /**
         * Click on a "cancel" button
         */
        processCancelButton(): void;

        /**
         * Update form contents
         *
         * @param {object} param
         * @param {string} param.html
         * @param {string} param.js
         * @returns {Promise}
         */
        updateForm(param: {html: string, js: string}): Promise<void>;

        /**
         * Validate form elements
         * @return {Boolean} Whether client-side validation has passed, false if there are errors
         */
        validateElements(): boolean;

        /**
         * Disable buttons during form submission
         */
        disableButtons(): void;

        /**
         * Enable buttons after form submission (on validation error)
         */
        enableButtons(): void;

        /**
         * Submit the form via AJAX call to the core_form_dynamic_form WS
         */
        submitFormAjax(): Promise<void>;
    }
}