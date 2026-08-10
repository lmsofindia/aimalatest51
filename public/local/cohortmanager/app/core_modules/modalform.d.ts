declare module 'core_form/modalform' {
    class ModalForm {
        events: {
            FORM_SUBMITTED: string;
            FORM_CANCELLED: string;
            CLIENT_VALIDATION_ERROR: string;
            SERVER_VALIDATION_ERROR: string;
            ERROR: string;
            NOSUBMIT_BUTTON_PRESSED: string;
            SUBMIT_BUTTON_PRESSED: string;
            CANCEL_BUTTON_PRESSED: string;
            LOADED: string;
        };

        constructor(config: {
            formClass: string;
            moduleName?: string;
            modalConfig?: any;
            args?: any;
            saveButtonText?: string;
            saveButtonClasses?: string;
            returnFocus?: HTMLElement;
        });

        show(): Promise<void>;
        addEventListener(event: string, callback: Function): void;
        getBody(formDataString: string): Promise<{ html: string, js: string }>;
    }

    export default ModalForm;
}