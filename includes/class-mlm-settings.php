<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_Settings {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_settings_page() {
        add_submenu_page(
            'edit.php?post_type=location',
            'Location Settings',
            'Settings',
            'manage_options',
            'mlm-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'mlm_settings_group', 'location_settings', [
            'sanitize_callback' => [ $this, 'sanitize_settings' ]
        ] );

        add_settings_section(
            'mlm_main_section',
            'General Settings',
            '__return_false',
            'mlm-settings'
        );

        add_settings_field(
            'google_map_api_key',
            'Google Map API Key',
            [ $this, 'render_google_map_api_key_field' ],
            'mlm-settings',
            'mlm_main_section'
        );

        add_settings_field(
            'show_phone',
            'Show Phone in Shortcode',
            [ $this, 'render_show_phone_field' ],
            'mlm-settings',
            'mlm_main_section'
        );

        add_settings_field(
            'default_map_zoom',
            'Default Map Zoom',
            [ $this, 'render_default_map_zoom_field' ],
            'mlm-settings',
            'mlm_main_section'
        );
    }

    public function sanitize_settings( $input ) {
        $output = [];
        $output['show_phone'] = isset( $input['show_phone'] ) ? (bool) $input['show_phone'] : false;
        $output['google_map_api_key'] = isset( $input['google_map_api_key'] ) ? sanitize_text_field( $input['google_map_api_key'] ) : '';
        $output['default_map_zoom'] = isset( $input['default_map_zoom'] ) ? absint( $input['default_map_zoom'] ) : 12;
        return $output;
    }

    public function render_google_map_api_key_field() {
        $options = get_option( 'location_settings' );
        $api_key = isset( $options['google_map_api_key'] ) ? $options['google_map_api_key'] : '';
        echo '<input type="text" name="location_settings[google_map_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
    }

    public function render_default_map_zoom_field() {
        $options = get_option( 'location_settings' );
        $default_map_zoom = isset( $options['default_map_zoom'] ) ? $options['default_map_zoom'] : 12;
        echo '<input type="number" name="location_settings[default_map_zoom]" value="' . esc_attr( $default_map_zoom ) . '" class="small-text">';
    }

    public function render_show_phone_field() {
        $options = get_option( 'location_settings' );
        $checked = isset( $options['show_phone'] ) && $options['show_phone'] ? 'checked' : '';
        echo '<input type="checkbox" name="location_settings[show_phone]" value="1" ' . $checked . '> Yes';
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Location Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'mlm_settings_group' );
                do_settings_sections( 'mlm-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

?>