<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_Options {

    private static $option_name = 'location_settings';

    /**
     * Register default options
     */
    public static function register_defaults() {
        $defaults = [
            'show_phone' => true,
            'default_map_zoom' => 12,
        ];

        $options = get_option( self::$option_name );
        if ( ! $options ) {
            add_option( self::$option_name, $defaults );
        }
    }

    /**
     * Get all plugin options
     */
    public static function get_options() {
        return get_option( self::$option_name, [] );
    }

    /**
     * Get a single option
     */
    public static function get( $key, $default = null ) {
        $options = self::get_options();
        return isset( $options[$key] ) ? $options[$key] : $default;
    }

    /**
     * Update a single option
     */
    public static function update( $key, $value ) {
        $options = self::get_options();
        $options[$key] = $value;
        update_option( self::$option_name, $options );
    }

    /**
     * Delete all plugin options
     */
    public static function delete() {
        delete_option( self::$option_name );
    }
}

?>