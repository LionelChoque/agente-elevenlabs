/**
 * Text Chat Interface for Anthropic AI
 */
(function($) {
    'use strict';

    /**
     * Text Chat Handler
     */
    class WPDualAITextChat {
        constructor() {
            this.chatContainer = $('#wp-dual-ai-text-chat');
            this.messagesContainer = $('#wp-dual-ai-messages');
            this.inputField = $('#wp-dual-ai-input');
            this.sendButton = $('#wp-dual-ai-send');
            this.loadingIndicator = $('#wp-dual-ai-loading');
            this.conversationHistory = [];
            this.productId = this.chatContainer.data('product-id') || 0;
            
            this.init();
        }

        /**
         * Initialize the chat interface
         */
        init() {
            // Display welcome message
            this.displayBotMessage(wpDualAI.welcomeMessage || 'Hello! How can I help you today?');
            
            // Setup event listeners
            this.sendButton.on('click', () => this.sendMessage());
            this.inputField.on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Open chat button
            $('#wp-dual-ai-open-text-chat').on('click', () => {
                $('#wp-dual-ai-text-chat-container').toggleClass('wp-dual-ai-active');
            });
            
            // Close chat button
            $('#wp-dual-ai-close-text-chat').on('click', () => {
                $('#wp-dual-ai-text-chat-container').removeClass('wp-dual-ai-active');
            });
        }

        /**
         * Send a message to the Anthropic API
         */
        sendMessage() {
            const userMessage = this.inputField.val().trim();
            
            if (!userMessage) {
                return;
            }
            
            // Display user message
            this.displayUserMessage(userMessage);
            
            // Clear input field
            this.inputField.val('');
            
            // Show loading indicator
            this.loadingIndicator.show();
            this.disableInput();
            
            // Add to conversation history
            this.conversationHistory.push({
                role: 'user',
                content: userMessage
            });
            
            // Prepare request data
            const requestData = {
                messages: this.conversationHistory,
                product_id: this.productId
            };
            
            // Send request to our backend proxy for Anthropic API
            $.ajax({
                url: wpDualAI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_dual_ai_text_chat',
                    nonce: wpDualAI.nonce,
                    request_data: requestData
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        const botMessage = response.data.content[0].text;
                        
                        // Display bot message
                        this.displayBotMessage(botMessage);
                        
                        // Add to conversation history
                        this.conversationHistory.push({
                            role: 'assistant',
                            content: botMessage
                        });
                        
                        // Limit history length to prevent token limits
                        if (this.conversationHistory.length > 10) {
                            this.conversationHistory = this.conversationHistory.slice(-10);
                        }
                    } else {
                        this.displayErrorMessage(response.data.message || 'An error occurred');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Error:', status, error);
                    this.displayErrorMessage('Failed to connect to the AI assistant. Please try again later.');
                },
                complete: () => {
                    // Hide loading indicator
                    this.loadingIndicator.hide();
                    this.enableInput();
                    
                    // Scroll to bottom
                    this.scrollToBottom();
                }
            });
        }

        /**
         * Display a user message in the chat
         */
        displayUserMessage(message) {
            const messageHtml = `
                <div class="wp-dual-ai-message wp-dual-ai-user-message">
                    <div class="wp-dual-ai-message-content">${this.escapeHtml(message)}</div>
                </div>
            `;
            
            this.messagesContainer.append(messageHtml);
            this.scrollToBottom();
        }

        /**
         * Display a bot message in the chat
         */
        displayBotMessage(message) {
            const formattedMessage = this.formatMessage(message);
            
            const messageHtml = `
                <div class="wp-dual-ai-message wp-dual-ai-bot-message">
                    <div class="wp-dual-ai-message-content">${formattedMessage}</div>
                </div>
            `;
            
            this.messagesContainer.append(messageHtml);
            this.scrollToBottom();
        }

        /**
         * Display an error message in the chat
         */
        displayErrorMessage(message) {
            const messageHtml = `
                <div class="wp-dual-ai-message wp-dual-ai-error-message">
                    <div class="wp-dual-ai-message-content">${this.escapeHtml(message)}</div>
                </div>
            `;
            
            this.messagesContainer.append(messageHtml);
            this.scrollToBottom();
        }

        /**
         * Format the message with Markdown and linkification
         */
        formatMessage(message) {
            // Simple markdown-like formatting
            let formattedMessage = this.escapeHtml(message);
            
            // Convert URLs to links
            formattedMessage = formattedMessage.replace(
                /(https?:\/\/[^\s]+)/g, 
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
            );
            
            // Convert **bold** to <strong>
            formattedMessage = formattedMessage.replace(
                /\*\*(.*?)\*\*/g, 
                '<strong>$1</strong>'
            );
            
            // Convert *italic* to <em>
            formattedMessage = formattedMessage.replace(
                /\*(.*?)\*/g, 
                '<em>$1</em>'
            );
            
            // Convert newlines to <br>
            formattedMessage = formattedMessage.replace(/\n/g, '<br>');
            
            return formattedMessage;
        }

        /**
         * Escape HTML special characters
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Scroll the chat container to the bottom
         */
        scrollToBottom() {
            this.messagesContainer.scrollTop(this.messagesContainer[0].scrollHeight);
        }

        /**
         * Disable input during processing
         */
        disableInput() {
            this.inputField.prop('disabled', true);
            this.sendButton.prop('disabled', true);
        }

        /**
         * Enable input after processing
         */
        enableInput() {
            this.inputField.prop('disabled', false);
            this.sendButton.prop('disabled', false);
            this.inputField.focus();
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#wp-dual-ai-text-chat').length) {
            new WPDualAITextChat();
        }
    });

})(jQuery);
