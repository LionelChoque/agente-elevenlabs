<?php
/**
 * Fired during plugin activation
 *
 * @link       https://bairesanalitica.com
 * @since      1.0.0
 *
 * @package    Agente_Main
 * @subpackage Agente_Main/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Agente_Main
 * @subpackage Agente_Main/includes
 */
class WP_Dual_AI_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Implementación de activación del plugin
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            interaction_type varchar(50) NOT NULL,
            api_provider varchar(20) NOT NULL,
            interaction_data longtext NOT NULL,
            interaction_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            session_id varchar(64) NOT NULL,
            PRIMARY KEY  (id),
            KEY api_provider (api_provider),
            KEY interaction_time (interaction_time),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
