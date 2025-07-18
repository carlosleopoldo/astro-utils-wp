<?php

/**
 * Plugin Name: Astro-Utils-WP
 * Plugin URI: https://github.com/carlsoleopoldo/astro-utils-wp
 * Description: Utilidades para integrar WordPress con Astro, incluyendo exposición de meta fields de Elementor y endpoints personalizados para Landing Pages
 * Version: 1.0.3
 * Author: Carlos Leopoldo Magaña Zavala
 * Author URI: https://carlsoleopoldo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: astro-utils
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}

class IEEG_Astro_Utils
{
    const VERSION = '1.0.3';
    private static $instance = null;

    private function __construct()
    {
        $this->define_constants();
        $this->init_hooks();
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function define_constants()
    {
        define('IEEG_ASTRO_UTILS_VERSION', self::VERSION);
        define('IEEG_ASTRO_UTILS_PLUGIN_FILE', __FILE__);
        define('IEEG_ASTRO_UTILS_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('IEEG_ASTRO_UTILS_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('IEEG_ASTRO_UTILS_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }

    private function init_hooks()
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'expose_cpts_to_rest'], 5);
        add_action('rest_api_init', [$this, 'register_rest_api_extensions']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            'astro-utils',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function expose_cpts_to_rest()
    {
        add_filter(
            'register_post_type_args',
            function ($args, $post_type) {
                if (in_array($post_type, ['elementor_library', 'e-landing-page'], true)) {
                    $args['public']                = true;
                    $args['show_in_rest']          = true;
                    $args['rest_base']             = ('elementor_library' === $post_type)
                        ? 'elementor-library'
                        : 'e-landing-page';
                    $args['rest_controller_class'] = 'WP_REST_Posts_Controller';
                }
                return $args;
            },
            20,
            2
        );
    }

    public function register_rest_api_extensions()
    {
        // Campos existentes...
        $this->register_elementor_meta_fields();
        $this->register_elementor_library_fields();
        $this->register_custom_meta_endpoint();
        $this->register_elementor_templates_endpoint();

        // Campos para e-landing-page
        register_rest_field(
            'e-landing-page',
            'astro_meta',
            [
                'get_callback'    => [$this, 'get_all_meta_fields'],
                'update_callback' => null,
                'schema' => [
                    'description' => __('Todos los meta fields seguros', 'astro-utils'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                ],
            ]
        );

        // Endpoint listado de Landing Pages
        register_rest_route('wp/v2', '/e-landing-page', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_landing_pages'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint detalle de una Landing Page
        register_rest_route('wp/v2', '/e-landing-page/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_landing_page'],
            'permission_callback' => '__return_true',
        ]);
    }

    /** Registrar meta fields genéricos */
    public function register_elementor_meta_fields()
    {
        register_rest_field(
            ['page', 'post'],
            'elementor_meta',
            [
                'get_callback' => [$this, 'get_elementor_meta_fields'],
                'update_callback' => null,
                'schema' => ['description' => __('Meta fields de Elementor', 'ieeg-astro-utils'), 'type' => 'object', 'context' => ['view', 'edit']],
            ]
        );
        register_rest_field(
            ['page', 'post'],
            'meta',
            [
                'get_callback' => [$this, 'get_all_meta_fields'],
                'update_callback' => null,
                'schema' => ['description' => __('Todos los meta fields', 'ieeg-astro-utils'), 'type' => 'object', 'context' => ['view', 'edit']],
            ]
        );
    }

    /**
     * Registrar campos específicos de elementor_library
     */
    public function register_elementor_library_fields()
    {
        // Añadir el campo template_type a la respuesta
        register_rest_field('elementor_library', 'template_type', array(
            'get_callback' => array($this, 'get_elementor_template_type'),
            'schema' => array(
                'description' => __('Tipo de plantilla de Elementor', 'ieeg-astro-utils'),
                'type' => 'string',
                'context' => array('view', 'edit'),
            ),
        ));

        // Añadir el contenido de Elementor
        register_rest_field('elementor_library', 'elementor_data', array(
            'get_callback' => array($this, 'get_elementor_data'),
            'schema' => array(
                'description' => __('Datos de Elementor en formato JSON', 'ieeg-astro-utils'),
                'type' => 'string',
                'context' => array('view', 'edit'),
            ),
        ));

        // Añadir condiciones de display (si existen)
        register_rest_field('elementor_library', 'display_conditions', array(
            'get_callback' => array($this, 'get_elementor_display_conditions'),
            'schema' => array(
                'description' => __('Condiciones de visualización de Elementor', 'ieeg-astro-utils'),
                'type' => 'array',
                'context' => array('view', 'edit'),
            ),
        ));

        // Añadir todos los meta fields de elementor_library
        register_rest_field('elementor_library', 'meta', array(
            'get_callback' => array($this, 'get_elementor_library_meta'),
            'schema' => array(
                'description' => __('Meta fields de la plantilla', 'ieeg-astro-utils'),
                'type' => 'object',
                'context' => array('view', 'edit'),
            ),
        ));
    }

    /**
     * Obtener tipo de plantilla de Elementor
     * 
     * @param array $post Datos del post
     * @return string Tipo de plantilla
     */
    public function get_elementor_template_type($post)
    {
        return get_post_meta($post['id'], '_elementor_template_type', true);
    }

    /**
     * Obtener datos de Elementor
     * 
     * @param array $post Datos del post
     * @return string Datos de Elementor
     */
    public function get_elementor_data($post)
    {
        return get_post_meta($post['id'], '_elementor_data', true);
    }

    /**
     * Obtener condiciones de visualización de Elementor
     * 
     * @param array $post Datos del post
     * @return array Condiciones de visualización
     */
    public function get_elementor_display_conditions($post)
    {
        return get_post_meta($post['id'], '_elementor_conditions', true);
    }

    /**
     * Obtener todos los meta fields de elementor_library
     * 
     * @param array $post Datos del post
     * @return array Meta fields de la plantilla
     */
    public function get_elementor_library_meta($post)
    {
        $all_meta = get_post_meta($post['id']);
        $filtered_meta = array();

        // Meta fields específicos de Elementor Library
        $elementor_library_keys = array(
            '_elementor_template_type',
            '_elementor_data',
            '_elementor_conditions',
            '_elementor_edit_mode',
            '_elementor_page_settings',
            '_elementor_css',
            '_elementor_version',
            '_wp_page_template'
        );

        foreach ($elementor_library_keys as $key) {
            if (isset($all_meta[$key])) {
                $filtered_meta[$key] = is_array($all_meta[$key]) ? $all_meta[$key][0] : $all_meta[$key];
            }
        }

        return $filtered_meta;
    }

    /**
     * Obtener meta fields específicos de Elementor
     * 
     * @param array $post Datos del post
     * @return array Meta fields de Elementor
     */
    public function get_elementor_meta_fields($post)
    {
        $meta_fields = array();

        // Lista de meta fields de Elementor que queremos exponer
        $elementor_meta_keys = array(
            '_elementor_page_settings',
            '_elementor_hide_title',
            '_hide_page_title',
            'hide_title',
            '_elementor_template_type',
            '_elementor_edit_mode',
            '_elementor_data'
        );

        foreach ($elementor_meta_keys as $key) {
            $value = get_post_meta($post['id'], $key, true);
            if (!empty($value)) {
                $meta_fields[$key] = $value;
            }
        }

        return $meta_fields;
    }

    /**
     * Obtener todos los meta fields (filtrados por seguridad)
     * 
     * @param array $post Datos del post
     * @return array Meta fields filtrados
     */
    public function get_all_meta_fields($post)
    {
        $all_meta = get_post_meta($post['id']);
        $filtered_meta = array();

        // Solo incluir meta fields que no sean sensibles
        $allowed_prefixes = array('_elementor', '_hide', 'hide_', '_yoast', '_wp_page');

        foreach ($all_meta as $key => $value) {
            $include = false;

            // Verificar si el meta field tiene un prefijo permitido
            foreach ($allowed_prefixes as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $include = true;
                    break;
                }
            }

            if ($include) {
                // Obtener solo el primer valor (get_post_meta devuelve arrays)
                $filtered_meta[$key] = is_array($value) ? $value[0] : $value;
            }
        }

        return $filtered_meta;
    }

    /**
     * Agregar endpoint personalizado para obtener meta fields
     */
    public function register_custom_meta_endpoint()
    {
        register_rest_route('wp/v2', '/pages/(?P<id>\d+)/meta', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_page_meta_endpoint'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
            'permission_callback' => '__return_true', // Permitir acceso público
        ));
    }

    /**
     * Registrar endpoint personalizado para plantillas de Elementor
     */
    public function register_elementor_templates_endpoint()
    {
        // Endpoint para obtener plantillas por tipo
        register_rest_route('elementor/v1', '/templates/(?P<type>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_elementor_templates_by_type'),
            'args' => array(
                'type' => array(
                    'validate_callback' => function ($param) {
                        return in_array($param, array('page', 'header', 'footer', 'single', 'archive', 'popup', 'section', 'widget'));
                    }
                ),
            ),
            'permission_callback' => '__return_true',
        ));

        // Endpoint para obtener todas las plantillas
        register_rest_route('elementor/v1', '/templates', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_elementor_templates'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Callback para obtener plantillas por tipo
     * 
     * @param WP_REST_Request $request Objeto de la petición
     * @return WP_REST_Response Respuesta con las plantillas
     */
    public function get_elementor_templates_by_type($request)
    {
        $template_type = $request['type'];
        $templates = $this->get_elementor_templates($template_type);

        $response_data = array();
        foreach ($templates as $template) {
            $response_data[] = array(
                'id' => $template->ID,
                'title' => $template->post_title,
                'slug' => $template->post_name,
                'template_type' => get_post_meta($template->ID, '_elementor_template_type', true),
                'date_created' => $template->post_date,
                'date_modified' => $template->post_modified,
                'elementor_data' => get_post_meta($template->ID, '_elementor_data', true),
                'conditions' => get_post_meta($template->ID, '_elementor_conditions', true),
                'status' => $template->post_status,
            );
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Callback para obtener todas las plantillas
     * 
     * @param WP_REST_Request $request Objeto de la petición
     * @return WP_REST_Response Respuesta con todas las plantillas
     */
    public function get_all_elementor_templates($request)
    {
        $templates = $this->get_elementor_templates();

        $response_data = array();
        foreach ($templates as $template) {
            $response_data[] = array(
                'id' => $template->ID,
                'title' => $template->post_title,
                'slug' => $template->post_name,
                'template_type' => get_post_meta($template->ID, '_elementor_template_type', true),
                'date_created' => $template->post_date,
                'date_modified' => $template->post_modified,
                'status' => $template->post_status,
            );
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Función auxiliar para obtener plantillas de Elementor
     * 
     * @param string $template_type Tipo de plantilla opcional
     * @return array Lista de plantillas
     */
    private function get_elementor_templates($template_type = '')
    {
        $args = array(
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft')
        );

        if (!empty($template_type)) {
            $args['meta_query'] = array(
                array(
                    'key' => '_elementor_template_type',
                    'value' => $template_type,
                    'compare' => '='
                )
            );
        }

        return get_posts($args);
    }

    /**
     * Callback para el endpoint personalizado de meta fields
     * 
     * @param WP_REST_Request $request Objeto de la petición REST
     * @return array|WP_Error Datos de meta fields o error
     */
    public function get_page_meta_endpoint($request)
    {
        $page_id = $request['id'];
        $keys = $request->get_param('keys');

        // Verificar que la página existe
        if (!get_post($page_id)) {
            return new WP_Error(
                'page_not_found',
                __('Página no encontrada', 'ieeg-astro-utils'),
                array('status' => 404)
            );
        }

        if ($keys) {
            $meta_keys = explode(',', $keys);
            $meta_data = array();

            foreach ($meta_keys as $key) {
                $value = get_post_meta($page_id, trim($key), true);
                if (!empty($value)) {
                    $meta_data[trim($key)] = $value;
                }
            }

            return $meta_data;
        }

        // Si no se especifican keys, devolver meta fields de Elementor
        return $this->get_elementor_meta_fields(array('id' => $page_id));
    }

    /**
     * Listado de Landing Pages (templates type = 'landing-page')
     */
    public function get_landing_pages($request)
    {
        $per_page = $request->get_param('per_page') ?: 10;
        $page     = $request->get_param('page') ?: 1;

        $query = new WP_Query([
            'post_type'      => 'e-landing-page',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'meta_query'     => [[
                'key'     => '_elementor_template_type',
                'value'   => 'landing-page',
                'compare' => '=',
            ]],
        ]);

        $data = [];
        foreach ($query->posts as $post) {
            $data[] = [
                'id'            => $post->ID,
                'title'         => $post->post_title,
                'slug'          => $post->post_name,
                'date_created'  => $post->post_date,
                'date_modified' => $post->post_modified,
            ];
        }

        return rest_ensure_response($data);
    }

    /**
     * Detalle de una Landing Page específica
     */
    public function get_landing_page($request)
    {
        $id   = (int) $request['id'];
        $post = get_post($id);

        if (
            ! $post ||
            'e-landing-page' !== $post->post_type
        ) {
            return new WP_Error(
                'landing_not_found',
                __('Landing page no encontrada', 'astro-utils'),
                ['status' => 404]
            );
        }

        $response = [
            'id'            => $post->ID,
            'title'         => $post->post_title,
            'slug'          => $post->post_name,
            'date_created'  => $post->post_date,
            'date_modified' => $post->post_modified,
            'elementor_data' => get_post_meta($id, '_elementor_data', true),
            'meta'          => $this->get_elementor_library_meta(['id' => $id]),
        ];

        return rest_ensure_response($response);
    }

    public function activate()
    {
        flush_rewrite_rules();
        add_option('ieeg_astro_utils_version', self::VERSION);
        error_log('IEEG-Astro-Utils plugin activado - Versión: ' . self::VERSION);
    }

    public function deactivate()
    {
        flush_rewrite_rules();
        error_log('IEEG-Astro-Utils plugin desactivado');
    }
}

function ieeg_astro_utils()
{
    return IEEG_Astro_Utils::get_instance();
}

ieeg_astro_utils();
