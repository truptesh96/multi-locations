<?php
/**
 * Plugin Name: Multiple Location Manager
 * Description: Manage multiple business locations with custom post type, meta fields, shortcode, and settings.
 * Version: 1.0.0
 * Author: Truptesh Patel
 * Author URI: https://github.com/truptesh96/multi-locations
 * License: GPL2
 * Text Domain: multiple-location-manager
 */

$hook_suffix = 'multiple-location-manager';

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'MLM_PATH', plugin_dir_path( __FILE__ ) );
define( 'MLM_URL', plugin_dir_url( __FILE__ ) );

// Autoload includes
require_once MLM_PATH . 'includes/class-mlm-cpt.php';
require_once MLM_PATH . 'includes/class-mlm-metabox.php';
require_once MLM_PATH . 'includes/class-mlm-shortcode.php';
require_once MLM_PATH . 'includes/class-mlm-settings.php';
require_once MLM_PATH . 'includes/class-mlm-options.php';

// Init plugin
function mlm_init_plugin() {
    MLM_Options::register_defaults();
    new MLM_CPT();
    new MLM_Metabox();
    new MLM_Shortcode();
    new MLM_Settings();
}
add_action( 'plugins_loaded', 'mlm_init_plugin' );

// Load assets
function mlm_enqueue_assets() {
    wp_enqueue_style( 'mlm-admin-style', MLM_URL . '/assets/css/mlm-admin.css', [], '1.0.0' );
    
    // Get Google Maps API key from settings
    $options = get_option( 'location_settings' );
    $api_key = isset( $options['google_map_api_key'] ) ? $options['google_map_api_key'] : '';
    
    // Only enqueue Google Maps if we have an API key
    if ( !empty( $api_key ) ) {
        wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&callback=initMap', array(), null, true );
        wp_script_add_data( 'google-maps', 'async', true );
        wp_script_add_data( 'google-maps', 'defer', true );
    }
}
add_action( 'wp_enqueue_scripts', 'mlm_enqueue_assets' );

// Hook into admin enqueue
add_action( 'admin_enqueue_scripts', 'my_plugin_admin_styles' );

function my_plugin_admin_styles( $hook_suffix ) {
    $screen = get_current_screen();
     
    wp_enqueue_style( 'my-plugin-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css', array(), '1.0.0', 'all' );
    

     
}