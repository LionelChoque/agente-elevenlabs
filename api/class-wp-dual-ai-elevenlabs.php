<?php
/**
 * Integración optimizada con la API de ElevenLabs.
 */
class WP_Dual_AI_ElevenLabs {

    private $api_base_url = 'https://api.elevenlabs.io/v1';
    private $cache_time = 300; // 5 minutos

    public function __construct() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->enable_debug_logging();
        }
    }

    private function enable_debug_logging() {
        add_action('wp_dual_ai_api_log', function($message, $data = []) {
            if (empty($data)) {
                error_log('ElevenLabs API: ' . $message);
            } else {
                error_log('ElevenLabs API: ' . $message . ' - ' . json_encode($data));
            }
        }, 10, 2);
    }

    private function debug_log($message, $data = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            do_action('wp_dual_ai_api_log', $message, $data);
        }
    }

    private function get_api_key() {
        return get_option('wp_dual_ai_elevenlabs_api_key', '');
    }

    private function get_agent_id() {
        return get_option('wp_dual_ai_elevenlabs_agent_id', '');
    }

    public function has_valid_credentials() {
        $api_key = $this->get_api_key();
        $agent_id = $this->get_agent_id();
        
        return (!empty($api_key) && !empty($agent_id));
    }

    private function get_session_id() {
        if (!isset($_COOKIE['wp_dual_ai_session'])) {
            $session_id = wp_generate_uuid4();
            setcookie('wp_dual_ai_session', $session_id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            return $session_id;
        }
        
        return sanitize_text_field($_COOKIE['wp_dual_ai_session']);
    }

    public function log_interaction($interaction_type, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        $this->debug_log('Registrando interacción', [
            'type' => $interaction_type,
            'data' => $data
        ]);
        
        return $wpdb->insert(
            $table_name,
            array(
                'interaction_type' => sanitize_text_field($interaction_type),
                'api_provider' => 'elevenlabs',
                'interaction_data' => json_encode($data),
                'interaction_time' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'session_id' => $this->get_session_id()
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
    }

    private function get_auth_headers() {
        return [
            'xi-api-key' => $this->get_api_key(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    /**
     * API request with caching and error handling
     */
    private function api_request($endpoint, $args = [], $use_cache = false, $cache_time = null) {
        if (!$this->has_valid_credentials()) {
            return new WP_Error(
                'missing_credentials',
                'ElevenLabs API credentials are missing',
                ['status' => 400]
            );
        }
        
        // Default request arguments
        $default_args = [
            'method' => 'GET',
            'headers' => $this->get_auth_headers(),
            'timeout' => 15,
            'redirection' => 5,
            'httpversion' => '1.1',
            'sslverify' => true
        ];
        
        // Merge with provided arguments
        $request_args = wp_parse_args($args, $default_args);
        
        // Full endpoint URL
        $url = rtrim($this->api_base_url, '/') . '/' . ltrim($endpoint, '/');
        
        // Generate cache key if needed
        $cache_key = null;
        if ($use_cache) {
            $cache_key = 'elevenlabs_' . md5($url . serialize($request_args));
            $cached_response = get_transient($cache_key);
            
            if ($cached_response !== false) {
                $this->debug_log('Using cached response', ['endpoint' => $endpoint]);
                return $cached_response;
            }
        }
        
        // Make the request
        $this->debug_log('Making API request', [
            'url' => $url,
            'method' => $request_args['method']
        ]);
        
        $response = wp_remote_request($url, $request_args);
        
        // Check for request errors
        if (is_wp_error($response)) {
            $this->debug_log('API request error', [
                'error' => $response->get_error_message()
            ]);
            
            return new WP_Error(
                'api_request_error',
                'Error connecting to ElevenLabs API: ' . $response->get_error_message(),
                ['status' => 500]
            );
        }
        
        // Get response code and body
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Handle errors by response code
        if ($response_code < 200 || $response_code >= 300) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['message']) 
                ? $error_data['message'] 
                : 'Unknown API error';
            
            $this->debug_log('API response error', [
                'code' => $response_code,
                'message' => $error_message
            ]);
            
            // Custom error messages for common errors
            switch ($response_code) {
                case 401:
                    $error_message = 'Authentication error: Check your ElevenLabs API key';
                    break;
                case 404:
                    $error_message = 'Resource not found: ' . $error_message;
                    break;
                case 429:
                    $error_message = 'API rate limit exceeded. Try again later';
                    break;
            }
            
            return new WP_Error(
                'api_response_error',
                $error_message,
                ['status' => $response_code]
            );
        }
        
        // Process successful response
        $result = $response_body;
        
        // Parse JSON response
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        if (strpos($content_type, 'application/json') !== false) {
            $result = json_decode($response_body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->debug_log('JSON decode error', [
                    'error' => json_last_error_msg()
                ]);
                
                return new WP_Error(
                    'json_decode_error',
                    'Error decoding JSON response: ' . json_last_error_msg(),
                    ['status' => 500]
                );
            }
        }
        
        // Cache response if needed
        if ($use_cache && $cache_key) {
            $cache_duration = $cache_time ?? $this->cache_time;
            set_transient($cache_key, $result, $cache_duration);
            $this->debug_log('Response cached', [
                'duration' => $cache_duration,
                'key' => $cache_key
            ]);
        }
        
        return $result;
    }

    /**
     * Get signed WebSocket URL for voice conversation
     */
    public function get_signed_url() {
        $api_key = $this->get_api_key();
        $agent_id = $this->get_agent_id();
        
        if (empty($api_key) || empty($agent_id)) {
            return new WP_Error(
                'missing_credentials',
                'ElevenLabs API key or Agent ID is missing',
                ['status' => 400]
            );
        }
        
        $this->debug_log('Requesting signed URL for agent ' . $agent_id);
        
        // Cache key for signed URL
        $cache_key = 'elevenlabs_signed_url_' . md5($agent_id);
        $cached_url = get_transient($cache_key);
        
        if ($cached_url) {
            $this->debug_log('Using cached signed URL');
            return new WP_REST_Response(['signed_url' => $cached_url], 200);
        }
        
        // URL for the signed_url endpoint
        $request_url = "{$this->api_base_url}/convai/conversation/get_signed_url?agent_id=" . urlencode($agent_id);
        
        $response = wp_remote_get(
            $request_url,
            [
                'headers' => [
                    'xi-api-key' => $api_key,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 15
            ]
        );
        
        if (is_wp_error($response)) {
            $this->debug_log('Error getting signed URL: ' . $response->get_error_message());
            return new WP_Error(
                'api_error',
                'Error connecting to ElevenLabs API: ' . $response->get_error_message(),
                ['status' => 500]
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200) {
            $error_message = isset($response_body['message']) 
                ? $response_body['message'] 
                : 'Unknown error';
                
            $this->debug_log('API Error: ' . $error_message);
            
            return new WP_Error(
                'api_error',
                'ElevenLabs API returned an error: ' . $error_message,
                ['status' => $response_code]
            );
        }
        
        // Verify response contains signed_url
        if (!isset($response_body['signed_url']) || empty($response_body['signed_url'])) {
            $this->debug_log('Invalid response: Missing signed_url');
            return new WP_Error(
                'invalid_response',
                'Invalid response from ElevenLabs API: Missing signed_url',
                ['status' => 500]
            );
        }
        
        $signed_url = $response_body['signed_url'];
        $this->debug_log('Signed URL successfully obtained');
        
        // Cache the signed URL (typically valid for 15 minutes, we'll cache for 5)
        set_transient($cache_key, $signed_url, 5 * MINUTE_IN_SECONDS);
        
        // Log this interaction for reporting
        $this->log_interaction('voice_agent_session', [
            'agent_id' => $agent_id,
            'timestamp' => time(),
            'type' => 'voice_agent'
        ]);
        
        return new WP_REST_Response(['signed_url' => $signed_url], 200);
    }

    /**
     * Generate speech from text using ElevenLabs TTS API
     */
    public function text_to_speech($text, $options = []) {
        if (!$this->has_valid_credentials()) {
            return new WP_Error(
                'missing_credentials',
                'ElevenLabs API key is missing',
                ['status' => 400]
            );
        }
        
        // Default options
        $default_options = [
            'voice_id' => get_option('wp_dual_ai_elevenlabs_voice_id', ''),
            'model_id' => 'eleven_multilingual_v2',
            'output_format' => 'mp3_44100_128',
            'voice_settings' => [
                'stability' => 0.75,
                'similarity_boost' => 0.75,
                'style' => 0.0,
                'use_speaker_boost' => true
            ]
        ];
        
        $options = wp_parse_args($options, $default_options);
        
        if (empty($options['voice_id'])) {
            return new WP_Error(
                'missing_voice_id',
                'A voice ID is required for TTS',
                ['status' => 400]
            );
        }
        
        // Generate cache key based on text and settings
        $cache_key = 'elevenlabs_tts_' . md5($text . serialize($options));
        $cached_file = get_transient($cache_key);
        
        // Return cached file if exists
        if ($cached_file && file_exists($cached_file['file_path'])) {
            $this->debug_log('Using cached TTS file');
            return $cached_file;
        }
        
        // Prepare request data
        $request_data = [
            'text' => $text,
            'model_id' => $options['model_id'],
            'voice_settings' => $options['voice_settings']
        ];
        
        // API endpoint with voice ID and output format
        $endpoint = "text-to-speech/{$options['voice_id']}?output_format={$options['output_format']}";
        
        // Make request to API
        $response = wp_remote_post(
            "{$this->api_base_url}/{$endpoint}",
            [
                'headers' => $this->get_auth_headers(),
                'body' => json_encode($request_data),
                'timeout' => 30
            ]
        );
        
        if (is_wp_error($response)) {
            $this->debug_log('TTS Error: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($response_body['message']) 
                ? $response_body['message'] 
                : 'Unknown error';
                
            $this->debug_log('TTS API Error: ' . $error_message);
            
            return new WP_Error(
                'api_error',
                'TTS API error: ' . $error_message,
                ['status' => $response_code]
            );
        }
        
        // Response contains audio data
        $audio_data = wp_remote_retrieve_body($response);
        
        // Store the audio data in a temporary file
        $upload_dir = wp_upload_dir();
        $file_name = 'elevenlabs_tts_' . md5(time() . wp_rand()) . '.mp3';
        $file_path = $upload_dir['basedir'] . '/wp-dual-ai-temp/' . $file_name;
        $file_url = $upload_dir['baseurl'] . '/wp-dual-ai-temp/' . $file_name;
        
        // Ensure the temporary directory exists
        wp_mkdir_p($upload_dir['basedir'] . '/wp-dual-ai-temp/');
        
        // Write audio data to file
        if (file_put_contents($file_path, $audio_data) === false) {
            $this->debug_log('Error writing audio file', [
                'path' => $file_path
            ]);
            
            return new WP_Error(
                'file_write_error',
                'Error saving audio file',
                ['status' => 500]
            );
        }
        
        // Result with URL and file path
        $result = [
            'audio_url' => $file_url,
            'file_path' => $file_path
        ];
        
        // Cache for 24 hours
        set_transient($cache_key, $result, 24 * HOUR_IN_SECONDS);
        
        // Log this interaction for reporting
        $this->log_interaction('text_to_speech', [
            'voice_id' => $options['voice_id'],
            'model_id' => $options['model_id'],
            'text_length' => strlen($text),
            'timestamp' => time()
        ]);
        
        return $result;
    }

    /**
     * Get available voices from ElevenLabs API
     */
    public function get_voices() {
        // Cache key for voices
        $cache_key = 'elevenlabs_voices';
        $cached_voices = get_transient($cache_key);
        
        if ($cached_voices) {
            return $cached_voices;
        }
        
        // Make API request with caching
        $response = $this->api_request('voices');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Verify response structure
        if (!isset($response['voices']) || !is_array($response['voices'])) {
            return new WP_Error(
                'invalid_response',
                'Invalid response from ElevenLabs API',
                ['status' => 500]
            );
        }
        
        // Format voice data for easier use
        $voices = [];
        foreach ($response['voices'] as $voice) {
            $voices[] = [
                'voice_id' => $voice['voice_id'],
                'name' => $voice['name'],
                'category' => isset($voice['category']) ? $voice['category'] : 'custom'
            ];
        }
        
        // Cache for 1 hour
        set_transient($cache_key, $voices, HOUR_IN_SECONDS);
        
        return $voices;
    }

    /**
     * Test connection to ElevenLabs API
     */
    public function test_connection() {
        if (!$this->has_valid_credentials()) {
            return new WP_Error(
                'missing_credentials',
                'ElevenLabs API credentials are missing',
                ['status' => 400]
            );
        }
        
        // Try to get voices as a connection test
        $response = $this->api_request('voices', [], false);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return [
            'success' => true,
            'voices' => $this->get_voices()
        ];
    }

    /**
     * Schedule cleanup of temporary files
     */
    public function schedule_temp_files_cleanup() {
        if (!wp_next_scheduled('wp_dual_ai_cleanup_temp_files')) {
            wp_schedule_event(time(), 'daily', 'wp_dual_ai_cleanup_temp_files');
        }
    }

    /**
     * Clean up temporary files older than 2 days
     */
    public function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/wp-dual-ai-temp/';
        
        if (!is_dir($temp_dir)) {
            return 0;
        }
        
        $files = glob($temp_dir . '*.mp3');
        $count = 0;
        
        foreach ($files as $file) {
            // Remove files older than 48 hours
            if (filemtime($file) < time() - 2 * DAY_IN_SECONDS) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }
        
        $this->debug_log('Temp files cleanup complete', [
            'files_removed' => $count
        ]);
        
        return $count;
    }
}
