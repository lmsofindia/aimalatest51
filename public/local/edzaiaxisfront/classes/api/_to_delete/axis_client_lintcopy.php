<?php

/**
 * HTTP client for the axis-ai FastAPI service.
 *
 * All communication between Moodle and axis-ai goes through this class.
 * Uses Moodle's curl wrapper for compatibility and proxy support.
 *
 * Error handling:
 *  - HTTP 4xx: throws axis_client_exception with the error body
 *  - HTTP 5xx: throws axis_client_exception with status code
 *  - Network error: throws axis_client_exception
 *  - All errors logged to Moodle debug log
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront\api;

defined('MOODLE_INTERNAL') || die();

class axis_client_exception extends \moodle_exception
{
    public int $http_status;
    public function __construct(string $message, int $http_status = 0)
    {
        $this->http_status = $http_status;
        parent::__construct('error_api_connection', 'local_edzaiaxisfront', '', null, $message);
    }
}

class axis_client
{

    private string $base_url;
    private string $api_key;
    private int    $timeout = 30;

    public function __construct(string $base_url = '', string $api_key = '')
    {
        $this->base_url = rtrim($base_url ?: get_config('local_edzaiaxisfront', 'api_base_url'), '/');
        $this->api_key  = $api_key ?: get_config('local_edzaiaxisfront', 'api_key');

        if (empty($this->base_url) || empty($this->api_key)) {
            throw new axis_client_exception('Axis AI not configured: missing api_base_url or api_key');
        }
    }

    // ── Tenant management ─────────────────────────────────────────────────────

    public function create_tenant(array $payload): array
    {
        return $this->post('/api/v1/admin/tenants', $payload);
    }

    public function update_tenant(string $tenant_id, array $payload): array
    {
        // Uses POST (not PUT) — Moodle's curl wrapper overrides CURLOPT_CUSTOMREQUEST
        // when $curl->post() is called, causing PUT to arrive as POST on the server.
        // Python accepts both PUT and POST for this endpoint.
        return $this->post("/api/v1/admin/tenants/{$tenant_id}", $payload);
    }

    /**
     * Sync rate limits from Moodle to this tenant without needing the tenant UUID.
     * The tenant is resolved from the API key on the Python side.
     * Uses POST (not PUT) to avoid Moodle curl wrapper issues with custom HTTP methods.
     */
    public function sync_rate_limits(array $payload): array
    {
        return $this->post('/api/v1/admin/tenants/me/settings', $payload);
    }

    public function get_tenant(string $tenant_id): array
    {
        return $this->get("/api/v1/admin/tenants/{$tenant_id}");
    }

    public function get_tenant_status(string $tenant_id): array
    {
        return $this->get("/api/v1/admin/tenants/{$tenant_id}/status");
    }

    // ── User overrides ────────────────────────────────────────────────────────

    /**
     * Create or update a per-user rate-limit override.
     * Uses the /me route so no tenant UUID is required — tenant resolved from API key.
     */
    public function upsert_user_override(array $payload): array
    {
        return $this->post('/api/v1/admin/tenants/me/user-overrides', $payload);
    }

    /**
     * List all per-user overrides for this tenant (resolved from API key).
     */
    public function list_user_overrides(): array
    {
        return $this->get('/api/v1/admin/tenants/me/user-overrides');
    }

    /**
     * Delete a per-user override (tenant resolved from API key).
     * Uses a POST to a /delete sub-path because Moodle curl sends POST even
     * when CURLOPT_CUSTOMREQUEST => 'DELETE' is set (post() overrides it).
     */
    public function delete_user_override(int $moodle_user_id): void
    {
        $this->post("/api/v1/admin/tenants/me/user-overrides/{$moodle_user_id}/delete", []);
    }

    // ── Content ingest ────────────────────────────────────────────────────────

    /**
     * Ingest a URL-based content item (PDF, YouTube, Vimeo, etc.)
     */
    public function ingest_url(array $payload): array
    {
        return $this->post('/api/v1/ingest', $payload);
    }

    /**
     * Ingest a file by uploading its raw bytes (multipart) to axis-ai's /ingest/file.
     * For protected mods (e.g. pdfprotect) whose file cannot be fetched over
     * webservice/pluginfile. Uses Moodle's \curl wrapper (like every other call in
     * this class) so it inherits proxy + SSL config. When an array param contains a
     * \CURLFile/\CURLStringFile, Moodle passes it straight to CURLOPT_POSTFIELDS and
     * cURL builds a proper multipart/form-data body with an auto-generated boundary.
     *
     * @param array $form content_type, moodle_course_id, moodle_cmid, moodle_user_id, title, tasks, language
     */
    public function ingest_file(string $filename, string $bytes, string $mimetype, array $form): array
    {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = $this->base_url . '/api/v1/ingest/file';

        $curl = new \curl(['ignoresecurity' => true]);
        $curl->setopt([
            'CURLOPT_TIMEOUT'        => max($this->timeout, 180),
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_RETURNTRANSFER' => true,
        ]);

        // Multipart upload: do NOT set Content-Type — cURL generates the
        // multipart/form-data boundary itself because one field is a CURLFile.
        $options = [
            'CURLOPT_HTTPHEADER' => [
                'Authorization: Bearer ' . $this->api_key,
                'Accept: application/json',
            ],
        ];

        $postdata = $form;
        $postdata['file'] = new \CURLStringFile($bytes, $filename, $mimetype ?: 'application/pdf');

        $response = $curl->post($url, $postdata, $options);

        $errno  = $curl->get_errno();
        $status = (int) $curl->get_info()['http_code'];

        if ($errno) {
            throw new axis_client_exception("Network error (errno {$errno}): " . $curl->error, 0);
        }
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new axis_client_exception("Invalid JSON response (HTTP {$status}): " . substr((string) $response, 0, 200), $status);
        }
        if ($status >= 400) {
            $detail = $decoded['detail'] ?? $decoded['message'] ?? "HTTP {$status}";
            if (is_array($detail)) {
                $detail = json_encode($detail);
            }
            throw new axis_client_exception("API error: {$detail}", $status);
        }
        return $decoded;
    }

    /**
     * Ingest pre-extracted SCORM chunks.
     */
    public function ingest_structured(array $payload): array
    {
        return $this->post('/api/v1/ingest/structured', $payload);
    }

    // ── Job polling ───────────────────────────────────────────────────────────

    public function get_job_status(string $job_id): array
    {
        return $this->get("/api/v1/jobs/{$job_id}");
    }

    // ── Content outputs ───────────────────────────────────────────────────────

    public function get_all_outputs(string $content_item_id): array
    {
        return $this->get("/api/v1/content/{$content_item_id}/outputs");
    }

    public function get_summary(string $content_item_id): array
    {
        return $this->get("/api/v1/content/{$content_item_id}/summary");
    }

    public function get_glossary(string $content_item_id): array
    {
        return $this->get("/api/v1/content/{$content_item_id}/glossary");
    }

    public function get_flashcards(string $content_item_id): array
    {
        return $this->get("/api/v1/content/{$content_item_id}/flashcards");
    }

    public function get_quiz_questions(string $content_item_id): array
    {
        return $this->get("/api/v1/content/{$content_item_id}/quiz-questions");
    }

    public function get_faq(string $content_item_id): array
    {
        return $this->get("/api/v1/content/{$content_item_id}/faq");
    }

    public function get_infographic(string $content_item_id): array
    {
        return $this->get("/api/v1/content/{$content_item_id}/infographic");
    }

    // ── Teacher edits ─────────────────────────────────────────────────────────

    /**
     * Bulk-replace all glossary terms for a content item with the teacher-edited set.
     * Existing active terms are soft-deleted; the new set is inserted as source='teacher_edit'.
     */
    public function replace_glossary(string $content_item_id, array $terms, int $user_id): void
    {
        $this->post("/api/v1/content/{$content_item_id}/glossary/bulk-replace", [
            'terms'         => $terms,
            'moodle_user_id' => $user_id,
        ]);
    }

    /**
     * Bulk-replace all flashcards for a content item with the teacher-edited set.
     * Existing active cards are soft-deleted; the new set is inserted as source='teacher_edit'.
     */
    public function replace_flashcards(string $content_item_id, array $cards, int $user_id): void
    {
        $this->post("/api/v1/content/{$content_item_id}/flashcards/bulk-replace", [
            'cards'          => $cards,
            'moodle_user_id' => $user_id,
        ]);
    }

    public function update_summary(string $content_item_id, string $content, int $editor_userid): array
    {
        // Uses POST (not PUT) because Moodle's curl wrapper cannot reliably send PUT:
        // CURLOPT_CUSTOMREQUEST => 'PUT' is overridden when $curl->post() is called.
        // The Python endpoint accepts both PUT and POST for compatibility.
        return $this->post("/api/v1/content/{$content_item_id}/summary", [
            'summary'          => $content,
            'moodle_user_id'   => $editor_userid,
        ]);
    }

    public function trigger_generation(string $content_item_id, array $tasks, array $options = []): array
    {
        return $this->post("/api/v1/content/{$content_item_id}/generate", [
            'tasks'   => $tasks,
            'options' => $options,
        ]);
    }

    public function regenerate_flashcards(string $content_item_id, int $count, string $language = 'en'): array
    {
        return $this->post("/api/v1/content/{$content_item_id}/flashcards/regenerate", [
            'count'           => $count,
            'output_language' => $language,
        ]);
    }

    public function regenerate_quiz(string $content_item_id, int $count, array $options = []): array
    {
        return $this->post("/api/v1/content/{$content_item_id}/quiz-questions/regenerate", array_merge(
            ['count' => $count],
            $options
        ));
    }

    // ── Chat ──────────────────────────────────────────────────────────────────

    public function create_chat_session(array $payload): array
    {
        return $this->post('/api/v1/chat/sessions', $payload);
    }

    public function send_chat_message(array $payload): array
    {
        return $this->post('/api/v1/chat/message', $payload);
    }

    public function get_session_history(string $session_id): array
    {
        return $this->get("/api/v1/chat/sessions/{$session_id}/history");
    }

    public function end_chat_session(string $session_id): array
    {
        return $this->post("/api/v1/chat/sessions/{$session_id}/end", []);
    }

    // ── Knowledge Base ────────────────────────────────────────────────────────

    public function kb_ingest_url(array $payload): array
    {
        return $this->post('/api/v1/kb/ingest', $payload);
    }

    public function list_kb_items(): array
    {
        return $this->get('/api/v1/kb/items');
    }

    public function delete_kb_item(string $kb_item_id): void
    {
        // Uses POST to /delete sub-path — Moodle curl wrapper compat (same issue as PUT).
        $this->post("/api/v1/kb/items/{$kb_item_id}/delete", []);
    }

    /**
     * Re-index an existing KB item with new text content.
     * Keeps the same axis_kb_item_id — does NOT create a new row in Python.
     * Old Qdrant vectors are deleted before the new ones are inserted by the Celery task.
     */
    public function kb_reindex_item(string $kb_item_id, array $payload): array
    {
        return $this->post("/api/v1/kb/items/{$kb_item_id}/reindex", $payload);
    }

    /**
     * Ingest plain text content into the KB (no URL needed).
     * Avoids the data: URI workaround that fails PHP's PARAM_URL validation.
     */
    public function kb_ingest_text(array $payload): array
    {
        return $this->post('/api/v1/kb/ingest/text', $payload);
    }

    /**
     * Ingest a file passed as base64-encoded content.
     * Moodle's external function API cannot do multipart uploads, so JS
     * reads the file as base64 and posts it as JSON.
     */
    public function kb_ingest_file_base64(array $payload): array
    {
        return $this->post('/api/v1/kb/ingest/base64', $payload);
    }

    /**
     * Backfill is_active=true on all existing Qdrant KB chunks for this tenant.
     * Call once after upgrading to fix items ingested before is_active was added.
     */
    public function kb_backfill_active(): array
    {
        return $this->post('/api/v1/kb/backfill-active', []);
    }

    public function toggle_kb_item(string $kb_item_id, bool $is_active): array
    {
        // Uses POST (not PUT) — Moodle curl wrapper compat. Python accepts both.
        return $this->post("/api/v1/kb/items/{$kb_item_id}", ['is_active' => $is_active]);
    }

    public function update_kb_item(string $kb_item_id, array $fields): array
    {
        // Sends only the changed fields (title, doc_type, is_active).
        // Uses POST — same compat reason as toggle_kb_item.
        return $this->post("/api/v1/kb/items/{$kb_item_id}", $fields);
    }

    // ── Health ────────────────────────────────────────────────────────────────

    public function health_check(): array
    {
        return $this->get('/api/v1/health/ready');
    }

    // ── HTTP methods ──────────────────────────────────────────────────────────

    private function get(string $path): array
    {
        return $this->request('GET', $path, null);
    }

    private function post(string $path, array $body): array
    {
        return $this->request('POST', $path, $body);
    }

    private function put(string $path, array $body): array
    {
        return $this->request('PUT', $path, $body);
    }

    private function delete(string $path): void
    {
        $this->request('DELETE', $path, null, true);
    }

    private function request(string $method, string $path, ?array $body, bool $no_response = false): array
    {
        $url = $this->base_url . $path;

        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl(['ignoresecurity' => true]);
        // $curl->setopt([
        //     CURLOPT_TIMEOUT        => $this->timeout,
        //     CURLOPT_CONNECTTIMEOUT => 10,
        //     CURLOPT_RETURNTRANSFER => true,
        // ]);

        // $curl->setHeader([
        //     'Authorization: Bearer ' . $this->api_key,
        //     'Content-Type: application/json',
        //     'Accept: application/json',
        // ]);

        $options = [
            'CURLOPT_HTTPHEADER' => [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json',
                'Accept: application/json',
            ]
        ];

        // this is working as in moodle 
        $curl->setopt([
            'CURLOPT_TIMEOUT'        => $this->timeout,
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_RETURNTRANSFER' => true,
        ]);

        $json_body = $body !== null ? json_encode($body) : null;

        switch ($method) {
            case 'GET':
                //   $response = $curl->get($url);
                $response = $curl->get($url, [], $options);
                break;
            case 'POST':
                $response = $curl->post($url, $json_body, $options);
                break;
            case 'PUT':
                $curl->setopt(['CURLOPT_CUSTOMREQUEST' => 'PUT']);
                $response = $curl->post($url, $json_body, $options);
                break;
            case 'DELETE':
                $curl->setopt(['CURLOPT_CUSTOMREQUEST' => 'DELETE']);
                $response = $curl->post($url, '', $options);
                break;
            default:
                throw new axis_client_exception("Unknown HTTP method: {$method}");
        }

        //print_r("Raw response: " . $response); // DEBUGGING
        $errno  = $curl->get_errno();
        $status = (int) $curl->get_info()['http_code'];

        if ($errno) {
            throw new axis_client_exception("Network error (errno {$errno}): " . $curl->error, 0);
        }

        if ($status === 204 || $no_response) {
            return [];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new axis_client_exception("Invalid JSON response (HTTP {$status}): " . substr($response, 0, 200), $status);
        }

        if ($status >= 400) {
            $detail = $decoded['detail'] ?? $decoded['message'] ?? "HTTP {$status}";

            if (is_array($detail)) {
                $detail = json_encode($detail);
            }

            throw new axis_client_exception("API error: {$detail}", $status);
        }

        //mtrace("API response (HTTP {$status}): " . json_encode($decoded)); // DEBUGGING
        return $decoded;
    }
}
