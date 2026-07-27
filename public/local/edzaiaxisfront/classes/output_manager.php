<?php

/**
 * Output manager — syncs AI-generated outputs from axis-ai into Moodle's local DB.
 *
 * Responsibilities:
 *  - Pull all outputs for a ContentItem from axis-ai after job completion.
 *  - Upsert summary, FAQ, infographic into local_edzaiaxisfront_outputs.
 *  - Upsert flashcards into local_edzaiaxisfront_flashcards.
 *  - Upsert glossary terms into local_edzaiaxisfront_glossary.
 *  - Upsert quiz questions into local_edzaiaxisfront_quiz_questions.
 *  - Update local_edzaiaxisfront_cm_config: generated_features, status='ready'.
 *
 * Visibility / permission notes:
 *  - is_teacher_edited is only reset if teacher has NOT edited (safe override).
 *  - is_visible inherits previous value; new items default to visible.
 *  - generated_features JSON is the ground-truth for what actually completed.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront;

use local_edzaiaxisfront\api\axis_client;
use local_edzaiaxisfront\api\axis_client_exception;

defined('MOODLE_INTERNAL') || die();

class output_manager
{

    private axis_client $client;

    public function __construct(?axis_client $client = null)
    {
        $this->client = $client ?? new axis_client();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Sync all outputs for a CM from axis-ai into Moodle local tables.
     * Called after a job reaches status='completed'.
     *
     * @param  int    $cmid              The Moodle course module ID
     * @param  string $content_item_id   The axis-ai ContentItem UUID
     * @return bool   true on success, false if no outputs found
     */
    public function sync_cm_outputs(int $cmid, string $content_item_id): bool
    {
        global $DB;

        try {

            // Correct the output now that the job is done
            //$outputs = $this->client->get_all_outputs($content_item_id);

            $outputs_raw = $this->client->get_all_outputs($content_item_id);

            $outputs = [];

            foreach ($outputs_raw as $item) {
                if (!empty($item['output_type']) && isset($item['payload'])) {
                    $outputs[$item['output_type']] = $item['payload'];
                }
            }
        } catch (axis_client_exception $e) {
            debugging("output_manager: failed to fetch outputs for cmid={$cmid}: " . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // NOTE: no mtrace/echo here — sync_cm_outputs is called synchronously from the
        // AJAX submit_ingest (already-ready path), and any echoed output corrupts the
        // JSON response. Use debugging() (goes to the debug log, not the output stream).
        //
        // IMPORTANT: do NOT early-return when the general /outputs payload is empty.
        // Quiz questions are served by a SEPARATE endpoint (get_quiz_questions) further
        // down, so an empty /outputs does not mean "nothing was generated". Bailing here
        // skipped the quiz sync AND mark_cm_ready(), stranding the CM in 'processing'
        // forever (e.g. a PDF whose summary generation failed on the backend but whose
        // quiz succeeded). Fall through so quiz still syncs and the CM is marked ready.
        $generated = [];

        // Summary

        if (!empty($outputs['summary']['summary'])) {
            $this->upsert_text_output($cmid, 'summary', $outputs['summary']['summary']);
            $generated[] = 'summary';
        }

        // FAQ — stored as JSON string (array of {question, answer} objects from Python)
        if (!empty($outputs['faq']['faqs']) && is_array($outputs['faq']['faqs'])) {
            $this->upsert_text_output($cmid, 'faq', json_encode($outputs['faq']['faqs']));
            $generated[] = 'faq';
        }

        // Infographic (stored as JSON string)
        if (!empty($outputs['infographic']['html'])) {
            $this->upsert_text_output($cmid, 'infographic', $outputs['infographic']['html']);
            $generated[] = 'infographic';
        }

        // Flashcards
        if (!empty($outputs['flashcards']['cards']) && is_array($outputs['flashcards'])) {
            $this->sync_flashcards($cmid, $outputs['flashcards']['cards']);
            $generated[] = 'flashcards';
        }

        // Glossary
        if (!empty($outputs['glossary']['terms'])) {
            $this->sync_glossary($cmid, $outputs['glossary']['terms']);
            $generated[] = 'glossary';
        }

        // Quiz — served by the dedicated /quiz-questions endpoint (QuizPoolResponse.items),
        // NOT the general /outputs payload. The old code looked for $outputs['quiz_questions']
        // (backend key is 'quiz'), so quiz never synced. Fetch it explicitly.
        try {
            $quizresp   = $this->client->get_quiz_questions($content_item_id);
            $quiz_items = $quizresp['items'] ?? $quizresp['questions'] ?? [];
            if (!empty($quiz_items) && is_array($quiz_items)) {
                $this->sync_quiz_questions($cmid, $quiz_items);
                $generated[] = 'quiz';
            }
        } catch (\Exception $qe) {
            debugging('output_manager: quiz sync failed for cmid=' . $cmid . ': ' . $qe->getMessage(), DEBUG_DEVELOPER);
        }

        // Transcript / chapters (video transcripts or book/page chapters from axis-ai)
        // Stored as JSON: [{title, timestamp, text}, ...] or [{title, content}, ...]
        if (!empty($outputs['transcript'])) {
            $transcript_data = $outputs['transcript'];
            // Python may return {segments: [...]} or {chapters: [...]} or just an array
            $segments = $transcript_data['segments'] ?? $transcript_data['chapters'] ?? $transcript_data;
            if (is_array($segments) && !empty($segments)) {
                $this->upsert_text_output($cmid, 'transcript', json_encode($segments));
                $generated[] = 'transcript';
            }
        }

        // Update cm_config: mark ready, set generated_features
        $this->mark_cm_ready($cmid, $generated);

        return true;
    }

    /**
     * Sync a single output type for a CM (used by regenerate flows).
     *
     * @param  int    $cmid
     * @param  string $content_item_id
     * @param  string $output_type     One of: summary|faq|infographic|flashcards|glossary|quiz
     */
    public function sync_single_output(int $cmid, string $content_item_id, string $output_type): void
    {
        switch ($output_type) {
            case 'summary':
                $data = $this->client->get_summary($content_item_id);
                if (!empty($data['content'])) {
                    $this->upsert_text_output($cmid, 'summary', $data['content'], false);
                }
                break;

            case 'faq':
                $data = $this->client->get_faq($content_item_id);
                // Python returns {faqs: [{question, answer}, ...]}
                if (!empty($data['faqs']) && is_array($data['faqs'])) {
                    $this->upsert_text_output($cmid, 'faq', json_encode($data['faqs']));
                }
                break;

            case 'infographic':
                $data = $this->client->get_infographic($content_item_id);
                if (!empty($data)) {
                    $this->upsert_text_output(
                        $cmid,
                        'infographic',
                        is_array($data) ? json_encode($data) : $data
                    );
                }
                break;

            case 'flashcards':
                $data = $this->client->get_flashcards($content_item_id);
                if (!empty($data['flashcards'])) {
                    $this->sync_flashcards($cmid, $data['flashcards']);
                }
                break;

            case 'glossary':
                $data = $this->client->get_glossary($content_item_id);
                if (!empty($data['terms'])) {
                    $this->sync_glossary($cmid, $data['terms']);
                }
                break;

            case 'quiz':
                $data = $this->client->get_quiz_questions($content_item_id);
                if (!empty($data['questions'])) {
                    $this->sync_quiz_questions($cmid, $data['questions']);
                }
                break;
        }
    }

    /**
     * Write a teacher-edited summary back to axis-ai and mark it as edited.
     *
     * @param  int    $cmid
     * @param  string $content_item_id
     * @param  string $new_content
     * @param  int    $editor_userid
     */
    public function save_teacher_edit(int $cmid, string $content_item_id, string $new_content, int $editor_userid): void
    {
        global $DB;

        // Push to axis-ai
        $this->client->update_summary($content_item_id, $new_content, $editor_userid);

        // Update local cache immediately
        $existing = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'summary']);
        $now = time();

        if ($existing) {
            $DB->update_record('local_edzaiaxisfront_outputs', (object)[
                'id'               => $existing->id,
                'content'          => $new_content,
                'is_teacher_edited' => 1,
                'timemodified'     => $now,
            ]);
        } else {
            $DB->insert_record('local_edzaiaxisfront_outputs', (object)[
                'cmid'             => $cmid,
                'output_type'      => 'summary',
                'content'          => $new_content,
                'is_teacher_edited' => 1,
                'is_visible'       => 1,
                'timecreated'      => $now,
                'timemodified'     => $now,
            ]);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Upsert a text output (summary, faq, infographic).
     * Does NOT overwrite teacher-edited summaries unless $allow_overwrite_edited is true.
     */
    private function upsert_text_output(int $cmid, string $type, string $content, bool $allow_overwrite_edited = false): void
    {
        global $DB;

        $existing = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => $type]);
        $now = time();

        if ($existing) {
            // Don't overwrite teacher-edited content unless explicitly allowed
            if ($existing->is_teacher_edited && !$allow_overwrite_edited) {
                return;
            }
            $DB->update_record('local_edzaiaxisfront_outputs', (object)[
                'id'               => $existing->id,
                'content'          => $content,
                'timemodified'     => $now,
            ]);
        } else {
            $DB->insert_record('local_edzaiaxisfront_outputs', (object)[
                'cmid'             => $cmid,
                'output_type'      => $type,
                'content'          => $content,
                'is_teacher_edited' => 0,
                'is_visible'       => 1,
                'timecreated'      => $now,
                'timemodified'     => $now,
            ]);
        }
    }

    /**
     * Sync flashcards: soft-replace by marking old cards inactive, inserting new set.
     * Preserves any cards marked is_active=0 by teacher (i.e. teacher hid them).
     */
    private function sync_flashcards(int $cmid, array $cards): void
    {
        global $DB;

        // Fetch existing card fronts to detect truly new vs. existing
        $existing_map = [];
        $existing_rows = $DB->get_records('local_edzaiaxisfront_flashcards', ['cmid' => $cmid], '', 'id,front,is_active,source');
        foreach ($existing_rows as $row) {
            $existing_map[md5($row->front)] = $row;
        }

        $now  = time();
        $pos  = 0;

        foreach ($cards as $card) {
            $front   = trim($card['front'] ?? '');
            $back    = trim($card['back'] ?? '');
            $source  = $card['source'] ?? 'ai_generated';

            if (empty($front) || empty($back)) continue;

            $key = md5($front);
            $pos++;

            if (isset($existing_map[$key])) {
                // Update position + back content, preserve is_active
                $DB->update_record('local_edzaiaxisfront_flashcards', (object)[
                    'id'           => $existing_map[$key]->id,
                    'back'         => $back,
                    'position'     => $pos,
                    'source'       => $source,
                    'timemodified' => $now,
                ]);
                unset($existing_map[$key]);
            } else {
                $DB->insert_record('local_edzaiaxisfront_flashcards', (object)[
                    'cmid'         => $cmid,
                    'front'        => $front,
                    'back'         => $back,
                    'position'     => $pos,
                    'is_active'    => 1,
                    'source'       => $source,
                    'timecreated'  => $now,
                    'timemodified' => $now,
                ]);
            }
        }

        // Cards no longer returned by axis-ai: mark inactive (don't delete, teacher may have referenced)
        foreach ($existing_map as $stale) {
            $DB->update_record('local_edzaiaxisfront_flashcards', (object)[
                'id'           => $stale->id,
                'is_active'    => 0,
                'timemodified' => $now,
            ]);
        }
    }

    /**
     * Sync glossary terms: full upsert by term (case-insensitive key).
     */
    private function sync_glossary(int $cmid, array $terms): void
    {
        global $DB;

        $existing_rows = $DB->get_records('local_edzaiaxisfront_glossary', ['cmid' => $cmid], '', 'id,term');
        $existing_map  = [];
        foreach ($existing_rows as $row) {
            $existing_map[strtolower(trim($row->term))] = $row->id;
        }

        $now = time();

        foreach ($terms as $item) {
            $term       = trim($item['term'] ?? '');
            $definition = trim($item['definition'] ?? '');
            $context    = trim($item['context_note'] ?? '');

            if (empty($term) || empty($definition)) continue;

            $key = strtolower($term);

            if (isset($existing_map[$key])) {
                $DB->update_record('local_edzaiaxisfront_glossary', (object)[
                    'id'           => $existing_map[$key],
                    'definition'   => $definition,
                    'context_note' => $context,
                    'timemodified' => $now,
                ]);
                unset($existing_map[$key]);
            } else {
                $DB->insert_record('local_edzaiaxisfront_glossary', (object)[
                    'cmid'         => $cmid,
                    'term'         => $term,
                    'definition'   => $definition,
                    'context_note' => $context,
                    'timecreated'  => $now,
                    'timemodified' => $now,
                ]);
            }
        }

        // Remove terms no longer in axis-ai output (glossary is auto-generated, safe to delete)
        if (!empty($existing_map)) {
            $DB->delete_records_list('local_edzaiaxisfront_glossary', 'id', array_values($existing_map));
        }
    }

    /**
     * Sync quiz questions: upsert by question text hash; preserve teacher is_active overrides.
     */
    private function sync_quiz_questions(int $cmid, array $questions): void
    {
        global $DB;

        $existing_rows = $DB->get_records('local_edzaiaxisfront_quiz_questions', ['cmid' => $cmid], '', 'id,axis_question_id,question_text,is_active');
        $existing_map  = [];
        foreach ($existing_rows as $row) {
            $existing_map[$row->axis_question_id ?: sha1((string) $row->question_text)] = $row;
        }

        $now = time();

        foreach ($questions as $q) {
            // axis-ai QuizQuestionResponse: question_text, correct_answer,
            // options=[{text,is_correct,feedback}], question_type, blooms_level, difficulty.
            $question   = trim($q['question_text'] ?? $q['question'] ?? '');
            $answer     = trim($q['correct_answer'] ?? $q['answer'] ?? '');
            $blooms     = $q['blooms_level'] ?? '';
            $difficulty = $q['difficulty'] ?? 'medium';
            $type       = $q['question_type'] ?? $q['type'] ?? 'multichoice';
            $axisId     = $q['id'] ?? $q['axis_question_id'] ?? null;
            $explanation = trim($q['explanation'] ?? '');

            // Flatten option objects to plain strings for the student UI, and derive the
            // answer from the option flagged is_correct when correct_answer is absent.
            $options = [];
            foreach (($q['options'] ?? []) as $opt) {
                if (is_array($opt)) {
                    $text = trim((string) ($opt['text'] ?? ''));
                    if ($text !== '') {
                        $options[] = $text;
                    }
                    if ($answer === '' && !empty($opt['is_correct'])) {
                        $answer = $text;
                    }
                } else {
                    $options[] = (string) $opt;
                }
            }

            if (empty($question)) continue;

            $key = $axisId ?: sha1($question);

            if (isset($existing_map[$key])) {
                $DB->update_record('local_edzaiaxisfront_quiz_questions', (object)[
                    'id'             => $existing_map[$key]->id,
                    'question_text'  => $question,
                    'correct_answer' => $answer,
                    'options_json'   => $options ? json_encode($options) : null,
                    'blooms_level'   => $blooms,
                    'difficulty'     => $difficulty,
                    'question_type'  => $type,
                    'explanation'    => $explanation,
                    'axis_question_id' => $axisId,
                    'timemodified'   => $now,
                ]);
                unset($existing_map[$key]);
            } else {
                $DB->insert_record('local_edzaiaxisfront_quiz_questions', (object)[
                    'cmid'             => $cmid,
                    'axis_question_id' => $axisId,
                    'question_text'    => $question,
                    'correct_answer'   => $answer,
                    'options_json'     => $options ? json_encode($options) : null,
                    'blooms_level'     => $blooms,
                    'difficulty'       => $difficulty,
                    'question_type'    => $type,
                    'explanation'      => $explanation,
                    'is_active'        => 1,
                    'source'           => 'generated',
                    'timecreated'      => $now,
                    'timemodified'     => $now,
                ]);
            }
        }

        // Questions removed from axis-ai: mark inactive
        foreach ($existing_map as $stale) {
            $DB->update_record('local_edzaiaxisfront_quiz_questions', (object)[
                'id'           => $stale->id,
                'is_active'    => 0,
                'timemodified' => $now,
            ]);
        }
    }

    /**
     * Mark a CM as ready and record which features were successfully generated.
     */
    private function mark_cm_ready(int $cmid, array $generated_features): void
    {
        global $DB;

        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);
        if (!$config) return;

        // Merge with previously generated features (don't remove features that weren't in this batch)
        $prev = json_decode($config->generated_features ?? '[]', true) ?: [];
        $merged = array_unique(array_merge($prev, $generated_features));

        $DB->update_record('local_edzaiaxisfront_cm_config', (object)[
            'id'                 => $config->id,
            'status'             => 'ready',
            'generated_features' => json_encode(array_values($merged)),
            'timemodified'       => time(),
        ]);
    }
}
