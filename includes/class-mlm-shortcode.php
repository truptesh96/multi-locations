
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_Shortcode {

public function __construct() {
  add_shortcode( 'locations', [ $this, 'render_locations' ] );
}

public function render_locations($args) {
  $view_type = isset( $args['view_type'] ) ? $args['view_type'] : 'grid cols-3';
  $map_visibility = isset( $args['map_visibility'] ) ? $args['map_visibility'] : 'visible';
   
  $qargs = [ 'post_type' => 'location', 'posts_per_page' => -1 ];
  $query = new WP_Query( $qargs );

  

  if ( ! $query->have_posts() ) {
    return '<p>No locations found.</p>';
  }

  $options = get_option( 'location_settings' );
  $default_map_zoom = isset( $options['default_map_zoom'] ) ? $options['default_map_zoom'] : 12;

  ob_start();
  echo '<div class="mlm-location-list mlm-block">';
?>
 
<?php if ( $map_visibility != 'hidden' ) : ?>
  <div class="map-wrap">
    <div id="map"></div>
  </div>
<?php endif; ?>

<div class="o-flex <?php echo esc_attr( $view_type ); ?>">
    <?php 
      while ( $query->have_posts() ) {
      
      $query->the_post();
      $postID = get_the_ID();
      $address = get_post_meta( $postID, '_location_address', true );
      $phone   = get_post_meta( $postID, '_location_phone', true );
      $map_url = get_post_meta( $postID, '_location_map', true );
      $lat = get_post_meta( $postID, '_location_lat', true );
      $long = get_post_meta( $postID, '_location_long', true );
      $location_type = get_post_meta( $postID, '_location_type', true );
      ?>
       <?php if ( $lat && $long ): ?>
        <div class="o-col c-info" data-lat="<?php echo $lat; ?>" data-lng="<?php echo $long; ?>" data-type="<?php echo $location_type; ?>">
          <h3 class="c-info--head"><?php the_title(); ?></h3>
          <address class="c-info--address"><?php echo esc_html( $address ); ?></address>
          <?php if ( $phone ): ?>
          <p><strong>Phone:</strong> <?php echo esc_html( $phone ); ?></p>
          <?php endif; ?>
          <?php echo $location_type; ?>
          <?php if ( $map_url ): ?>
            <p><a href="<?php echo esc_url( $map_url ); ?>" target="_blank">View on Map</a></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php
      }
      echo '</div>';
    ?>
    </div>

    <?php if ( $map_visibility != 'hidden' ) : ?>
      <script>
        const locdata = Array.from(document.querySelectorAll('.c-info')).map((el, index) => {
        const title = el.querySelector('.c-info--head')?.textContent.trim() || '';
        const address = el.querySelector('.c-info--address')?.textContent.trim() || '';
        const phone = el.querySelector('p strong')?.nextSibling?.textContent.trim() || '';
        const url = el.querySelector('a')?.href || '';
        const lat = parseFloat(el.dataset.lat);
        const lng = parseFloat(el.dataset.lng);
        const id = index + 1;
        const type = el.dataset.type || 'location';
        return { id, title, lat, lng, type, payload: { address, phone, url } };
      });

        const locations = locdata;
        console.log(locations);

        // Get location types from settings
        const locationTypes = <?php 
          $location_types = isset($options['location_types']) ? $options['location_types'] : [];  
          echo json_encode($location_types);
        ?>;

        // Default icon for fallback
        const DEFAULT_ICON = 'https://i.imgur.com/4NZ6uLY.png';

        const mapStyles = [
          { featureType: "poi", elementType: "all", stylers: [{ visibility: "off" }] }, // Hide all POIs
          { featureType: "poi.business", elementType: "all", stylers: [{ visibility: "off" }] }, // Hide businesses
          { featureType: "water", elementType: "geometry.fill", stylers: [{ color: "#fcfcfc" }] },
          { featureType: "road", elementType: "geometry", stylers: [{ color: "#f4f4f4" }] },
          { featureType: "road", elementType: "labels.text.fill", stylers: [{ color: "#f9f9f9" }] },
          { featureType: "landscape", elementType: "geometry.fill", stylers: [{ color: "#f4f4f4" }] },
        ];

        let map;
        let activeInfoWindow = null;

        // Wrap jQuery click handling inside initMap after markers are created
function initMap() {
    const mapCenter = {
        lat: <?php echo isset($options['center_lat']) && $options['center_lat'] !== '' ? esc_attr($options['center_lat']) : 43.559884; ?>,
        lng: <?php echo isset($options['center_lng']) && $options['center_lng'] !== '' ? esc_attr($options['center_lng']) : -79.581974; ?>
    };

    map = new google.maps.Map(document.getElementById('map'), {
        center: mapCenter,
        zoom: <?php echo $default_map_zoom ?: 14; ?>,
        styles: mapStyles,
        mapTypeControl: false,
        streetViewControl: false
    });

    const bounds = new google.maps.LatLngBounds();
    let activeInfoWindow = null;

    class CustomMarker extends google.maps.OverlayView {
        constructor(position, map, iconUrl, loc) {
            super();
            this.position = position;
            this.map = map;
            this.iconUrl = iconUrl;
            this.loc = loc;
            this.div = null;
            this.infoDiv = null;
            this.setMap(map);
        }

        onAdd() {
            this.div = document.createElement('div');
            this.div.className = 'mlm-marker-wrap';
            this.div.setAttribute('data-category', this.loc.type);

            const iconSrc = this.iconUrl || DEFAULT_ICON;
            const color = this.loc.markerColor || '#888';

            const markerDot = document.createElement('div');
            markerDot.className = 'custom-marker-dot';
            markerDot.style.backgroundColor = color;
           

            if (this.iconUrl) {
                const img = document.createElement('img');
                img.src = iconSrc;
                img.className = 'custom-marker-icon info-toggle';
                markerDot.appendChild(img);
            }

            this.div.appendChild(markerDot);

            this.infoDiv = document.createElement('div');
            this.infoDiv.className = 'custom-info-window';
            this.infoDiv.innerHTML = buildInfoWindowHtml(this.loc.title, this.loc.payload);
            this.div.appendChild(this.infoDiv);

            this.getPanes().overlayMouseTarget.appendChild(this.div);

            // jQuery click handler for this marker
            const $marker = jQuery(this.div);
            $marker.off('click').on('click', '.custom-marker-dot, .custom-marker-icon', (e) => {
                e.stopPropagation();

                // Close previously open info window
                if (activeInfoWindow && activeInfoWindow !== this.infoDiv) {
                    jQuery(activeInfoWindow).removeClass('show');
                }

                // Toggle current info window
                jQuery(this.infoDiv).toggleClass('show');
                activeInfoWindow = jQuery(this.infoDiv).hasClass('show') ? this.infoDiv : null;
            });
        }

        draw() {
            const projection = this.getProjection();
            if (!projection) return;
            const pos = projection.fromLatLngToDivPixel(this.position);
            if (this.div) {
                this.div.style.left = pos.x + 'px';
                this.div.style.top = pos.y + 'px';
            }
        }

        onRemove() {
            if (this.div) this.div.remove();
        }
    }

    locations.forEach(loc => {
        let iconUrl = DEFAULT_ICON;
        let markerColor = '';

        if (locationTypes[loc.type]) {
            if (locationTypes[loc.type].icon) iconUrl = locationTypes[loc.type].icon;
            markerColor = locationTypes[loc.type].color || '';
        }

        const marker = new CustomMarker(
            new google.maps.LatLng(loc.lat, loc.lng),
            map,
            iconUrl,
            { ...loc, markerColor }
        );
        bounds.extend(marker.position);
    });

    if (!bounds.isEmpty()) map.fitBounds(bounds);

    // Close info window on map click
    google.maps.event.addListener(map, 'click', () => {
        if (activeInfoWindow) {
            jQuery(activeInfoWindow).removeClass('show');
            activeInfoWindow = null;
        }
    });
}


        function buildInfoWindowHtml(title, payload) {
          const urlLink = payload.url ? `<p class="meta"><a href="${escapeHtml(payload.url)}" target="_blank" rel="noopener">View Location</a></p>` : '';
          return `
            <div>
              <h3>${escapeHtml(title)}</h3>
              <address>${escapeHtml(payload.address || '')}</address>
              ${urlLink}
            </div>
          `;
        }

        function escapeHtml(text = '') {
          return String(text)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
        }

        window.initMap = initMap;

        $('document').on('click','.custom-marker-dot:not(hasClass(.show))', function(){
            
          $(this).addClass('show');

        });

</script>

  <?php endif; ?>
    <?php
      wp_reset_postdata();
      return ob_get_clean();
    }
    
} // MLM Shortcode ends

?>