 
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_Shortcode {
    public function __construct() {
        add_shortcode( 'locations', [ $this, 'render_locations' ] );
    }

    public function render_locations() {
        $args = [ 'post_type' => 'location', 'posts_per_page' => -1 ];
        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return '<p>No locations found.</p>';
        }

        $options = get_option( 'location_settings' );
        $show_phone = isset( $options['show_phone'] ) && $options['show_phone'];

        ob_start();
        echo '<div class="mlm-location-list">';
        while ( $query->have_posts() ) {
            $postID = get_the_ID();
            $query->the_post();
            $address = get_post_meta( $postID, '_location_address', true );
            $phone   = get_post_meta( $postID, '_location_phone', true );
            $map_url = get_post_meta( $postID, '_location_map', true );
            $lat = get_post_meta( $postID, '_location_lat', true );
            $long = get_post_meta( $postID, '_location_long', true );
            ?>
            <div class="mlm-location-item">
                <h3><?php the_title(); ?></h3>
                <p><?php echo esc_html( $address ); ?></p>
                <?php if ( $lat && $long ): ?>
                    <p><strong>Latitude:</strong> <?php echo esc_html( $lat ); ?></p>
                    <p><strong>Longitude:</strong> <?php echo esc_html( $long ); ?></p>
                <?php endif; ?>

                <?php if ( $phone && $show_phone ): ?>
                    <p><strong>Phone:</strong> <?php echo esc_html( $phone ); ?></p>
                <?php endif; ?>
                <?php if ( $map_url ): ?>
                    <p><a href="<?php echo esc_url( $map_url ); ?>" target="_blank">View on Map</a></p>
                <?php endif; ?>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }
}

?>
