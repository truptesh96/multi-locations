<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_Metabox {
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_location_metabox' ] );
        add_action( 'save_post', [ $this, 'save_location_meta' ] );
    }

    public function add_location_metabox() {
        add_meta_box(
            'mlm_location_details',
            'Location Details',
            [ $this, 'render_location_metabox' ],
            'location',
            'normal',
            'default'
        );
    }

    public function render_location_metabox( $post ) {
        $address = get_post_meta( $post->ID, '_location_address', true );
        $phone   = get_post_meta( $post->ID, '_location_phone', true );
        $map_url = get_post_meta( $post->ID, '_location_map', true );
        $lat = get_post_meta( $post->ID, '_location_lat', true );
        $long = get_post_meta( $post->ID, '_location_long', true );
        ?>
        
        <div class="mlm-block o-flex cols-2">
        <div class="o-flex cols-2">
            <div class="o-col">
                <label>Latitude:</label>
                <input type="text" name="location_lat" value="<?php echo esc_attr( $lat ); ?>">
            </div>
            <div class="o-col">
                <label>Longitude:</label>
                <input type="text" name="location_long" value="<?php echo esc_attr( $long ); ?>">
            </div>
        </div>

        <div class="o-flex">
            <div class="o-col">
                <label>Google Map URL:</label>
                <input type="url" name="location_map" value="<?php echo esc_attr( $map_url ); ?>">
            </div>
             <div class="o-col">
                <label>Phone:</label>
                <input type="text" name="location_phone" value="<?php echo esc_attr( $phone ); ?>">
            </div>
        </div>
        <div class="o-flex">
            <div class="o-col">
                <label>Address:</label>
                <textarea name="location_address" ><?php echo esc_textarea( $address ); ?></textarea>
            </div>
        </div>
        </div>

        <?php
    }

    public function save_location_meta( $post_id ) {
        if ( array_key_exists( 'location_address', $_POST ) ) {
            update_post_meta( $post_id, '_location_address', sanitize_textarea_field( $_POST['location_address'] ) );
        }
        if ( array_key_exists( 'location_phone', $_POST ) ) {
            update_post_meta( $post_id, '_location_phone', sanitize_text_field( $_POST['location_phone'] ) );
        }
        if ( array_key_exists( 'location_map', $_POST ) ) {
            update_post_meta( $post_id, '_location_map', esc_url_raw( $_POST['location_map'] ) );
        }
        if ( array_key_exists( 'location_lat', $_POST ) ) {
            update_post_meta( $post_id, '_location_lat', sanitize_text_field( $_POST['location_lat'] ) );
        }
        if ( array_key_exists( 'location_long', $_POST ) ) {
            update_post_meta( $post_id, '_location_long', sanitize_text_field( $_POST['location_long'] ) );
        }
    }
}

?>