<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Dual_AI_Assistant
 * @subpackage WP_Dual_AI_Assistant/public
 */

class WP_Dual_AI_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The Anthropic API handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Dual_AI_Anthropic    $anthropic_api    The Anthropic API handler.
     */
    private $anthropic_api;

    /**
     * The ElevenLabs API handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Dual_AI_ElevenLabs    $elevenlabs_api    The ElevenLabs API handler.
     */
    private $elevenlabs_api;
    


    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of the plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Initialize API handlers
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-anthropic.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-wp-dual-ai-elevenlabs.php';
        
        $this->anthropic_api = new WP_Dual_AI_Anthropic();
        $this->elevenlabs_api = new WP_Dual_AI_ElevenLabs();
        
        // Check if ElevenLabs credentials are valid
        $this->validate_elevenlabs_credentials();
    }

    /**
     * Validate ElevenLabs credentials
     * 
     * @since    1.0.0
     * @access   private
     * @return   boolean    Whether credentials are valid
     */
    private function validate_elevenlabs_credentials() {
        $api_key = get_option('wp_dual_ai_elevenlabs_api_key', '');
        $agent_id = get_option('wp_dual_ai_elevenlabs_agent_id', '');
        
        return (!empty($api_key) && !empty($agent_id));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.1.0
     */
    public function enqueue_styles() {
        // Base public styles
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/wp-dual-ai-public.css',
            array(),
            $this->version,
            'all'
        );
        
        // Chat interface styles (used by both text and voice interfaces)
        wp_enqueue_style(
            $this->plugin_name . '-chat',
            plugin_dir_url(__FILE__) . 'css/wp-dual-ai-chat.css',
            array($this->plugin_name),
            $this->version,
            'all'
        );
    }

    /**
 * Register the JavaScript for the public-facing side of the site.
 *
 * @since    1.3.0
 */
