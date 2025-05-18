<?php
/**
 * Provide a admin area view for the plugin settings
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/admin/partials
 */
?>

<div class="wp-dual-ai-settings-container">
    <h2>Plugin Settings</h2>
    <p>Configure your Dual AI Assistant with the settings below.</p>

    <div class="wp-dual-ai-settings-tabs">
        <ul class="wp-dual-ai-settings-tabs-nav">
            <li><a href="#general-settings" class="active">General</a></li>
            <li><a href="#anthropic-settings">Text Chat (Anthropic)</a></li>
            <li><a href="#elevenlabs-settings">Voice Chat (ElevenLabs)</a></li>
        </ul>

        <div class="wp-dual-ai-settings-tabs-content">
            <!-- General Settings -->
            <div id="general-settings" class="wp-dual-ai-settings-tab active">
                <form method="post" action="options.php" class="wp-dual-ai-settings-form">
                    <?php
                    settings_fields('wp_dual_ai_general');
                    do_settings_sections('wp_dual_ai_general');
                    ?>
                    
                    <h3>Shortcodes</h3>
                    <p>Use these shortcodes to add AI assistants to any page or post:</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Text Chat</th>
                            <td>
                                <code>[dual_ai_text_chat]</code>
                                <p class="description">Adds a text-based chat interface powered by Anthropic.</p>
                                <p>Parameters:</p>
                                <ul>
                                    <li><code>title</code> - Chat window title (default: "Chat with AI Assistant")</li>
                                    <li><code>product_id</code> - WooCommerce product ID for context (optional)</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Voice Chat</th>
                            <td>
                                <code>[dual_ai_voice_chat]</code>
                                <p class="description">Adds a voice-based chat interface powered by ElevenLabs.</p>
                                <p>Parameters:</p>
                                <ul>
                                    <li><code>title</code> - Chat window title (default: "Voice Assistant")</li>
                                    <li><code>product_id</code> - WooCommerce product ID for context (optional)</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Chat Buttons</th>
                            <td>
                                <code>[dual_ai_chat_buttons]</code>
                                <p class="description">Adds floating buttons to open chat interfaces.</p>
                                <p>Parameters:</p>
                                <ul>
                                    <li><code>text</code> - Show text chat button (true/false, default: true)</li>
                                    <li><code>voice</code> - Show voice chat button (true/false, default: true)</li>
                                    <li><code>position</code> - Button position (bottom-right, bottom-left, default: bottom-right)</li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
<form method="post" action="options.php" enctype="multipart/form-data" class="wp-dual-ai-settings-form">
    <input type="hidden" id="wp_dual_ai_ringtone_id" name="wp_dual_ai_ringtone_id" value="<?php echo esc_attr(get_option('wp_dual_ai_ringtone_id', '')); ?>" />
<span id="ringtone-filename"><?php echo esc_html(wp_get_attachment_url(get_option('wp_dual_ai_ringtone_id', ''))); ?></span>
    <button type="button" id="subir-archivo-de-ringtone" class="button">Subir archivo de ringtone</button>
            <!-- Anthropic Settings -->
            <div id="anthropic-settings" class="wp-dual-ai-settings-tab">
                <form method="post" action="options.php" class="wp-dual-ai-settings-form">
                    <?php
                    settings_fields('wp_dual_ai_anthropic');
                    do_settings_sections('wp_dual_ai_anthropic');
                    ?>
                    
                    <div class="wp-dual-ai-test-container">
                        <h3>Test Anthropic API Connection</h3>
                        <p>Test your API key to ensure it's working correctly.</p>
                        <button id="wp-dual-ai-test-anthropic" class="button">Test Connection</button>
                        <div id="wp-dual-ai-anthropic-test-result" class="wp-dual-ai-test-result"></div>
                    </div>
                    
                    <?php submit_button(); ?>
                </form>
            </div>

            <!-- ElevenLabs Settings -->
            <div id="elevenlabs-settings" class="wp-dual-ai-settings-tab">
                <form method="post" action="options.php" class="wp-dual-ai-settings-form">
                    <?php
                    settings_fields('wp_dual_ai_elevenlabs');
                    do_settings_sections('wp_dual_ai_elevenlabs');
                    ?>
                    
                    <div class="wp-dual-ai-test-container">
                        <h3>Test ElevenLabs API Connection</h3>
                        <p>Test your API key to ensure it's working correctly. This will also fetch available voices.</p>
                        <button id="wp-dual-ai-test-elevenlabs" class="button">Test Connection</button>
                        <div id="wp-dual-ai-elevenlabs-test-result" class="wp-dual-ai-test-result"></div>
                    </div>
                    
                    <h3>Implementation Guide</h3>
                    <div class="wp-dual-ai-implementation-guide">
                        <ol>
                            <li>
                                <strong>Get ElevenLabs API Key</strong>
                                <p>Sign up for an ElevenLabs account and get your API key from <a href="https://elevenlabs.io/app/api-key" target="_blank">ElevenLabs Dashboard</a>.</p>
                            </li>
                            <li>
                                <strong>Create Voice Agent</strong>
                                <p>Create a Conversational AI agent in the <a href="https://elevenlabs.io/app/conversational-ai" target="_blank">ElevenLabs Conversation AI</a> section.</p>
                            </li>
                            <li>
                                <strong>Copy Agent ID</strong>
                                <p>After creating your agent, copy the Agent ID and paste it in the field above.</p>
                            </li>
                            <li>
                                <strong>Select Voice</strong>
                                <p>Choose a voice for text-to-speech functionality. This will be used for the ringtone and any pre-recorded messages.</p>
                            </li>
                            <li>
                                <strong>Place Shortcodes</strong>
                                <p>Use the shortcodes from the General tab to add AI assistants to your pages.</p>
                            </li>
                        </ol>
                    </div>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Settings tabs
    $('.wp-dual-ai-settings-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.wp-dual-ai-settings-tabs-nav a').removeClass('active');
        $(this).addClass('active');
        
        // Show target content
        $('.wp-dual-ai-settings-tab').removeClass('active');
        $($(this).attr('href')).addClass('active');
    });
    
    // Password toggle
    $('.wp-dual-ai-api-key-field').each(function() {
        const $field = $(this);
        const $input = $field.find('input');
        const $toggle = $('<button type="button" class="wp-dual-ai-api-key-toggle"><span class="dashicons dashicons-visibility"></span></button>');
        
        $field.append($toggle);
        
        $toggle.on('click', function() {
            const type = $input.attr('type');
            
            if (type === 'password') {
                $input.attr('type', 'text');
                $toggle.html('<span class="dashicons dashicons-hidden"></span>');
            } else {
                $input.attr('type', 'password');
                $toggle.html('<span class="dashicons dashicons-visibility"></span>');
            }
        });
    });
});
</script>
