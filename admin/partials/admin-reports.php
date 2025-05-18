<?php
/**
 * Provide a admin area view for the plugin reports
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/admin/partials
 */

// Get filter parameters
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
$provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';

// Initialize reports handler
require_once plugin_dir_path(dirname(__FILE__)) . 'reports/class-wp-dual-ai-reports.php';
$reports = new WP_Dual_AI_Reports();

// Get statistics
$stats = $reports->get_statistics($start_date, $end_date, $provider);

// Format dates for display
$display_start_date = $start_date ? date_i18n(get_option('date_format'), strtotime($start_date)) : '';
$display_end_date = $end_date ? date_i18n(get_option('date_format'), strtotime($end_date)) : '';

// Prepare chart data
$chart_labels = array();
$chart_data = array();

foreach ($stats['daily_counts'] as $date => $count) {
    $chart_labels[] = date_i18n(get_option('date_format'), strtotime($date));
    $chart_data[] = $count;
}

// Prepare provider chart data
$provider_labels = array('Anthropic (Text)', 'ElevenLabs (Voice)');
$provider_data = array($stats['anthropic_interactions'], $stats['elevenlabs_interactions']);
?>

<div class="wp-dual-ai-reports-container">
    <h2>Interaction Reports</h2>
    <p>View detailed reports of AI interactions on your site.</p>

    <!-- Filters -->
    <div class="wp-dual-ai-report-filters">
        <div class="wp-dual-ai-date-range">
            <label for="wp-dual-ai-start-date">From:</label>
            <input type="text" id="wp-dual-ai-start-date" class="wp-dual-ai-date-input" value="<?php echo esc_attr($start_date); ?>" placeholder="YYYY-MM-DD">
            
            <label for="wp-dual-ai-end-date">To:</label>
            <input type="text" id="wp-dual-ai-end-date" class="wp-dual-ai-date-input" value="<?php echo esc_attr($end_date); ?>" placeholder="YYYY-MM-DD">
        </div>
        
        <div class="wp-dual-ai-provider-filter">
            <label for="wp-dual-ai-provider-filter">Provider:</label>
            <select id="wp-dual-ai-provider-filter">
                <option value="" <?php selected($provider, ''); ?>>All Providers</option>
                <option value="anthropic" <?php selected($provider, 'anthropic'); ?>>Anthropic (Text)</option>
                <option value="elevenlabs" <?php selected($provider, 'elevenlabs'); ?>>ElevenLabs (Voice)</option>
            </select>
        </div>
        
        <button id="wp-dual-ai-apply-filter" class="button">Apply Filters</button>
        <button id="wp-dual-ai-export-csv" class="button">Export CSV</button>
    </div>

    <!-- Overview Stats -->
    <div class="wp-dual-ai-stats-container">
        <div class="wp-dual-ai-stat-card">
            <h4>Total Interactions</h4>
            <div class="value"><?php echo esc_html($stats['total_interactions']); ?></div>
        </div>
        
        <div class="wp-dual-ai-stat-card">
            <h4>Text Chat</h4>
            <div class="value"><?php echo esc_html($stats['anthropic_interactions']); ?></div>
        </div>
        
        <div class="wp-dual-ai-stat-card">
            <h4>Voice Chat</h4>
            <div class="value"><?php echo esc_html($stats['elevenlabs_interactions']); ?></div>
        </div>
        
        <div class="wp-dual-ai-stat-card">
            <h4>Unique Users</h4>
            <div class="value"><?php echo esc_html($stats['unique_users']); ?></div>
        </div>
        
        <div class="wp-dual-ai-stat-card">
            <h4>Unique Sessions</h4>
            <div class="value"><?php echo esc_html($stats['unique_sessions']); ?></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="wp-dual-ai-chart-container">
        <div class="wp-dual-ai-chart-header">
            <h3>Interactions Over Time</h3>
            <?php if ($display_start_date || $display_end_date) : ?>
                <p>
                    <?php 
                    if ($display_start_date && $display_end_date) {
                        echo esc_html("From {$display_start_date} to {$display_end_date}");
                    } elseif ($display_start_date) {
                        echo esc_html("From {$display_start_date}");
                    } elseif ($display_end_date) {
                        echo esc_html("Until {$display_end_date}");
                    }
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="wp-dual-ai-chart">
            <canvas id="wp-dual-ai-daily-chart" data-chart="<?php echo esc_attr(json_encode(array('labels' => $chart_labels, 'data' => $chart_data))); ?>"></canvas>
        </div>
    </div>
    
    <div class="wp-dual-ai-chart-container">
        <div class="wp-dual-ai-chart-header">
            <h3>Interactions by Provider</h3>
        </div>
        <div class="wp-dual-ai-chart" style="height: 250px;">
            <canvas id="wp-dual-ai-provider-chart" data-chart="<?php echo esc_attr(json_encode(array('labels' => $provider_labels, 'data' => $provider_data))); ?>"></canvas>
        </div>
    </div>

    <!-- Detailed Interactions -->
    <div class="wp-dual-ai-recent-interactions">
        <h3>Recent Interactions</h3>
        <?php
        // Get interactions based on filters
        $interactions = $reports->get_recent_interactions(20, $provider);
        
        if (!empty($interactions)) :
            foreach ($interactions as $interaction) :
                $formatted_data = $interaction['formatted_data'];
                $type_class = $interaction['api_provider'] === 'anthropic' ? 'anthropic' : 'elevenlabs';
                $type_label = $interaction['api_provider'] === 'anthropic' ? 'Text Chat' : 'Voice Chat';
                $interaction_time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($interaction['interaction_time']));
                ?>
                <div class="wp-dual-ai-interaction-card">
                    <div class="wp-dual-ai-interaction-header">
                        <span class="wp-dual-ai-interaction-type <?php echo esc_attr($type_class); ?>">
                            <?php if ($interaction['api_provider'] === 'anthropic') : ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                            <?php else : ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                    <line x1="12" y1="19" x2="12" y2="23"></line>
                                </svg>
                            <?php endif; ?>
                            <?php echo esc_html($type_label); ?>
                        </span>
                        <span class="wp-dual-ai-interaction-time"><?php echo esc_html($interaction_time); ?></span>
                    </div>
                    
                    <div class="wp-dual-ai-interaction-user">
                        <div class="wp-dual-ai-user-avatar">
                            <?php echo esc_html(substr($interaction['user_data']['display_name'], 0, 1)); ?>
                        </div>
                        <?php echo esc_html($interaction['user_data']['display_name']); ?>
                        <?php if (!empty($interaction['user_data']['email'])) : ?>
                            <span class="wp-dual-ai-user-email">(<?php echo esc_html($interaction['user_data']['email']); ?>)</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($formatted_data['type'] === 'chat') : ?>
                        <div class="wp-dual-ai-interaction-content">
                            <p><strong>User:</strong> <?php echo esc_html($formatted_data['input']); ?></p>
                            <p><strong>AI:</strong> <?php echo esc_html($formatted_data['output']); ?></p>
                        </div>
                    <?php elseif ($formatted_data['type'] === 'voice_agent') : ?>
                        <div class="wp-dual-ai-interaction-content">
                            <p>Voice agent session initiated with Agent ID: <?php echo esc_html($formatted_data['agent_id']); ?></p>
                        </div>
                    <?php elseif ($formatted_data['type'] === 'tts') : ?>
                        <div class="wp-dual-ai-interaction-content">
                            <p>Text-to-speech generated (<?php echo esc_html($formatted_data['text_length']); ?> characters)</p>
                        </div>
                    <?php else : ?>
                        <div class="wp-dual-ai-interaction-content">
                            <p><?php echo esc_html($interaction['interaction_type']); ?> interaction</p>
                            <pre><?php echo esc_html(json_encode($formatted_data['data'], JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php
            endforeach;
        else :
            ?>
            <p>No interactions found for the selected filters.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Load Chart.js from CDN if not already loaded
if (typeof Chart === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
    script.onload = initCharts;
    document.head.appendChild(script);
} else {
    jQuery(document).ready(initCharts);
}

function initCharts() {
    if (typeof Chart !== 'undefined') {
        // Daily chart
        const dailyCtx = document.getElementById('wp-dual-ai-daily-chart');
        if (dailyCtx) {
            const chartData = JSON.parse(dailyCtx.dataset.chart);
            
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Interactions',
                        data: chartData.data,
                        backgroundColor: 'rgba(79, 70, 229, 0.2)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 2,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Provider chart
        const providerCtx = document.getElementById('wp-dual-ai-provider-chart');
        if (providerCtx) {
            const chartData = JSON.parse(providerCtx.dataset.chart);
            
            new Chart(providerCtx, {
                type: 'pie',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: [
                            'rgba(79, 70, 229, 0.7)',
                            'rgba(121, 80, 242, 0.7)'
                        ],
                        borderColor: [
                            'rgba(79, 70, 229, 1)',
                            'rgba(121, 80, 242, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }
}
</script>
