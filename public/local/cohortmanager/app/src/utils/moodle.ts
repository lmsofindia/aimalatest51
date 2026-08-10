import Ajax from "core/ajax";
import * as Config from "core/config";
import Notification from "core/notification";
import DynamicForm from "core_form/dynamicform";
import ModalForm from "core_form/modalform";
import Templates from "core/templates";
import { useStringsStore } from '../stores/strings';
import { useAppStore } from '../stores/app';
import Fragment from 'core/fragment';
import {
  extractSearchResponse,
  extractCreateResponse,
  extractUpdateResponse,
  extractDeleteResponse,
  extractGetCohortsResponse
} from './ajax-response';

// Import interfaces from the unified interfaces file
import type {
  Template
} from '../types/interfaces';


const isDebugEnabled = Config.developerdebug;

/**
 * Perform an AJAX call to a Moodle web service.
 *
 * @param {string} method - The name of the web service method to call.
 * @param {Object} [args={}] - The arguments to pass to the web service.
 *
 * @returns {Promise<Object>} - The result of the AJAX call.
 *
 * @throws {Error} If the AJAX call fails, this function will rethrow the error.
 */
const ajax = async (method: string, args: Object): Promise<object> => {
  const request = {
    methodname: method,
    args: Object.assign({}, args),
  };

  try {
    const responses = await Ajax.call([request]);
    // Ajax.call returns an array, we need the first response
    return responses[0];
  } catch (e) {
    if (isDebugEnabled) {
      Notification.exception(e);
    }
    throw e;
  }
};

// Funções específicas para cada tipo de chamada AJAX com tratamento de resposta tipado

/**
 * Buscar cohorts com tratamento de resposta tipado
 */
const searchCohorts = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_search_cohorts', args);
  return extractSearchResponse(response);
};

/**
 * Criar cohorts com tratamento de resposta tipado
 */
const createCohorts = async (args: Object): Promise<any[]> => {
  const response = await ajax('local_cohortmanager_create_cohorts', args);
  return extractCreateResponse(response).cohorts;
};

/**
 * Atualizar cohorts com tratamento de resposta tipado
 */
const updateCohorts = async (args: Object): Promise<Object> => {
  await ajax('local_cohortmanager_update_cohorts', args);
  return extractUpdateResponse();
};

/**
 * Deletar cohorts com tratamento de resposta tipado
 */
const deleteCohorts = async (args: Object): Promise<Object> => {
  await ajax('local_cohortmanager_delete_cohorts', args);
  return extractDeleteResponse();
};

/**
 * Obter detalhes de cohorts com tratamento de resposta tipado
 */
const getCohorts = async (args: Object): Promise<any[]> => {
  const response = await ajax('local_cohortmanager_get_cohorts', args);
  return extractGetCohortsResponse(response);
};

/**
 * Obter membros de cohorts com tratamento de resposta tipado
 */
const getCohortMembers = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_cohort_members_table_data', args);
  return response;
};

/**
 * Get cohort context information with treatment
 */
const getCohortContextInfo = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_cohort_context_info', args);
  return response;
};

/**
 * Get all strings for the local_cohortmanager component from external service
 */
const getAllStrings = async (): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_all_strings', {});
  return response;
};

/**
 * Get app configuration from external service
 */
const getAppConfig = async (): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_app_config', {});
  return response;
};

/**
 * Get cohort customfields AJAX
 */
const getCohortCustomfieldFormAjax = async (cohortid: number): Promise<any> => {
  const response = await ajax('core_form_dynamic_form', {
    form: 'local_cohortmanager\\form\\customfield_form',
    formdata: `id=${cohortid}`
  });
  return response;
};

/**
 * Count cohort members
 */
const countCohortMembers = async (cohortid: number): Promise<any> => {
  const response = await ajax('local_cohortmanager_count_cohort_members', {
    cohortid: cohortid
  });
  return response;
};

