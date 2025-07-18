<?php
/**
 * Este archivo contiene stubs (definiciones de funciones) para WordPress
 * Solo se usa para desarrollo y no afecta el funcionamiento del plugin
 * Ayuda al IDE a reconocer las funciones de WordPress y eliminar errores de diagnóstico
 *
 * @package Astro-Utils-WP
 */

// Evitar ejecución directa
if (!defined('ABSPATH')) {
    return;
}

// Funciones de WordPress
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

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {}
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {}
}

if (!function_exists('register_rest_field')) {
    function register_rest_field($object_type, $attribute, $args = array()) {}
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = array(), $override = false) {}
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {}
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

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($response) {}
}

if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules($hard = true) {}
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {}
}

// Clases de WordPress
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {}
}

if (!class_exists('WP_Error')) {
    class WP_Error {}
}

if (!class_exists('WP_Query')) {
    class WP_Query {}
}