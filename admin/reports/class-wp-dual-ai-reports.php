<?php
/**
 * The reports functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/admin/reports
 */

class WP_Dual_AI_Reports {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Nothing to initialize yet
    }

    /**
     * Generate a CSV export of interactions.
     *
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @param string $api_provider Filter by API provider (anthropic, elevenlabs, or empty for all)
     * @return string|false CSV data as string or false on failure
     */
    public function generate_csv($start_date = '', $end_date = '', $api_provider = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        // Build query
        $query = "SELECT * FROM {$table_name} WHERE 1=1";
        $params = array();
        
        // Add date filters if provided
        if (!empty($start_date)) {
            $query .= " AND DATE(interaction_time) >= %s";
            $params[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $query .= " AND DATE(interaction_time) <= %s";
            $params[] = $end_date;
        }
        
        // Add API provider filter if provided
        if (!empty($api_provider)) {
            $query .= " AND api_provider = %s";
            $params[] = $api_provider;
        }
        
        // Order by interaction time
        $query .= " ORDER BY interaction_time DESC";
        
        // Prepare and execute query
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        if (empty($results)) {
            return false;
        }
        
        // Build CSV data
        $csv_data = array();
        
        // Headers
        $headers = array(
            'ID',
            'Type',
            'Provider',
            'Data',
            'Time',
            'User ID',
            'Session ID'
        );
        
        $csv_data[] = implode(',', array_map(array($this, 'escape_csv'), $headers));
        
        // Rows
        foreach ($results as $row) {
            $csv_row = array(
                $row['id'],
                $row['interaction_type'],
                $row['api_provider'],
                // Simplify the interaction data for CSV
                $this->simplify_interaction_data($row['interaction_data']),
                $row['interaction_time'],
                $row['user_id'],
                $row['session_id']
            );
            
            $csv_data[] = implode(',', array_map(array($this, 'escape_csv'), $csv_row));
        }
        
        return implode("\n", $csv_data);
    }

    /**
     * Get interactions statistics.
     *
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @param string $api_provider Filter by API provider (anthropic, elevenlabs, or empty for all)
     * @return array Statistics data
     */
    public function get_statistics($start_date = '', $end_date = '', $api_provider = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        // Initialize statistics array
        $stats = array(
            'total_interactions' => 0,
            'unique_users' => 0,
            'unique_sessions' => 0,
            'anthropic_interactions' => 0,
            'elevenlabs_interactions' => 0,
            'interaction_types' => array(),
            'daily_counts' => array()
        );
        
        // Base query components
        $where_clause = "WHERE 1=1";
        $params = array();
        
        // Add date filters if provided
        if (!empty($start_date)) {
            $where_clause .= " AND DATE(interaction_time) >= %s";
            $params[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $where_clause .= " AND DATE(interaction_time) <= %s";
            $params[] = $end_date;
        }
        
        // Add API provider filter if provided
        if (!empty($api_provider)) {
            $where_clause .= " AND api_provider = %s";
            $params[] = $api_provider;
        }
        
        // Total interactions
        $query = "SELECT COUNT(*) FROM {$table_name} {$where_clause}";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $stats['total_interactions'] = $wpdb->get_var($query);
        
        // Unique users
        $query = "SELECT COUNT(DISTINCT user_id) FROM {$table_name} {$where_clause}";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $stats['unique_users'] = $wpdb->get_var($query);
        
        // Unique sessions
        $query = "SELECT COUNT(DISTINCT session_id) FROM {$table_name} {$where_clause}";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $stats['unique_sessions'] = $wpdb->get_var($query);
        
        // Interactions by provider
        $query = "SELECT api_provider, COUNT(*) as count FROM {$table_name} {$where_clause} GROUP BY api_provider";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $provider_counts = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($provider_counts as $count) {
            if ($count['api_provider'] === 'anthropic') {
                $stats['anthropic_interactions'] = $count['count'];
            } elseif ($count['api_provider'] === 'elevenlabs') {
                $stats['elevenlabs_interactions'] = $count['count'];
            }
        }
        
        // Interaction types
        $query = "SELECT interaction_type, COUNT(*) as count FROM {$table_name} {$where_clause} GROUP BY interaction_type";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $type_counts = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($type_counts as $count) {
            $stats['interaction_types'][$count['interaction_type']] = $count['count'];
        }
        
        // Daily interaction counts
        $query = "SELECT DATE(interaction_time) as date, COUNT(*) as count FROM {$table_name} {$where_clause} GROUP BY DATE(interaction_time) ORDER BY date ASC";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $daily_counts = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($daily_counts as $count) {
            $stats['daily_counts'][$count['date']] = $count['count'];
        }
        
        return $stats;
    }

    /**
     * Simplify interaction data for CSV.
     *
     * @param string $json_data JSON data
     * @return string Simplified data
     */
    private function simplify_interaction_data($json_data) {
        $data = json_decode($json_data, true);
        
        if (!is_array($data)) {
            return 'Invalid data';
        }
        
        $simplified = '';
        
        // Handle different interaction types differently
        if (isset($data['input']) && isset($data['output'])) {
            // Chat interaction
            $simplified = 'Q: ' . substr($data['input'], 0, 50) . 
                          (strlen($data['input']) > 50 ? '...' : '') . 
                          ' A: ' . substr($data['output'], 0, 50) . 
                          (strlen($data['output']) > 50 ? '...' : '');
        } elseif (isset($data['text_length'])) {
            // TTS interaction
            $simplified = 'Text length: ' . $data['text_length'] . ' chars';
        } elseif (isset($data['agent_id'])) {
            // Voice agent interaction
            $simplified = 'Agent ID: ' . $data['agent_id'];
        } else {
            // Other interactions - extract keys and values
            foreach ($data as $key => $value) {
                if (is_scalar($value)) {
                    $simplified .= $key . ': ' . $value . '; ';
                }
            }
        }
        
        return $this->escape_csv($simplified);
    }

    /**
     * Escape a string for CSV output.
     *
     * @param string $str String to escape
     * @return string Escaped string
     */
    private function escape_csv($str) {
        $str = str_replace('"', '""', $str);
        
        // If the string contains a comma, newline, or double quote, enclose it in double quotes
        if (strpos($str, ',') !== false || strpos($str, '"') !== false || 
            strpos($str, "\n") !== false || strpos($str, "\r") !== false) {
            $str = '"' . $str . '"';
        }
        
        return $str;
    }

    /**
     * Get recent interactions for display in the admin.
     *
     * @param int $limit Number of interactions to return
     * @param string $api_provider Filter by API provider
     * @return array Recent interactions
     */
    public function get_recent_interactions($limit = 10, $api_provider = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dual_ai_interactions';
        
        // Build query
        $query = "SELECT * FROM {$table_name} WHERE 1=1";
        $params = array();
        
        // Add API provider filter if provided
        if (!empty($api_provider)) {
            $query .= " AND api_provider = %s";
            $params[] = $api_provider;
        }
        
        // Order by interaction time and limit
        $query .= " ORDER BY interaction_time DESC LIMIT %d";
        $params[] = $limit;
        
        // Prepare and execute query
        $query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Process interaction data
        foreach ($results as &$row) {
            $data = json_decode($row['interaction_data'], true);
            
            // Format interaction data for display
            if (isset($data['input']) && isset($data['output'])) {
                $row['formatted_data'] = array(
                    'type' => 'chat',
                    'input' => $data['input'],
                    'output' => $data['output']
                );
            } elseif (isset($data['text_length'])) {
                $row['formatted_data'] = array(
                    'type' => 'tts',
                    'text_length' => $data['text_length']
                );
            } elseif (isset($data['agent_id'])) {
                $row['formatted_data'] = array(
                    'type' => 'voice_agent',
                    'agent_id' => $data['agent_id']
                );
            } else {
                $row['formatted_data'] = array(
                    'type' => 'other',
                    'data' => $data
                );
            }
            
            // Get user data if available
            if (!empty($row['user_id'])) {
                $user = get_user_by('id', $row['user_id']);
                if ($user) {
                    $row['user_data'] = array(
                        'display_name' => $user->display_name,
                        'email' => $user->user_email
                    );
                } else {
                    $row['user_data'] = array(
                        'display_name' => 'Guest',
                        'email' => ''
                    );
                }
            } else {
                $row['user_data'] = array(
                    'display_name' => 'Guest',
                    'email' => ''
                );
            }
        }
        
        return $results;
    }
}
