<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Cleanup plugin options
delete_option( 'location_settings' );

// Optional: delete all locations
$locations = get_posts([ 'post_type' => 'location', 'numberposts' => -1 ]);
foreach ( $locations as $location ) {
    wp_delete_post( $location->ID, true );
} 

?>