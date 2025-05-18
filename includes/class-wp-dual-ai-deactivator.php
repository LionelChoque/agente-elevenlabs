<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://bairesanalitica.com
 * @since      1.0.0
 *
 * @package    Agente_Main
 * @subpackage Agente_Main/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Agente_Main
 * @subpackage Agente_Main/includes
 */
class WP_Dual_AI_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Implementación de desactivación del plugin
        
        // Eliminar programaciones cron si existen
        wp_clear_scheduled_hook('wp_dual_ai_cleanup_temp_files');
    }
}
