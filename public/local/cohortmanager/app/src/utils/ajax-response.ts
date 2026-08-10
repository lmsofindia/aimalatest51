import type {
  CohortSearchResponse,
  CohortCreateResponse,
  CohortUpdateResponse,
  CohortDeleteResponse,
  AjaxResponse
} from '../types/interfaces';
import Notification from 'core/notification';

/**
 * Função segura para extrair dados da resposta da API core_cohort_search_cohorts
 */
export const extractSearchResponse = (response: AjaxResponse): CohortSearchResponse => {
  
  // Handle the case where response might be an array of promises or actual data
  let cohorts = [];
  let total = 0;

  cohorts = Array.isArray(response.cohorts) ? response.cohorts : [];
  total = response.total || 0;
  
  return {
    cohorts,
    total
  };
};

/**
 * Função segura para extrair dados da resposta da API core_cohort_create_cohorts
 */
export const extractCreateResponse = (response: AjaxResponse): CohortCreateResponse => {
  return {
    cohorts: Array.isArray(response) ? response : []
  };
};

/**
 * Função segura para extrair dados da resposta da API core_cohort_update_cohorts
 */
export const extractUpdateResponse = (): CohortUpdateResponse => {
  return {
    success: true
  };
};

/**
 * Função segura para extrair dados da resposta da API core_cohort_delete_cohorts
 */
export const extractDeleteResponse = (): CohortDeleteResponse => {
  return {
    success: true
  };
};

/**
 * Função segura para extrair dados da resposta da API core_cohort_get_cohorts
 */
export const extractGetCohortsResponse = (response: AjaxResponse): any[] => {
  return Array.isArray(response) ? response : [];
};

/**
 * Função genérica para extrair dados de resposta com fallback seguro
 */
export const safeExtractResponse = <T>(response: AjaxResponse, extractor: (response: AjaxResponse) => T, defaultValue: T): T => {
  try {
    return extractor(response);
  } catch (err) {
    Notification.exception(err);
    return defaultValue;
  }
};