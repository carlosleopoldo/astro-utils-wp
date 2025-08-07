<?php

/**
 * Plugin Name: Astro-Utils-WP
 * Plugin URI: https://github.com/carlsoleopoldo/astro-utils-wp
 * Description: Utilidades para integrar WordPress con Astro. Expone campos de Elementor, permite filtrar páginas por template y añade CPT elementor_library a REST API.
 * Version: 1.1.0
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
    exit();
}

class IEEG_Astro_Utils {
    const VERSION = '1.1.0';
    private static $instance = null;

    /**
     * Constructor privado para implementar patrón Singleton
     *
     * @return void
     */
    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia única de la clase (patrón Singleton)
     *
     * @return IEEG_Astro_Utils Instancia única de la clase
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define las constantes utilizadas por el plugin
     *
     * @return void
     */
    private function define_constants() {
        define('IEEG_ASTRO_UTILS_VERSION', self::VERSION);
        define('IEEG_ASTRO_UTILS_PLUGIN_FILE', __FILE__);
        define('IEEG_ASTRO_UTILS_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('IEEG_ASTRO_UTILS_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('IEEG_ASTRO_UTILS_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }

    /**
     * Inicializa los hooks de WordPress
     *
     * @return void
     */
    private function init_hooks() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'expose_cpts_to_rest'], 5);
        add_action('rest_api_init', [$this, 'register_elementor_landing_pages_endpoint']);
        add_action('rest_api_init', [$this, 'register_rest_api_extensions']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_execute_webhook', [$this, 'handle_webhook_execution']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Carga el dominio de texto para internacionalización
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'astro-utils',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/',
        );
    }

    /**
     * Exponer elementor_library y e-landing-page a REST API para mantener formato nativo
     *
     * @return void
     */
    public function expose_cpts_to_rest() {
        add_filter(
            'register_post_type_args',
            function ($args, $post_type) {
                if (in_array($post_type, ['elementor_library', 'e-landing-page'], true)) {
                    $args['public'] = true;
                    $args['show_in_rest'] = true;
                    $args['rest_base'] =
                        'elementor_library' === $post_type ? 'elementor-library' : 'e-landing-page';
                    $args['rest_controller_class'] = 'WP_REST_Posts_Controller';
                }
                return $args;
            },
            20,
            2,
        );
    }

    /**
     * Registra extensiones para la API REST de WordPress
     *
     * Añade campos y endpoints personalizados para Elementor y landing pages
     *
     * @return void
     */
    public function register_rest_api_extensions() {
        // Campos existentes...
        $this->register_elementor_meta_fields();
        $this->register_elementor_library_fields();
        $this->register_custom_meta_endpoint();
        $this->register_elementor_templates_endpoint();

        // Campos para e-landing-page
        register_rest_field('e-landing-page', 'astro_meta', [
            'get_callback' => [$this, 'get_all_meta_fields'],
            'update_callback' => null,
            'schema' => [
                'description' => __('Todos los meta fields seguros', 'astro-utils'),
                'type' => 'object',
                'context' => ['view', 'edit'],
            ],
        ]);

        // Endpoint listado de Landing Pages
        register_rest_route('wp/v2', '/e-landing-page', [
            'methods' => 'GET',
            'callback' => [$this, 'get_landing_pages'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint detalle de una Landing Page
        register_rest_route('wp/v2', '/e-landing-page/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_landing_page'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Registra campos meta genéricos para Elementor en la API REST
     *
     * @return void
     */
    public function register_elementor_meta_fields() {
        register_rest_field(['page', 'post'], 'elementor_meta', [
            'get_callback' => [$this, 'get_elementor_meta_fields'],
            'update_callback' => null,
            'schema' => [
                'description' => __('Meta fields de Elementor', 'ieeg-astro-utils'),
                'type' => 'object',
                'context' => ['view', 'edit'],
            ],
        ]);
        register_rest_field(['page', 'post'], 'meta', [
            'get_callback' => [$this, 'get_all_meta_fields'],
            'update_callback' => null,
            'schema' => [
                'description' => __('Todos los meta fields', 'ieeg-astro-utils'),
                'type' => 'object',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * Registra campos específicos de elementor_library en la API REST
     *
     * @return void
     */
    public function register_elementor_library_fields() {
        // Añadir el campo template_type a la respuesta
        register_rest_field('elementor_library', 'template_type', [
            'get_callback' => [$this, 'get_elementor_template_type'],
            'schema' => [
                'description' => __('Tipo de plantilla de Elementor', 'ieeg-astro-utils'),
                'type' => 'string',
                'context' => ['view', 'edit'],
            ],
        ]);

        // Añadir el contenido de Elementor
        register_rest_field('elementor_library', 'elementor_data', [
            'get_callback' => [$this, 'get_elementor_data'],
            'schema' => [
                'description' => __('Datos de Elementor en formato JSON', 'ieeg-astro-utils'),
                'type' => 'string',
                'context' => ['view', 'edit'],
            ],
        ]);

        // Añadir condiciones de display (si existen)
        register_rest_field('elementor_library', 'display_conditions', [
            'get_callback' => [$this, 'get_elementor_display_conditions'],
            'schema' => [
                'description' => __(
                    'Condiciones de visualización de Elementor',
                    'ieeg-astro-utils',
                ),
                'type' => 'array',
                'context' => ['view', 'edit'],
            ],
        ]);

        // Añadir todos los meta fields de elementor_library
        register_rest_field('elementor_library', 'meta', [
            'get_callback' => [$this, 'get_elementor_library_meta'],
            'schema' => [
                'description' => __('Meta fields de la plantilla', 'ieeg-astro-utils'),
                'type' => 'object',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * Obtener tipo de plantilla de Elementor
     *
     * @param array $post Datos del post
     * @return string Tipo de plantilla
     */
    public function get_elementor_template_type($post) {
        return get_post_meta($post['id'], '_elementor_template_type', true);
    }

    /**
     * Obtener datos de Elementor
     *
     * @param array $post Datos del post
     * @return string Datos de Elementor
     */
    public function get_elementor_data($post) {
        return get_post_meta($post['id'], '_elementor_data', true);
    }

    /**
     * Obtener condiciones de visualización de Elementor
     *
     * @param array $post Datos del post
     * @return array Condiciones de visualización
     */
    public function get_elementor_display_conditions($post) {
        return get_post_meta($post['id'], '_elementor_conditions', true);
    }

    /**
     * Obtener todos los meta fields de elementor_library
     *
     * @param array $post Datos del post
     * @return array Meta fields de la plantilla
     */
    public function get_elementor_library_meta($post) {
        $all_meta = get_post_meta($post['id']);
        $filtered_meta = [];

        // Meta fields específicos de Elementor Library
        $elementor_library_keys = [
            '_elementor_template_type',
            '_elementor_data',
            '_elementor_conditions',
            '_elementor_edit_mode',
            '_elementor_page_settings',
            '_elementor_css',
            '_elementor_version',
            '_wp_page_template',
        ];

        foreach ($elementor_library_keys as $key) {
            if (isset($all_meta[$key])) {
                $filtered_meta[$key] = is_array($all_meta[$key])
                    ? $all_meta[$key][0]
                    : $all_meta[$key];
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
    public function get_elementor_meta_fields($post) {
        $meta_fields = [];

        // Lista de meta fields de Elementor que queremos exponer
        $elementor_meta_keys = [
            '_elementor_page_settings',
            '_elementor_hide_title',
            '_hide_page_title',
            'hide_title',
            '_elementor_template_type',
            '_elementor_edit_mode',
            '_elementor_data',
        ];

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
    public function get_all_meta_fields($post) {
        $all_meta = get_post_meta($post['id']);
        $filtered_meta = [];

        // Solo incluir meta fields que no sean sensibles
        $allowed_prefixes = ['_elementor', '_hide', 'hide_', '_yoast', '_wp_page'];

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
    public function register_custom_meta_endpoint() {
        register_rest_route('wp/v2', '/pages/(?P<id>\d+)/meta', [
            'methods' => 'GET',
            'callback' => [$this, 'get_page_meta_endpoint'],
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ],
            ],
            'permission_callback' => '__return_true', // Permitir acceso público
        ]);
    }

    /**
     * Registrar endpoints para Landing Pages integrados en Elementor
     *
     * @return void
     */
    public function register_elementor_landing_pages_endpoint() {
        // Endpoint para listar todas las landing pages
        register_rest_route('elementor/v1', '/landing-pages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_elementor_landing_pages'],
            'args' => [
                'per_page' => [
                    'default' => 10,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    },
                ],
                'page' => [
                    'default' => 1,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0;
                    },
                ],
                'status' => [
                    'default' => 'publish',
                    'validate_callback' => function ($param) {
                        return in_array($param, ['publish', 'draft', 'private', 'any']);
                    },
                ],
                'search' => [
                    'default' => '',
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
            ],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint para obtener detalle de una landing page por slug
        register_rest_route('elementor/v1', '/landing-pages/(?P<slug>[a-zA-Z0-9-_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_elementor_landing_page_by_slug'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param) && !empty($param);
                    },
                ],
            ],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Registrar endpoint personalizado para plantillas de Elementor
     */
    public function register_elementor_templates_endpoint() {
        // Endpoint para obtener detalle de un template específico por ID
        register_rest_route('elementor/v1', '/templates/(?P<type>[a-zA-Z0-9-]+)/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_elementor_template_detail'],
            'args' => [
                'type' => [
                    'validate_callback' => function ($param) {
                        return in_array($param, [
                            'page',
                            'header',
                            'footer',
                            'single',
                            'archive',
                            'popup',
                            'section',
                            'widget',
                            'landing-page',
                        ]);
                    },
                ],
                'id' => [
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ],
            ],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint para obtener plantillas por tipo
        register_rest_route('elementor/v1', '/templates/(?P<type>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_elementor_templates_by_type'],
            'args' => [
                'type' => [
                    'validate_callback' => function ($param) {
                        return in_array($param, [
                            'page',
                            'header',
                            'footer',
                            'single',
                            'archive',
                            'popup',
                            'section',
                            'widget',
                        ]);
                    },
                ],
            ],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint para obtener todas las plantillas
        register_rest_route('elementor/v1', '/templates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_elementor_templates'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Obtiene listado de Landing Pages con filtros avanzados
     *
     * @param WP_REST_Request $request Objeto de la petición REST
     * @return WP_REST_Response Respuesta con el listado de landing pages
     */
    public function get_elementor_landing_pages($request) {
        $per_page = $request->get_param('per_page') ?: 10;
        $page = $request->get_param('page') ?: 1;
        $status = $request->get_param('status') ?: 'publish';
        $search = $request->get_param('search') ?: '';

        $query_args = [
            'post_type' => 'e-landing-page',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => $status === 'any' ? ['publish', 'draft', 'private'] : $status,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'landing-page',
                    'compare' => '=',
                ],
            ],
        ];

        // Agregar búsqueda si se proporciona
        if (!empty($search)) {
            $query_args['s'] = $search;
        }

        $query = new WP_Query($query_args);

        $data = [];
        foreach ($query->posts as $post) {
            $data[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'date_created' => $post->post_date,
                'date_modified' => $post->post_modified,
                'author_id' => $post->post_author,
                'template_type' => get_post_meta($post->ID, '_elementor_template_type', true),
                'permalink' => get_permalink($post->ID),
                'edit_url' => admin_url('post.php?post=' . $post->ID . '&action=edit'),
                'featured_image_id' => get_post_thumbnail_id($post->ID),
                'featured_image_url' => get_the_post_thumbnail_url($post->ID, 'full'),

                // Meta básico para listado
                'elementor_edit_mode' => get_post_meta($post->ID, '_elementor_edit_mode', true),
                'elementor_version' => get_post_meta($post->ID, '_elementor_version', true),
            ];
        }

        // Información de paginación
        $response = rest_ensure_response($data);
        $response->header('X-WP-Total', $query->found_posts);
        $response->header('X-WP-TotalPages', $query->max_num_pages);

        return $response;
    }

    /**
     * Método alternativo más robusto para renderizar contenido de Elementor
     * Agregar este método a tu clase
     */
    private function render_elementor_content_advanced($post_id) {
        // Verificaciones iniciales
        if (!did_action('elementor/loaded') || !class_exists('\Elementor\Plugin')) {
            return apply_filters('the_content', get_post_field('post_content', $post_id));
        }

        // Verificar si el post fue construido con Elementor
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        if (empty($elementor_data)) {
            return apply_filters('the_content', get_post_field('post_content', $post_id));
        }

        // Configurar el contexto de WordPress
        global $post, $wp_query;
        $original_post = $post;
        $post = get_post($post_id);

        // Simular estar en la página para que los hooks funcionen
        $wp_query->is_singular = true;
        $wp_query->is_single = false;
        $wp_query->is_page = true;
        $wp_query->queried_object = $post;
        $wp_query->queried_object_id = $post_id;

        setup_postdata($post);

        try {
            // Método 1: Usar el builder content (más confiable)
            $elementor_frontend = \Elementor\Plugin::$instance->frontend;

            // Asegurar que los scripts y estilos estén encolados
            if (method_exists($elementor_frontend, 'enqueue_styles')) {
                $elementor_frontend->enqueue_styles();
            }

            // Obtener documento de Elementor
            $document = \Elementor\Plugin::$instance->documents->get($post_id);

            if ($document && $document->is_built_with_elementor()) {
                // Renderizar usando el documento
                $content = $document->get_content();

                // Si el contenido está vacío, usar el frontend builder
                if (empty($content)) {
                    $content = $elementor_frontend->get_builder_content_for_display($post_id, true);
                }

                // Aplicar filtros adicionales de WordPress
                $content = do_shortcode($content);
                $content = wptexturize($content);
                $content = convert_smilies($content);
                // $content = wpautop($content);

                return $content;
            }
        } catch (Exception $e) {
            error_log('Elementor render error: ' . $e->getMessage());
        } finally {
            // Restaurar contexto original
            $post = $original_post;
            wp_reset_postdata();
        }

        // Fallback a contenido estándar
        return apply_filters('the_content', get_post_field('post_content', $post_id));
    }

    /**
     * Obtiene el detalle completo de una Landing Page por slug
     * en formato compatible con WordPress REST API estándar
     *
     * @param WP_REST_Request $request Objeto de la petición REST
     * @return WP_REST_Response|WP_Error Respuesta con el detalle de la landing page o error
     */
    public function get_elementor_landing_page_by_slug($request) {
        $slug = $request['slug'];

        // Buscar la landing page por slug
        $query = new WP_Query([
            'post_type' => 'e-landing-page',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft', 'private'],
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'landing-page',
                    'compare' => '=',
                ],
            ],
        ]);

        if (!$query->have_posts()) {
            return new WP_Error(
                'landing_page_not_found',
                __('Landing page no encontrada', 'astro-utils'),
                ['status' => 404],
            );
        }

        $post = $query->posts[0];
        $post_id = $post->ID;

        // Configurar el contexto global para el renderizado
        global $wp_query;
        $original_query = $wp_query;
        $wp_query = $query;

        // Configurar el post global
        setup_postdata($post);

        // Aplicar filtros de contenido para renderizado completo
        // $rendered_content = apply_filters('the_content', $post->post_content);
        $rendered_content = $this->render_elementor_content_advanced($post_id);
        $rendered_title = apply_filters('the_title', $post->post_title, $post_id);
        $rendered_excerpt = apply_filters('the_excerpt', $post->post_excerpt);

        // Obtener autor info
        $author_data = get_userdata($post->post_author);

        // Preparar respuesta en formato WordPress REST API
        $response_data = [
            'id' => $post_id,
            'date' => $post->post_date,
            'date_gmt' => $post->post_date_gmt,
            'guid' => [
                'rendered' => get_permalink($post_id),
                'raw' => $post->guid,
            ],
            'modified' => $post->post_modified,
            'modified_gmt' => $post->post_modified_gmt,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'type' => $post->post_type,
            'link' => get_permalink($post_id),

            // Título renderizado
            'title' => [
                'rendered' => $rendered_title,
                'raw' => $post->post_title,
            ],

            // Contenido renderizado
            'content' => [
                'rendered' => $rendered_content,
                'raw' => $post->post_content,
                'protected' => !empty($post->post_password),
            ],

            // Excerpt renderizado
            'excerpt' => [
                'rendered' => $rendered_excerpt ?: wp_trim_excerpt('', $post),
                'raw' => $post->post_excerpt,
                'protected' => !empty($post->post_password),
            ],

            // Información del autor
            'author' => $post->post_author,
            'author_name' => $author_data ? $author_data->display_name : '',

            // Imagen destacada
            'featured_media' => get_post_thumbnail_id($post_id),
            'featured_media_src_url' => get_the_post_thumbnail_url($post_id, 'full'),

            // Estados del post
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'sticky' => is_sticky($post_id),
            'template' => get_page_template_slug($post_id),
            'parent' => $post->post_parent,
            'menu_order' => $post->menu_order,

            // Datos específicos de Elementor
            'elementor' => [
                'data' => get_post_meta($post_id, '_elementor_data', true),
                'version' => get_post_meta($post_id, '_elementor_version', true),
                'edit_mode' => get_post_meta($post_id, '_elementor_edit_mode', true),
                'page_settings' => get_post_meta($post_id, '_elementor_page_settings', true),
                'css' => get_post_meta($post_id, '_elementor_css', true),
                'template_type' => get_post_meta($post_id, '_elementor_template_type', true),
                'conditions' => get_post_meta($post_id, '_elementor_conditions', true),
            ],

            // URLs útiles para administración
            'admin_urls' => [
                'edit' => admin_url('post.php?post=' . $post_id . '&action=edit'),
                'preview' => get_preview_post_link($post_id),
                'elementor_edit' => admin_url('post.php?post=' . $post_id . '&action=elementor'),
            ],

            // Estadísticas del contenido
            'stats' => [
                'word_count' => str_word_count(strip_tags($rendered_content)),
                'reading_time' => ceil(str_word_count(strip_tags($rendered_content)) / 200),
                'character_count' => strlen(strip_tags($rendered_content)),
            ],

            // Metadatos personalizados completos
            'meta' => $this->get_formatted_meta_fields($post_id),

            // Enlaces relacionados (HAL-like)
            '_links' => [
                'self' => [
                    [
                        'href' => rest_url('astro-utils/v1/landing-pages/' . $slug),
                    ],
                ],
                'collection' => [
                    [
                        'href' => rest_url('astro-utils/v1/landing-pages'),
                    ],
                ],
                'about' => [
                    [
                        'href' => rest_url('wp/v2/types/e-landing-page'),
                    ],
                ],
                'author' => [
                    [
                        'embeddable' => true,
                        'href' => rest_url('wp/v2/users/' . $post->post_author),
                    ],
                ],
                'wp:featuredmedia' => get_post_thumbnail_id($post_id)
                    ? [
                        [
                            'embeddable' => true,
                            'href' => rest_url('wp/v2/media/' . get_post_thumbnail_id($post_id)),
                        ],
                    ]
                    : [],
            ],
        ];

        // Restaurar el contexto original
        wp_reset_postdata();
        $wp_query = $original_query;

        // Aplicar filtro para permitir modificaciones del response
        $response_data = apply_filters(
            'astro_utils_landing_page_detail_response',
            $response_data,
            $post_id,
            $slug,
        );

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Obtiene los meta fields formateados para la respuesta
     *
     * @param int $post_id ID del post
     * @return array Meta fields formateados
     */
    private function get_formatted_meta_fields($post_id) {
        $meta = get_post_meta($post_id);
        $formatted_meta = [];

        foreach ($meta as $key => $values) {
            // Formatear según el tipo de meta
            if (count($values) === 1) {
                $value = $values[0];

                // Intentar deserializar si es necesario
                if (is_serialized($value)) {
                    $value = maybe_unserialize($value);
                }

                $formatted_meta[$key] = $value;
            } else {
                // Multiple values
                $formatted_meta[$key] = array_map(function ($value) {
                    return is_serialized($value) ? maybe_unserialize($value) : $value;
                }, $values);
            }
        }

        return $formatted_meta;
    }

    /**
     * Función auxiliar para obtener landing pages con filtros personalizados
     *
     * @param array $args Argumentos adicionales para WP_Query
     * @return array Lista de landing pages
     */
    private function get_landing_pages_query($args = []) {
        $default_args = [
            'post_type' => 'e-landing-page',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft'],
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'landing-page',
                    'compare' => '=',
                ],
            ],
        ];

        $query_args = array_merge($default_args, $args);
        return get_posts($query_args);
    }

    /**
     * Callback para obtener el detalle de un template específico
     *
     * @param WP_REST_Request $request Objeto de la petición
     * @return WP_REST_Response|WP_Error Respuesta con el detalle del template o error
     */
    public function get_elementor_template_detail($request) {
        $template_id = (int) $request['id'];
        $template_type = $request['type'];

        // Verificar que el post existe y es del tipo correcto
        $post = get_post($template_id);

        if (!$post || 'elementor_library' !== $post->post_type) {
            return new WP_Error('template_not_found', __('Template no encontrado', 'astro-utils'), [
                'status' => 404,
            ]);
        }

        // Verificar que el template es del tipo solicitado
        $actual_template_type = get_post_meta($template_id, '_elementor_template_type', true);
        if ($actual_template_type !== $template_type) {
            return new WP_Error(
                'template_type_mismatch',
                sprintf(
                    __('El template no es del tipo "%s". Tipo actual: "%s"', 'astro-utils'),
                    $template_type,
                    $actual_template_type,
                ),
                ['status' => 400],
            );
        }

        // Preparar la respuesta completa con todos los datos del template
        $response_data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'template_type' => $actual_template_type,
            'date_created' => $post->post_date,
            'date_modified' => $post->post_modified,
            'author_id' => $post->post_author,

            // Datos específicos de Elementor
            'elementor_data' => get_post_meta($template_id, '_elementor_data', true),
            'elementor_version' => get_post_meta($template_id, '_elementor_version', true),
            'elementor_edit_mode' => get_post_meta($template_id, '_elementor_edit_mode', true),
            'elementor_page_settings' => get_post_meta(
                $template_id,
                '_elementor_page_settings',
                true,
            ),
            'elementor_css' => get_post_meta($template_id, '_elementor_css', true),

            // Condiciones de visualización
            'display_conditions' => get_post_meta($template_id, '_elementor_conditions', true),

            // Meta fields completos del template
            'meta' => $this->get_elementor_library_meta(['id' => $template_id]),

            // URLs útiles
            'permalink' => get_permalink($template_id),
            'edit_url' => admin_url('post.php?post=' . $template_id . '&action=edit'),

            // Información adicional
            'featured_image_id' => get_post_thumbnail_id($template_id),
            'featured_image_url' => get_the_post_thumbnail_url($template_id, 'full'),
        ];

        // Filtro para permitir modificaciones del response
        $response_data = apply_filters(
            'astro_utils_template_detail_response',
            $response_data,
            $template_id,
            $template_type,
        );

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Callback para obtener plantillas por tipo
     *
     * @param WP_REST_Request $request Objeto de la petición
     * @return WP_REST_Response Respuesta con las plantillas
     */
    public function get_elementor_templates_by_type($request) {
        $template_type = $request['type'];
        $templates = $this->get_elementor_templates($template_type);

        $response_data = [];
        foreach ($templates as $template) {
            $response_data[] = [
                'id' => $template->ID,
                'title' => $template->post_title,
                'slug' => $template->post_name,
                'template_type' => get_post_meta($template->ID, '_elementor_template_type', true),
                'date_created' => $template->post_date,
                'date_modified' => $template->post_modified,
                'elementor_data' => get_post_meta($template->ID, '_elementor_data', true),
                'conditions' => get_post_meta($template->ID, '_elementor_conditions', true),
                'status' => $template->post_status,
            ];
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Callback para obtener todas las plantillas
     *
     * @param WP_REST_Request $request Objeto de la petición
     * @return WP_REST_Response Respuesta con todas las plantillas
     */
    public function get_all_elementor_templates($request) {
        $templates = $this->get_elementor_templates();

        $response_data = [];
        foreach ($templates as $template) {
            $response_data[] = [
                'id' => $template->ID,
                'title' => $template->post_title,
                'slug' => $template->post_name,
                'template_type' => get_post_meta($template->ID, '_elementor_template_type', true),
                'date_created' => $template->post_date,
                'date_modified' => $template->post_modified,
                'status' => $template->post_status,
            ];
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Función auxiliar para obtener plantillas de Elementor
     *
     * @param string $template_type Tipo de plantilla opcional
     * @return array Lista de plantillas
     */
    private function get_elementor_templates($template_type = '') {
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft'],
        ];

        if (!empty($template_type)) {
            $args['meta_query'] = [
                [
                    'key' => '_elementor_template_type',
                    'value' => $template_type,
                    'compare' => '=',
                ],
            ];
        }

        return get_posts($args);
    }

    /**
     * Callback para el endpoint personalizado de meta fields
     *
     * @param WP_REST_Request $request Objeto de la petición REST
     * @return array|WP_Error Datos de meta fields o error
     */
    public function get_page_meta_endpoint($request) {
        $page_id = $request['id'];
        $keys = $request->get_param('keys');

        // Verificar que la página existe
        if (!get_post($page_id)) {
            return new WP_Error('page_not_found', __('Página no encontrada', 'ieeg-astro-utils'), [
                'status' => 404,
            ]);
        }

        if ($keys) {
            $meta_keys = explode(',', $keys);
            $meta_data = [];

            foreach ($meta_keys as $key) {
                $value = get_post_meta($page_id, trim($key), true);
                if (!empty($value)) {
                    $meta_data[trim($key)] = $value;
                }
            }

            return $meta_data;
        }

        // Si no se especifican keys, devolver meta fields de Elementor
        return $this->get_elementor_meta_fields(['id' => $page_id]);
    }

    /**
     * Obtiene un listado de Landing Pages (templates type = 'landing-page')
     *
     * @param WP_REST_Request $request Objeto de la petición REST
     * @return WP_REST_Response Respuesta con el listado de landing pages
     */
    public function get_landing_pages($request) {
        $per_page = $request->get_param('per_page') ?: 10;
        $page = $request->get_param('page') ?: 1;

        $query = new WP_Query([
            'post_type' => 'e-landing-page',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'landing-page',
                    'compare' => '=',
                ],
            ],
        ]);

        $data = [];
        foreach ($query->posts as $post) {
            $data[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'date_created' => $post->post_date,
                'date_modified' => $post->post_modified,
            ];
        }

        return rest_ensure_response($data);
    }

    /**
     * Obtiene el detalle de una Landing Page específica
     *
     * @param WP_REST_Request $request Objeto de la petición REST
     * @return WP_REST_Response|WP_Error Respuesta con el detalle de la landing page o error
     */
    public function get_landing_page($request) {
        $id = (int) $request['id'];
        $post = get_post($id);

        if (!$post || 'e-landing-page' !== $post->post_type) {
            return new WP_Error(
                'landing_not_found',
                __('Landing page no encontrada', 'astro-utils'),
                ['status' => 404],
            );
        }

        $response = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'date_created' => $post->post_date,
            'date_modified' => $post->post_modified,
            'elementor_data' => get_post_meta($id, '_elementor_data', true),
            'meta' => $this->get_elementor_library_meta(['id' => $id]),
        ];

        return rest_ensure_response($response);
    }

    /**
     * Método ejecutado durante la activación del plugin
     *
     * Actualiza las reglas de reescritura y registra la versión del plugin
     *
     * @return void
     */
    public function activate() {
        flush_rewrite_rules();
        add_option('ieeg_astro_utils_version', self::VERSION);
        error_log('IEEG-Astro-Utils plugin activado - Versión: ' . self::VERSION);
    }

    /**
     * Método ejecutado durante la desactivación del plugin
     *
     * Actualiza las reglas de reescritura
     *
     * @return void
     */
    public function deactivate() {
        flush_rewrite_rules();
        error_log('IEEG-Astro-Utils plugin desactivado');
    }

    /**
     * Agrega las páginas de administración al menú de WordPress
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Sitio Estático', 'astro-utils'),
            __('Sitio Estático', 'astro-utils'),
            'publish_posts',
            'astro-utils',
            [$this, 'admin_page_webhook'],
            'dashicons-rest-api',
            30,
        );

        add_submenu_page(
            'astro-utils',
            __('Generar Sitio Estático', 'astro-utils'),
            __('Generar Sitio Estático', 'astro-utils'),
            'publish_posts',
            'astro-utils',
            [$this, 'admin_page_webhook'],
        );

        add_submenu_page(
            'astro-utils',
            __('Configuración', 'astro-utils'),
            __('Configuración', 'astro-utils'),
            'manage_options',
            'astro-utils-settings',
            [$this, 'admin_page_settings'],
        );
    }

    /**
     * Registra las configuraciones del plugin
     *
     * @return void
     */
    public function register_settings() {
        register_setting('astro_utils_settings', 'astro_utils_webhook_url');
        register_setting('astro_utils_settings', 'astro_utils_secret_webhook');

        add_settings_section(
            'astro_utils_webhook_section',
            __('Configuración del Generador de Sitio Estático', 'astro-utils'),
            [$this, 'webhook_section_callback'],
            'astro_utils_settings',
        );

        add_settings_field(
            'webhook_url',
            __('URL del Webhook', 'astro-utils'),
            [$this, 'webhook_url_callback'],
            'astro_utils_settings',
            'astro_utils_webhook_section',
        );

        add_settings_field(
            'secret_webhook',
            __('Secret Webhook', 'astro-utils'),
            [$this, 'secret_webhook_callback'],
            'astro_utils_settings',
            'astro_utils_webhook_section',
        );
    }

    /**
     * Callback para la sección de webhook
     *
     * @return void
     */
    public function webhook_section_callback() {
        echo '<p>' . __('Configura la URL y el secret para el webhook.', 'astro-utils') . '</p>';
    }

    /**
     * Callback para el campo URL del webhook
     *
     * @return void
     */
    public function webhook_url_callback() {
        $value = get_option('astro_utils_webhook_url', '');
        echo '<input type="url" name="astro_utils_webhook_url" value="' .
            esc_attr($value) .
            '" class="regular-text" placeholder="https://ejemplo.com/webhook" />';
        echo '<p class="description">' .
            __('URL completa donde se enviará el webhook.', 'astro-utils') .
            '</p>';
    }

    /**
     * Callback para el campo secret del webhook
     *
     * @return void
     */
    public function secret_webhook_callback() {
        $value = get_option('astro_utils_secret_webhook', '');
        echo '<input type="text" name="astro_utils_secret_webhook" value="' .
            esc_attr($value) .
            '" class="regular-text" placeholder="' .
            __('Ingresa el secret', 'astro-utils') .
            '" />';
        echo '<p class="description">' .
            __('Secret utilizado para autenticar el webhook.', 'astro-utils') .
            '</p>';
    }

    /**
     * Página de configuración del plugin
     *
     * @return void
     */
    public function admin_page_settings() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('astro_utils_settings');
                do_settings_sections('astro_utils_settings');
                submit_button(__('Guardar Configuración', 'astro-utils'));?>
            </form>
        </div>
        <?php
    }

    /**
     * Página para generar el sitio estático
     *
     * @return void
     */
    public function admin_page_webhook() {
        $webhook_url = get_option('astro_utils_webhook_url', '');
        $secret_webhook = get_option('astro_utils_secret_webhook', '');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (empty($webhook_url) || empty($secret_webhook)): ?>
                <div class="notice notice-warning">
                    <p><?php _e(
                        'Debes configurar la URL del webhook y el secret antes de poder ejecutarlo.',
                        'astro-utils',
                    ); ?>
                    <a href="<?php echo admin_url(
                        'admin.php?page=astro-utils-settings',
                    ); ?>"><?php _e('Ir a Configuración', 'astro-utils'); ?></a></p>
                </div>
            <?php else: ?>
                <div class="webhook-container" style="text-align: center; margin-top: 50px;">
                    <h2><?php _e('Generar Sitio Estático', 'astro-utils'); ?></h2>
                    <p><?php _e(
                        'Haz clic en el bot{on para generar sitio estático, este proceso puede tardar 5 minutos en completarse.',
                        'astro-utils',
                    ); ?></p>

                    <button id="execute-webhook" class="button button-primary button-hero" style="margin: 20px;">
                        <?php _e('Generar Sitio Estático', 'astro-utils'); ?>
                    </button>

                    <div id="webhook-result" style="margin-top: 20px;"></div>
                </div>

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#execute-webhook').on('click', function() {
                        var button = $(this);
                        var resultDiv = $('#webhook-result');
                        var ajaxCompleted = false;
                        var ajaxResult = '';

                        // Cambiar estado del botón y limpiar resultados
                        button.prop('disabled', true).text('<?php echo esc_js(__('Ejecutando...', 'astro-utils')); ?>');
                        resultDiv.html('');

                        // Ejecutar AJAX
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'execute_webhook',
                                nonce: '<?php echo wp_create_nonce('execute_webhook_nonce'); ?>'
                            },
                            success: function(response) {
                                ajaxCompleted = true;
                                if (response.success) {
                                    ajaxResult = '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
                                } else {
                                    ajaxResult = '<div class="notice notice-error"><p>' + response.data.message + '</p></div>';
                                }
                            },
                            error: function() {
                                ajaxCompleted = true;
                                ajaxResult = '<div class="notice notice-error"><p><?php echo esc_js(__('Error al generar el sitio estático.', 'astro-utils')); ?></p></div>';
                            }
                        });

                        // Mostrar mensaje de espera inmediatamente después de ejecutar AJAX
                        resultDiv.html('<div class="notice notice-info"><p><?php echo esc_js(__('Este proceso puede tardar unos minutos, por favor espere', 'astro-utils')); ?></p></div>');

                        // Timer de 8 minutos (480000 ms) para restaurar el botón
                        setTimeout(function() {
                            button.prop('disabled', false).text('<?php echo esc_js(__('Generar Sitio Estático Ahora', 'astro-utils')); ?>');
                            
                            // Mostrar el resultado del AJAX si ya se completó
                            if (ajaxCompleted && ajaxResult) {
                                resultDiv.html(ajaxResult);
                            } else if (!ajaxCompleted) {
                                // Si el AJAX aún no se completó después de 8 minutos, mostrar mensaje de timeout
                                resultDiv.html('<div class="notice notice-warning"><p><?php echo esc_js(__('El proceso ha tomado más tiempo del esperado. Por favor, verifique el estado manualmente.', 'astro-utils')); ?></p></div>');
                            }
                        }, 480000); // 8 minutos
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Maneja la ejecución del webhook vía AJAX
     *
     * @return void
     */
    public function handle_webhook_execution() {
        // Verificar nonce de seguridad
        if (!wp_verify_nonce($_POST['nonce'], 'execute_webhook_nonce')) {
            wp_die(__('Error de seguridad.', 'astro-utils'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'astro-utils'));
        }

        $webhook_url = get_option('astro_utils_webhook_url', '');
        $secret_webhook = get_option('astro_utils_secret_webhook', '');

        if (empty($webhook_url) || empty($secret_webhook)) {
            wp_send_json_error([
                'message' => __('URL del webhook o secret no configurados.', 'astro-utils'),
            ]);
        }

        // Preparar los datos del webhook
        $webhook_data = [
            'event' => 'publish',
            'timestamp' => current_time('c'), // Formato ISO 8601
        ];

        // Preparar headers
        $headers = [
            'X-Webhook-Secret' => $secret_webhook,
            'Content-Type' => 'application/json',
        ];

        // Ejecutar la petición
        $response = wp_remote_post($webhook_url, [
            'headers' => $headers,
            'body' => json_encode($webhook_data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' =>
                    __('Error al generar sitio estático: ', 'astro-utils') .
                    $response->get_error_message(),
            ]);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code >= 200 && $response_code < 300) {
            wp_send_json_success([
                'message' =>
                    __(
                        'Solicitud de generación de sitio estático generada exitosamente. Código de respuesta: ',
                        'astro-utils',
                    ) . $response_code,
            ]);
        } else {
            wp_send_json_error([
                'message' =>
                    __('Error en al generar sitio estático. Código de respuesta: ', 'astro-utils') .
                    $response_code .
                    '. Respuesta: ' .
                    $response_body,
            ]);
        }
    }
}

/**
 * Función de acceso global al plugin
 *
 * @return IEEG_Astro_Utils Instancia única del plugin
 */
function ieeg_astro_utils() {
    return IEEG_Astro_Utils::get_instance();
}

ieeg_astro_utils();
