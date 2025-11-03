<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_CPT {
    public function __construct() {
        add_action( 'init', [ $this, 'register_location_cpt' ] );
        add_action( 'init', [ $this, 'register_location_taxonomies' ] );
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
            'supports'      => [ 'title', 'thumbnail' ],
            'show_in_rest'  => true,
        ];

        register_post_type( 'location', $args );
    }

    public function register_location_taxonomies() {
        $labels = [
            'name'              => 'Location Categories',
            'singular_name'     => 'Location Category',
            'search_items'      => 'Search Location Categories',
            'all_items'         => 'All Location Categories',
            'parent_item'       => 'Parent Category',
            'parent_item_colon' => 'Parent Category:',
            'edit_item'         => 'Edit Location Category',
            'update_item'       => 'Update Location Category',
            'add_new_item'      => 'Add New Location Category',
            'new_item_name'     => 'New Location Category Name',
            'menu_name'         => 'Categories',
        ];

        register_taxonomy( 'location_category', [ 'location' ], [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'location-category' ],
        ] );
    }
} 
?>