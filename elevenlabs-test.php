<?php
// Coloca este archivo en la raíz del plugin
require_once '../../../wp-load.php'; // Carga WordPress

// Verifica que el plugin esté cargado
echo "<h1>ElevenLabs Integration Test</h1>";

// Verifica que las clases existan
if (class_exists('WP_Dual_AI_ElevenLabs')) {
    echo "<p>✅ Clase WP_Dual_AI_ElevenLabs encontrada</p>";
} else {
    echo "<p>❌ Clase WP_Dual_AI_ElevenLabs NO encontrada. Verifica la carga del plugin.</p>";
}

// Verifica las opciones de configuración
echo "<h2>Configuración</h2>";
echo "<p>API Key: " . (get_option('wp_dual_ai_elevenlabs_api_key') ? "✅ Configurada" : "❌ No configurada") . "</p>";
echo "<p>Agent ID: " . (get_option('wp_dual_ai_elevenlabs_agent_id') ? "✅ Configurado" : "❌ No configurado") . "</p>";

// Verifica registro de endpoints REST
echo "<h2>REST API</h2>";
$routes = rest_get_server()->get_routes();
$found = false;
foreach ($routes as $route => $handlers) {
    if (strpos($route, 'wp-dual-ai/v1') !== false) {
        echo "<p>✅ Ruta encontrada: $route</p>";
        $found = true;
    }
}
if (!$found) {
    echo "<p>❌ No se encontraron rutas wp-dual-ai/v1</p>";
}

// Intenta obtener una URL firmada directamente
if (class_exists('WP_Dual_AI_ElevenLabs')) {
    echo "<h2>Prueba de API</h2>";
    try {
        $elevenlabs = new WP_Dual_AI_ElevenLabs();
        $result = $elevenlabs->get_signed_url();
        if (is_wp_error($result)) {
            echo "<p>❌ Error: " . $result->get_error_message() . "</p>";
        } else {
            echo "<p>✅ URL firmada obtenida correctamente</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Excepción: " . $e->getMessage() . "</p>";
    }
}
