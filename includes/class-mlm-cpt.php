<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_CPT {
    public function __construct() {
        add_action( 'init', [ $this, 'register_location_cpt' ] );
    }

    public function register_location_cpt() {
        $labels = [
            'name'          => 'Locations',
            'singular_name' => 'Location',
            'add_new'       => 'Add New',
            'edit_item'     => 'Edit Location',
            'view_item'     => 'View Location',
        ];

        $args = [
            'labels'        => $labels,
            'public'        => true,
            'menu_icon'     => 'dashicons-location-alt',
            'supports'      => [ 'title', 'editor', 'thumbnail' ],
            'show_in_rest'  => true,
        ];

        register_post_type( 'location', $args );
    }
} 
?>