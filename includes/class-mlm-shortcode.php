
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

  $adjust_zoom = isset( $options['adjust_zoom'] ) && $options['adjust_zoom'];
  $default_map_zoom = isset( $options['default_map_zoom'] ) ? $options['default_map_zoom'] : 12;

  ob_start();
  echo '<div class="mlm-location-list mlm-block">';
?>
 
<div class="map-wrap">

  <?php if ( $map_visibility != 'hidden' ) : ?>
    <div id="map"></div>
       

      <script>
      const locations = [
      {id:1,title:'Pantheon',lat:41.898610,lng:12.476872,type:'monument',payload:{address:'Piazza della Rotonda, Rome',description:'Ancient Roman temple, now church.',url:'https://en.wikipedia.org/wiki/Pantheon,_Rome'}},
      {id:2,title:'Colosseum',lat:41.890210,lng:12.492231,type:'historic',payload:{address:'Piazza del Colosseo, Rome',description:'Large amphitheatre built in 70–80 CE.',url:'https://en.wikipedia.org/wiki/Colosseum'}},
      {id:3,title:'Villa Borghese',lat:41.914206,lng:12.492231,type:'park',payload:{address:'Viale delle Belle Arti, Rome',description:'Large landscaped garden and galleries.',url:'https://en.wikipedia.org/wiki/Villa_Borghese'}}
      ];

      const ICONS = {
      monument: 'http://localhost/brightwater/wp-content/uploads/2025/07/The-Mason-Towns-Logo-1.png',
      historic: 'https://i.imgur.com/7yqQ1gW.png',
      park: 'https://i.imgur.com/9bKxJfK.png',
      default: 'https://i.imgur.com/4NZ6uLY.png'
      };

      const mapStyles = [
      { featureType: "water", elementType: "geometry.fill", stylers: [{ color: "#0f3443" }] },
      { featureType: "road", elementType: "geometry", stylers: [{ color: "#ffffff" }] },
      { featureType: "road", elementType: "labels.text.fill", stylers: [{ color: "#2c3e50" }] },
      { featureType: "landscape", elementType: "geometry.fill", stylers: [{ color: "#dfe6e9" }] },
      { featureType: "poi.park", elementType: "geometry.fill", stylers: [{ color: "#55efc4" }] }
      ];

      let map;
      let activeInfoWindow = null; // currently open info window

      function initMap() {

      const mapCenter = { 
      lat: <?php echo isset($options['center_lat']) && $options['center_lat'] !== '' ? esc_attr($options['center_lat']) : 0; ?>, 
      lng: <?php echo isset($options['center_lng']) && $options['center_lng'] !== '' ? esc_attr($options['center_lng']) : 0; ?> 
      };

      map = new google.maps.Map(document.getElementById('map'), {
      center: mapCenter,
      zoom: <?php echo $default_map_zoom; ?>,
      styles: mapStyles,
      mapTypeControl: false,
      streetViewControl: true
      });

      const bounds = new google.maps.LatLngBounds();

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

      // Marker icon
      const img = document.createElement('img');
      img.src = this.iconUrl;
      img.className = 'custom-marker-icon';
      img.setAttribute('data-category', this.loc.type);

      // Info window inside marker wrapper
      this.infoDiv = document.createElement('div');
      this.infoDiv.className = 'custom-info-window';
      this.infoDiv.innerHTML = buildInfoWindowHtml(this.loc.title, this.loc.payload);

      // Append icon and info to wrapper
      this.div.appendChild(img);
      this.div.appendChild(this.infoDiv);

      this.getPanes().overlayMouseTarget.appendChild(this.div);

      // Toggle info window
      this.div.addEventListener('click', (e) => {
      e.stopPropagation();
      if (activeInfoWindow && activeInfoWindow !== this.infoDiv) {
      activeInfoWindow.classList.remove('show');
      }
      this.infoDiv.classList.toggle('show');
      activeInfoWindow = this.infoDiv.classList.contains('show') ? this.infoDiv : null;
      });
      }

      draw() {
      const projection = this.getProjection();
      if (!projection) return;

      const pos = projection.fromLatLngToDivPixel(this.position);
      this.div.style.left = pos.x + 'px';
      this.div.style.top = pos.y + 'px';
      }

      onRemove() {
      if (this.div) this.div.remove();
      }
      }

      // Add all markers
      locations.forEach(loc => {
      const iconUrl = ICONS[loc.type] || ICONS.default;
      const marker = new CustomMarker(new google.maps.LatLng(loc.lat, loc.lng), map, iconUrl, loc);
      bounds.extend(marker.position);
      });

      <?php if ( $adjust_zoom ): ?>
      if ( !bounds.isEmpty() ) map.fitBounds(bounds);
      <?php endif; ?>

      // Close info window on map click
      map.addListener('click', () => {
      if (activeInfoWindow) {
      activeInfoWindow.classList.remove('show');
      activeInfoWindow = null;
      }
      });
      }

      // Build HTML for info window
      function buildInfoWindowHtml(title, payload) {
      const urlLink = payload.url ? `<p class="meta"><a href="${escapeHtml(payload.url)}" target="_blank" rel="noopener">View Location</a></p>` : '';
      return `
        <div>
          <h3>${escapeHtml(title)}</h3>
          <div>${escapeHtml(payload.address || '')}</div>
          <p class="meta">${escapeHtml(payload.description || '')}</p>
          ${urlLink}
        </div>
        `;
      }

      // Escape HTML safely
      function escapeHtml(text = '') {
      return String(text)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
      }

      window.initMap = initMap;

      </script>
    </div>
  <?php endif; ?>

  <div class="o-flex <?php echo esc_attr( $view_type ); ?>">
    <?php 
      while ( $query->have_posts() ) {
      $postID = get_the_ID();
      $query->the_post();
      $address = get_post_meta( $postID, '_location_address', true );
      $phone   = get_post_meta( $postID, '_location_phone', true );
      $map_url = get_post_meta( $postID, '_location_map', true );
      $lat = get_post_meta( $postID, '_location_lat', true );
      $long = get_post_meta( $postID, '_location_long', true );
      ?>
      <?php if ( $lat && $long ): ?>
        <div class="o-col c-info" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $long ); ?>">
          <h3 class="c-info--head"><?php the_title(); ?></h3>
          <address class="c-info--address"><?php echo esc_html( $address ); ?></address>
          <?php if ( $phone ): ?>
          <p><strong>Phone:</strong> <?php echo esc_html( $phone ); ?></p>
          <?php endif; ?>
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
    <?php
      wp_reset_postdata();
      return ob_get_clean();
    }
    
} // MLM Shortcode ends

?>