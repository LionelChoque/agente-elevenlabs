<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/admin
 */

class WP_Dual_AI_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The reports handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Dual_AI_Reports    $reports    The reports handler.
     */
    private $reports;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of the plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Initialize reports handler
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/reports/class-wp-dual-ai-reports.php';
        $this->reports = new WP_Dual_AI_Reports();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/wp-dual-ai-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/wp-dual-ai-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script($this->plugin_name, 'wpDualAIAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_dual_ai_admin_nonce')
        ));
    }

    /**
     * Add plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            'Dual AI Assistant',
            'Dual AI Assistant',
            'manage_options',
            'wp-dual-ai-assistant',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-microphone',
            26
        );
        
        // Settings submenu
        add_submenu_page(
            'wp-dual-ai-assistant',
            'Settings',
            'Settings',
            'manage_options',
            'wp-dual-ai-assistant-settings',
            array($this, 'display_plugin_admin_settings')
        );
        
        // Reports submenu
        add_submenu_page(
            'wp-dual-ai-assistant',
            'Reports',
            'Reports',
            'manage_options',
            'wp-dual-ai-assistant-reports',
            array($this, 'display_plugin_admin_reports')
        );
    }

    /**
     * Render the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once 'partials/admin-display.php';
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_settings() {
        include_once 'partials/admin-settings.php';
    }

    /**
     * Render the reports page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_reports() {
        include_once 'partials/admin-reports.php';
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // General Settings
        register_setting('wp_dual_ai_general', 'wp_dual_ai_welcome_message');
        register_setting('wp_dual_ai_general', 'wp_dual_ai_show_on_products');
        
        // Anthropic API Settings
        register_setting('wp_dual_ai_anthropic', 'wp_dual_ai_anthropic_api_key');
        register_setting('wp_dual_ai_anthropic', 'wp_dual_ai_anthropic_model');
        register_setting('wp_dual_ai_anthropic', 'wp_dual_ai_anthropic_system_prompt');
        
        // ElevenLabs API Settings
        register_setting('wp_dual_ai_elevenlabs', 'wp_dual_ai_elevenlabs_api_key');
        register_setting('wp_dual_ai_elevenlabs', 'wp_dual_ai_elevenlabs_agent_id');
        register_setting('wp_dual_ai_elevenlabs', 'wp_dual_ai_elevenlabs_voice_id');


// ElevenLabs Settings fields
add_settings_field(
    'wp_dual_ai_elevenlabs_ringtone',
    'Ringtone personalizado',
    array($this, 'elevenlabs_ringtone_callback'),
    'wp_dual_ai_elevenlabs',
    'wp_dual_ai_elevenlabs_section'
);

        // General Settings section
        add_settings_section(
            'wp_dual_ai_general_section',
            'General Settings',
            array($this, 'general_settings_section_callback'),
            'wp_dual_ai_general'
        );
        
        // Anthropic Settings section
        add_settings_section(
            'wp_dual_ai_anthropic_section',
            'Anthropic API Settings',
            array($this, 'anthropic_settings_section_callback'),
            'wp_dual_ai_anthropic'
        );
        
        // ElevenLabs Settings section
        add_settings_section(
            'wp_dual_ai_elevenlabs_section',
            'ElevenLabs API Settings',
            array($this, 'elevenlabs_settings_section_callback'),
            'wp_dual_ai_elevenlabs'
        );
        
        // General Settings fields
        add_settings_field(
            'wp_dual_ai_welcome_message',
            'Welcome Message',
            array($this, 'welcome_message_callback'),
            'wp_dual_ai_general',
            'wp_dual_ai_general_section'
        );
        
        add_settings_field(
            'wp_dual_ai_show_on_products',
            'Show on Product Pages',
            array($this, 'show_on_products_callback'),
            'wp_dual_ai_general',
            'wp_dual_ai_general_section'
        );
        
        // Anthropic Settings fields
        add_settings_field(
            'wp_dual_ai_anthropic_api_key',
            'Anthropic API Key',
            array($this, 'anthropic_api_key_callback'),
            'wp_dual_ai_anthropic',
            'wp_dual_ai_anthropic_section'
        );
        
        add_settings_field(
            'wp_dual_ai_anthropic_model',
            'Anthropic Model',
            array($this, 'anthropic_model_callback'),
            'wp_dual_ai_anthropic',
            'wp_dual_ai_anthropic_section'
        );
        
        add_settings_field(
            'wp_dual_ai_anthropic_system_prompt',
            'System Prompt',
            array($this, 'anthropic_system_prompt_callback'),
            'wp_dual_ai_anthropic',
            'wp_dual_ai_anthropic_section'
        );
        
        // ElevenLabs Settings fields
        add_settings_field(
            'wp_dual_ai_elevenlabs_api_key',
            'ElevenLabs API Key',
            array($this, 'elevenlabs_api_key_callback'),
            'wp_dual_ai_elevenlabs',
            'wp_dual_ai_elevenlabs_section'
        );
        
        add_settings_field(
            'wp_dual_ai_elevenlabs_agent_id',
            'ElevenLabs Agent ID',
            array($this, 'elevenlabs_agent_id_callback'),
            'wp_dual_ai_elevenlabs',
            'wp_dual_ai_elevenlabs_section'
        );
        
        add_settings_field(
            'wp_dual_ai_elevenlabs_voice_id',
            'ElevenLabs Voice ID',
            array($this, 'elevenlabs_voice_id_callback'),
            'wp_dual_ai_elevenlabs',
            'wp_dual_ai_elevenlabs_section'
        );
    }
// Inicializar Media Uploader para el ringtone
$('#subir-archivo-de-ringtone').on('click', function(e) {
    e.preventDefault();
    
    var ringtoneUploader = wp.media({
        title: 'Seleccionar archivo de ringtone',
        button: {
            text: 'Usar este archivo'
        },
        multiple: false,
        library: {
            type: ['audio/mpeg', 'audio/wav']
        }
    });
    
    ringtoneUploader.on('select', function() {
        var attachment = ringtoneUploader.state().get('selection').first().toJSON();
        $('#wp_dual_ai_ringtone_id').val(attachment.id);
        $('#ringtone-filename').text(attachment.filename);
    });
    
    ringtoneUploader.open();
});    
/**
 * ElevenLabs ringtone field callback.
 *
 * @since    1.1.0
 */