public function enqueue_scripts() {
    // Enqueue jQuery as basic requirement
    wp_enqueue_script('jquery');
    
    // CORRECCIÓN: Determinar la URL del plugin correctamente
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    
    // Main public script
    wp_register_script(
        $this->plugin_name,
        $plugin_url . 'public/js/wp-dual-ai-public.js',
        array('jquery'),
        $this->version . '.' . time(),
        true
    );
    
    // Text chat script
    wp_register_script(
        $this->plugin_name . '-text-chat',
        $plugin_url . 'public/js/wp-dual-ai-text-chat.js',
        array('jquery', $this->plugin_name),
        $this->version . '.' . time(),
        true
    );
    
    // Adaptador para el SDK de ElevenLabs (NUEVO)
    wp_register_script(
        $this->plugin_name . '-elevenlabs-adapter',
        $plugin_url . 'public/js/elevenlabs-adapter.js',
        array('jquery'),
        $this->version . '.' . time(),
        true
    );
    
    // Voice chat script
    wp_register_script(
        $this->plugin_name . '-voice-chat',
        $plugin_url . 'public/js/wp-dual-ai-voice-chat.js',
        array('jquery', $this->plugin_name, $this->plugin_name . '-elevenlabs-adapter'),
        $this->version . '.' . time(),
        true
    );
    
    // Obtener el ID del ringtone personalizado
    $ringtone_id = get_option('wp_dual_ai_elevenlabs_ringtone', '');
    $ringtone_url = '';
    $ringtone_fallback_url = '';

    if (!empty($ringtone_id) && wp_attachment_is_image($ringtone_id) === false) {
        // Ringtone personalizado
        $ringtone_url = wp_get_attachment_url($ringtone_id);
        
        // Verificar si existe una versión alternativa del archivo
        $attachment_meta = wp_get_attachment_metadata($ringtone_id);
        if (isset($attachment_meta['formats']) && isset($attachment_meta['formats']['wav'])) {
            $ringtone_fallback_url = wp_get_attachment_url($attachment_meta['formats']['wav']);
        }
    } else {
        // CORRECCIÓN: Usar la ruta correcta del plugin
        $ringtone_url = $plugin_url . 'assets/audio/phone-ring.mp3';
        $ringtone_fallback_url = $plugin_url . 'assets/audio/phone-ring.wav';
    }
    
    // Localize script with configuration data
    wp_localize_script($this->plugin_name, 'wpDualAI', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => esc_url_raw(rest_url()),
        'restNonce' => wp_create_nonce('wp_rest'),
        'nonce' => wp_create_nonce('wp_dual_ai_nonce'),
        'welcomeMessage' => get_option('wp_dual_ai_welcome_message', 'Hello! How can I help you today?'),
        'ringtoneUrl' => $ringtone_url,
        'ringtoneUrlFallback' => $ringtone_fallback_url,
        'isWooCommerce' => class_exists('WooCommerce') ? 'true' : 'false',
        'debugMode' => defined('WP_DEBUG') && WP_DEBUG ? true : false,
        'elevenlabsConfigured' => $this->validate_elevenlabs_credentials(),
        'pluginUrl' => $plugin_url,  // CORRECCIÓN: URL correcta del plugin
        'timestamp' => time(),
        'pluginName' => $this->plugin_name // NUEVO: Nombre del plugin para depuración
    ));
    
    // Enqueue all scripts
    wp_enqueue_script($this->plugin_name);
    wp_enqueue_script($this->plugin_name . '-elevenlabs-adapter');
    wp_enqueue_script($this->plugin_name . '-text-chat');
    wp_enqueue_script($this->plugin_name . '-voice-chat');
}
    /**
     * Register shortcodes for chat interfaces.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('dual_ai_text_chat', array($this, 'text_chat_shortcode'));
        add_shortcode('dual_ai_voice_chat', array($this, 'voice_chat_shortcode'));
        add_shortcode('dual_ai_chat_buttons', array($this, 'chat_buttons_shortcode'));
    }

    /**
     * Shortcode callback for text chat interface.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Rendered shortcode.
     */
    public function text_chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'title' => 'Chat with AI Assistant'
        ), $atts);
        
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/chat-interface.php';
        return ob_get_clean();
    }

    /**
     * Shortcode callback for voice chat interface.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   Rendered shortcode.
     */
    public function voice_chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'title' => 'Voice Assistant'
        ), $atts);
        
        // Add a notice if credentials aren't configured
        if (!$this->validate_elevenlabs_credentials()) {
            $notice = '<div class="wp-dual-ai-error-notice">';
            $notice .= 'ElevenLabs API credentials not configured. Please configure them in the plugin settings.';
            $notice .= '</div>';
            return $notice;
        }
        
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/voice-interface.php';
        return ob_get_clean();
    }

/**
 * Shortcode para los botones de chat con diseño mejorado
 * 
 * - Color fijo #2baab4
 * - Botón más grande y completamente clickeable
 * - Icono de dedo señalando hacia arriba cuando está activo
 */