/**
 * Count cohort enrol instances
 */
const countCohortEnrolInstances = async (cohortid: number): Promise<any> => {
  const response = await ajax('local_cohortmanager_count_cohort_enrol_instances', {
    cohortid: cohortid
  });
  return response;
};

/**
 * Delete cohort members with treatment
 */
const deleteCohortMembers = async (args: Object): Promise<Object> => {
  await ajax('local_cohortmanager_delete_cohort_members', args);
  return extractDeleteResponse();
};

/**
 * Get cohort enrol instances with treatment
 */
const getCohortEnrolInstances = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_cohort_enrol_instances', args);
  return response;
};

/**
 * Get potential cohort courses with treatment
 */
const getPotentialCohortCourses = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_potential_cohort_courses', args);
  return response;
};

/**
 * Get cohort course roles with treatment
 */
const getCohortCourseRoles = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_cohort_course_roles', args);
  return response;
};

/**
 * Create cohort enrol instances with treatment
 */
const createCohortEnrolInstances = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_create_cohort_enrol_instances', args);
  return response;
};

/**
 * Get course groups with treatment
 */
const getCourseGroups = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_course_groups', args);
  return response;
};

/**
 * Delete cohort enrol instance with treatment
 */
const deleteCohortEnrolInstance = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_delete_cohort_enrol_instance', args);
  return response;
};

const toggleCohortEnrolInstanceStatus = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_toggle_cohort_enrol_instance_status', args);
  return response;
};

/**
 * Get user context roles with treatment
 */
const getUserContextRoles = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_user_context_roles', args);
  return response;
};

/**
 * Get single role with treatment
 */
const getRole = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_get_role', args);
  return response;
};

/**
 * Create role with treatment
 */
const createRole = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_create_role', args);
  return response;
};

/**
 * Update role with treatment
 */
const updateRole = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_update_role', args);
  return response;
};

/**
 * Delete role with treatment
 */
const deleteRole = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_delete_role', args);
  return response;
};

const listCohortRoleAssignments = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_list_cohort_role_assignments', args);
  return response;
};

const deleteCohortRoleAssignment = async (args: Object): Promise<any> => {
  const response = await ajax('local_cohortmanager_delete_cohort_role_assignment', args);
  return response;
};

const showAddCohortRoleAssignmentForm = (cohortid: number, title: string, saveButtonText: string): ModalForm => {
  const modalForm = new ModalForm({
    formClass: 'local_cohortmanager\\form\\add_cohort_role_assignment_form',
    saveButtonText: saveButtonText,
    modalConfig: {
      title: title,
    },
    args: {
      cohortid: cohortid
    }
  });
  return modalForm;
};

/**
 * Get cohort customfields
 */
const getCohortCustomfieldForm = async (cohortid: number): Promise<Template> => {

  const response = await getCohortCustomfieldFormAjax(cohortid);

  return {
    html: response.html,
    js: Fragment.processCollectedJavascript(response.javascript)
  };
};

/**
 * Get cohort customfields
 */
const getCustomfieldDynamicForm = async (selector: string): Promise<any> => {

  const element = document.querySelector(selector) as HTMLElement;
  const response = new DynamicForm(
    element,
    'local_cohortmanager\\form\\customfield_form'
  );
  return response;
};

/**
 * Show add Members modal
 */
const showAddMembersForm = (cohortid: number, title: string, saveButtonText: string): ModalForm => {

  const modalForm = new ModalForm({
    formClass: 'local_cohortmanager\\form\\add_members_form',
    saveButtonText: saveButtonText,
    modalConfig: {
      title:  title ,
    },
    args: {
      id: cohortid
    }
  });
  return modalForm;
};

/**
 * Show add Enrol Instances modal
 */
