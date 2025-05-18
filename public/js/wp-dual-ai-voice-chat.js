/**
 * WordPress Voice Chat using ElevenLabs Official SDK
 * 
 * This script integrates the ElevenLabs Voice Chat library with WordPress
 * to create a natural, conversational voice interface.
 *
 * @since      2.3.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/public/js
 */
(function($) {
    'use strict';

    // Debug and logging configuration
    const DEBUG = true;
    const log = (...args) => DEBUG && console.log('[Voice Chat]', ...args);
    const error = (...args) => console.error('[Voice Chat Error]', ...args);

    /**
     * ElevenLabs Voice Chat Integration for WordPress
     */
    class WPElevenLabsVoiceChat {
        constructor() {
            // UI Elements
            this.voiceChatContainer = $('#wp-dual-ai-voice-chat');
            this.startButton = $('#wp-dual-ai-start-call');
            this.endButton = $('#wp-dual-ai-end-call');
            this.statusIndicator = $('#wp-dual-ai-status');
            this.transcriptContainer = $('#wp-dual-ai-transcript');
            this.volumeIndicator = $('#wp-dual-ai-volume-level');
            this.errorContainer = $('#wp-dual-ai-error-container');
            this.errorMessage = $('.wp-dual-ai-error-message');
            
            // State variables
            this.productId = this.voiceChatContainer.data('product-id') || 0;
            this.ringtoneAudio = null;
            this.isRingtoneLoaded = false;
            this.isCallActive = false;
            this.isBotSpeaking = false;
            
            // ElevenLabs connection
            this.elevenLabs = null;
            this.elevenLabsAgent = null;
            
            // Initialize
            this.setupEventListeners();
            this.updateUIState('idle');
            this.displaySystemMessage('Voice assistant ready. Click "Start Call" to begin.');
            this.preloadRingtone();
        }
        
        /**
         * Preload ringtone with error handling
         */
        preloadRingtone() {
            try {
                // Primary ringtone
                this.ringtoneAudio = new Audio(wpDualAI.ringtoneUrl);
                
                // Configure as loop
                this.ringtoneAudio.loop = true;
                
                // Success event
                this.ringtoneAudio.addEventListener('canplaythrough', () => {
                    this.isRingtoneLoaded = true;
                    log('Ringtone preloaded successfully');
                });
                
                // Error handling
                this.ringtoneAudio.addEventListener('error', (e) => {
                    error('Error loading ringtone:', e);
                    
                    // Try fallback if available
                    if (wpDualAI.ringtoneUrlFallback) {
                        try {
                            this.ringtoneAudio = new Audio(wpDualAI.ringtoneUrlFallback);
                            this.ringtoneAudio.loop = true;
                            this.ringtoneAudio.load();
                        } catch (fallbackError) {
                            error('Error creating fallback ringtone:', fallbackError);
                        }
                    } else {
                        // Create silent ringtone as last resort
                        try {
                            this.ringtoneAudio = new Audio('data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA/+M4wAAAAAAAAAAAAEluZm8AAAAPAAAAAwAABPQAjIyMjIyMjIyMqqqqqqqqqqqqqtXV1dXV1dXV1dX//////////////////////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP/7UGQAAAAaZUAAAAAAABllQAAAEpGpIf4eEASgAAQ/g8ACIAAB//MsxABMOZpgGNmZrYRnwAA2OAAgIyAjIgCRBxA+UlwoAiUAeQdjYKCYZlD3QHQHmZk0nGxUSj03woGwjPLjGFy4cK1U//MsxA8Ti0rcGjBTr4PCgoNiwmNA4SHhQaFAIYdh4nDBocFAtf/ygVHCACEGg4KDN1/6DE4iRHgzqYjIhE0NDJESEBkS//MsxBsTitK8BjGLh4mWD9v/6QqHgPFyMdE4ySIxqThYqMDYAaZ//QsWIB4kCQ4PEhIoIGhsoMERgvEB8oYGBgv+//MsxCcXw5qwBjmfbGnQGBRQyNioo+MEyMmEBIXFgkDLCY6SIRMSDQ+XLlTZLCQOCguUMC4+OECIB8kVFBwrGCk0Jv/7UGRAAAAGWVAAAAAAAAZZVAAAAE/////JVlhQcGxUeKDxMfLFxoiKjYwKEBwQDAoICg0PEBcTBxASAAAAA==');
                            this.ringtoneAudio.loop = true;
                        } catch (silentError) {
                            error('Error creating silent ringtone:', silentError);
                        }
                    }
                });
                
                // Start preload
                this.ringtoneAudio.load();
            } catch (e) {
                error('Error initializing ringtone:', e);
            }
        }
        
        /**
         * Set up event listeners for UI
         */
        setupEventListeners() {
            // Main buttons
            this.startButton.on('click', () => {
                try {
                    this.startConversation();
                } catch (e) {
                    error('Error in startButton handler:', e);
                    this.displayError('Error starting conversation: ' + e.message);
                }
            });
            
            this.endButton.on('click', () => {
                try {
                    this.endConversation();
                } catch (e) {
                    error('Error in endButton handler:', e);
                    // Force cleanup if error
                    this.cleanupResources();
                    this.isCallActive = false;
                    this.updateUIState('idle');
                }
            });
            
            // UI
            $('#wp-dual-ai-open-voice-chat').on('click', () => {
                try {
                    $('#wp-dual-ai-voice-chat-container').toggleClass('wp-dual-ai-active');
                    this.hideError();
                    this.unlockAudio();
                } catch (e) {
                    error('Error in open-voice-chat handler:', e);
                }
            });
            
            $('#wp-dual-ai-close-voice-chat').on('click', () => {
                try {
                    $('#wp-dual-ai-voice-chat-container').removeClass('wp-dual-ai-active');
                    if (this.isCallActive) {
                        this.endConversation();
                    }
                } catch (e) {
                    error('Error in close-voice-chat handler:', e);
                }
            });
            
            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                try {
                    if (this.isCallActive) {
                        this.cleanupResources();
                    }
                } catch (e) {
                    // Silent error in unload
                }
            });
        }
        
        /**
         * Unlock audio for mobile browsers
         */
        unlockAudio() {
            log('Unlocking audio for mobile devices');
            
            try {
                // Create temporary audio context
                const tempContext = new (window.AudioContext || window.webkitAudioContext)();
                const buffer = tempContext.createBuffer(1, 1, 44100);
                const source = tempContext.createBufferSource();
                source.buffer = buffer;
                source.connect(tempContext.destination);
                source.start(0);
                
                // Clean up
                setTimeout(() => {
                    try {
                        tempContext.close().catch(() => {});
                    } catch (e) {}
                }, 300);
                
                log('Audio unlocked successfully');
                return true;
            } catch (e) {
                error('Error unlocking audio:', e);
                return false;
            }
        }
        
        /**
         * Start conversation with ElevenLabs agent
         */
        async startConversation() {
            log('Starting conversation with ElevenLabs');
            this.hideError();
            
            try {
                this.updateUIState('connecting');
                this.displaySystemMessage('Connecting to voice assistant...');
                this.playRingtone();
                
                // Unlock audio for mobile
                this.unlockAudio();
                
                // Request microphone access
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: true
                    });
                    
                    log('Microphone access granted');
                    
                    // Stop tracks immediately - ElevenLabs SDK will handle audio
                    stream.getTracks().forEach(track => track.stop());
                } catch (micError) {
                    throw new Error('Microphone access denied: ' + micError.message);
                }
                
                // Get signed WebSocket URL
                try {
                    const startTime = performance.now();
                    
                    // Ensure REST API configuration exists
                    if (!wpDualAI || !wpDualAI.restUrl) {
                        throw new Error('REST API configuration not found. Please check the plugin configuration.');
                    }
                    
                    const restUrl = wpDualAI.restUrl + 'wp-dual-ai/v1/elevenlabs/signed-url';
                    const nonce = wpDualAI.restNonce || '';
                    
                    // Prepare headers
                    const headers = {
                        'Content-Type': 'application/json'
                    };
                    
                    if (nonce) {
                        headers['X-WP-Nonce'] = nonce;
                    }
                    
                    // Try POST first (preferred method)
                    let response = await fetch(restUrl, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            browser: navigator.userAgent,
                            timestamp: Date.now()
                        })
                    });
                    
                    // If 404, try GET as fallback
                    if (response.status === 404) {
                        log('POST endpoint not found, trying GET as fallback');
                        
                        response = await fetch(restUrl, {
                            method: 'GET',
                            headers: headers
                        });
                    }
                    
                    // Check for configuration issues
                    if (response.status === 404) {
                        throw new Error('The ElevenLabs endpoint is not properly configured on the server. ' +
                                      'Contact the administrator to verify the ElevenLabs integration.');
                    }
                    
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Error getting signed URL (${response.status}): ${errorText}`);
                    }
                    
                    let data;
                    try {
                        data = await response.json();
                    } catch (jsonError) {
                        throw new Error('Invalid response from server');
                    }
                    
                    if (!data || !data.signed_url) {
                        throw new Error('Invalid response: Missing signed URL');
                    }
                    
                    const endTime = performance.now();
                    const apiLatency = endTime - startTime;
                    
                    log(`URL obtained in ${apiLatency.toFixed(1)}ms`);
                    
                    // Initialize ElevenLabs with the signed URL
                    await this.initializeElevenLabsSDK(data.signed_url);
                    
                } catch (apiError) {
                    throw new Error('Connection error: ' + apiError.message);
                }
            } catch (error) {
                this.displayError(error.message);
                this.updateUIState('error');
                this.stopRingtone();
                this.cleanupResources();
                return false;
            }
            
            return true;
        }
        
        /**
         * Initialize ElevenLabs SDK with signed URL
         */

 async initializeElevenLabsSDK(signedUrl) {
    try {
        await this.loadElevenLabsSDK();
        
        log('Inicializando cliente de ElevenLabs con URL firmada');
        
        // Determinar qué objeto SDK usar
        const sdk = window.elevenlabs || window.ElevenLabs;
        
        if (!sdk) {
            throw new Error('SDK de ElevenLabs no disponible después de cargar');
        }
        
        // Verificar que la clase Conversation está disponible
        if (!sdk.Conversation) {
            throw new Error('API Conversation no disponible en el SDK cargado');
        }
        
        log('API Conversation encontrada, iniciando sesión...');
        
        // Configuración de callbacks para la sesión
        const options = {
            signedUrl: signedUrl,
            onConnect: ({ conversationId }) => {
                log('Conectado con ID de conversación:', conversationId);
                this.stopRingtone();
                this.updateUIState('connected');
                this.displaySystemMessage('Conectado al asistente de voz');
            },
            onDisconnect: (details) => {
                log('Desconectado:', details);
                if (this.isCallActive) {
                    this.displaySystemMessage('Conexión cerrada: ' + (details.reason || 'Desconexión normal'));
                    this.cleanupResources();
                    this.isCallActive = false;
                    this.updateUIState('idle');
                }
            },
            onMessage: ({ message, source }) => {
                log('Mensaje recibido:', { message, source });
                if (source === 'ai') {
                    this.displayBotMessage(message);
                } else if (source === 'user') {
                    this.displayUserMessage(message);
                }
            },
            onError: (message, context) => {
                error('Error de la API:', message, context);
                this.displayError('Error: ' + message);
            },
            onModeChange: ({ mode }) => {
                log('Modo cambiado a:', mode);
                if (mode === 'speaking') {
                    this.setBotSpeaking(true);
                    this.voiceChatContainer.addClass('wp-dual-ai-speaking');
                } else if (mode === 'listening') {
                    this.setBotSpeaking(false);
                    this.voiceChatContainer.removeClass('wp-dual-ai-speaking');
                }
            },
            onStatusChange: ({ status }) => {
                log('Estado cambiado a:', status);
                if (status === 'connected') {
                    this.stopRingtone();
                    this.updateUIState('connected');
                }
            }
        };
        
        // Iniciar sesión usando la API oficial de Conversation
        log('Intentando iniciar conversación con opciones:', JSON.stringify(options, null, 2));
        const conversation = await sdk.Conversation.startSession(options);
        
        if (!conversation) {
            throw new Error('No se pudo iniciar la conversación (objeto nulo)');
        }
        
        log('Conversación iniciada correctamente');
        this.elevenLabsConversation = conversation;
        this.isCallActive = true;
        this.updateUIState('connected');
        this.displaySystemMessage('Conectado al asistente de voz');
        
    } catch (e) {
        error('Error inicializando SDK de ElevenLabs:', e);
        throw new Error('Error inicializando chat de voz: ' + e.message);
    }
}
     registerVoiceChatEvents() {
    if (!this.elevenLabsVoiceChat) return;
    
    // Eventos de conexión
    this.elevenLabsVoiceChat.on('connected', () => {
        log('Voice Chat connected');
        this.updateUIState('connected');
        this.displaySystemMessage('Connected to voice assistant');
    });
    
    this.elevenLabsVoiceChat.on('disconnected', (event) => {
        log('Voice Chat disconnected', event);
        if (this.isCallActive) {
            this.displaySystemMessage('Connection closed');
            this.cleanupResources();
            this.isCallActive = false;
            this.updateUIState('idle');
        }
    });
    
    this.elevenLabsVoiceChat.on('error', (err) => {
        error('Voice Chat error:', err);
        this.displayError('Error: ' + (err.message || 'Unknown error'));
    });
    
    // Eventos de transcripción
    this.elevenLabsVoiceChat.on('speechRecognized', (transcription) => {
        if (transcription && transcription.text) {
            log('User speech recognized:', transcription.text);
            this.displayUserMessage(transcription.text);
        }
    });
    
    this.elevenLabsVoiceChat.on('message', (message) => {
        if (message && message.text) {
            log('Bot message:', message.text);
            this.displayBotMessage(message.text);
        }
    });
    
    // Eventos de audio
    this.elevenLabsVoiceChat.on('botSpeechStart', () => {
        log('Bot started speaking');
        this.setBotSpeaking(true);
        this.voiceChatContainer.addClass('wp-dual-ai-speaking');
    });
    
    this.elevenLabsVoiceChat.on('botSpeechEnd', () => {
        log('Bot stopped speaking');
        this.setBotSpeaking(false);
        this.voiceChatContainer.removeClass('wp-dual-ai-speaking');
    });
    
    this.elevenLabsVoiceChat.on('userSpeechStart', () => {
        log('User started speaking');
        // Actualizar indicador de volumen
        this.updateVolumeIndicator(70);
    });
    
    this.elevenLabsVoiceChat.on('userSpeechEnd', () => {
        log('User stopped speaking');
        this.updateVolumeIndicator(0);
    });
    
    // Eventos de volumen del micrófono (si están disponibles)
    this.elevenLabsVoiceChat.on('volumeChange', (volume) => {
        const scaledVolume = Math.min(100, Math.max(0, Math.round(volume * 100)));
        this.updateVolumeIndicator(scaledVolume);
    });
    
    // Eventos de interrupción
    this.elevenLabsVoiceChat.on('interruptionStarted', () => {
        log('Interruption detected');
        this.displaySystemMessage('Listening...', 'interruption');
    });
    
    this.elevenLabsVoiceChat.on('interruptionEnded', () => {
        log('Interruption ended');
    });
}   
       /**
 * Manejadores de eventos para Voice Chat
 */
handleStateChange(newState) {
    console.log('Estado del Voice Chat cambiado:', newState);
    
    switch (newState) {
        case 'connected':
            this.updateUIState('connected');
            this.displaySystemMessage('Conectado al asistente de voz');
            break;
            
        case 'disconnected':
            if (this.isCallActive) {
                this.displaySystemMessage('Conexión cerrada');
                this.cleanupResources();
                this.isCallActive = false;
                this.updateUIState('idle');
            }
            break;
            
        case 'connecting':
            this.updateUIState('connecting');
            this.displaySystemMessage('Conectando...');
            break;
            
        case 'error':
            this.updateUIState('error');
            break;
    }
}

handleError(error) {
    console.error('Error de Voice Chat:', error);
    this.displayError('Error: ' + (error.message || 'Error desconocido'));
}

handleBotMessage(message) {
    console.log('Mensaje del asistente:', message);
    if (message && message.text) {
        this.displayBotMessage(message.text);
    }
}

handleUserTranscript(transcript) {
    console.log('Transcripción del usuario:', transcript);
    if (transcript && transcript.text) {
        this.displayUserMessage(transcript.text);
    }
}

handleBotSpeechStart() {
    console.log('El asistente comenzó a hablar');
    this.setBotSpeaking(true);
    this.voiceChatContainer.addClass('wp-dual-ai-speaking');
}

handleBotSpeechEnd() {
    console.log('El asistente terminó de hablar');
    this.setBotSpeaking(false);
    this.voiceChatContainer.removeClass('wp-dual-ai-speaking');
}

handleUserSpeechStart() {
    console.log('Usuario comenzó a hablar');
    // Actualizar indicador de volumen
    this.updateVolumeIndicator(70);
}

handleUserSpeechEnd() {
    console.log('Usuario terminó de hablar');
    this.updateVolumeIndicator(0);
} 
       /**
 * Carga el SDK de ElevenLabs con múltiples estrategias de fallback
 * @returns {Promise} Promesa que se resuelve cuando el SDK está disponible
 */
async loadElevenLabsSDK() {
    return new Promise((resolve, reject) => {
        log('Intentando cargar SDK de ElevenLabs');
        
        // 1. Verificar si ya está cargado
        if (window.elevenlabs || window.ElevenLabs) {
            log('SDK ya está cargado en el navegador');
            resolve();
            return;
        }
        
        // 2. Verificar si el adaptador está disponible
        if (window.ElevenLabsAdapter) {
            log('Usando ElevenLabsAdapter');
            window.ElevenLabsAdapter.init()
                .then(() => resolve())
                .catch(err => reject(err));
            return;
        }
        
        // 3. Cargar ElevenLabsAdapter primero
        const adapterScript = document.createElement('script');
        adapterScript.src = wpDualAI.pluginUrl + 'public/js/elevenlabs-adapter.js';
        adapterScript.async = true;
        
        adapterScript.onload = () => {
            log('Adaptador cargado, inicializando...');
            
            if (window.ElevenLabsAdapter) {
                window.ElevenLabsAdapter.init()
                    .then(() => resolve())
                    .catch(err => reject(err));
            } else {
                // Última opción: cargar desde CDN
                const sdkScript = document.createElement('script');
                sdkScript.src = 'https://cdn.jsdelivr.net/npm/@11labs/client@latest/dist/client.umd.js';
                sdkScript.async = true;
                
                sdkScript.onload = () => {
                    log('SDK cargado desde CDN');
                    // Dar un breve tiempo para inicializar
                    setTimeout(() => {
                        if (window.ElevenLabsClient) {
                            window.elevenlabs = window.ElevenLabsClient;
                            window.ElevenLabs = window.ElevenLabsClient;
                            resolve();
                        } else {
                            reject(new Error('SDK cargado pero no se pudo detectar el objeto global'));
                        }
                    }, 100);
                };
                
                sdkScript.onerror = () => {
                    reject(new Error('No se pudo cargar el SDK desde ninguna fuente'));
                };
                
                document.head.appendChild(sdkScript);
            }
        };
        
        adapterScript.onerror = () => {
            log('Error cargando adaptador, intentando cargar SDK directamente');
            
            // Intento directo desde CDN
            const directScript = document.createElement('script');
            directScript.src = 'https://cdn.jsdelivr.net/npm/@11labs/client@latest/dist/client.umd.js';
            directScript.async = true;
            
            directScript.onload = () => {
                log('SDK cargado desde CDN directamente');
                // Dar un breve tiempo para inicializar
                setTimeout(() => {
                    if (window.ElevenLabsClient) {
                        window.elevenlabs = window.ElevenLabsClient;
                        window.ElevenLabs = window.ElevenLabsClient;
                        resolve();
                    } else {
                        reject(new Error('SDK cargado pero no se pudo detectar el objeto global'));
                    }
                }, 100);
            };
            
            directScript.onerror = () => {
                reject(new Error('No se pudo cargar el SDK desde ninguna fuente'));
            };
            
            document.head.appendChild(directScript);
        };
        
        document.head.appendChild(adapterScript);
    });
}
        
        /**
         * Register event listeners for the ElevenLabs agent
         */
        registerAgentEventListeners() {
            if (!this.elevenLabsAgent) return;
            
            // Connection events
            this.elevenLabsAgent.on('connected', () => {
                log('ElevenLabs agent connected');
                this.stopRingtone();
                this.updateUIState('connected');
                this.displaySystemMessage('Connected to voice assistant');
            });
            
            this.elevenLabsAgent.on('disconnected', (event) => {
                log('ElevenLabs agent disconnected', event);
                
                if (this.isCallActive) {
                    this.displaySystemMessage('Connection closed unexpectedly');
                    this.cleanupResources();
                    this.isCallActive = false;
                    this.updateUIState('idle');
                }
            });
            
            this.elevenLabsAgent.on('error', (err) => {
                error('ElevenLabs agent error:', err);
                this.displayError('Error: ' + (err.message || 'Unknown error'));
            });
            
            // Audio state events
            this.elevenLabsAgent.on('agentSpeechStart', () => {
                log('Agent started speaking');
                this.setBotSpeaking(true);
                this.voiceChatContainer.addClass('wp-dual-ai-speaking');
            });
            
            this.elevenLabsAgent.on('agentSpeechEnd', () => {
                log('Agent stopped speaking');
                this.setBotSpeaking(false);
                this.voiceChatContainer.removeClass('wp-dual-ai-speaking');
            });
            
            this.elevenLabsAgent.on('userSpeechStart', () => {
                log('User started speaking');
                this.updateVolumeIndicator(70); // Show activity
            });
            
            this.elevenLabsAgent.on('userSpeechEnd', () => {
                log('User stopped speaking');
                this.updateVolumeIndicator(0); // Reset
            });
            
            // Volume updates
            this.elevenLabsAgent.on('microphoneVolumeChange', (volume) => {
                // Scale volume to 0-100%
                const scaledVolume = Math.min(100, Math.max(0, Math.round(volume * 100)));
                this.updateVolumeIndicator(scaledVolume);
            });
            
            // Transcript events
            this.elevenLabsAgent.on('agentMessage', (message) => {
                log('Agent message:', message);
                this.displayBotMessage(message.text);
            });
            
            this.elevenLabsAgent.on('userMessage', (message) => {
                log('User message:', message);
                this.displayUserMessage(message.text);
            });
            
            // Interruption events
            this.elevenLabsAgent.on('interruptionStart', () => {
                log('Interruption detected');
                this.displaySystemMessage('Listening...', 'interruption');
            });
            
            this.elevenLabsAgent.on('interruptionEnd', () => {
                log('Interruption ended');
            });
        }
        
        /**
         * Update the volume indicator with the current level
         */
        updateVolumeIndicator(level) {
            // Update indicator
            this.volumeIndicator.css('width', level + '%');
            
            // Update color classes
            this.volumeIndicator.parent()
                .removeClass('low medium high')
                .addClass(level < 33 ? 'low' : (level < 66 ? 'medium' : 'high'));
        }
        
        /**
         * Set bot speaking state
         */
        setBotSpeaking(speaking) {
            this.isBotSpeaking = speaking;
            
            if (speaking) {
                this.voiceChatContainer.addClass('wp-dual-ai-speaking');
            } else {
                // Small delay to avoid visual flickering
                setTimeout(() => {
                    if (!this.isBotSpeaking) {
                        this.voiceChatContainer.removeClass('wp-dual-ai-speaking');
                    }
                }, 50);
            }
        }
        
       /**
 * Finalizar conversación correctamente
 */
endConversation() {
    log('Finalizando conversación');
    
    if (this.elevenLabsConversation) {
        try {
            this.elevenLabsConversation.endSession();
        } catch (e) {
            error('Error al desconectar conversación:', e);
        }
    }
    
    this.stopRingtone();
    this.cleanupResources();
    this.isCallActive = false;
    this.updateUIState('idle');
    this.displaySystemMessage('Llamada finalizada');
    
    return true;
}
        
        /**
         * Clean up resources
         */
cleanupResources() {
    // Limpiar la conversación de ElevenLabs
    if (this.elevenLabsConversation) {
        try {
            this.elevenLabsConversation.endSession();
        } catch (e) {
            error('Error al desconectar la conversación:', e);
        }
        this.elevenLabsConversation = null;
    }
    
    // Resetear UI
    this.volumeIndicator.css('width', '0%');
    this.volumeIndicator.parent().removeClass('low medium high');
    this.voiceChatContainer.removeClass('wp-dual-ai-speaking');
    
    // Resetear flags
    this.isBotSpeaking = false;
    
    log('Recursos liberados');
}

        
        /**
         * Play ringtone with error handling
         */
        playRingtone() {
            if (!this.ringtoneAudio || !this.isRingtoneLoaded) {
                log('Ringtone not available');
                return;
            }
            
            try {
                this.ringtoneAudio.currentTime = 0;
                this.ringtoneAudio.volume = 1.0;
                
                const playPromise = this.ringtoneAudio.play();
                
                if (playPromise !== undefined) {
                    playPromise.catch(e => {
                        log('Error playing ringtone:', e);
                        
                        // Retry with user interaction
                        setTimeout(() => {
                            // Unlock audio and retry
                            this.unlockAudio();
                            this.ringtoneAudio.play().catch(() => {
                                log('Could not play ringtone, possible browser restriction');
                            });
                        }, 500);
                    });
                }
            } catch (e) {
                error('General ringtone error:', e);
            }
        }
        
        /**
         * Stop ringtone
         */
        stopRingtone() {
            if (this.ringtoneAudio) {
                try {
                    this.ringtoneAudio.pause();
                    this.ringtoneAudio.currentTime = 0;
                } catch (e) {}
            }
        }
        
        /**
         * Update UI state
         */
        updateUIState(state) {
            log('Updating state to', state);
            
            // Update classes with smooth transition
            this.voiceChatContainer.removeClass('wp-dual-ai-idle wp-dual-ai-connecting wp-dual-ai-connected wp-dual-ai-error wp-dual-ai-speaking');
            this.voiceChatContainer.addClass('wp-dual-ai-' + state);
            
            // Update elements based on state
            switch (state) {
                case 'idle':
                    this.startButton.show();
                    this.endButton.hide();
                    this.statusIndicator.text('Ready to start');
                    break;
                case 'connecting':
                    this.startButton.hide();
                    this.endButton.show();
                    this.statusIndicator.text('Connecting...');
                    break;
                case 'connected':
                    this.startButton.hide();
                    this.endButton.show();
                    this.statusIndicator.text('On call');
                    break;
                case 'error':
                    this.startButton.show();
                    this.endButton.hide();
                    this.statusIndicator.text('Error');
                    break;
            }
        }
        
        /**
         * Display user message in transcript
         */
        displayUserMessage(message) {
            const messageHtml = `
                <div class="wp-dual-ai-message wp-dual-ai-user-message">
                    <div class="wp-dual-ai-message-content">${this.escapeHtml(message)}</div>
                </div>
            `;
            
            this.transcriptContainer.append(messageHtml);
            this.scrollTranscriptToBottom();
            
            // Highlight animation
            const newMessage = this.transcriptContainer.find('.wp-dual-ai-message:last');
            newMessage.addClass('wp-dual-ai-highlight');
            setTimeout(() => {
                newMessage.removeClass('wp-dual-ai-highlight');
            }, 300);
        }
        
        /**
         * Display bot message in transcript
         */
        displayBotMessage(message) {
            const messageHtml = `
                <div class="wp-dual-ai-message wp-dual-ai-bot-message">
                    <div class="wp-dual-ai-message-content">${this.escapeHtml(message)}</div>
                </div>
            `;
            
            this.transcriptContainer.append(messageHtml);
            this.scrollTranscriptToBottom();
            
            // Highlight animation
            const newMessage = this.transcriptContainer.find('.wp-dual-ai-message:last');
            newMessage.addClass('wp-dual-ai-highlight');
            setTimeout(() => {
                newMessage.removeClass('wp-dual-ai-highlight');
            }, 300);
        }
        
        /**
         * Display system message in transcript
         */
        displaySystemMessage(message, className = '') {
            const messageHtml = `
                <div class="wp-dual-ai-message wp-dual-ai-system-message ${className}">
                    <div class="wp-dual-ai-message-content">${this.escapeHtml(message)}</div>
                </div>
            `;
            
            this.transcriptContainer.append(messageHtml);
            this.scrollTranscriptToBottom();
        }
        
        /**
         * Display error with visual feedback
         */
        displayError(message) {
            error('Error:', message);
            
            const messageHtml = `
                <div class="wp-dual-ai-message wp-dual-ai-error-message">
                    <div class="wp-dual-ai-message-content">${this.escapeHtml(message)}</div>
                </div>
            `;
            
            this.transcriptContainer.append(messageHtml);
            this.scrollTranscriptToBottom();
            
            this.errorMessage.text(message);
            this.errorContainer.show();
            
            // Visual highlight
            this.errorContainer.addClass('wp-dual-ai-highlight');
            setTimeout(() => {
                this.errorContainer.removeClass('wp-dual-ai-highlight');
            }, 500);
        }
        
        /**
         * Hide error container
         */
        hideError() {
            this.errorContainer.hide();
        }
        
        /**
         * Escape HTML safely
         */
        escapeHtml(text) {
            if (typeof text !== 'string') {
                return '';
            }
            
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Scroll transcript to bottom with smooth animation
         */
        scrollTranscriptToBottom() {
            this.transcriptContainer.stop().animate({
                scrollTop: this.transcriptContainer[0].scrollHeight
            }, 300);
        }
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        try {
            if ($('#wp-dual-ai-voice-chat').length) {
                // Add enhanced CSS for better UI experience
                const enhancedStyles = `
                    /* Animations for messages */
                    .wp-dual-ai-highlight {
                        animation: highlight 0.5s ease;
                    }
                    
                    .wp-dual-ai-user-message, .wp-dual-ai-bot-message {
                        transition: all 0.3s ease;
                        animation: messageIn 0.3s ease;
                    }
                    
                    @keyframes highlight {
                        0% { background-color: rgba(255,255,100,0.3); }
                        100% { background-color: transparent; }
                    }
                    
                    @keyframes messageIn {
                        from { opacity: 0; transform: translateY(10px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    
                    /* Speaking indicator */
                    .wp-dual-ai-speaking .wp-dual-ai-status::after {
                        content: " (speaking)";
                        color: #4CAF50;
                        font-weight: bold;
                    }
                    
                    /* Error styling */
                    .wp-dual-ai-error-message {
                        background-color: rgba(255, 0, 0, 0.1);
                        border-left: 3px solid #f44336;
                        padding-left: 10px;
                    }
                    
                    /* Mobile responsiveness */
                    @media (max-width: 767px) {
                        #wp-dual-ai-voice-chat-container {
                            max-width: 100%;
                            width: 100%;
                        }
                        
                        .wp-dual-ai-transcript {
                            max-height: 250px;
                        }
                    }
                `;
                
                $('<style>').text(enhancedStyles).appendTo('head');
                
                try {
                    // Initialize voice chat with ElevenLabs SDK
                    window.wpDualAIVoiceChat = new WPElevenLabsVoiceChat();
                    log('ElevenLabs Voice Chat initialized');
                } catch (initError) {
                    error('Error initializing Voice Chat:', initError);
                    console.error('Complete error details:', initError);
                    
                    // Show error to user
                    if ($('#wp-dual-ai-transcript').length) {
                        $('#wp-dual-ai-transcript').append(`
                            <div class="wp-dual-ai-message wp-dual-ai-error-message">
                                <div class="wp-dual-ai-message-content">
                                    Error initializing voice chat. Please reload the page or contact support.
                                </div>
                            </div>
                        `);
                    }
                }
            }
        } catch (e) {
            console.error('[Voice Chat Critical Error]', e);
        }
    });

})(jQuery);
