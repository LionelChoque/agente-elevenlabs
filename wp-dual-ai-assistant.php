<?php
/**
 * Plugin Name: Dual AI Assistant
 * Plugin URI: https://yourwebsite.com/dual-ai-assistant
 * Description: Advanced WordPress plugin that integrates Anthropic text-based AI chat and ElevenLabs voice-based AI interaction.
 * Version: 1.9.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-dual-ai-assistant
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('WP_DUAL_AI_VERSION', '1.1.0');
define('WP_DUAL_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DUAL_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
// Agrega este código en el archivo wp-dual-ai-assistant.php (archivo principal del plugin)
add_action('rest_api_init', function() {
    // Endpoint para obtener URL firmada de ElevenLabs
    register_rest_route('wp-dual-ai/v1', '/elevenlabs/signed-url', [
        'methods' => 'GET',
        'callback' => function() {
            $elevenlabs_api = new WP_Dual_AI_ElevenLabs();
            return $elevenlabs_api->get_signed_url();
        },
        'permission_callback' => '__return_true'
    ]);
});
/**
 * The code that runs during plugin activation.
 */
function activate_wp_dual_ai_assistant() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-dual-ai-activator.php';
    WP_Dual_AI_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wp_dual_ai_assistant() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-dual-ai-deactivator.php';
    WP_Dual_AI_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_dual_ai_assistant');
register_deactivation_hook(__FILE__, 'deactivate_wp_dual_ai_assistant');

/**
 * The core plugin class
 */
require plugin_dir_path(__FILE__) . 'includes/class-wp-dual-ai-assistant.php';
/**
 * Registrar endpoints de REST API directamente
 */
add_action('rest_api_init', function() {
    // Endpoint para obtener URL firmada de ElevenLabs
    register_rest_route('wp-dual-ai/v1', '/elevenlabs/signed-url', array(
        'methods' => array('GET', 'POST'),  // Permitir ambos métodos
        'callback' => function($request) {
            // Cargar la clase ElevenLabs si no está cargada
            require_once dirname(__FILE__) . '/api/class-wp-dual-ai-elevenlabs.php';
            
            // Crear instancia y llamar al método
            $elevenlabs_api = new WP_Dual_AI_ElevenLabs();
            return $elevenlabs_api->get_signed_url();
        },
        'permission_callback' => '__return_true', // Permitir acceso público
    ));
});
/**
 * Begins execution of the plugin.
 */
function run_wp_dual_ai_assistant() {
    $plugin = new WP_Dual_AI_Assistant();
    $plugin->run();
}
run_wp_dual_ai_assistant();