const showAddEnrolInstancesForm = (cohortid: number, title: string, saveButtonText: string): ModalForm => {

  const modalForm = new ModalForm({
    formClass: 'local_cohortmanager\\form\\add_enrol_instances_form',
    saveButtonText: saveButtonText,
    modalConfig: {
      title:  title,
    },
    args: {
      id: cohortid
    }
  });
  return modalForm;
};

/**
 * Show edit Enrol Instances modal
 */
const showEditEnrolInstancesForm = (enrolinstanceid: number, title: string, saveButtonText: string): ModalForm => {

  const modalForm = new ModalForm({
    formClass: 'local_cohortmanager\\form\\edit_enrol_instances_form',
    saveButtonText: saveButtonText,
    modalConfig: {
      title:  title,
    },
    args: {
      id: enrolinstanceid
    }
  });
  return modalForm;
};

/**
 * Show create/edit role modal
 */
const showRoleForm = (roleid: number, title: string, saveButtonText: string): ModalForm => {

  const modalForm = new ModalForm({
    formClass: 'local_cohortmanager\\form\\role_form',
    saveButtonText: saveButtonText,
    modalConfig: {
      title: title,
    },
    args: {
      roleid: roleid
    }
  });
  return modalForm;
};

/**
 *
 */
const getCustomfieldTemplateConfig = async (component: string, area: string, itemid: number): Promise<any> => {
  const response = await ajax('core_customfield_reload_template', {
    component: component,
    area: area,
    itemid: itemid
  });

  return response;
};

/**
 * 
 */
const getCustomfieldlist = async (): Promise<Template> => {

  let data = await getCustomfieldTemplateConfig(
    'core_cohort',
    'cohort',
    0
  );

  const response = await Templates.renderForPromise('core_customfield/list', data);

  return {
    html: response.html,
    js: response.js
  };
};

/**
 * Perform an AJAX call to a Moodle web service with file uploads.
 *
 * @param {Object} request - The request object to pass to the web service.
 * @param {File[]} files - The files to upload to the web service.
 *
 * @returns {Promise<Object>} - The result of the AJAX call.
 *
 * @throws {Error} If the AJAX call fails, this function will rethrow the error.
 */
const requestWithFiles = async (request: object, files: File[]): Promise<object> => {
  try {
    return await Ajax.callWithFiles(request, files);
  } catch (e) {
    if (isDebugEnabled) {
      Notification.exception(e);
    }
    throw e;
  }
};

/**
 * Load all strings for the application from the external service
 * This replaces the need to declare all string keys manually
 */
const loadAllStrings = async () => {
  const stringsStore = useStringsStore();
  await stringsStore.loadAllStringsFromExternal();
};

/**
 * Load all App configs
 * This replaces the need to declare all string keys manually
 */
const loadAppConfigs = async () => {
  const appStore = useAppStore();
  await appStore.fetchAppConfig();
};

export {
  ajax,
  requestWithFiles,
  searchCohorts,
  createCohorts,
  updateCohorts,
  deleteCohorts,
  getCohorts,
  getCohortMembers,
  getCohortContextInfo,
  getAllStrings,
  getAppConfig,
  loadAllStrings,
  loadAppConfigs,
  getCustomfieldTemplateConfig,
  getCustomfieldlist,
  getCohortCustomfieldForm,
  getCustomfieldDynamicForm,
  showAddMembersForm,
  showAddEnrolInstancesForm,
  showEditEnrolInstancesForm,
  showRoleForm,
  countCohortMembers,
  deleteCohortMembers,
  getCohortEnrolInstances,
  countCohortEnrolInstances,
  getPotentialCohortCourses,
  getCohortCourseRoles,
  createCohortEnrolInstances,
  getCourseGroups,
  deleteCohortEnrolInstance,
  toggleCohortEnrolInstanceStatus,
  getUserContextRoles,
  getRole,
  createRole,
  updateRole,
  deleteRole,
  listCohortRoleAssignments,
  deleteCohortRoleAssignment,
  showAddCohortRoleAssignmentForm
};