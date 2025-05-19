<?php
/**
 * Provide a public-facing view for the text chat component
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/public/partials
 */
?>

<div id="wp-dual-ai-text-chat-container" class="wp-dual-ai-chat-container">
    <div class="wp-dual-ai-chat-header">
        <h3><?php echo esc_html($atts['title']); ?></h3>
        <button id="wp-dual-ai-close-text-chat" class="wp-dual-ai-close-button" aria-label="Close chat">×</button>
    </div>
    
    <div id="wp-dual-ai-text-chat" class="wp-dual-ai-chat" data-product-id="<?php echo esc_attr($atts['product_id']); ?>">
        <div id="wp-dual-ai-messages" class="wp-dual-ai-messages"></div>
        
        <div class="wp-dual-ai-input-container">
            <div id="wp-dual-ai-loading" class="wp-dual-ai-loading" style="display: none;">
                <div class="wp-dual-ai-loading-dots">
                    <div class="wp-dual-ai-loading-dot"></div>
                    <div class="wp-dual-ai-loading-dot"></div>
                    <div class="wp-dual-ai-loading-dot"></div>
                </div>
            </div>
            
            <textarea id="wp-dual-ai-input" class="wp-dual-ai-input" placeholder="Type your message here..." rows="1"></textarea>
            
            <button id="wp-dual-ai-send" class="wp-dual-ai-send-button">
                <img src="https://bairesanalitica.com/wp-content/uploads/2025/05/logo-BA.png" alt="BA" width="24" height="24" class="ba-logo-small">
            </button>
        </div>
        
        <div class="wp-dual-ai-footer">
            <p>Powered by <a href="https://www.anthropic.com/" target="_blank" rel="noopener noreferrer">Anthropic</a></p>
        </div>
    </div>
</div>
