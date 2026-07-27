<?php

/**
 * Language strings — English.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name
$string['pluginname'] = 'Axis AI Learning Tools';

// ── Admin settings ────────────────────────────────────────────────────────────
$string['settings_heading']          = 'Axis AI Connection';
$string['settings_heading_desc']     = 'Configure your connection to the Axis AI service. Changes are synced to the AI service immediately on save.';
$string['api_base_url']              = 'Axis AI Base URL';
$string['api_base_url_desc']         = 'Base URL of the Axis AI FastAPI service (e.g. https://ai.yourdomain.com). No trailing slash.';
$string['api_key']                   = 'API Key';
$string['api_key_desc']              = 'Your Axis AI API key. This is stored securely and never displayed again after saving.';
$string['plugin_enabled']            = 'Enable Plugin';
$string['plugin_enabled_desc']       = 'Master switch. When disabled, no AI features are visible to teachers or students.';
$string['tenant_id']                 = 'Tenant ID';
$string['tenant_id_desc']            = 'Auto-populated after first successful connection to the Axis AI service.';

// Vimeo token
$string['vimeotoken']               = 'Vimeo API Token';
$string['vimeotoken_desc']          = 'Your Vimeo API token, required for processing videos from the Vimeo platform. This is stored securely and never displayed again after saving. Only needed if you are using the Videotime module or have Vimeo URLs — if your videos are all from YouTube or PeerTube, you can leave this blank.';
// ── Feature flags ─────────────────────────────────────────────────────────────
$string['features_heading']          = 'Enabled AI Features';
$string['features_heading_desc']     = 'Control which AI output types are available to teachers and students site-wide. Teachers may restrict these further per activity, but cannot enable features disabled here.';
$string['feature_summary']           = 'AI Summary';
$string['feature_glossary']          = 'Glossary';
$string['feature_flashcards']        = 'Flashcards';
$string['feature_quiz']              = 'Quiz Questions';
$string['feature_faq']               = 'FAQ';
$string['feature_infographic']       = 'Infographic';
$string['feature_chatbot']           = 'Study with AI (Chatbot)';
$string['feature_kb_chat']           = 'Support Chatbot';

// ── Rate limits ───────────────────────────────────────────────────────────────
$string['limits_heading']            = 'Usage Limits';
$string['limits_heading_desc']       = 'Set baseline rate limits per user. Enter 0 for unlimited. Individual users can be given higher or lower limits on the User Overrides page.';
$string['chat_session_msg_limit']    = 'Messages per Session';
$string['chat_session_msg_limit_desc'] = 'Maximum number of chat messages a user can send in a single session (0 = unlimited).';
$string['chat_daily_msg_limit']      = 'Messages per Day';
$string['chat_daily_msg_limit_desc'] = 'Maximum chat messages per user per calendar day (0 = unlimited).';
$string['chat_monthly_msg_limit']    = 'Messages per Month';
$string['chat_monthly_msg_limit_desc'] = 'Maximum chat messages per user per calendar month (0 = unlimited).';
$string['token_monthly_limit']       = 'Tenant Token Limit (Monthly)';
$string['token_monthly_limit_desc']  = 'Total tokens consumed across ALL users for this Moodle installation per month (0 = unlimited). This protects your AI cost budget.';

// ── Navigation ────────────────────────────────────────────────────────────────
$string['ai_manager']                = 'AI Learning Tools';
$string['ai_manager_nav']            = 'AI Manager';
$string['course_ai_dashboard']       = 'Course AI Dashboard';
$string['knowledge_base']            = 'Support Knowledge Base';
$string['user_overrides']            = 'User Limit Overrides';

// ── Dashboard ─────────────────────────────────────────────────────────────────
$string['dashboard_title']           = 'AI Learning Tools — {$a}';
$string['no_activities']             = 'No compatible activities found in this course. Upload PDF files, add video URLs, or create SCORM packages to get started.';
$string['configure_selected']        = 'Configure AI for Selected';
$string['select_all']                = 'Select All';
$string['deselect_all']              = 'Deselect All';
$string['activities_selected']       = '{$a} activities selected';
$string['ai_status']                 = 'AI Status';
$string['status_not_configured']     = 'Not configured';
$string['status_pending']            = 'Pending';
$string['status_processing']         = 'Processing…';
$string['status_ready']              = 'Ready';
$string['status_error']              = 'Error';
$string['status_partial']            = 'Partial';
$string['last_generated']            = 'Generated {$a}';
$string['outputs_ready']             = '{$a} outputs ready';
$string['edit_outputs']              = 'Preview & Edit';
$string['toggle_visibility']         = 'Show/Hide from students';

// ── Wizard steps ──────────────────────────────────────────────────────────────
$string['wizard_title']              = 'AI Content Generation Wizard';
$string['wizard_subtitle']           = 'Generate AI learning materials in 5 easy steps';
$string['step1_title']               = 'Select Activities';
$string['step2_title']               = 'Select Outputs';
$string['step3_title']               = 'Configure Parameters';
$string['step4_title']               = 'Review & Generate';
$string['step5_title']               = 'Monitor Progress';
$string['step_next']                 = 'Next';
$string['step_prev']                 = 'Previous';
$string['step_generate']             = 'Generate';
$string['step_cancel']               = 'Cancel';

// Wizard step 1
$string['select_activities_desc']    = 'Choose which course activities to generate AI content for. Activities with existing content show their current status.';
$string['already_generated']         = 'Already generated — will refresh';
$string['processing']                = 'Currently processing';
$string['activity_type_pdf']         = 'PDF File';
$string['activity_type_video']       = 'Video';
$string['activity_type_scorm']       = 'SCORM';
$string['activity_type_page']        = 'Page';
$string['activity_type_book']        = 'Book';
$string['activity_type_url']         = 'URL';
$string['activity_type_unsupported'] = 'Unsupported type';

// Wizard step 2
$string['select_outputs_desc']       = 'Choose which types of AI content to generate. Only features enabled by your site administrator are shown.';
$string['output_summary']            = 'Summary';
$string['output_summary_desc']       = 'A concise overview of the content.';
$string['output_glossary']           = 'Glossary';
$string['output_glossary_desc']      = 'Key terms and definitions.';
$string['output_flashcards']         = 'Flashcards';
$string['output_flashcards_desc']    = 'Interactive study cards.';
$string['output_quiz']               = 'Quiz Questions';
$string['output_quiz_desc']          = 'Practice questions with answers.';
$string['output_faq']                = 'FAQ';
$string['output_faq_desc']           = 'Frequently asked questions.';
$string['output_infographic']        = 'Infographic';
$string['output_infographic_desc']   = 'Visual HTML summary.';

// Wizard step 3
$string['config_params_desc']        = 'Configure generation parameters. These apply to all selected activities.';
$string['language_label']            = 'Output Language';
$string['custom_instructions']       = 'Custom Instructions (optional)';
$string['custom_instructions_help']  = 'Extra guidance for the AI — e.g. "Focus on chapters 2-4" or "Use clinical terminology".';
$string['summary_length']            = 'Summary Length';
$string['summary_brief']             = 'Brief';
$string['summary_standard']          = 'Standard';
$string['summary_detailed']          = 'Detailed';
$string['flashcard_count']           = 'Flashcards to Generate';
$string['quiz_count']                = 'Quiz Questions to Generate';
$string['quiz_difficulty']           = 'Difficulty';
$string['quiz_easy']                 = 'Easy';
$string['quiz_balanced']             = 'Balanced';
$string['quiz_hard']                 = 'Hard';
$string['blooms_heading']            = "Bloom's Taxonomy Distribution";
$string['blooms_desc']               = 'Set the cognitive level distribution for quiz questions. Must total 100%.';
$string['blooms_remember']           = 'Remember';
$string['blooms_understand']         = 'Understand';
$string['blooms_apply']              = 'Apply';
$string['blooms_analyze']            = 'Analyze';
$string['blooms_evaluate']           = 'Evaluate';
$string['blooms_create']             = 'Create';
$string['question_types_heading']    = 'Question Types';
$string['qt_multichoice']            = 'Multiple Choice';
$string['qt_truefalse']              = 'True / False';
$string['qt_shortanswer']            = 'Short Answer';
$string['qt_essay']                  = 'Essay';

// Wizard step 4
$string['review_desc']               = 'Review your configuration before generating.';
$string['review_activities']         = 'Activities: {$a}';
$string['review_outputs']            = 'Outputs to generate: {$a}';
$string['review_warning_refresh']    = '{$a} activities will have their existing content refreshed.';
$string['generate_btn']              = 'Generate AI Content';

// Wizard step 5
$string['monitor_desc']              = 'Your content is being generated. You can leave this page — progress is saved.';
$string['monitor_complete']          = 'All done! AI content is ready for students.';
$string['monitor_error']             = 'Some activities encountered errors. Check the dashboard for details.';

// ── Student panel ─────────────────────────────────────────────────────────────
$string['student_panel_title']       = 'AI Learning Tools';
$string['tab_summary']               = 'Summary';
$string['tab_glossary']              = 'Glossary';
$string['tab_flashcards']            = 'Flashcards';
$string['tab_quiz']                  = 'Practice Quiz';
$string['tab_faq']                   = 'FAQ';
$string['tab_infographic']           = 'Infographic';
$string['tab_chat']                  = 'Chat';
$string['chat_study_mode']           = 'Study with AI';
$string['chat_support_mode']         = 'Support';
$string['chat_placeholder']          = 'Ask a question about this content…';
$string['chat_send']                 = 'Send';
$string['chat_thinking']             = 'Thinking…';
$string['no_content_yet']            = 'AI content is being prepared. Check back shortly.';
$string['content_hidden']            = 'This content type is not currently available.';
$string['flashcard_flip']            = 'Tap to flip';
$string['flashcard_prev']            = 'Previous';
$string['flashcard_next']            = 'Next';
$string['quiz_check']                = 'Check Answer';
$string['quiz_next_q']               = 'Next Question';
$string['quiz_result']               = 'Your Score: {$a}%';
$string['request_regenerate']        = 'Request Refresh';
$string['regenerate_requested']      = 'Refresh requested — a teacher will review your request.';

// ── Errors ────────────────────────────────────────────────────────────────────
$string['error_no_api_key']          = 'Axis AI API key not configured. Please contact your administrator.';
$string['error_api_connection']      = 'Could not connect to AI service. Please try again later. ({$a})';
$string['error_tenant_not_found']    = 'Tenant configuration not found. Please re-save the plugin settings.';
$string['error_no_permission']       = 'You do not have permission to perform this action.';
$string['error_invalid_cmid']        = 'Invalid course module ID.';
$string['error_unsupported_type']    = 'This activity type is not supported for AI generation.';
$string['error_job_failed']          = 'AI processing failed for {$a}. Please try regenerating.';

// ── Capabilities ──────────────────────────────────────────────────────────────
$string['edzaiaxisfront:managecourse']  = 'Manage AI content for courses (teacher)';
$string['edzaiaxisfront:viewstudent']   = 'View AI learning tools (student)';
$string['edzaiaxisfront:manageplugin']  = 'Manage plugin settings (admin)';
$string['edzaiaxisfront:viewreports']   = 'View AI usage reports';

// ── Dashboard (template strings) ──────────────────────────────────────────────
$string['dashboard_title']           = 'AI Manager';
$string['no_supported_cms']          = 'No supported activities found in this course. Supported types: PDF, video, SCORM, Page, Book, URL.';
$string['wizard_launch']             = 'New Generation Wizard';
$string['status_not_ingested']       = 'Not ingested';
$string['status_failed']             = 'Failed';
$string['plugindisabled']            = 'The Axis AI plugin is currently disabled.';

// ── Wizard (template strings) ─────────────────────────────────────────────────
$string['wizard_step1']              = 'Activities';
$string['wizard_step2']              = 'Outputs';
$string['wizard_step3']              = 'Parameters';
$string['wizard_step4']              = 'Review';
$string['wizard_step5']              = 'Progress';
$string['wizard_step1_heading']      = 'Step 1 — Select Activities';
$string['wizard_step1_desc']         = 'Select one or more activities to generate AI content for. Activities already processed will be refreshed.';
$string['wizard_step2_heading']      = 'Step 2 — Select Output Types';
$string['wizard_step2_desc']         = 'Choose which AI outputs to generate. Only features enabled by your administrator are shown.';
$string['wizard_step3_heading']      = 'Step 3 — Configure Parameters';
$string['wizard_step3_desc']         = 'Customise generation settings. These apply to all selected activities.';
$string['wizard_step4_heading']      = 'Step 4 — Review & Confirm';
$string['wizard_step4_info']         = 'Review the configuration above. Once you click Generate, the AI will begin processing. You can safely leave this page — progress is tracked automatically.';
$string['wizard_step5_heading']      = 'Step 5 — Generating Content…';
$string['wizard_step5_desc']         = 'Content is being generated in the background. This page updates automatically.';
$string['wizard_next']               = 'Next';
$string['wizard_back']               = 'Back';
$string['wizard_generate']           = 'Generate AI Content';
$string['wizard_done']               = '✓ All done! AI content is now available for students.';
$string['wizard_back_dashboard']     = '← Back to AI Manager';
$string['param_language']            = 'Content Language';
$string['param_output_language']     = 'Output Language';
$string['param_focus_areas']         = 'Focus Areas (optional)';
$string['param_quiz_count']          = 'Quiz Questions';
$string['param_flashcard_count']     = 'Flashcards';
$string['param_chunk_size']          = 'Chunk Size (tokens)';
$string['blooms_total']              = 'Total';

// ── Settings navigation ────────────────────────────────────────────────────────
$string['settings_nav']              = 'Settings';

// ── Admin reports ──────────────────────────────────────────────────────────────
$string['reports_title']             = 'AI Token Usage Report';
$string['reports_desc']              = 'View AI token consumption per user across the site. Click "Daily" to drill into a user\'s daily breakdown.';
$string['reports_month']             = 'Month';
$string['reports_search']            = 'Search';
$string['reports_apply']             = 'Apply';
$string['reports_export_csv']        = 'Export CSV';
$string['reports_col_user']          = 'User';
$string['reports_col_username']      = 'Username';
$string['reports_col_tokens']        = 'Tokens Used';
$string['reports_col_limit']         = 'Limit';
$string['reports_col_usage']         = 'Usage %';
$string['reports_col_last']          = 'Last Active';
$string['reports_col_actions']       = 'Actions';
$string['reports_note']              = 'Token counts update in real time. "Limit" shows the effective value: user override if set, otherwise site default.';

// ── User overrides ─────────────────────────────────────────────────────────────
$string['user_overrides_title']              = 'User Limit Overrides';
$string['user_overrides_desc']               = 'Grant or restrict individual users beyond the site-level defaults. Leave any field blank to use the site default for that limit. Changes sync to the AI service immediately.';
$string['user_overrides_site_defaults']      = 'Site defaults';
$string['user_overrides_defaults_note']      = 'Overrides apply only to fields you explicitly set. Blank fields inherit the site default.';
$string['user_overrides_add']                = 'Add or Update Override';
$string['user_overrides_search_user']        = 'Find user';
$string['user_overrides_search_note']        = 'Search by name, username, or email. Select a user then click "Set Limits".';
$string['user_overrides_set_limits']         = 'Set Limits…';
$string['user_overrides_existing']           = 'Users with Custom Limits';
$string['user_overrides_col_user']           = 'User';
$string['user_overrides_col_session']        = 'Msgs/Session';
$string['user_overrides_col_daily']          = 'Msgs/Day';
$string['user_overrides_col_monthly_msg']    = 'Msgs/Month';
$string['user_overrides_col_monthly_tokens'] = 'Tokens/Month';
$string['user_overrides_col_note']           = 'Note / Set By';
$string['user_overrides_col_actions']        = 'Actions';

// ── Scheduled tasks ────────────────────────────────────────────────────────────
$string['task_poll_jobs']            = 'Axis AI: Poll processing jobs';
$string['task_sync_outputs']         = 'Axis AI: Sync AI outputs to Moodle';

// ── Chatbot panel ─────────────────────────────────────────────────────────────
$string['chatbot_open_label']         = 'Open Axis AI Assistant';
$string['chatbot_panel_label']        = 'Axis AI Assistant';
$string['chatbot_welcome_title']      = 'Axis AI';
$string['chatbot_header_subtitle']    = 'Your learning assistant';
$string['chatbot_back']               = 'Back to menu';
$string['chatbot_minimise']           = 'Minimise';
$string['chatbot_close']              = 'Close';
$string['chatbot_welcome_hello']      = 'Hello!';
$string['chatbot_welcome_tagline']    = 'What would you like to do today?';
$string['chatbot_your_stats']         = 'Your stats';
$string['chatbot_stat_courses']       = 'Courses';
$string['chatbot_stat_badges']        = 'Badges';
$string['chatbot_stat_streak']        = 'Streak';
$string['chatbot_stat_points']        = 'Points';
$string['chatbot_choose_mode']        = 'Choose a mode to get started:';
$string['chatbot_mode_study']         = 'Study with AI';
$string['chatbot_mode_study_sub']     = 'Chat about your course content';
$string['chatbot_mode_support']       = 'Support';
$string['chatbot_mode_support_sub']   = 'Get answers from our knowledge base';
$string['chatbot_mode_analysis']      = 'My Analysis';
$string['chatbot_mode_analysis_sub']  = 'View your learning progress';
$string['chatbot_left_panel']         = 'Navigation panel';
$string['chatbot_subtab_learn']       = 'Learn';
$string['chatbot_subtab_practice']    = 'Practice';
$string['chatbot_select_course_prompt'] = 'Select a course on the left to begin.';
$string['chatbot_messages_region']    = 'Chat messages';
$string['chatbot_typing_label']       = 'AI is typing…';
$string['chatbot_input_placeholder']  = 'Type your message… (Enter to send)';
$string['chatbot_input_aria']         = 'Chat message input';
$string['chatbot_input_hint']         = 'Press Enter to send · Shift+Enter for new line';
$string['chatbot_send']               = 'Send message';
$string['chatbot_right_collapse']     = 'Collapse side panel';
$string['chatbot_right_expand']       = 'Expand side panel';
$string['chatbot_metric_courses']     = 'My Courses';
$string['chatbot_metric_certs']       = 'Certificates';
$string['chatbot_metric_points']      = 'Points';
$string['chatbot_metric_progress']    = 'My Progress';
$string['chatbot_avg_grade']          = 'Average Grade';

// ── Student panel ─────────────────────────────────────────────────────────────
$string['student_panel_label']            = 'AI Learning Tools';
$string['student_panel_tab_label']        = 'AI Tools';
$string['student_panel_open']             = 'Open AI Learning Tools';
$string['student_panel_close']            = 'Close AI Learning Tools';
$string['student_panel_not_ready']        = 'AI content is being generated…';
$string['student_panel_not_ready_sub']    = 'Check back in a few minutes. Your teacher has requested AI tools for this activity.';
$string['student_panel_error']            = 'Could not load AI content. Please try again later.';
$string['student_panel_tabs_label']       = 'AI output tabs';
$string['student_panel_search_terms']     = 'Search terms…';
$string['student_panel_search_faq']       = 'Search questions…';
$string['student_panel_fc_shuffle']       = 'Shuffle';
$string['student_panel_fc_flip_hint']     = 'Click to flip card';
$string['student_panel_fc_question']      = 'Question';
$string['student_panel_fc_answer']        = 'Answer';
$string['student_panel_fc_prev']          = 'Previous card';
$string['student_panel_fc_next']          = 'Next card';
$string['student_panel_quiz_intro']       = 'Test your knowledge of this activity with an AI-generated quiz.';
$string['student_panel_quiz_count']       = 'Questions:';
$string['student_panel_quiz_diff']        = 'Difficulty:';
$string['student_panel_quiz_start']       = 'Start Quiz';
$string['student_panel_quiz_next']        = 'Next Question →';
$string['student_panel_quiz_retry']       = 'Try Again';
$string['student_panel_quiz_options_label'] = 'Answer choices';
$string['student_panel_chat_study']       = 'Study with AI';
$string['student_panel_chat_support']     = 'Support';
$string['student_panel_chat_messages']    = 'Chat messages';
$string['student_panel_chat_placeholder'] = 'Ask about this activity…';
$string['student_panel_chat_input_aria']  = 'Chat input';
$string['student_panel_chat_send']        = 'Send message';

// ── Privacy metadata strings ───────────────────────────────────────────────────
$string['privacy:metadata:chat_sessions']                   = 'Stores a record of each AI chat session started by the user.';
$string['privacy:metadata:chat_sessions:userid']            = 'The Moodle user who started the session.';
$string['privacy:metadata:chat_sessions:cmid']              = 'The course module this session was about.';
$string['privacy:metadata:chat_sessions:courseid']          = 'The course this session was associated with.';
$string['privacy:metadata:chat_sessions:chat_mode']         = 'The mode of the chat (study or support).';
$string['privacy:metadata:chat_sessions:message_count']     = 'Number of messages exchanged in this session.';
$string['privacy:metadata:chat_sessions:tokens_used']       = 'AI tokens consumed during this session.';
$string['privacy:metadata:chat_sessions:timecreated']       = 'When the session was started.';
$string['privacy:metadata:token_usage']                     = 'Stores aggregated daily token and message usage per user.';
$string['privacy:metadata:token_usage:userid']              = 'The Moodle user this usage belongs to.';
$string['privacy:metadata:token_usage:day_date']            = 'The date of this usage record (YYYY-MM-DD).';
$string['privacy:metadata:token_usage:tokens_used']         = 'Total AI tokens consumed on this date.';
$string['privacy:metadata:token_usage:msg_count']           = 'Number of messages sent on this date.';
$string['privacy:metadata:user_limits']                     = 'Stores per-user rate limit overrides set by administrators.';
$string['privacy:metadata:user_limits:userid']              = 'The Moodle user this override applies to.';
$string['privacy:metadata:user_limits:chat_session_msg_limit'] = 'Maximum messages allowed per session.';
$string['privacy:metadata:user_limits:chat_daily_msg_limit']   = 'Maximum messages allowed per day.';
$string['privacy:metadata:user_limits:chat_monthly_msg_limit'] = 'Maximum messages allowed per month.';
$string['privacy:metadata:user_limits:token_monthly_limit']    = 'Maximum tokens allowed per month.';
$string['privacy:metadata:user_limits:note']               = 'Administrative note explaining the override.';
$string['privacy:metadata:axis_ai']                        = 'Chat messages and content identifiers are sent to the Axis AI external service for processing. The service receives only a pseudonymous Moodle user ID — no name or email is shared.';
$string['privacy:metadata:axis_ai:moodle_user_id']         = 'A numeric identifier representing the Moodle user (no name or email is sent).';
$string['privacy:metadata:axis_ai:chat_messages']          = 'The text of chat messages the user sends to the AI.';
$string['privacy:metadata:axis_ai:content_items']          = 'Identifiers for course content items processed by the AI.';

// ── Knowledge Base admin ───────────────────────────────────────────────────────
$string['kb_admin_title']               = 'Support Knowledge Base';
$string['kb_admin_desc']                = 'Manage the content that powers the Support Chatbot. Add URLs or paste text to ingest into the knowledge base. Ingested items are processed asynchronously — status updates automatically.';
$string['kb_add_item']                  = 'Add Knowledge Item';
$string['kb_tab_url']                   = 'From URL';
$string['kb_tab_text']                  = 'Paste Text';
$string['kb_url_label']                 = 'URL';
$string['kb_url_help']                  = 'Enter a publicly accessible URL. The page content will be extracted and indexed. Supported: web pages, HTML docs.';
$string['kb_title_label']               = 'Title';
$string['kb_title_placeholder']         = 'e.g. Getting Started Guide';
$string['kb_desc_label']                = 'Description';
$string['kb_desc_placeholder']          = 'Brief description of this content (shown in admin list only)';
$string['kb_ingest_url']                = 'Ingest URL';
$string['kb_text_content_label']        = 'Content';
$string['kb_text_content_placeholder']  = 'Paste your support documentation, FAQs, policy text, or any other content here…';
$string['kb_text_help']                 = 'Plain text or markdown. The content will be chunked and indexed for semantic search.';
$string['kb_ingest_text']               = 'Save & Index';
$string['kb_file_upload_label']         = 'Or upload a file (PDF, TXT, DOC/DOCX)';
$string['kb_file_help']                 = 'Select a file to upload. It will be extracted and indexed for semantic search.';
$string['kb_ingest_file']               = 'Upload & Index';
$string['kb_edit_title']                = 'Edit Knowledge Base Item';
$string['kb_edit_doctype']              = 'Document Type';
$string['kb_edit_content_help']         = 'Edit the text and save to re-index this item. Leave unchanged to keep existing content.';
$string['kb_edit_content_placeholder']  = 'Paste or type the content to index…';
$string['kb_edit_no_content_warning']   = 'No content was stored for this item (it may have been a file upload or created before content tracking was added). You can paste replacement text here to re-index it.';
$string['kb_backfill_label']            = 'Fix KB Search';
$string['kb_backfill_help']             = 'Run this once if existing KB items are not returning results in chat. It stamps is_active=true on all Qdrant vectors for active KB items.';
$string['kb_doctype_support']           = 'Support / Help Desk';
$string['kb_doctype_policy']            = 'Policy';
$string['kb_doctype_how_to']            = 'How-To Guide';
$string['kb_doctype_faq']               = 'FAQ';
$string['kb_doctype_announcement']      = 'Announcement';
$string['kb_doctype_other']             = 'Other';
$string['cancel']                       = 'Cancel';
$string['savechanges']                  = 'Save changes';
$string['kb_items_title']               = 'Knowledge Base Items';
$string['kb_no_items']                  = 'No items in the knowledge base yet.';
$string['kb_no_items_desc']             = 'Add URLs or paste text above to build your support knowledge base.';
$string['kb_col_title']                 = 'Title / Source';
$string['kb_col_source']                = 'URL';
$string['kb_col_status']                = 'Status';
$string['kb_col_added']                 = 'Added';
$string['kb_col_actions']               = 'Actions';
$string['optional']                     = 'optional';
$string['refresh']                      = 'Refresh';
$string['loading']                      = 'Loading…';

// ── Transcript feature ─────────────────────────────────────────────────────────
$string['feature_transcript']           = 'Transcript / Chapters';
$string['tab_transcript']               = 'Transcript';
$string['transcript_no_content']        = 'No transcript available for this activity.';
$string['transcript_jump_to']           = 'Jump to';

// ── Misc ──────────────────────────────────────────────────────────────────────
$string['privacy:metadata']          = 'The Axis AI plugin sends content data to an external AI service for processing. User chat messages are sent to and stored by this service.';
