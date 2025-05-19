<?php
/**
 * Provide a public-facing view for the voice chat component
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/public/partials
 */
?>

<div id="wp-dual-ai-voice-chat-container" class="wp-dual-ai-chat-container">
    <div class="wp-dual-ai-chat-header">
        <h3><?php echo esc_html($atts['title']); ?></h3>
        <button id="wp-dual-ai-close-voice-chat" class="wp-dual-ai-close-button" aria-label="Close voice chat">×</button>
    </div>
    
    <div id="wp-dual-ai-voice-chat" class="wp-dual-ai-chat wp-dual-ai-voice wp-dual-ai-idle" data-product-id="<?php echo esc_attr($atts['product_id']); ?>">
        <div class="wp-dual-ai-voice-call-status">
            <div class="wp-dual-ai-voice-avatar">
                <div class="wp-dual-ai-voice-avatar-inner">
                    <img src="https://bairesanalitica.com/wp-content/uploads/2025/05/logo-BA.png" alt="BA" width="48" height="48" class="ba-logo">
                </div>
                <div class="wp-dual-ai-voice-animation">
                    <div class="wp-dual-ai-voice-wave"></div>
                    <div class="wp-dual-ai-voice-wave"></div>
                    <div class="wp-dual-ai-voice-wave"></div>
                </div>
            </div>
            
            <div class="wp-dual-ai-status-text">
                <p id="wp-dual-ai-status">Ready to start</p>
            </div>
            
            <div id="wp-dual-ai-volume-indicator" class="wp-dual-ai-volume-indicator">
                <div id="wp-dual-ai-volume-level" class="wp-dual-ai-volume-level"></div>
            </div>
        </div>
        
        <div class="wp-dual-ai-call-controls">
            <button id="wp-dual-ai-start-call" class="wp-dual-ai-call-button wp-dual-ai-start-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <span>Start Call</span>
            </button>
            
            <button id="wp-dual-ai-end-call" class="wp-dual-ai-call-button wp-dual-ai-end-button" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.42 19.42 0 0 1-3.33-2.67m-2.67-3.34a19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"></path>
                    <line x1="23" y1="1" x2="1" y2="23"></line>
                </svg>
                <span>End Call</span>
            </button>
        </div>
        
        <div class="wp-dual-ai-transcript-container">
            <h4>Conversation Transcript</h4>
            <div id="wp-dual-ai-transcript" class="wp-dual-ai-transcript"></div>
        </div>
        
        <!-- Custom error container -->
        <div id="wp-dual-ai-error-container" class="wp-dual-ai-error-container" style="display: none;">
            <div class="wp-dual-ai-error-message"></div>
        </div>
        
        <div class="wp-dual-ai-footer">
            <p>Powered by <a href="https://elevenlabs.io/" target="_blank" rel="noopener noreferrer">ElevenLabs</a></p>
        </div>
    </div>
</div>