public function chat_buttons_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => true,
        'voice' => true,
        'position' => 'bottom-right'
    ), $atts);
    
    // Verificar si se deben mostrar los botones
    $show_text = ($atts['text'] !== false && $atts['text'] !== 'false');
    $show_voice = ($atts['voice'] !== false && $atts['voice'] !== 'false' && $this->validate_elevenlabs_credentials());
    
    // Generar un ID único para este conjunto de botones
    $unique_id = 'dual-ai-' . uniqid();
    
    // Incluir estilos inline para garantizar que se apliquen
    $output = '
    <style>
        /* Estilos para el contenedor principal */
        #' . $unique_id . ' {
            position: fixed;
            ' . ($atts['position'] === 'bottom-right' ? 'right: 20px;' : 'left: 20px;') . '
            bottom: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        /* Botón principal - más grande para mejor clickabilidad */
        #' . $unique_id . '-toggle {
            width: 64px; /* Aumentado de 56px */
            height: 64px; /* Aumentado de 56px */
            border-radius: 50%;
            background-color: #2baab4; /* Color fijo según lo solicitado */
            color: #ffffff;
            border: none;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            position: relative;
            padding: 0; /* Eliminar padding para mejor clickabilidad */
        }
        
        #' . $unique_id . '-toggle:hover {
            transform: scale(1.05); /* Ligero efecto de escala al pasar el mouse */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Cuando está activo, cambia a gris oscuro */
        #' . $unique_id . '-toggle.active {
            background-color: #333333;
            color: white;
        }
        
        /* Asegurar que todo el botón sea clickeable */
        #' . $unique_id . '-toggle::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50%;
            z-index: 1;
        }
        
        /* Iconos */
        #' . $unique_id . ' .icon {
            display: none;
            width: 28px; /* Icono ligeramente más grande */
            height: 28px; /* Icono ligeramente más grande */
            position: relative;
            z-index: 2;
        }
        
        #' . $unique_id . ' .icon.visible {
            display: block;
        }
        
        /* Contenedor de opciones */
        #' . $unique_id . '-options {
            display: none;
            flex-direction: column;
            gap: 12px;
        }
        
        #' . $unique_id . '-options.visible {
            display: flex;
        }
        
        /* Botones de opción */
        #' . $unique_id . ' .option-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            position: relative;
            transition: width 0.3s ease, transform 0.2s ease;
            overflow: hidden;
        }
        
        #' . $unique_id . ' .option-button:hover {
            width: 140px;
            border-radius: 30px;
            transform: scale(1.02);
        }
        
        #' . $unique_id . ' .option-button:active {
            transform: scale(0.98);
        }
        
        #' . $unique_id . ' .option-button svg {
            position: absolute;
            left: 18px;
            width: 24px;
            height: 24px;
        }
        
        #' . $unique_id . ' .option-button span {
            position: absolute;
            left: 54px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        #' . $unique_id . ' .option-button:hover span {
            opacity: 1;
        }
        
        /* Botón de texto */
        #' . $unique_id . '-text-button {
            background-color: #4f46e5;
            color: white;
        }
        
        #' . $unique_id . '-text-button:hover {
            background-color: #4338ca;
        }
        
        /* Botón de voz */
        #' . $unique_id . '-voice-button {
            background-color: #7950f2;
            color: white;
        }
        
        #' . $unique_id . '-voice-button:hover {
            background-color: #6741d9;
        }
        
        /* Animación para el botón principal */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(237, 246, 249, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(237, 246, 249, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(237, 246, 249, 0);
            }
        }
        
        #' . $unique_id . '-toggle.pulse {
            animation: pulse 1.5s infinite;
        }
        
        @media (max-width: 480px) {
            #' . $unique_id . ' .option-button:hover {
                width: 60px;
            }
            
            #' . $unique_id . ' .option-button span {
                display: none;
            }
        }
    </style>
    ';
    
    // Contenedor principal
    $output .= '<div id="' . $unique_id . '">';
    
    // Opciones (inicialmente ocultas)
    $output .= '<div id="' . $unique_id . '-options">';
    
    // Botón de chat de texto
    if ($show_text) {
        $output .= '
        <button id="' . $unique_id . '-text-button" class="option-button" aria-label="Chat de Texto">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>Chat de Texto</span>
        </button>';
    }
    
    // Botón de chat de voz
    if ($show_voice) {
        $output .= '
        <button id="' . $unique_id . '-voice-button" class="option-button" aria-label="Chat de Voz">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                <line x1="12" y1="19" x2="12" y2="23"></line>
            </svg>
            <span>Chat de Voz</span>
        </button>';
    }
    
    $output .= '</div>';
    
    // Botón principal de alternar con iconos
    $output .= '<button id="' . $unique_id . '-toggle" aria-label="Abrir chat">';
    
    // Icono de matraz de laboratorio (visible por defecto)
$output .= '
<svg class="icon tree-icon visible" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M10 2h4"></path>
    <path d="M12 2v7"></path>
    <path d="M7 20h10"></path>
    <path d="M7 20l5 -11"></path>
    <path d="M17 20l-5 -11"></path>
