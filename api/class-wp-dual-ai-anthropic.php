<?php
/**
 * The Anthropic API integration class.
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/api
 */

class WP_Dual_AI_Anthropic {

    /**
     * The base API URL for Anthropic.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_base_url    The base URL for API requests.
     */
    private $api_base_url = 'https://api.anthropic.com/v1';

    /**
     * The API version for Anthropic.
     *
     * @since    1.9.0
     * @access   private
     * @var      string    $api_version    The API version to use.
     */
    private $api_version = '2023-06-01';

    /**
     * Debug mode flag.
     *
     * @since    1.9.0
     * @access   private
     * @var      boolean    $debug_mode    Whether to enable debug logging.
     */
    private $debug_mode = false;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Enable debug mode if WP_DEBUG is defined and true
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_mode = true;
        }
    }

    /**
     * Log debug information if debug mode is enabled.
     *
     * @since    1.9.0
     * @access   private
     * @param    string    $message    Debug message.
     * @param    array     $data       Optional data to log.
     */
    private function debug_log($message, $data = array()) {
        if (!$this->debug_mode) {
            return;
        }

        if (empty($data)) {
            error_log('Anthropic API: ' . $message);
        } else {
            error_log('Anthropic API: ' . $message . ' - ' . json_encode($data));
        }
    }

    /**
     * Get the Anthropic API key from settings.
     *
     * @return string API key
     */
    private function get_api_key() {
        return get_option('wp_dual_ai_anthropic_api_key', '');
    }

    /**
     * Get the Anthropic model from settings.
     *
     * @return string Model name
     */
    private function get_model() {
        return get_option('wp_dual_ai_anthropic_model', 'claude-3-sonnet-20240229');
    }

    /**
     * Test connection to the Anthropic API.
     *
     * @since    1.9.0
     * @param    string    $api_key    Optional API key to test. If not provided, will use the saved one.
     * @return   array|WP_Error       Result of the test or error.
     */
    public function test_connection($api_key = '') {
        $this->debug_log('Testing Anthropic API connection');

        // Use provided API key or get from settings
        $api_key = !empty($api_key) ? $api_key : $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error(
                'missing_api_key',
                'Anthropic API key is missing',
                array('status' => 400)
            );
        }

        // Use a simple request to test the connection
        $test_message = array(
            'model' => $this->get_model(),
            'max_tokens' => 10,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Say hello world'
                )
            )
        );

        $response = wp_remote_post(
            "{$this->api_base_url}/messages",
            array(
                'headers' => array(
                    'x-api-key' => $api_key,
                    'anthropic-version' => $this->api_version,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($test_message),
                'timeout' => 15
            )
        );

        if (is_wp_error($response)) {
            $this->debug_log('API connection error', array('error' => $response->get_error_message()));
            return new WP_Error(
                'api_error',
                'Error connecting to Anthropic API: ' . $response->get_error_message(),
                array('status' => 500)
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = isset($response_body['error']['message']) 
                ? $response_body['error']['message'] 
                : 'Unknown error occurred';
                
            $this->debug_log('API test failed', array(
                'code' => $response_code,
                'message' => $error_message
            ));
            
            return new WP_Error(
                'api_error',
                'Anthropic API returned an error: ' . $error_message,
                array('status' => $response_code)
            );
        }

        $this->debug_log('API test successful', array('model' => $response_body['model']));

        return array(
            'success' => true,
            'model' => $response_body['model'],
            'message' => 'API connection successful'
        );
    }

    /**
     * Log interactions with the Anthropic API for reporting.
     *
     * @param string $interaction_type Type of interaction
     * @param array $data Data associated with the interaction
     * @return int|false The ID of the log entry or false on failure
     */
    public function log_interaction($interaction_type, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        $this->debug_log('Logging interaction', array(
            'type' => $interaction_type,
            'data' => $data
        ));
        
        return $wpdb->insert(
            $table_name,
            array(
                'interaction_type' => sanitize_text_field($interaction_type),
                'api_provider' => 'anthropic',
                'interaction_data' => json_encode($data),
                'interaction_time' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'session_id' => $this->get_session_id()
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Generate or retrieve a session ID for tracking conversations.
     *
     * @return string Session ID
     */
    private function get_session_id() {
        if (!isset($_COOKIE['wp_dual_ai_session'])) {
            $session_id = wp_generate_uuid4();
            setcookie('wp_dual_ai_session', $session_id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            return $session_id;
        }
        
        return sanitize_text_field($_COOKIE['wp_dual_ai_session']);
    }

    /**
     * Send a chat request to the Anthropic API.
     *
     * @param array $parameters Request parameters including messages
     * @return array|WP_Error API response or error
     */
    public function send_chat_request($parameters) {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            $this->debug_log('Missing API key');
            return new WP_Error(
                'missing_api_key',
                'Anthropic API key is missing',
                array('status' => 400)
            );
        }
        
        // Default parameters
        $default_parameters = array(
            'model' => $this->get_model(),
            'max_tokens' => 1024,
            'temperature' => 0.7,
            'system' => get_option('wp_dual_ai_anthropic_system_prompt', ''),
            'messages' => array()
        );
        
        $parameters = wp_parse_args($parameters, $default_parameters);
        
        // Validate messages format
        if (empty($parameters['messages']) || !is_array($parameters['messages'])) {
            $this->debug_log('Invalid messages format');
            return new WP_Error(
                'invalid_messages',
                'Messages must be a non-empty array',
                array('status' => 400)
            );
        }
        
        // Get product context if in a product page
        if (isset($parameters['product_id']) && function_exists('wc_get_product')) {
            $product_id = absint($parameters['product_id']);
            $product = wc_get_product($product_id);
            
            if ($product) {
                // Add product context to system prompt
                $product_context = "You are assisting with information about the product: " . $product->get_name() . "\n";
                $product_context .= "Description: " . strip_tags($product->get_description()) . "\n";
                $product_context .= "Price: " . $product->get_price_html() . "\n";
                
                if (!empty($parameters['system'])) {
                    $parameters['system'] = $product_context . "\n" . $parameters['system'];
                } else {
                    $parameters['system'] = $product_context;
                }
            }
        }
        
        // Prepare request data with required fields
        $request_data = array(
            'model' => $parameters['model'],
            'messages' => $parameters['messages'],
            'max_tokens' => $parameters['max_tokens'],
            'temperature' => $parameters['temperature']
        );
        
        if (!empty($parameters['system'])) {
            $request_data['system'] = $parameters['system'];
        }
        
        $this->debug_log('Sending request to Anthropic API', array('model' => $parameters['model']));
        
        // Send request to Anthropic API
        $response = wp_remote_post(
            "{$this->api_base_url}/messages",
            array(
                'headers' => array(
                    'x-api-key' => $api_key,
                    'anthropic-version' => $this->api_version,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($request_data),
                'timeout' => 30
            )
        );
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->debug_log('API request error', array('error' => $error_message));
            
            return new WP_Error(
                'api_error',
                'Error connecting to Anthropic API: ' . $error_message,
                array('status' => 500)
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200) {
            $error_message = isset($response_body['error']['message']) 
                ? $response_body['error']['message'] 
                : 'Unknown error occurred';
                
            $this->debug_log('API response error', array(
                'code' => $response_code,
                'message' => $error_message
            ));
            
            return new WP_Error(
                'api_error',
                'Anthropic API returned an error: ' . $error_message,
                array('status' => $response_code)
            );
        }
        
        // Log this interaction for reporting
        $input_text = '';
        if (!empty($parameters['messages']) && is_array($parameters['messages'])) {
            $last_message = end($parameters['messages']);
            if (isset($last_message['content'])) {
                $input_text = is_string($last_message['content']) ? 
                    $last_message['content'] : 
                    json_encode($last_message['content']);
            }
        }
        
        $this->log_interaction('chat_message', array(
            'input' => $input_text,
            'output' => $response_body['content'][0]['text'],
            'model' => $parameters['model'],
            'timestamp' => time()
        ));
        
        $this->debug_log('API request successful');
        
        return $response_body;
    }

    /**
     * Get models available from Anthropic.
     * Provides a predefined list based on documentation.
     *
     * @return array List of available models
     */
    public function get_models() {
        return array(
            // Claude 3 family (most recent first)
            'claude-3-5-sonnet-20240620' => 'Claude 3.5 Sonnet (June 2024)',
            'claude-3-opus-20240229' => 'Claude 3 Opus (Feb 2024)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Feb 2024)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Mar 2024)',
            
            // Claude 2 family
            'claude-2.1' => 'Claude 2.1',
            'claude-2.0' => 'Claude 2.0',
            
            // Claude Instant (older models)
            'claude-instant-1.2' => 'Claude Instant 1.2'
        );
    }
}
