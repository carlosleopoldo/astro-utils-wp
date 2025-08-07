<?php
/**
 * WordPress Stubs for IDE Support
 * 
 * Este archivo contiene definiciones de funciones y clases de WordPress
 * para ayudar al IDE a reconocerlas y eliminar errores de diagnóstico.
 * 
 * NOTA: Este archivo es solo para propósitos de desarrollo y no afecta
 * el funcionamiento del plugin.
 */

// Prevenir ejecución directa
if (!defined('ABSPATH')) {
    exit;
}

// Funciones principales de WordPress
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {}
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {}
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {}
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('register_rest_field')) {
    function register_rest_field($object_type, $attribute, $args = array()) {}
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = array(), $override = false) {}
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {}
}

if (!function_exists('get_posts')) {
    function get_posts($args = null) {}
}

if (!function_exists('get_post')) {
    function get_post($post = null, $output = OBJECT, $filter = 'raw') {}
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {}
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {}
}

if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules($hard = true) {}
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {}
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {}
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {}
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function) {}
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $function) {}
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {}
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($response) {}
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {}
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = array()) {}
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {}
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {}
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {}
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {}
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {}
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {}
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {}
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability, $object_id = null) {}
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {}
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {}
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '') {}
}

if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = array()) {}
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {}
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array()) {}
}

if (!function_exists('settings_fields')) {
    function settings_fields($option_group) {}
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections($page) {}
}

if (!function_exists('submit_button')) {
    function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) {}
}

if (!function_exists('get_admin_page_title')) {
    function get_admin_page_title() {}
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {}
}

if (!function_exists('esc_html')) {
    function esc_html($text) {}
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {}
}

// Clases principales de WordPress
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {}
}

if (!class_exists('WP_Error')) {
    class WP_Error {}
}

if (!class_exists('WP_Query')) {
    class WP_Query {}
}

// Namespace y clases de Elementor
namespace Elementor {
    if (!class_exists('Plugin')) {
        class Plugin {
            public static $instance;
            
            public static function instance() {
                return self::$instance;
            }
            
            public $documents;
            public $frontend;
            public $db;
        }
    }
    
    if (!class_exists('Core\DocumentTypes\Page')) {
        namespace Core\DocumentTypes {
            class Page {}
        }
    }
    
    if (!class_exists('Core\Files\CSS\Post')) {
        namespace Core\Files\CSS {
            class Post {}
        }
    }
}