</svg>';
    
    // Icono de gota de agua
    $output .= '
    <svg class="icon water-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
    </svg>';
    
    // Icono de industria
    $output .= '
    <svg class="icon industry-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"></path>
    </svg>';
    
    // Icono de dedo señalando hacia arriba (para cuando está activo)
    $output .= '
    <svg class="icon pointer-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 1v16"></path>
        <path d="m19 7-7-6-7 6"></path>
        <path d="M12 17v6"></path>
    </svg>';
    
    $output .= '</button>';
    
    // JavaScript para la funcionalidad
    $output .= '
    <script>
    (function() {
        // Esperar a que el DOM esté listo
        document.addEventListener("DOMContentLoaded", function() {
            // Elementos UI
            const toggleButton = document.getElementById("' . $unique_id . '-toggle");
            const optionsContainer = document.getElementById("' . $unique_id . '-options");
            const textButton = document.getElementById("' . $unique_id . '-text-button");
            const voiceButton = document.getElementById("' . $unique_id . '-voice-button");
            
            // Elementos de iconos
            const treeIcon = toggleButton.querySelector(".tree-icon");
            const waterIcon = toggleButton.querySelector(".water-icon");
            const industryIcon = toggleButton.querySelector(".industry-icon");
            const pointerIcon = toggleButton.querySelector(".pointer-icon");
            
            // Variables de estado
            let isOpen = false;
            let currentIcon = "tree";
            let iconInterval = null;
            
            // Función para cambiar iconos (solo cambia el icono, no el color)
            function cycleIcon() {
                // Solo cambiar si no está abierto
                if (isOpen) return;
                
                // Ocultar todos los iconos
                treeIcon.classList.remove("visible");
                waterIcon.classList.remove("visible");
                industryIcon.classList.remove("visible");
                pointerIcon.classList.remove("visible");
                
                // Establecer el siguiente icono
                if (currentIcon === "tree") {
                    currentIcon = "water";
                    waterIcon.classList.add("visible");
                } else if (currentIcon === "water") {
                    currentIcon = "industry";
                    industryIcon.classList.add("visible");
                } else {
                    currentIcon = "tree";
                    treeIcon.classList.add("visible");
                }
            }
            
            // Iniciar ciclo de iconos
            iconInterval = setInterval(cycleIcon, 3000);
            
            // Función para alternar el menú
            function toggleMenu() {
                isOpen = !isOpen;
                
                if (isOpen) {
                    // Mostrar opciones
                    optionsContainer.classList.add("visible");
                    
                    // Cambiar a icono de dedo señalando hacia arriba
                    treeIcon.classList.remove("visible");
                    waterIcon.classList.remove("visible");
                    industryIcon.classList.remove("visible");
                    pointerIcon.classList.add("visible");
                    
                    // Cambiar clase del botón
                    toggleButton.classList.add("active");
                    
                    // Aplicar efecto de pulso inicial
                    toggleButton.classList.add("pulse");
                    setTimeout(() => {
                        toggleButton.classList.remove("pulse");
                    }, 1500);
                    
                } else {
                    // Ocultar opciones
                    optionsContainer.classList.remove("visible");
                    
                    // Volver al icono actual del ciclo
                    pointerIcon.classList.remove("visible");
                    toggleButton.classList.remove("active");
                    
                    if (currentIcon === "tree") {
                        treeIcon.classList.add("visible");
                    } else if (currentIcon === "water") {
                        waterIcon.classList.add("visible");
                    } else {
                        industryIcon.classList.add("visible");
                    }
                }
            }
            
            // Event Listeners
            toggleButton.addEventListener("click", toggleMenu);
            
            // Abrir chat de texto
            if (textButton) {
                textButton.addEventListener("click", function() {
                    // Cerrar menú
                    toggleMenu();
                    
                    // Detener ciclo de iconos
                    clearInterval(iconInterval);
                    
                    // Abrir chat de texto
                    document.getElementById("wp-dual-ai-text-chat-container").classList.add("wp-dual-ai-active");
                });
            }
            
            // Abrir chat de voz
            if (voiceButton) {
                voiceButton.addEventListener("click", function() {
                    // Cerrar menú
                    toggleMenu();
                    
                    // Detener ciclo de iconos
                    clearInterval(iconInterval);
                    
                    // Abrir chat de voz
                    document.getElementById("wp-dual-ai-voice-chat-container").classList.add("wp-dual-ai-active");
                });
            }
            
            // Listener para cerrar chats
            document.addEventListener("click", function(e) {
                if (e.target.classList.contains("wp-dual-ai-close-button")) {
                    // Reiniciar ciclo de iconos
                    if (!iconInterval) {
                        iconInterval = setInterval(cycleIcon, 3000);
                    }
                }
            });
            
            // Cerrar menú cuando se hace clic fuera
            document.addEventListener("click", function(e) {
                if (isOpen && !optionsContainer.contains(e.target) && e.target !== toggleButton && !toggleButton.contains(e.target)) {
                    toggleMenu();
                }
            });
            
            // Agregar efecto de pulso inicial para llamar la atención
            setTimeout(() => {
                toggleButton.classList.add("pulse");
                setTimeout(() => {
                    toggleButton.classList.remove("pulse");
                }, 4500);
            }, 1000);
        });
    })();
    </script>';
    
    $output .= '</div>';
    
    // Añadir interfaces de chat al footer
    add_action('wp_footer', array($this, 'add_chat_interfaces_to_footer'));
    
    return $output;
}

    /**
     * Add chat interfaces to footer when buttons are used.
     *
     * @since    1.0.0
     */
    public function add_chat_interfaces_to_footer() {
        $product_id = 0;
        
        // Get product ID if on product page
        if (function_exists('is_product') && is_product()) {
            global $product;
            if ($product) {
                $product_id = $product->get_id();
            }
        }
        
        // Add text chat interface
        $atts = array(
            'product_id' => $product_id,
            'title' => 'Chat with AI Assistant'
        );
        include plugin_dir_path(__FILE__) . 'partials/chat-interface.php';
        
        // Only add voice chat if credentials are configured
        if ($this->validate_elevenlabs_credentials()) {
            $atts = array(
                'product_id' => $product_id,
                'title' => 'Voice Assistant'
            );
            include plugin_dir_path(__FILE__) . 'partials/voice-interface.php';
        }
    }

    /**
     * Display chat interfaces on product pages.
     *
     * @since    1.0.0
     */
    public function display_chat_interfaces() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        $show_on_products = get_option('wp_dual_ai_show_on_products', 'buttons');
        
        if ($show_on_products === 'none') {
            return;
        }
        
        // Display based on settings
        if ($show_on_products === 'buttons' || $show_on_products === 'all') {
            echo do_shortcode('[dual_ai_chat_buttons product_id="' . $product_id . '"]');
        }
        
        if ($show_on_products === 'text' || $show_on_products === 'all') {
            echo do_shortcode('[dual_ai_text_chat product_id="' . $product_id . '"]');
        }
        
        if ($show_on_products === 'voice' || $show_on_products === 'all') {
            echo do_shortcode('[dual_ai_voice_chat product_id="' . $product_id . '"]');
        }
    }

    /**
     * AJAX handler for text chat.
     *
     * @since    1.0.0
     */
    public function handle_text_chat() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Get request data
        $request_data = isset($_POST['request_data']) ? $_POST['request_data'] : array();
        
        if (!is_array($request_data) || empty($request_data['messages'])) {
            wp_send_json_error(array('message' => 'Invalid request data'), 400);
        }
        
        // Send request to Anthropic API
        $response = $this->anthropic_api->send_chat_request($request_data);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code()
            ), 500);
        }
        
        wp_send_json_success($response);
    }

    public function init_voice_chat() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_dual_ai_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }
    
    // Verificar ElevenLabs credentials
    if (!$this->validate_elevenlabs_credentials()) {
        wp_send_json_error(array(
            'message' => 'ElevenLabs API credentials not configured',
            'code' => 'credentials_missing'
        ), 400);
    }
    
    // Get signed URL from ElevenLabs API with caching
    $cache_key = 'elevenlabs_signed_url_' . md5(get_option('wp_dual_ai_elevenlabs_agent_id', ''));
    $cached_response = get_transient($cache_key);
    
    if ($cached_response) {
        wp_send_json_success($cached_response);
        return;
    }
    
    $response = $this->elevenlabs_api->get_signed_url();
    
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => $response->get_error_message(),
            'code' => $response->get_error_code()
        ), 500);
    }
    
    // Cache the response for 5 minutes (signed URLs expire)
    $response_data = $response->get_data();
    set_transient($cache_key, $response_data, 5 * MINUTE_IN_SECONDS);
    
    wp_send_json_success($response_data);
}
    }

