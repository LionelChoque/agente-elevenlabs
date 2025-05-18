
<?php
/**
 * The core plugin class.
 */
class WP_Dual_AI_Assistant {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = WP_DUAL_AI_VERSION;
        $this->plugin_name = 'wp-dual-ai-assistant'; // Asegúrate de que este es el nombre correcto

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_rest_api_endpoints();
    }

    private function load_dependencies() {
        require_once WP_DUAL_AI_PLUGIN_DIR . 'includes/class-wp-dual-ai-loader.php';
        require_once WP_DUAL_AI_PLUGIN_DIR . 'includes/class-wp-dual-ai-i18n.php';
        require_once WP_DUAL_AI_PLUGIN_DIR . 'admin/class-wp-dual-ai-admin.php';
        require_once WP_DUAL_AI_PLUGIN_DIR . 'public/class-wp-dual-ai-public.php';
        require_once WP_DUAL_AI_PLUGIN_DIR . 'api/class-wp-dual-ai-anthropic.php';
        require_once WP_DUAL_AI_PLUGIN_DIR . 'api/class-wp-dual-ai-elevenlabs.php';

        $this->loader = new WP_Dual_AI_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new WP_Dual_AI_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new WP_Dual_AI_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_wp_dual_ai_export_csv', $plugin_admin, 'export_interactions_csv');
        $this->loader->add_action('wp_ajax_wp_dual_ai_test_anthropic', $plugin_admin, 'test_anthropic_connection');
        $this->loader->add_action('wp_ajax_wp_dual_ai_test_elevenlabs', $plugin_admin, 'test_elevenlabs_connection');
    }

    private function define_public_hooks() {
        $plugin_public = new WP_Dual_AI_Public($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            $this->loader->add_action('woocommerce_after_single_product_summary', $plugin_public, 'display_chat_interfaces', 15);
        }
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_wp_dual_ai_text_chat', $plugin_public, 'handle_text_chat');
        $this->loader->add_action('wp_ajax_nopriv_wp_dual_ai_text_chat', $plugin_public, 'handle_text_chat');
    }

    private function define_rest_api_endpoints() {
        add_action('rest_api_init', function() {
            // ElevenLabs WebSocket URL endpoint
            register_rest_route('wp-dual-ai/v1', '/elevenlabs/signed-url', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_elevenlabs_signed_url'),
                'permission_callback' => '__return_true' // Public endpoint, secured with nonce
            ));

            // Text-to-speech endpoint
            register_rest_route('wp-dual-ai/v1', '/elevenlabs/text-to-speech', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_text_to_speech'),
                'permission_callback' => '__return_true' // Public endpoint, secured with nonce
            ));

            // Anthropic chat endpoint
            register_rest_route('wp-dual-ai/v1', '/anthropic/chat', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_anthropic_chat'),
                'permission_callback' => '__return_true' // Public endpoint, secured with nonce
            ));
        });
    }

    public function get_elevenlabs_signed_url($request) {
        // Verify nonce
        if (!$this->verify_rest_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid security token', array('status' => 403));
        }

        $elevenlabs_api = new WP_Dual_AI_ElevenLabs();
        return $elevenlabs_api->get_signed_url();
    }

    public function handle_text_to_speech($request) {
        // Verify nonce
        if (!$this->verify_rest_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid security token', array('status' => 403));
        }

        $text = sanitize_textarea_field($request->get_param('text'));
        $voice_id = sanitize_text_field($request->get_param('voice_id'));
        
        if (empty($text) || empty($voice_id)) {
            return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
        }
        
        $elevenlabs_api = new WP_Dual_AI_ElevenLabs();
        $result = $elevenlabs_api->text_to_speech($text, array('voice_id' => $voice_id));
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }

    public function handle_anthropic_chat($request) {
        // Verify nonce
        if (!$this->verify_rest_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid security token', array('status' => 403));
        }
        
        $parameters = $request->get_json_params();
        
        if (empty($parameters) || empty($parameters['messages'])) {
            return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
        }
        
        $anthropic_api = new WP_Dual_AI_Anthropic();
        $result = $anthropic_api->send_chat_request($parameters);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }

    private function verify_rest_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        return wp_verify_nonce($nonce, 'wp_rest');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