public function elevenlabs_ringtone_callback() {
    $ringtone_id = get_option('wp_dual_ai_elevenlabs_ringtone', '');
    ?>
    <div class="wp-dual-ai-ringtone-field">
        <?php if (!empty($ringtone_id)) : 
            $ringtone_url = wp_get_attachment_url($ringtone_id);
            $ringtone_filename = basename(get_attached_file($ringtone_id));
        ?>
            <div class="wp-dual-ai-current-ringtone">
                <p>Ringtone actual: <strong><?php echo esc_html($ringtone_filename); ?></strong></p>
                <audio controls style="vertical-align: middle; max-width: 250px;">
                    <source src="<?php echo esc_url($ringtone_url); ?>" type="audio/mpeg">
                    Tu navegador no soporta la reproducción de audio.
                </audio>
                <button type="button" class="button wp-dual-ai-remove-ringtone">Quitar</button>
                <input type="hidden" name="wp_dual_ai_elevenlabs_ringtone" id="wp_dual_ai_elevenlabs_ringtone" value="<?php echo esc_attr($ringtone_id); ?>">
            </div>
        <?php else : ?>
            <input type="hidden" name="wp_dual_ai_elevenlabs_ringtone" id="wp_dual_ai_elevenlabs_ringtone" value="">
        <?php endif; ?>
        
        <button type="button" class="button wp-dual-ai-upload-ringtone">
            <?php echo empty($ringtone_id) ? 'Subir archivo de ringtone' : 'Cambiar ringtone'; ?>
        </button>
        
        <p class="description">Sube un archivo MP3 para usar como ringtone en el chat de voz. Tamaño máximo recomendado: 500KB. Formatos soportados: MP3, WAV.</p>
        <p class="description ringtone-default-note">Si no se selecciona ningún ringtone, se usará el tono predeterminado del sistema.</p>
    </div>
    <?php
}
    /**
     * General Settings section callback.
     *
     * @since    1.0.0
     */
    public function general_settings_section_callback() {
        echo '<p>Configure general settings for the Dual AI Assistant.</p>';
    }

    /**
     * Anthropic Settings section callback.
     *
     * @since    1.0.0
     */
    public function anthropic_settings_section_callback() {
        echo '<p>Configure Anthropic API settings for the text chat functionality.</p>';
    }

    /**
     * ElevenLabs Settings section callback.
     *
     * @since    1.0.0
     */
    public function elevenlabs_settings_section_callback() {
        echo '<p>Configure ElevenLabs API settings for the voice chat functionality.</p>';
    }

    /**
     * Welcome message field callback.
     *
     * @since    1.0.0
     */
    public function welcome_message_callback() {
        $value = get_option('wp_dual_ai_welcome_message', 'Hello! How can I help you today?');
        ?>
        <textarea name="wp_dual_ai_welcome_message" id="wp_dual_ai_welcome_message" rows="3" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">This message will be displayed when a user starts a new chat.</p>
        <?php
    }

    /**
     * Show on products field callback.
     *
     * @since    1.0.0
     */
    public function show_on_products_callback() {
        $value = get_option('wp_dual_ai_show_on_products', 'buttons');
        ?>
        <select name="wp_dual_ai_show_on_products" id="wp_dual_ai_show_on_products">
            <option value="none" <?php selected($value, 'none'); ?>>Do not show</option>
            <option value="buttons" <?php selected($value, 'buttons'); ?>>Show chat buttons only</option>
            <option value="text" <?php selected($value, 'text'); ?>>Show text chat only</option>
            <option value="voice" <?php selected($value, 'voice'); ?>>Show voice chat only</option>
            <option value="all" <?php selected($value, 'all'); ?>>Show all interfaces</option>
        </select>
        <p class="description">Choose how to display AI assistants on WooCommerce product pages.</p>
        <?php
    }

    /**
     * Anthropic API key field callback.
     *
     * @since    1.0.0
     */
    public function anthropic_api_key_callback() {
        $value = get_option('wp_dual_ai_anthropic_api_key', '');
        ?>
        <input type="password" name="wp_dual_ai_anthropic_api_key" id="wp_dual_ai_anthropic_api_key" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">Enter your Anthropic API key. <a href="https://console.anthropic.com/account/keys" target="_blank">Get your API key</a></p>
        <?php
    }

    /**
     * Anthropic model field callback.
     *
     * @since    1.0.0
     */
    public function anthropic_model_callback() {
        $value = get_option('wp_dual_ai_anthropic_model', 'claude-3-sonnet-20240229');
        
        // Get instance of the Anthropic API class
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-anthropic.php';
        $anthropic_api = new WP_Dual_AI_Anthropic();
        $models = $anthropic_api->get_models();
        ?>
        <select name="wp_dual_ai_anthropic_model" id="wp_dual_ai_anthropic_model">
            <?php foreach ($models as $model_id => $model_name) : ?>
                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($value, $model_id); ?>><?php echo esc_html($model_name); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description">Select the Anthropic model to use for text chat.</p>
        <?php
    }

    /**
     * Anthropic system prompt field callback.
     *
     * @since    1.0.0
     */
    public function anthropic_system_prompt_callback() {
        $value = get_option('wp_dual_ai_anthropic_system_prompt', '');
        ?>
        <textarea name="wp_dual_ai_anthropic_system_prompt" id="wp_dual_ai_anthropic_system_prompt" rows="5" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">System prompt to provide context for the AI. For product-specific chats, product details will be added automatically.</p>
        <?php
    }

    /**
     * ElevenLabs API key field callback.
     *
     * @since    1.0.0
     */
    public function elevenlabs_api_key_callback() {
        $value = get_option('wp_dual_ai_elevenlabs_api_key', '');
        ?>
        <input type="password" name="wp_dual_ai_elevenlabs_api_key" id="wp_dual_ai_elevenlabs_api_key" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">Enter your ElevenLabs API key. <a href="https://elevenlabs.io/app/api-key" target="_blank">Get your API key</a></p>
        <?php
    }

    /**
     * ElevenLabs Agent ID field callback.
     *
     * @since    1.0.0
     */
    public function elevenlabs_agent_id_callback() {
        $value = get_option('wp_dual_ai_elevenlabs_agent_id', '');
        ?>
        <input type="text" name="wp_dual_ai_elevenlabs_agent_id" id="wp_dual_ai_elevenlabs_agent_id" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">Enter your ElevenLabs Agent ID. <a href="https://elevenlabs.io/app/conversational-ai" target="_blank">Create an agent</a></p>
        <?php
    }

    /**
     * ElevenLabs Voice ID field callback.
     *
     * @since    1.0.0
     */
    public function elevenlabs_voice_id_callback() {
        $value = get_option('wp_dual_ai_elevenlabs_voice_id', '');
        
        // Get instance of the ElevenLabs API class
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-elevenlabs.php';
        $elevenlabs_api = new WP_Dual_AI_ElevenLabs();
        $voices = $elevenlabs_api->get_voices();
        ?>
        <select name="wp_dual_ai_elevenlabs_voice_id" id="wp_dual_ai_elevenlabs_voice_id">
            <option value="">Select a voice</option>
            <?php if (is_array($voices)) : ?>
                <?php foreach ($voices as $voice) : ?>
                    <option value="<?php echo esc_attr($voice['voice_id']); ?>" <?php selected($value, $voice['voice_id']); ?>><?php echo esc_html($voice['name']); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <p class="description">Select a voice for text-to-speech. This voice will be used for the ringtone and any pre-recorded messages.</p>
        <?php
    }

    /**
     * Register AJAX hooks for the admin area.
     * 
     * @since    1.1.0
     */
    public function register_ajax_hooks() {
        // Original AJAX handler
        add_action('wp_ajax_wp_dual_ai_export_csv', array($this, 'export_interactions_csv'));
        
        // New AJAX handlers for improved UX/UI
        add_action('wp_ajax_wp_dual_ai_refresh_dashboard', array($this, 'refresh_dashboard_data'));
        add_action('wp_ajax_wp_dual_ai_get_chart_data', array($this, 'get_chart_data_ajax'));
        add_action('wp_ajax_wp_dual_ai_check_status', array($this, 'check_system_status'));
        
        // API test handlers
        add_action('wp_ajax_wp_dual_ai_test_anthropic', array($this, 'test_anthropic_connection'));
        add_action('wp_ajax_wp_dual_ai_test_elevenlabs', array($this, 'test_elevenlabs_connection'));
    }

    /**
     * Test Anthropic API connection.
     *
     * @since    1.1.0
     */
    public function test_anthropic_connection() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'), 403);
        }
        
        // Get API key from request or settings
        $api_key = isset($_POST['api_key']) && !empty($_POST['api_key']) ? 
                   sanitize_text_field($_POST['api_key']) : 
                   get_option('wp_dual_ai_anthropic_api_key');
                   
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'), 400);
        }
        
        // Test connection
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-anthropic.php';
        $anthropic_api = new WP_Dual_AI_Anthropic();
        $result = $anthropic_api->test_connection($api_key);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 500);
        }
        
        wp_send_json_success($result);
    }

    /**
     * Test ElevenLabs API connection.
     *
     * @since    1.1.0
     */
    public function test_elevenlabs_connection() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'), 403);
        }
        
        // Get API key from request or settings
        $api_key = isset($_POST['api_key']) && !empty($_POST['api_key']) ? 
                   sanitize_text_field($_POST['api_key']) : 
                   get_option('wp_dual_ai_elevenlabs_api_key');
                   
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'), 400);
        }
        
        // Test connection
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-elevenlabs.php';
        $elevenlabs_api = new WP_Dual_AI_ElevenLabs();
        $result = $elevenlabs_api->test_connection($api_key);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 500);
        }
        
        wp_send_json_success($result);
    }

    /**
     * Export interactions as CSV (AJAX handler).
     *
     * @since    1.0.0
     */
    public function export_interactions_csv() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'), 403);
        }
        
        // Get parameters
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $api_provider = isset($_POST['api_provider']) ? sanitize_text_field($_POST['api_provider']) : '';
        
        // Generate CSV
        $csv_data = $this->reports->generate_csv($start_date, $end_date, $api_provider);
        
        if (!$csv_data) {
            wp_send_json_error(array('message' => 'No data found or error generating CSV'), 404);
        }
        
        // Return CSV data
        wp_send_json_success(array(
            'csv_data' => $csv_data,
            'filename' => 'dual-ai-interactions-' . date('Y-m-d') . '.csv'
        ));
    }

    /**
 * Nuevos métodos para la clase WP_Dual_AI_Admin para soportar las mejoras de UX/UI
 * (Solo los métodos nuevos o actualizados)
 */

    /**
     * Refresh dashboard data via AJAX.
     *
     * @since    1.1.0
     */
    public function refresh_dashboard_data() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'), 403);
        }
        
        // Get parameters
        $date_range = isset($_POST['dateRange']) ? sanitize_text_field($_POST['dateRange']) : '7days';
        $start_date = isset($_POST['startDate']) ? sanitize_text_field($_POST['startDate']) : '';
        $end_date = isset($_POST['endDate']) ? sanitize_text_field($_POST['endDate']) : '';
        
        // Determine date filter based on selected range
        $date_filter = $this->get_date_filter($date_range, $start_date, $end_date);
        
        // Get metrics
        $metrics = $this->get_dashboard_metrics($date_filter);
        
        // Get chart data
        $chart_data = $this->get_chart_data($date_filter, 'daily');
        
        // Get recent interactions
        $recent_interactions = $this->reports->get_recent_interactions(5);
        
        // Add time ago for each interaction
        foreach ($recent_interactions as &$interaction) {
            $interaction['time_ago'] = human_time_diff(strtotime($interaction['interaction_time']), current_time('timestamp')) . ' ago';
        }
        
        wp_send_json_success(array(
            'metrics' => $metrics,
            'chart_data' => $chart_data,
            'recent_interactions' => $recent_interactions
        ));
    }

    /**
     * Get date filter for SQL queries.
     *
     * @since    1.1.0
     * @param    string    $date_range    Selected date range.
     * @param    string    $start_date    Custom start date.
     * @param    string    $end_date      Custom end date.
     * @return   array     SQL WHERE clause and parameters.
     */
    private function get_date_filter($date_range, $start_date, $end_date) {
        $where = '';
        $params = array();
        
        switch ($date_range) {
            case 'today':
                $where = "WHERE DATE(interaction_time) = %s";
                $params[] = date('Y-m-d');
                break;
                
            case 'yesterday':
                $where = "WHERE DATE(interaction_time) = %s";
                $params[] = date('Y-m-d', strtotime('-1 day'));
                break;
                
            case '7days':
                $where = "WHERE DATE(interaction_time) >= %s";
                $params[] = date('Y-m-d', strtotime('-7 days'));
                break;
                
            case '30days':
                $where = "WHERE DATE(interaction_time) >= %s";
                $params[] = date('Y-m-d', strtotime('-30 days'));
                break;
                
            case 'custom':
                if (!empty($start_date) && !empty($end_date)) {
                    $where = "WHERE DATE(interaction_time) BETWEEN %s AND %s";
                    $params[] = $start_date;
                    $params[] = $end_date;
                } elseif (!empty($start_date)) {
                    $where = "WHERE DATE(interaction_time) >= %s";
                    $params[] = $start_date;
                } elseif (!empty($end_date)) {
                    $where = "WHERE DATE(interaction_time) <= %s";
                    $params[] = $end_date;
                }
                break;
                
            default:
                $where = "WHERE 1=1";
                break;
        }
        
        return array(
            'where' => $where,
            'params' => $params
        );
    }

    /**
     * Get dashboard metrics.
     *
     * @since    1.1.0
     * @param    array    $date_filter    SQL WHERE clause and parameters.
     * @return   array    Dashboard metrics.
     */
    private function get_dashboard_metrics($date_filter) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        // Extract where clause and params
        $where = $date_filter['where'];
        $params = $date_filter['params'];
        
        // Clone params for each query
        $total_params = $params;
        $anthropic_params = array_merge($params, array('anthropic'));
        $elevenlabs_params = array_merge($params, array('elevenlabs'));
        
        // Get total interactions
        $query = "SELECT COUNT(*) FROM $table_name $where";
        $total_interactions = $wpdb->get_var($wpdb->prepare($query, $total_params));
        
        // Get text chat interactions
        $query = "SELECT COUNT(*) FROM $table_name $where AND api_provider = %s";
        $total_anthropic = $wpdb->get_var($wpdb->prepare($query, $anthropic_params));
        
        // Get voice chat interactions
        $query = "SELECT COUNT(*) FROM $table_name $where AND api_provider = %s";
        $total_elevenlabs = $wpdb->get_var($wpdb->prepare($query, $elevenlabs_params));
        
        // Get unique users
        $query = "SELECT COUNT(DISTINCT user_id) FROM $table_name $where";
        $unique_users = $wpdb->get_var($wpdb->prepare($query, $params));
        
        // Calculate percentages
        $text_percent = $total_interactions > 0 ? round(($total_anthropic / $total_interactions) * 100) : 0;
        $voice_percent = $total_interactions > 0 ? round(($total_elevenlabs / $total_interactions) * 100) : 0;
        
        // Get previous period data for comparison
        $previous_period = $this->get_previous_period_data($date_filter);
        
        // Calculate percent change
        $percent_change = 0;
        if ($previous_period > 0) {
            $percent_change = round((($total_interactions - $previous_period) / $previous_period) * 100);
        }
        
        $trend_class = $percent_change >= 0 ? 'positive' : 'negative';
        $trend_icon = $percent_change >= 0 ? '↑' : '↓';
        
        return array(
            'total_interactions' => number_format($total_interactions),
            'total_anthropic' => number_format($total_anthropic),
            'total_elevenlabs' => number_format($total_elevenlabs),
            'unique_users' => number_format($unique_users),
            'text_percent' => $text_percent,
            'voice_percent' => $voice_percent,
            'percent_change' => $percent_change,
            'trend_class' => $trend_class,
            'trend_icon' => $trend_icon
        );
    }

    /**
     * Get previous period data for comparison.
     *
     * @since    1.1.0
     * @param    array    $current_filter    Current date filter.
     * @return   int      Count from previous period.
     */
    private function get_previous_period_data($current_filter) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        // Extract current where clause and params
        $current_where = $current_filter['where'];
        $current_params = $current_filter['params'];
        
        // Determine previous period based on current period
        if (strpos($current_where, 'DATE(interaction_time) = %s') !== false) {
            // Current is a single day (today or yesterday)
            $previous_where = "WHERE DATE(interaction_time) = %s";
            $previous_params = array(date('Y-m-d', strtotime('-1 day', strtotime($current_params[0]))));
        } elseif (strpos($current_where, 'DATE(interaction_time) >= %s') !== false && count($current_params) === 1) {
            // Current is a range from a date to now
            $days_ago = round((time() - strtotime($current_params[0])) / 86400);
            $previous_start = date('Y-m-d', strtotime('-' . ($days_ago * 2) . ' days'));
            $previous_end = date('Y-m-d', strtotime('-' . ($days_ago + 1) . ' days'));
            
            $previous_where = "WHERE DATE(interaction_time) BETWEEN %s AND %s";
            $previous_params = array($previous_start, $previous_end);
        } elseif (strpos($current_where, 'DATE(interaction_time) BETWEEN %s AND %s') !== false) {
            // Current is a custom date range
            $days_diff = round((strtotime($current_params[1]) - strtotime($current_params[0])) / 86400) + 1;
            $previous_end = date('Y-m-d', strtotime('-1 day', strtotime($current_params[0])));
            $previous_start = date('Y-m-d', strtotime('-' . $days_diff . ' days', strtotime($previous_end)));
            
            $previous_where = "WHERE DATE(interaction_time) BETWEEN %s AND %s";
            $previous_params = array($previous_start, $previous_end);
        } else {
            // Default to previous 7 days if no match
            $previous_where = "WHERE DATE(interaction_time) BETWEEN %s AND %s";
            $previous_params = array(
                date('Y-m-d', strtotime('-14 days')),
                date('Y-m-d', strtotime('-8 days'))
            );
        }
        
        // Query previous period
        $query = "SELECT COUNT(*) FROM $table_name $previous_where";
        return $wpdb->get_var($wpdb->prepare($query, $previous_params));
    }

    /**
     * Get chart data.
     *
     * @since    1.1.0
     * @param    array     $date_filter    SQL WHERE clause and parameters.
     * @param    string    $view           Chart view (daily/weekly).
     * @return   array     Chart data.
     */
    public function get_chart_data($date_filter, $view = 'daily') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        // Extract where clause and params
        $where = $date_filter['where'];
        $params = $date_filter['params'];
        
        // Determine grouping
        $group_format = $view === 'weekly' ? 'YEARWEEK(interaction_time, 1)' : 'DATE(interaction_time)';
        $label_format = $view === 'weekly' ? 'CONCAT("Week ", WEEK(interaction_time, 1))' : 'DATE_FORMAT(interaction_time, "%b %e")';
        
        // Query for text chat data
        $text_params = array_merge($params, array('anthropic'));
        $text_query = "SELECT 
            $group_format as period, 
            $label_format as label, 
            COUNT(*) as count 
            FROM $table_name 
            $where AND api_provider = %s 
            GROUP BY period 
            ORDER BY period ASC";
        
        $text_results = $wpdb->get_results($wpdb->prepare($text_query, $text_params), ARRAY_A);
        
        // Query for voice chat data
        $voice_params = array_merge($params, array('elevenlabs'));
        $voice_query = "SELECT 
            $group_format as period, 
            $label_format as label, 
            COUNT(*) as count 
            FROM $table_name 
            $where AND api_provider = %s 
            GROUP BY period 
            ORDER BY period ASC";
        
        $voice_results = $wpdb->get_results($wpdb->prepare($voice_query, $voice_params), ARRAY_A);
        
        // Process results into chart data
        $labels = array();
        $text_data = array();
        $voice_data = array();
        
        // Create a merged set of all periods
        $periods = array();
        foreach ($text_results as $row) {
            $periods[$row['period']] = $row['label'];
        }
        foreach ($voice_results as $row) {
            $periods[$row['period']] = $row['label'];
        }
        
        // Sort periods
        ksort($periods);
        
        // Create data arrays
        foreach ($periods as $period => $label) {
            $labels[] = $label;
            
            // Find text count for this period
            $text_count = 0;
            foreach ($text_results as $row) {
                if ($row['period'] == $period) {
                    $text_count = (int)$row['count'];
                    break;
                }
            }
            $text_data[] = $text_count;
            
            // Find voice count for this period
            $voice_count = 0;
            foreach ($voice_results as $row) {
                if ($row['period'] == $period) {
                    $voice_count = (int)$row['count'];
                    break;
                }
            }
            $voice_data[] = $voice_count;
        }
        
        return array(
            'labels' => $labels,
            'text_data' => $text_data,
            'voice_data' => $voice_data
        );
    }

    /**
     * AJAX handler for getting chart data.
     *
     * @since    1.1.0
     */
    public function get_chart_data_ajax() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'), 403);
        }
        
        // Get parameters
        $view = isset($_POST['view']) ? sanitize_text_field($_POST['view']) : 'daily';
        $date_range = isset($_POST['dateRange']) ? sanitize_text_field($_POST['dateRange']) : '7days';
        $start_date = isset($_POST['startDate']) ? sanitize_text_field($_POST['startDate']) : '';
        $end_date = isset($_POST['endDate']) ? sanitize_text_field($_POST['endDate']) : '';
        
        // Determine date filter
        $date_filter = $this->get_date_filter($date_range, $start_date, $end_date);
        
        // Get chart data
        $chart_data = $this->get_chart_data($date_filter, $view);
        
        wp_send_json_success($chart_data);
    }

    /**
     * AJAX handler for checking system status.
     *
     * @since    1.1.0
     */
    public function check_system_status() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'), 403);
        }
        
        // Check Anthropic API
        $anthropic_api_key = get_option('wp_dual_ai_anthropic_api_key');
        $anthropic_status = array(
            'configured' => !empty($anthropic_api_key),
            'connection' => false
        );
        
        // Check ElevenLabs API
        $elevenlabs_api_key = get_option('wp_dual_ai_elevenlabs_api_key');
        $elevenlabs_agent_id = get_option('wp_dual_ai_elevenlabs_agent_id');
        $elevenlabs_status = array(
            'configured' => !empty($elevenlabs_api_key) && !empty($elevenlabs_agent_id),
            'connection' => false
        );
        
        // Check WooCommerce
        $woocommerce_status = array(
            'active' => class_exists('WooCommerce'),
            'version' => class_exists('WooCommerce') ? WC()->version : null
        );
        
        // Test API connections if configured
        if ($anthropic_status['configured']) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-anthropic.php';
            $anthropic_api = new WP_Dual_AI_Anthropic();
            $test_result = $anthropic_api->test_connection();
            $anthropic_status['connection'] = !is_wp_error($test_result);
            if (!is_wp_error($test_result)) {
                $anthropic_status['model'] = $test_result['model'] ?? '';
            }
        }
        
        if ($elevenlabs_status['configured']) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-elevenlabs.php';
            $elevenlabs_api = new WP_Dual_AI_ElevenLabs();
            $test_result = $elevenlabs_api->test_connection();
            $elevenlabs_status['connection'] = !is_wp_error($test_result);
            if (!is_wp_error($test_result)) {
                $elevenlabs_status['voices_count'] = count($test_result['voices'] ?? array());
            }
        }
        
        wp_send_json_success(array(
            'anthropic' => $anthropic_status,
            'elevenlabs' => $elevenlabs_status,
            'woocommerce' => $woocommerce_status
        ));
    }
}
