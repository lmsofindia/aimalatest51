// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Repository: thin wrapper around Moodle AJAX for all plugin external functions.
 *
 * @module     local_edzaiaxisfront/repository
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

const call = (methodname, args) =>
    Ajax.call([{methodname, args}])[0];

// ── Teacher / Dashboard ────────────────────────────────────────────────────

export const getCourseCms = (courseid) =>
    call('local_edzaiaxisfront_get_course_cms', {courseid});

export const getCmConfig = (cmid) =>
    call('local_edzaiaxisfront_get_cm_config', {cmid});

export const saveWizardConfig = (cmid, enabled_features, generation_config) =>
    call('local_edzaiaxisfront_save_wizard_config', {
        cmid,
        enabled_features: JSON.stringify(enabled_features),
        generation_config: JSON.stringify(generation_config),
    });

export const submitIngest = (cmids) =>
    call('local_edzaiaxisfront_submit_ingest', {cmids: JSON.stringify(cmids)});

export const getJobStatus = (cmid) =>
    call('local_edzaiaxisfront_get_job_status', {cmid});

export const toggleOutputVisibility = (cmid, output_type, is_visible) =>
    call('local_edzaiaxisfront_toggle_output_visibility', {cmid, output_type, is_visible});

export const saveSummaryEdit = (cmid, content) =>
    call('local_edzaiaxisfront_save_summary_edit', {cmid, content});

export const triggerRegenerate = (cmid, tasks) =>
    call('local_edzaiaxisfront_trigger_regenerate', {cmid, tasks: JSON.stringify(tasks)});

// ── Student panel ──────────────────────────────────────────────────────────

export const getCmOutputs = (cmid) =>
    call('local_edzaiaxisfront_get_cm_outputs', {cmid});

export const createChatSession = (cmid, chat_mode = 'study') =>
    call('local_edzaiaxisfront_create_chat_session', {cmid, chat_mode});

export const sendChatMessage = (session_id, message) =>
    call('local_edzaiaxisfront_send_chat_message', {session_id, message});

export const endChatSession = (session_id) =>
    call('local_edzaiaxisfront_end_chat_session', {session_id});

// ── Admin: Token reports ────────────────────────────────────────────────────

export const getTokenUsageReport = (month_year, search = '', page = 0, perpage = 100) =>
    call('local_edzaiaxisfront_get_token_usage_report', {month_year, search, page, perpage});

export const getUserDailyUsage = (userid, month_year) =>
    call('local_edzaiaxisfront_get_user_daily_usage', {userid, month_year});

// ── Admin: User overrides ───────────────────────────────────────────────────

export const saveUserOverride = (
    userid,
    chat_session_msg_limit,
    chat_daily_msg_limit,
    chat_monthly_msg_limit,
    token_monthly_limit,
    note = '',
) =>
    call('local_edzaiaxisfront_save_user_override', {
        userid,
        chat_session_msg_limit,
        chat_daily_msg_limit,
        chat_monthly_msg_limit,
        token_monthly_limit,
        note,
    });

export const deleteUserOverride = (userid) =>
    call('local_edzaiaxisfront_delete_user_override', {userid});

export const listUserOverrides = () =>
    call('local_edzaiaxisfront_list_user_overrides', {});

// ── Admin: KB ──────────────────────────────────────────────────────────────

export const ingestKbUrl = (url, title = '', description = '', doc_type = 'support') =>
    call('local_edzaiaxisfront_ingest_kb_url', {url, title, description, doc_type});

export const ingestKbText = (title, content, description = '', doc_type = 'support') =>
    call('local_edzaiaxisfront_ingest_kb_text', {title, content, description, doc_type});

export const ingestKbFile = (title, filename, file_base64, doc_type = 'support') =>
    call('local_edzaiaxisfront_ingest_kb_file', {title, filename, file_base64, doc_type});

export const listKbItems = () =>
    call('local_edzaiaxisfront_list_kb_items', {});

export const toggleKbItem = (kb_item_id, is_active) =>
    call('local_edzaiaxisfront_toggle_kb_item', {kb_item_id, is_active});

export const deleteKbItem = (kb_item_id) =>
    call('local_edzaiaxisfront_delete_kb_item', {kb_item_id});

export const updateKbItem = (kb_item_id, title, doc_type, content = '') =>
    call('local_edzaiaxisfront_update_kb_item', {kb_item_id, title, doc_type, content});

export const kbBackfillActive = () =>
    call('local_edzaiaxisfront_kb_backfill_active', {});

// ── Teacher content editing ────────────────────────────────────────────────

export const getTeacherCmOutputs = (cmid) =>
    call('local_edzaiaxisfront_get_teacher_cm_outputs', {cmid});

export const saveGlossaryEdit = (cmid, terms_json) =>
    call('local_edzaiaxisfront_save_glossary_edit', {cmid, terms_json});

export const saveFlashcardsEdit = (cmid, cards_json) =>
    call('local_edzaiaxisfront_save_flashcards_edit', {cmid, cards_json});

export const saveQuizEdit = (cmid, questions_json) =>
    call('local_edzaiaxisfront_save_quiz_edit', {cmid, questions_json});

export const saveFaqEdit = (cmid, faqs) =>
    call('local_edzaiaxisfront_save_faq_edit', {cmid, faqs});

export const getCourseAiCmids = (courseid) =>
    call('local_edzaiaxisfront_get_course_ai_cmids', {courseid});

// ── Chatbot / thread helpers ───────────────────────────────────────────────

export const getSessionMessages = (session_id) =>
    call('local_edzaiaxisfront_get_session_messages', {session_id});

export const renameThread = (session_id, name) =>
    call('local_edzaiaxisfront_rename_thread', {session_id, name});

export const getSupportRightPanel = () =>
    call('local_edzaiaxisfront_get_support_right_panel', {});

export const getStudyActivityStream = (limit = 8) =>
    call('local_edzaiaxisfront_get_study_activity_stream', {limit});

export const getTokenUsageSummary = () =>
    call('local_edzaiaxisfront_get_token_usage_summary', {});
