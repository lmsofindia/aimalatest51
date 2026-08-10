/**
 * Unified interfaces for Cohort Manager application
 * This file consolidates all interfaces from across the application
 */

// ============================================================================
// MOODLE API INTERFACES
// ============================================================================

/**
 * Interface base para Cohort
 */
export interface Cohort {
  id: number;
  name: string;
  idnumber: string;
  description: string;
  descriptionformat: number;
  visible: boolean;
  theme?: string;
  members: number;
  enrols: number;
  customfields?: Array<{
    name: string;
    shortname: string;
    type: string;
    valueraw: string;
    value: string;
  }>;
}

/**
 * Resposta da API core_cohort_search_cohorts
 */
export interface CohortSearchResponse {
  cohorts: Cohort[];
  total: number;
}

/**
 * Resposta da API core_cohort_create_cohorts
 */
export interface CohortCreateResponse {
  cohorts: Cohort[];
}

/**
 * Resposta da API core_cohort_update_cohorts
 */
export interface CohortUpdateResponse {
  success: Boolean;
}

/**
 * Resposta da API core_cohort_delete_cohorts
 */
export interface CohortDeleteResponse {
  success: Boolean;
}

/**
 * Resposta genérica da API Ajax
 */
export interface AjaxResponse {
  [key: string]: any;
}

/**
 * Tipos de retorno para as funções AJAX
 */
export type MoodleAjaxResponse<T = AjaxResponse> = T;

// ============================================================================
// UTILITY INTERFACES
// ============================================================================

/**
 * Template interface for HTML/JS responses
 */
export interface Template {
  html: string;
  js: string;
}

/**
 * Cohort interface for cohort operations
 */
export interface CohortInterface {
  cohortid: number;
  userids: number[];
}

/**
 * Cohort member interface
 */
export interface CohortMember {
  id: number;
  fullname: string;
  username: string;
  email: string;
  firstname: string;
  lastname: string;
  picture: number;
  imagealt: string;
  city: string;
  country: string;
}

/**
 * Cohort enrol instance interface
 */
export interface CohortEnrolInstance {
  id: number;
  courseid: number;
  courseshortname: string;
  coursefullname: string;
  roleid: number;
  rolename: string;
  status: boolean;
  cohortid: number;
  enroled: number;
  groupid: number;
  groupname: string;
  groupmembers: number;
  timecreated: string;
  timemodified: string;
}

/**
 * Options for TinyMCE editor initialization
 */
export interface TinyMceOptions {
  context?: number;
  draftitemid?: number;
  filepicker?: Record<string, any>;
  language?: Record<string, any>;
  currentLanguage?: string;
  branding?: boolean;
  css?: string[];
  extended_valid_elements?: string;
  plugins?: Record<string, any>;
}

// ============================================================================
// STORE INTERFACES
// ============================================================================

/**
 * External string data interface
 */
export interface ExternalStringData {
  identifier: string;
  value: string;
}

/**
 * Language data interface
 */
export interface LanguageData {
  language: string;
  strings: ExternalStringData[];
}

/**
 * Strings state interface
 */
export interface StringsState {
  strings: Record<string, string>;
  loading: boolean;
  error: string | null;
  loadedComponents: Set<string>;
}

/**
 * App configuration interface
 */
export interface AppConfig {
  allowcohortthemes?: boolean;
  themelist?: Array<Object>;
  contextlist?: Array<Object>;
  tinymceconfig?: string;
}

/**
 * App state interface
 */
export interface AppState {
  appConfig: AppConfig | null;
  error: string | null;
}

// ============================================================================
// MOODLE CORE MODULE INTERFACES
// ============================================================================

/**
 * DynamicForm events interface
 */
export interface DynamicFormEvents {
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
 * Form submission response interface
 */
export interface FormSubmissionResponse {
  submitted: boolean;
  html: string;
  javascript: string;
  data?: string;
}

/**
 * Fragment parameters interface
 */
export interface FragmentParams {
  [key: string]: any;
}

/**
 * Fragment result interface
 */
export interface FragmentResult {
  html: string;
  javascript: string;
}

/**
 * String request interface
 */
export interface StringRequest {
  key: string;
  component?: string;
  lang?: string;
  param?: object | string;
}

/**
 * ModalForm events interface
 */
export interface ModalFormEvents {
  FORM_SUBMITTED: string;
  FORM_CANCELLED: string;
  CLIENT_VALIDATION_ERROR: string;
  SERVER_VALIDATION_ERROR: string;
  ERROR: string;
  NOSUBMIT_BUTTON_PRESSED: string;
  SUBMIT_BUTTON_PRESSED: string;
  CANCEL_BUTTON_PRESSED: string;
  LOADED: string;
}

/**
 * Toast options interface
 */
export interface ToastOptions {
  message: string;
  type?: string | object;
  delay?: number;
}

/**
* Course interface for potential courses
*/
export interface Course {
 id: number;
 fullname: string;
}

/**
 * Role interface for course roles
 */
export interface Role {
 id: number;
 name: string;
}

export interface RoleFormData extends Role {
  shortname: string;
  description: string;
  archetype: string;
}

/**
 * Group interface for course groups
 */
export interface Group {
 id: number;
 name: string;
 courseid: number;
}

/**
* Selected course with role and status
*/
export interface SelectedCourse {
 courseid: number;
 coursename: string;
 status: number;
 roleid: number;
 rolename: string;
 groupid?: number;
 groupname?: string;
}
export interface Pagination {
  page: number;
  perpage: number;
  total: number;
}

export interface CohortRoleAssignment {
  id: number;
  userid: number;
  userfullname: string;
  useremail: string;
  roleid: number;
  rolename: string;
  roleshortname: string;
  cohortid: number;
  cohortname: string;
  userroleassignmentscount: number;
  timecreated: number;
  timemodified: number;
}