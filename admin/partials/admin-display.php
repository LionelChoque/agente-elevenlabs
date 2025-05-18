<?php
/**
 * Provide a admin area view for the plugin dashboard
 *
 * @since      1.1.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/admin/partials
 */
?>

<div class="wrap wp-dual-ai-admin-container">
    <div class="wp-dual-ai-admin-header">
        <div class="wp-dual-ai-header-branding">
            <svg class="wp-dual-ai-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <div class="wp-dual-ai-title-area">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <p class="wp-dual-ai-version">Version <?php echo esc_html(WP_DUAL_AI_VERSION); ?></p>
            </div>
        </div>
        
        <div class="wp-dual-ai-header-actions">
            <a href="https://docs.example.com/dual-ai-assistant" target="_blank" class="wp-dual-ai-help-link">
                <span class="dashicons dashicons-book"></span>
                Documentation
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="wp-dual-ai-tabs-wrapper">
        <nav class="nav-tab-wrapper wp-dual-ai-tabs">
            <a href="#" class="nav-tab" data-tab="wp-dual-ai-dashboard">
                <span class="dashicons dashicons-chart-bar"></span>
                Dashboard
            </a>
            <a href="#" class="nav-tab" data-tab="wp-dual-ai-settings-tab">
                <span class="dashicons dashicons-admin-settings"></span>
                Settings
            </a>
            <a href="#" class="nav-tab" data-tab="wp-dual-ai-reports-tab">
                <span class="dashicons dashicons-analytics"></span>
                Reports
            </a>
        </nav>
        
        <!-- Date Range Filter -->
        <div class="wp-dual-ai-date-filter">
            <label for="wp-dual-ai-date-range">Time Period:</label>
            <select id="wp-dual-ai-date-range" class="wp-dual-ai-select">
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="7days" selected>Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
                <option value="custom">Custom Range</option>
            </select>
            
            <div id="wp-dual-ai-custom-date" style="display: none;">
                <input type="text" id="wp-dual-ai-start-date" class="wp-dual-ai-date-input" placeholder="Start Date">
                <span>to</span>
                <input type="text" id="wp-dual-ai-end-date" class="wp-dual-ai-date-input" placeholder="End Date">
                <button id="wp-dual-ai-apply-date" class="button">Apply</button>
            </div>
        </div>
    </div>

    <div id="wp-dual-ai-dashboard" class="wp-dual-ai-tab-content">
        <!-- Main Dashboard Grid -->
        <div class="wp-dual-ai-dashboard-grid">
            <!-- KPI Cards -->
            <div class="wp-dual-ai-card wp-dual-ai-metrics-grid">
                <?php
                // Get counts for stats
                global $wpdb;
                $table_name = $wpdb->prefix . 'dual_ai_interactions';
                
                // Filter by date if needed
                $date_filter = '';
                $date_params = array();
                
                // Default to last 7 days
                $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
                $date_filter = "WHERE DATE(interaction_time) >= %s";
                $date_params[] = $seven_days_ago;
                
                // Get statistics
                $total_interactions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name $date_filter", $date_params));
                $total_anthropic = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name $date_filter AND api_provider = 'anthropic'", $date_params));
                $total_elevenlabs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name $date_filter AND api_provider = 'elevenlabs'", $date_params));
                $unique_users = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM $table_name $date_filter", $date_params));
                $unique_sessions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT session_id) FROM $table_name $date_filter", $date_params));
                
                // Last period comparison (previous 7 days)
                $previous_period_start = date('Y-m-d', strtotime('-14 days'));
                $previous_period_end = date('Y-m-d', strtotime('-8 days'));
                $previous_period = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE DATE(interaction_time) BETWEEN %s AND %s", $previous_period_start, $previous_period_end));
                
                $percent_change = 0;
                if ($previous_period > 0) {
                    $percent_change = round((($total_interactions - $previous_period) / $previous_period) * 100);
                }
                
                $trend_class = $percent_change >= 0 ? 'positive' : 'negative';
                $trend_icon = $percent_change >= 0 ? '↑' : '↓';
                ?>
                
                <!-- Total Interactions KPI -->
                <div class="wp-dual-ai-metric-card">
                    <div class="wp-dual-ai-metric-icon total">
                        <span class="dashicons dashicons-admin-comments"></span>
                    </div>
                    <div class="wp-dual-ai-metric-content">
                        <h3>Total Interactions</h3>
                        <div class="wp-dual-ai-metric-value">
                            <?php echo esc_html(number_format($total_interactions)); ?>
                            <span class="wp-dual-ai-trend <?php echo esc_attr($trend_class); ?>">
                                <?php echo esc_html($trend_icon . ' ' . abs($percent_change) . '%'); ?>
                            </span>
                        </div>
                        <p class="wp-dual-ai-metric-period">Last 7 days</p>
                    </div>
                </div>
                
                <!-- Text Chat KPI -->
                <div class="wp-dual-ai-metric-card">
                    <div class="wp-dual-ai-metric-icon text">
                        <span class="dashicons dashicons-format-chat"></span>
                    </div>
                    <div class="wp-dual-ai-metric-content">
                        <h3>Text Chat</h3>
                        <div class="wp-dual-ai-metric-value">
                            <?php echo esc_html(number_format($total_anthropic)); ?>
                            <?php 
                            $text_percent = $total_interactions > 0 ? round(($total_anthropic / $total_interactions) * 100) : 0;
                            ?>
                            <span class="wp-dual-ai-metric-percent"><?php echo esc_html($text_percent); ?>%</span>
                        </div>
                        <div class="wp-dual-ai-progress-bar">
                            <div class="wp-dual-ai-progress-fill text" style="width: <?php echo esc_attr($text_percent); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Voice Chat KPI -->
                <div class="wp-dual-ai-metric-card">
                    <div class="wp-dual-ai-metric-icon voice">
                        <span class="dashicons dashicons-microphone"></span>
                    </div>
                    <div class="wp-dual-ai-metric-content">
                        <h3>Voice Chat</h3>
                        <div class="wp-dual-ai-metric-value">
                            <?php echo esc_html(number_format($total_elevenlabs)); ?>
                            <?php 
                            $voice_percent = $total_interactions > 0 ? round(($total_elevenlabs / $total_interactions) * 100) : 0;
                            ?>
                            <span class="wp-dual-ai-metric-percent"><?php echo esc_html($voice_percent); ?>%</span>
                        </div>
                        <div class="wp-dual-ai-progress-bar">
                            <div class="wp-dual-ai-progress-fill voice" style="width: <?php echo esc_attr($voice_percent); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Users KPI -->
                <div class="wp-dual-ai-metric-card">
                    <div class="wp-dual-ai-metric-icon users">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="wp-dual-ai-metric-content">
                        <h3>Unique Users</h3>
                        <div class="wp-dual-ai-metric-value">
                            <?php echo esc_html(number_format($unique_users)); ?>
                        </div>
                        <p class="wp-dual-ai-metric-period">Active users in period</p>
                    </div>
                </div>
            </div>
            
            <!-- Activity Chart -->
            <div class="wp-dual-ai-card wp-dual-ai-chart-card">
                <div class="wp-dual-ai-card-header">
                    <h3>Activity Trend</h3>
                    <div class="wp-dual-ai-card-actions">
                        <button class="wp-dual-ai-toggle-btn active" data-view="daily">Daily</button>
                        <button class="wp-dual-ai-toggle-btn" data-view="weekly">Weekly</button>
                    </div>
                </div>
                <div class="wp-dual-ai-chart-container">
                    <canvas id="wp-dual-ai-activity-chart" height="300"></canvas>
                </div>
            </div>
            
            <!-- API Status Card -->
            <div class="wp-dual-ai-card wp-dual-ai-status-card">
                <div class="wp-dual-ai-card-header">
                    <h3>System Status</h3>
                    <div class="wp-dual-ai-card-actions">
                        <button id="wp-dual-ai-refresh-status" class="wp-dual-ai-icon-btn">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                </div>
                
                <?php
                // Check if API keys are configured
                $anthropic_api_key = get_option('wp_dual_ai_anthropic_api_key');
                $elevenlabs_api_key = get_option('wp_dual_ai_elevenlabs_api_key');
                $elevenlabs_agent_id = get_option('wp_dual_ai_elevenlabs_agent_id');
                ?>
                
                <div class="wp-dual-ai-status-list">
                    <div class="wp-dual-ai-status-item">
                        <div class="wp-dual-ai-status-icon <?php echo !empty($anthropic_api_key) ? 'success' : 'warning'; ?>">
                            <?php if (!empty($anthropic_api_key)) : ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                            <?php else : ?>
                                <span class="dashicons dashicons-warning"></span>
                            <?php endif; ?>
                        </div>
                        <div class="wp-dual-ai-status-text">
                            <h4>Anthropic API</h4>
                            <p><?php echo !empty($anthropic_api_key) ? 'Connected and operational' : 'Not configured - Text chat unavailable'; ?></p>
                        </div>
                        <div class="wp-dual-ai-status-action">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-dual-ai-assistant-settings')); ?>" class="button">
                                <?php echo !empty($anthropic_api_key) ? 'Settings' : 'Configure'; ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="wp-dual-ai-status-item">
                        <div class="wp-dual-ai-status-icon <?php echo !empty($elevenlabs_api_key) && !empty($elevenlabs_agent_id) ? 'success' : 'warning'; ?>">
                            <?php if (!empty($elevenlabs_api_key) && !empty($elevenlabs_agent_id)) : ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                            <?php else : ?>
                                <span class="dashicons dashicons-warning"></span>
                            <?php endif; ?>
                        </div>
                        <div class="wp-dual-ai-status-text">
                            <h4>ElevenLabs Voice AI</h4>
                            <p><?php echo !empty($elevenlabs_api_key) && !empty($elevenlabs_agent_id) ? 'Connected and operational' : 'Not fully configured - Voice chat unavailable'; ?></p>
                        </div>
                        <div class="wp-dual-ai-status-action">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-dual-ai-assistant-settings')); ?>" class="button">
                                <?php echo !empty($elevenlabs_api_key) && !empty($elevenlabs_agent_id) ? 'Settings' : 'Configure'; ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="wp-dual-ai-status-item">
                        <div class="wp-dual-ai-status-icon <?php echo class_exists('WooCommerce') ? 'success' : 'neutral'; ?>">
                            <?php if (class_exists('WooCommerce')) : ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                            <?php else : ?>
                                <span class="dashicons dashicons-minus"></span>
                            <?php endif; ?>
                        </div>
                        <div class="wp-dual-ai-status-text">
                            <h4>WooCommerce Integration</h4>
                            <p><?php echo class_exists('WooCommerce') ? 'Active and integrated with product pages' : 'WooCommerce not active'; ?></p>
                        </div>
                        <div class="wp-dual-ai-status-action">
                            <?php if (class_exists('WooCommerce')) : ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings')); ?>" class="button">WooCommerce Settings</a>
                            <?php else : ?>
                                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')); ?>" class="button">Install WooCommerce</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions and Documentation -->
            <div class="wp-dual-ai-card wp-dual-ai-actions-card">
                <div class="wp-dual-ai-card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="wp-dual-ai-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-dual-ai-assistant-settings')); ?>" class="wp-dual-ai-action-btn">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span>Configure Settings</span>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-dual-ai-assistant-reports')); ?>" class="wp-dual-ai-action-btn">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <span>Export Reports</span>
                    </a>
                    
                    <a href="#" id="wp-dual-ai-test-connections" class="wp-dual-ai-action-btn">
                        <span class="dashicons dashicons-networking"></span>
                        <span>Test API Connections</span>
                    </a>
                    
                    <a href="#" id="wp-dual-ai-shortcode-helper" class="wp-dual-ai-action-btn">
                        <span class="dashicons dashicons-shortcode"></span>
                        <span>Shortcode Helper</span>
                    </a>
                </div>
            </div>
            
            <!-- Recent Interactions -->
            <div class="wp-dual-ai-card wp-dual-ai-interactions-card">
                <div class="wp-dual-ai-card-header">
                    <h3>Recent Interactions</h3>
                    <div class="wp-dual-ai-card-actions">
                        <select id="wp-dual-ai-filter-interactions" class="wp-dual-ai-select">
                            <option value="all">All Types</option>
                            <option value="anthropic">Text Chat</option>
                            <option value="elevenlabs">Voice Chat</option>
                        </select>
                    </div>
                </div>
                
                <div class="wp-dual-ai-recent-interactions-list">
                    <?php
                    // Initialize reports handler
                    require_once plugin_dir_path(dirname(__FILE__)) . 'reports/class-wp-dual-ai-reports.php';
                    $reports = new WP_Dual_AI_Reports();
                    
                    // Get recent interactions
                    $recent_interactions = $reports->get_recent_interactions(5);
                    
                    if (!empty($recent_interactions)) :
                        foreach ($recent_interactions as $interaction) :
                            $formatted_data = $interaction['formatted_data'];
                            $type_class = $interaction['api_provider'] === 'anthropic' ? 'anthropic' : 'elevenlabs';
                            $type_label = $interaction['api_provider'] === 'anthropic' ? 'Text Chat' : 'Voice Chat';
                            $type_icon = $interaction['api_provider'] === 'anthropic' ? 'format-chat' : 'microphone';
                            $interaction_time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($interaction['interaction_time']));
                            $time_ago = human_time_diff(strtotime($interaction['interaction_time']), current_time('timestamp')) . ' ago';
                            ?>
                            <div class="wp-dual-ai-interaction-item <?php echo esc_attr($type_class); ?>">
                                <div class="wp-dual-ai-interaction-icon">
                                    <span class="dashicons dashicons-<?php echo esc_attr($type_icon); ?>"></span>
                                </div>
                                
                                <div class="wp-dual-ai-interaction-content">
                                    <div class="wp-dual-ai-interaction-header">
                                        <h4><?php echo esc_html($type_label); ?></h4>
                                        <span class="wp-dual-ai-interaction-time" title="<?php echo esc_attr($interaction_time); ?>">
                                            <?php echo esc_html($time_ago); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="wp-dual-ai-interaction-user">
                                        <div class="wp-dual-ai-user-avatar">
                                            <?php echo esc_html(substr($interaction['user_data']['display_name'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo esc_html($interaction['user_data']['display_name']); ?></span>
                                    </div>
                                    
                                    <?php if ($formatted_data['type'] === 'chat') : ?>
                                        <div class="wp-dual-ai-interaction-message">
                                            <p class="wp-dual-ai-interaction-query"><?php echo esc_html(substr($formatted_data['input'], 0, 120) . (strlen($formatted_data['input']) > 120 ? '...' : '')); ?></p>
                                        </div>
                                    <?php elseif ($formatted_data['type'] === 'voice_agent') : ?>
                                        <div class="wp-dual-ai-interaction-message">
                                            <p>Voice conversation session initiated</p>
                                        </div>
                                    <?php elseif ($formatted_data['type'] === 'tts') : ?>
                                        <div class="wp-dual-ai-interaction-message">
                                            <p>Text-to-speech generated (<?php echo esc_html($formatted_data['text_length']); ?> characters)</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php
                        endforeach;
                    else :
                        ?>
                        <div class="wp-dual-ai-empty-state">
                            <span class="dashicons dashicons-format-chat"></span>
                            <p>No interactions recorded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="wp-dual-ai-card-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-dual-ai-assistant-reports')); ?>" class="wp-dual-ai-view-all-btn">
                        View All Interactions <span class="dashicons dashicons-arrow-right-alt"></span>
                    </a>
                </div>
            </d
