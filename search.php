<?php
/**
 * Proxy de búsqueda para WordPress REST API con CORS
 *
 * Este archivo actúa como un proxy independiente que recibe parámetros de búsqueda
 * y los reenvía a los endpoints de WordPress REST API, configurando los headers
 * CORS correctos para permitir peticiones desde cualquier dominio.
 *
 * @author Carlos Leopoldo Magaña Zavala
 * @version 1.0.0
 */

// Limpiar headers CORS existentes para evitar duplicados
header_remove('Access-Control-Max-Age');

// Configurar headers CORS limpios
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Manejar peticiones OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir métodos GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Método no permitido',
        'message' => 'Solo se permiten peticiones GET',
    ]);
    exit();
}

/**
 * Configuración del proxy
 */
class WordPressSearchProxy {
    private $wordpress_url;
    private $allowed_endpoints;

    /**
     * Constructor del proxy
     */
    public function __construct() {
        // Detectar automáticamente la URL de WordPress
        $this->wordpress_url = $this->detect_wordpress_url();

        // Endpoints permitidos para el proxy
        $this->allowed_endpoints = [
            'posts' => '/wp/v2/posts',
            'pages' => '/wp/v2/pages',
            'all' => 'global', // Búsqueda global en posts y pages
        ];
    }

    /**
     * Detecta automáticamente la URL base de WordPress
     *
     * @return string URL base de WordPress
     */
    private function detect_wordpress_url() {
        // Intentar detectar desde la ubicación del archivo
        $current_dir = dirname(__FILE__);
        $wp_config_path = $current_dir;

        // Buscar wp-config.php subiendo directorios
        for ($i = 0; $i < 5; $i++) {
            if (file_exists($wp_config_path . '/wp-config.php')) {
                break;
            }
            $wp_config_path = dirname($wp_config_path);
        }

        // Construir URL base
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);

        // Si estamos en el directorio raíz de WP
        if (file_exists($current_dir . '/wp-config.php')) {
            return $protocol . '://' . $host . $script_dir;
        }

        // Si estamos en un subdirectorio (como wp-content/plugins)
        $relative_path = str_replace($wp_config_path, '', $current_dir);
        $base_path = str_replace($relative_path, '', $script_dir);

