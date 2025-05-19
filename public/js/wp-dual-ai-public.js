/**
 * Public-facing JavaScript for Dual AI Assistant
 * Enhanced with icon cycling functionality
 */
(function($) {
    'use strict';

    /**
     * Enhanced Floating Action Button functionality with icon cycling
     */
    class WPDualAIFloatingButton {
        constructor() {
            this.toggleButton = $('#wp-dual-ai-toggle-button');
            this.chatButtonsContainer = $('.wp-dual-ai-chat-buttons');
            this.textChatButton = $('#wp-dual-ai-open-text-chat');
            this.voiceChatButton = $('#wp-dual-ai-open-voice-chat');
            this.textChatContainer = $('#wp-dual-ai-text-chat-container');
            this.voiceChatContainer = $('#wp-dual-ai-voice-chat-container');
            this.closeChatButtons = $('.wp-dual-ai-close-button');
            
            // Icon elements
            this.treeIcon = $('.wp-dual-ai-icon-tree');
            this.waterIcon = $('.wp-dual-ai-icon-water');
            this.industryIcon = $('.wp-dual-ai-icon-industry');
            this.thumbsUpIcon = $('.wp-dual-ai-icon-thumbsup');
            
            // State tracking
            this.isOpen = false;
            this.currentIconIndex = 0;
            this.icons = [
                { element: this.treeIcon, class: 'tree-active' },
                { element: this.waterIcon, class: 'water-active' },
                { element: this.industryIcon, class: 'industry-active' }
            ];
            
            // Icon cycling interval
            this.cycleInterval = null;
            
            this.init();
        }
        
        /**
         * Initialize the floating button
         */
        init() {
            // Toggle button click event
            this.toggleButton.on('click', () => {
                this.toggleOptions();
            });
            
            // Text chat button click event
            this.textChatButton.on('click', () => {
                this.openTextChat();
            });
            
            // Voice chat button click event
            this.voiceChatButton.on('click', () => {
                this.openVoiceChat();
            });
            
            // Close buttons click events
            this.closeChatButtons.on('click', () => {
                this.closeAllChats();
            });
            
            // Pulse animation on load to draw attention
            setTimeout(() => {
                this.toggleButton.addClass('pulse');
                
                // Remove pulse after 3 pulses
                setTimeout(() => {
                    this.toggleButton.removeClass('pulse');
                    
                    // Start icon cycling after pulse animation
                    this.startIconCycle();
                }, 4500);
            }, 1000);
            
            // Close options when clicking outside
            $(document).on('click', (e) => {
                if (this.isOpen && 
                    !this.chatButtonsContainer.has(e.target).length && 
                    !this.toggleButton.is(e.target) && 
                    !this.toggleButton.has(e.target).length) {
                    this.closeOptions();
                }
            });
        }
        
        /**
         * Start cycling through icons
         */
        startIconCycle() {
            // If already cycling, don't start another interval
            if (this.cycleInterval) return;
            
            // Set initial icon
            this.setActiveIcon(0);
            
            // Start cycling every 3 seconds
            this.cycleInterval = setInterval(() => {
                if (!this.isOpen) {
                    this.currentIconIndex = (this.currentIconIndex + 1) % this.icons.length;
                    this.setActiveIcon(this.currentIconIndex);
                }
            }, 3000);
        }
        
        /**
         * Stop icon cycling
         */
        stopIconCycle() {
            if (this.cycleInterval) {
                clearInterval(this.cycleInterval);
                this.cycleInterval = null;
            }
        }
        
        /**
         * Set active icon by index
         */
        setActiveIcon(index) {
            // Remove active class from all icons
            $('.wp-dual-ai-toggle-icon').removeClass('active');
            
            // Remove button state classes
            this.toggleButton.removeClass('tree-active water-active industry-active open');
            
            if (this.isOpen) {
                // Show thumbs up icon when open
                this.thumbsUpIcon.addClass('active');
                this.toggleButton.addClass('open');
            } else {
                // Show the icon at the current index
                const icon = this.icons[index];
                icon.element.addClass('active');
                this.toggleButton.addClass(icon.class);
            }
        }
        
        /**
         * Toggle options visibility
         */
        toggleOptions() {
            if (this.isOpen) {
                this.closeOptions();
            } else {
                this.openOptions();
            }
        }
        
        /**
         * Open options
         */
        openOptions() {
            this.isOpen = true;
            this.chatButtonsContainer.addClass('active');
            this.setActiveIcon(this.currentIconIndex); // This will show thumbs up
        }
        
        /**
         * Close options
         */
        closeOptions() {
            this.isOpen = false;
            this.chatButtonsContainer.removeClass('active');
            this.setActiveIcon(this.currentIconIndex); // This will restore current cycling icon
        }
        
        /**
         * Open text chat
         */
        openTextChat() {
            this.closeOptions();
            this.stopIconCycle(); // Stop cycling when chat is open
            this.textChatContainer.addClass('wp-dual-ai-active');
        }
        
        /**
         * Open voice chat
         */
        openVoiceChat() {
            this.closeOptions();
            this.stopIconCycle(); // Stop cycling when chat is open
            this.voiceChatContainer.addClass('wp-dual-ai-active');
        }
        
        /**
         * Close all chat interfaces
         */
        closeAllChats() {
            this.textChatContainer.removeClass('wp-dual-ai-active');
            this.voiceChatContainer.removeClass('wp-dual-ai-active');
            this.startIconCycle(); // Restart icon cycling when chat is closed
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.wp-dual-ai-chat-buttons').length) {
            new WPDualAIFloatingButton();
        }
    });

})(jQuery);
// Función para añadir el globo de diálogo de bienvenida
// Replace this function in public/js/wp-dual-ai-public.js
function addWelcomeBubble() {
    // Verify if element already exists
    if (document.querySelector('.wp-dual-ai-welcome-bubble')) return;
    
    // Create bubble element
    const bubble = document.createElement('div');
    bubble.className = 'wp-dual-ai-welcome-bubble';
    bubble.innerHTML = `
        <button class="wp-dual-ai-welcome-bubble-close">×</button>
        <p class="wp-dual-ai-welcome-bubble-text">Hola, puedo ayudarte? Soy tu asistente.</p>
    `;
    
    // Find the chat buttons container - using more robust selector
    const chatButtons = document.querySelector('.wp-dual-ai-chat-buttons, [aria-label="Abrir chat"]');
    if (chatButtons) {
        chatButtons.appendChild(bubble);
        
        // Add event to close bubble
        const closeButton = bubble.querySelector('.wp-dual-ai-welcome-bubble-close');
        closeButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            bubble.remove();
            
            // Save to localStorage to prevent reappearing in this session
            localStorage.setItem('wp_dual_ai_bubble_closed', 'true');
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!bubble.contains(e.target) && !chatButtons.contains(e.target)) {
                bubble.remove();
            }
        });
    }
}

// Update event listener to ensure it runs after everything is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only show if not closed before
    if (!localStorage.getItem('wp_dual_ai_bubble_closed')) {
        // Force the bubble to show on next page load
        localStorage.removeItem('wp_dual_ai_bubble_closed');
        // Small delay for appearance after initial load
        setTimeout(addWelcomeBubble, 1500);
    }
});

// Add this to force show the bubble when testing
window.forceShowWelcomeBubble = function() {
    localStorage.removeItem('wp_dual_ai_bubble_closed');
    addWelcomeBubble();
};
