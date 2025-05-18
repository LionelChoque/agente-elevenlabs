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
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>
        
        <div class="wp-dual-ai-footer">
            <p>Powered by <a href="https://www.anthropic.com/" target="_blank" rel="noopener noreferrer">Anthropic</a></p>
        </div>
    </div>
</div>