        return $protocol . '://' . $host . $base_path;
    }

    /**
     * Procesa la petición de búsqueda
     */
    public function handle_request() {
        try {
            // Obtener parámetros de la petición
            $endpoint_type = $_GET['endpoint'] ?? 'posts';
            $search_term = $_GET['search'] ?? ($_GET['s'] ?? '');
            $per_page = intval($_GET['per_page'] ?? ($_GET['limit'] ?? 9));
            $page = intval($_GET['page'] ?? ($_GET['paged'] ?? 1));
            $status = $_GET['status'] ?? 'publish';
            $slug = $_GET['slug'] ?? '';
            $id = $_GET['id'] ?? '';

            // Validar endpoint
            if (!isset($this->allowed_endpoints[$endpoint_type])) {
                throw new Exception('Endpoint no válido: ' . $endpoint_type);
            }

            // Construir URL del endpoint
            $endpoint_url = $this->build_endpoint_url(
                $endpoint_type,
                $search_term,
                $per_page,
                $page,
                $status,
                $slug,
                $id,
            );

            // Manejar búsqueda global
            if ($endpoint_type === 'all') {
                $response = $this->make_global_request($endpoint_url);
            } else {
                // Realizar petición al endpoint de WordPress
                $response = $this->make_request($endpoint_url);
            }

            // Devolver respuesta
            echo $response;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Error en la petición',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Construye la URL del endpoint con parámetros
     *
     * @param string $endpoint_type Tipo de endpoint
     * @param string $search_term Término de búsqueda
     * @param int $per_page Elementos por página
     * @param int $page Página actual
     * @param string $status Estado de los posts
     * @param string $slug Slug específico
     * @param string $id ID específico
     * @return string|array URL completa del endpoint o array de URLs para búsqueda global
     */
    private function build_endpoint_url(
        $endpoint_type,
        $search_term,
        $per_page,
        $page,
        $status,
        $slug,
        $id
    ) {
        // Manejar búsqueda global
        if ($endpoint_type === 'all') {
            return $this->build_global_search_urls($search_term, $per_page, $page, $status, $id);
        }

        $base_endpoint = $this->allowed_endpoints[$endpoint_type];
        $url = rtrim($this->wordpress_url, '/') . '/wp-json' . $base_endpoint;

        // Si se especifica un ID, añadirlo a la URL
        if (!empty($id)) {
            $url .= '/' . $id;
        }

        // Construir parámetros de consulta
        $params = [];

        if (!empty($search_term)) {
            $params['search'] = urlencode($search_term);
        }

        if ($per_page > 0 && $per_page <= 100) {
            $params['per_page'] = $per_page;
        }

        if ($page > 0) {
            $params['page'] = $page;
        }

        if (!empty($status) && in_array($status, ['publish', 'draft', 'private', 'any'])) {
            $params['status'] = $status;
        }

        // Añadir parámetros adicionales según el endpoint
        if ($endpoint_type === 'posts' || $endpoint_type === 'pages') {
            // Parámetros estándar de WordPress REST API
            $additional_params = [
                'orderby',
                'order',
                'author',
                'categories',
                'tags',
                'meta_key',
                'meta_value',
            ];
            foreach ($additional_params as $param) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $params[$param] = urlencode($_GET[$param]);
                }
            }
        }

        // Construir query string
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Construye las URLs para búsqueda global en posts y pages
     *
     * @param string $search_term Término de búsqueda
     * @param int $per_page Elementos por página
     * @param int $page Página actual
     * @param string $status Estado de los posts
     * @param string $id ID específico
     * @return array Array con URLs de posts y pages
     */
    private function build_global_search_urls($search_term, $per_page, $page, $status, $id) {
        $urls = [];

        // URL para posts
        $urls['posts'] = $this->build_single_endpoint_url(
            'posts',
            $search_term,
            $per_page,
            $page,
            $status,
            $id,
        );

        // URL para pages
        $urls['pages'] = $this->build_single_endpoint_url(
            'pages',
            $search_term,
            $per_page,
            $page,
            $status,
            $id,
        );

        return $urls;
    }

    /**
     * Construye URL para un endpoint específico
     *
     * @param string $endpoint_type Tipo de endpoint
     * @param string $search_term Término de búsqueda
     * @param int $per_page Elementos por página
     * @param int $page Página actual
     * @param string $status Estado de los posts
     * @param string $id ID específico
     * @return string URL completa del endpoint
     */
    private function build_single_endpoint_url(
        $endpoint_type,
        $search_term,
        $per_page,
        $page,
        $status,
        $id
    ) {
        $base_endpoint = $this->allowed_endpoints[$endpoint_type];
        $url = rtrim($this->wordpress_url, '/') . '/wp-json' . $base_endpoint;

        // Si se especifica un ID, añadirlo a la URL
        if (!empty($id)) {
            $url .= '/' . $id;
        }

        // Construir parámetros de consulta
        $params = [];

        if (!empty($search_term)) {
            $params['search'] = urlencode($search_term);
        }

        if ($per_page > 0 && $per_page <= 100) {
            $params['per_page'] = $per_page;
        }

        if ($page > 0) {
            $params['page'] = $page;
        }

        if (!empty($status) && in_array($status, ['publish', 'draft', 'private', 'any'])) {
            $params['status'] = $status;
        }

        // Añadir parámetros adicionales
        $additional_params = [
            'orderby',
            'order',
            'author',
            'categories',
            'tags',
            'meta_key',
            'meta_value',
        ];
        foreach ($additional_params as $param) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $params[$param] = urlencode($_GET[$param]);
            }
        }

        // Construir query string
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Realiza búsqueda global en posts y pages
     *
     * @param array $urls Array con URLs de posts y pages
     * @return string Respuesta JSON combinada
     * @throws Exception Si hay error en las peticiones
     */
    private function make_global_request($urls) {
        $combined_results = [];

        try {
            // Realizar petición a posts
            $posts_response = $this->make_request($urls['posts']);
            $posts_data = json_decode($posts_response, true);

            if ($posts_data && is_array($posts_data)) {
                // Agregar tipo de contenido a cada post
                foreach ($posts_data as &$post) {
                    $post['content_type'] = 'post';
                }
                $combined_results = array_merge($combined_results, $posts_data);
            }

            // Realizar petición a pages
            $pages_response = $this->make_request($urls['pages']);
            $pages_data = json_decode($pages_response, true);

            if ($pages_data && is_array($pages_data)) {
                // Agregar tipo de contenido a cada página
                foreach ($pages_data as &$page) {
                    $page['content_type'] = 'page';
                }
                $combined_results = array_merge($combined_results, $pages_data);
            }

            // Ordenar por fecha de modificación (más reciente primero)
            usort($combined_results, function ($a, $b) {
                $date_a = strtotime($a['modified'] ?? ($a['date'] ?? '1970-01-01'));
                $date_b = strtotime($b['modified'] ?? ($b['date'] ?? '1970-01-01'));
                return $date_b - $date_a;
            });
        } catch (Exception $e) {
            throw new Exception('Error en búsqueda global: ' . $e->getMessage());
        }

        return json_encode($combined_results, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Realiza la petición HTTP al endpoint de WordPress
     *
     * @param string $url URL del endpoint
     * @return string Respuesta JSON
     * @throws Exception Si hay error en la petición
     */
    private function make_request($url) {
        // Usar cURL si está disponible
        if (function_exists('curl_init')) {
            return $this->make_curl_request($url);
        }

        // Fallback a file_get_contents
        return $this->make_file_request($url);
    }

    /**
     * Realiza petición usando cURL
     *
     * @param string $url URL del endpoint
     * @return string Respuesta JSON
     * @throws Exception Si hay error en la petición
     */
    private function make_curl_request($url) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'WordPress Search Proxy/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false || !empty($error)) {
            throw new Exception('Error cURL: ' . $error);
        }

        if ($http_code >= 400) {
            throw new Exception('Error HTTP: ' . $http_code);
        }

        return $response;
    }

    /**
     * Realiza petición usando file_get_contents
     *
     * @param string $url URL del endpoint
     * @return string Respuesta JSON
     * @throws Exception Si hay error en la petición
     */
    private function make_file_request($url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'User-Agent: WordPress Search Proxy/1.0',
                ],
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception('Error al realizar la petición HTTP');
        }

        return $response;
    }

    /**
     * Muestra información de ayuda sobre el uso del proxy
     */
    public function show_help() {
        $help = [
            'title' => 'WordPress Search Proxy API',
            'version' => '1.0.0',
            'description' => 'Proxy para realizar búsquedas en WordPress REST API con soporte CORS',
            'base_url' => $this->wordpress_url,
            'usage' => [
                'endpoint' => 'Tipo de endpoint (posts, pages, all)',
                'search' => 'Término de búsqueda',
                'per_page' => 'Elementos por página (1-100, default: 9)',
                'page' => 'Número de página (default: 1)',
                'status' => 'Estado de los posts (publish, draft, private, any)',
                'id' => 'ID específico',
            ],
            'examples' => [
                'Buscar posts' => '?endpoint=posts&search=ejemplo&per_page=5',
                'Buscar páginas' => '?endpoint=pages&search=contacto',
                'Búsqueda global' => '?endpoint=all&search=wordpress&per_page=10',
                'Post por ID' => '?endpoint=posts&id=123',
                'Página por ID' => '?endpoint=pages&id=456',
            ],
            'allowed_endpoints' => array_keys($this->allowed_endpoints),
        ];

        echo json_encode($help, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// Inicializar y procesar la petición
$proxy = new WordPressSearchProxy();

// Si no hay parámetros, mostrar ayuda
if (empty($_GET) || isset($_GET['help'])) {
    $proxy->show_help();
} else {
    $proxy->handle_request();
}
?>
