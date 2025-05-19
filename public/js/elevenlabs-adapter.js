/**
 * ElevenLabs Adapter for WP Dual AI Assistant
 * 
 * Este adaptador proporciona una interfaz unificada para cargar e inicializar
 * el SDK de ElevenLabs, ya sea desde el archivo local o desde CDN como fallback.
 * 
 * @since      1.9.0
 * @package    WP_Dual_AI_Assistant
 */

// Evitar que se ejecute directamente
(function() {
    'use strict';

    // Configurar objeto global para debugging
    const DEBUG = true;
    const log = (...args) => DEBUG && console.log('[ElevenLabsAdapter]', ...args);
    const error = (...args) => console.error('[ElevenLabsAdapter Error]', ...args);

    // Crear un espacio de nombres global para el adaptador
    window.ElevenLabsAdapter = {
        // Estado de inicialización
        _initialized: false,
        _initializing: false,
        _sdk: null,

        /**
         * Inicializa el SDK de ElevenLabs
         * @returns {Promise} Promesa que se resuelve cuando el SDK está listo
         */
        init: function() {
            // Evitar inicializaciones múltiples
            if (this._initialized) {
                log('SDK ya inicializado');
                return Promise.resolve(this._sdk);
            }

            if (this._initializing) {
                log('SDK ya está siendo inicializado');
                return new Promise((resolve, reject) => {
                    const checkInterval = setInterval(() => {
                        if (this._initialized) {
                            clearInterval(checkInterval);
                            resolve(this._sdk);
                        }
                    }, 100);

                    // Timeout después de 10 segundos
                    setTimeout(() => {
                        clearInterval(checkInterval);
                        reject(new Error('Timeout al esperar inicialización del SDK'));
                    }, 10000);
                });
            }

            this._initializing = true;
            log('Iniciando carga del SDK de ElevenLabs');

            return this._loadSDK()
                .then(sdk => {
                    this._sdk = sdk;
                    this._initialized = true;
                    this._initializing = false;
                    log('SDK cargado e inicializado correctamente');
                    
                    // Exponer globalmente para compatibilidad
                    window.elevenlabs = sdk;
                    window.ElevenLabs = sdk;
                    
                    return sdk;
                })
                .catch(err => {
                    this._initializing = false;
                    error('Error al inicializar el SDK:', err);
                    throw err;
                });
        },

        /**
         * Carga el SDK desde diferentes fuentes
         */
        _loadSDK: function() {
            return new Promise((resolve, reject) => {
                // 1. Verificar si el SDK ya está cargado globalmente
                if (window.elevenlabs || window.ElevenLabs) {
                    log('SDK ya cargado globalmente');
                    resolve(window.elevenlabs || window.ElevenLabs);
                    return;
                }

                // 2. Intentar cargar desde el archivo local incluido en el plugin
                const localSDKPath = (window.wpDualAI && window.wpDualAI.pluginUrl) 
                    ? window.wpDualAI.pluginUrl + 'public/js/elevenlabs-sdk.min.js'
                    : '/wp-content/plugins/wp-dual-ai-assistant/public/js/elevenlabs-sdk.min.js';

                log('Intentando cargar SDK desde:', localSDKPath);
                
                const script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = localSDKPath;
                script.async = true;

                script.onload = () => {
                    log('SDK local cargado correctamente');
                    
                    // Verificar si el SDK expuso los objetos esperados
                    if (window.client) {
                        // Compatibilidad con formato de SDK minificado
                        log('SDK detectado en formato minificado (window.client)');
                        resolve(window.client);
                        return;
                    }
                    
                    if (window.elevenlabs || window.ElevenLabs) {
                        log('SDK detectado globalmente después de cargar');
                        resolve(window.elevenlabs || window.ElevenLabs);
                        return;
                    }
                    
                    // Si llegamos aquí, el SDK se cargó pero no expuso los objetos esperados
                    // Intentamos con CDN como fallback
                    this._loadFromCDN().then(resolve).catch(reject);
                };

                script.onerror = () => {
                    error('Error al cargar SDK local, intentando desde CDN');
                    this._loadFromCDN().then(resolve).catch(reject);
                };

                document.head.appendChild(script);
            });
        },

        /**
         * Intenta cargar el SDK desde CDN como fallback
         */
        _loadFromCDN: function() {
            return new Promise((resolve, reject) => {
                log('Intentando cargar SDK desde CDN');
                
                const cdnScript = document.createElement('script');
                cdnScript.src = 'https://cdn.jsdelivr.net/npm/@11labs/client@latest/dist/client.umd.js';
                cdnScript.async = true;
                
                cdnScript.onload = () => {
                    log('SDK cargado desde CDN');
                    
                    // Dar tiempo para que se inicialice
                    setTimeout(() => {
                        if (window.ElevenLabsClient) {
                            log('SDK de CDN detectado como ElevenLabsClient');
                            resolve(window.ElevenLabsClient);
                        } else if (window.elevenlabs || window.ElevenLabs) {
                            log('SDK de CDN detectado globalmente');
                            resolve(window.elevenlabs || window.ElevenLabs);
                        } else {
                            reject(new Error('SDK cargado pero no se detectaron los objetos globales esperados'));
                        }
                    }, 200);
                };
                
                cdnScript.onerror = () => {
                    error('Error al cargar SDK desde CDN');
                    reject(new Error('No se pudo cargar el SDK de ElevenLabs desde ninguna fuente'));
                };
                
                document.head.appendChild(cdnScript);
            });
        },

        /**
         * Verifica si el SDK está inicializado
         */
        isInitialized: function() {
            return this._initialized;
        },

        /**
         * Obtiene la instancia del SDK
         */
        getSDK: function() {
            if (!this._initialized) {
                error('SDK no inicializado. Llame a init() primero');
                return null;
            }
            return this._sdk;
        }
    };

    // Auto-inicializar si window.wpDualAI ya está disponible
    if (window.wpDualAI) {
        log('wpDualAI detectado, inicializando automáticamente');
        window.ElevenLabsAdapter.init().catch(err => {
            error('Error en auto-inicialización:', err);
        });
    } else {
        // Esperar a que el documento esté listo
        document.addEventListener('DOMContentLoaded', function() {
            if (window.wpDualAI) {
                log('wpDualAI detectado en DOMContentLoaded, inicializando');
                window.ElevenLabsAdapter.init().catch(err => {
                    error('Error en inicialización en DOMContentLoaded:', err);
                });
            }
        });
    }
})();
