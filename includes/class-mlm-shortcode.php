
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
<style>
.custom-marker-dot {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 5px rgba(0,0,0,0.3);
  border: 2px solid white;
  transition: transform 0.2s ease;
}
.custom-marker-dot:hover {
  transform: scale(1.2);
}
.mlm-marker-wrap {
  position: absolute;
  transform: translate(-50%, -50%);
}
.custom-info-window {
  display: none;
  position: absolute;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border-radius: 4px;
  padding: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  min-width: 200px;
  z-index: 10;
}
.custom-info-window.show {
  display: block;
}
</style>

<?php if ( $map_visibility != 'hidden' ) : ?>
  <div class="map-wrap">
    <div id="map"></div>
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
      $location_type = get_post_meta( $postID, '_location_type', true );
      ?>
      <?php if ( $lat && $long ): ?>
        <div class="o-col c-info" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $long ); ?>" data-type="<?php echo esc_attr( $location_type ); ?>">
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
  if (empty($location_types)) {
    $location_types = [
      'monument' => ['name' => 'Monument', 'icon' => '', 'color' => '#e74c3c'],
      'historic' => ['name' => 'Historic', 'icon' => '', 'color' => '#3498db'],
      'park' => ['name' => 'Park', 'icon' => '', 'color' => '#2ecc71'],
      'business' => ['name' => 'Business', 'icon' => '', 'color' => '#f39c12'],
      'restaurant' => ['name' => 'Restaurant', 'icon' => '', 'color' => '#9b59b6']
    ];
  }
  echo json_encode($location_types);
?>;

// Default icon for fallback
const DEFAULT_ICON = 'https://i.imgur.com/4NZ6uLY.png';

const mapStyles = [
  { featureType: "water", elementType: "geometry.fill", stylers: [{ color: "#0f3443" }] },
  { featureType: "road", elementType: "geometry", stylers: [{ color: "#ffffff" }] },
  { featureType: "road", elementType: "labels.text.fill", stylers: [{ color: "#2c3e50" }] },
  { featureType: "landscape", elementType: "geometry.fill", stylers: [{ color: "#dfe6e9" }] },
  { featureType: "poi.park", elementType: "geometry.fill", stylers: [{ color: "#55efc4" }] }
];

let map;
let activeInfoWindow = null;

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

    // Create marker element
    if (this.iconUrl !== DEFAULT_ICON) {
      // Use custom icon if provided
      const img = document.createElement('img');
      img.src = this.iconUrl;
      img.className = 'custom-marker-icon info-toggle';
      img.setAttribute('data-category', this.loc.type);
      this.div.appendChild(img);
    } else if (this.loc.markerColor) {
      // Use colored dot with type's color
      const dot = document.createElement('div');
      dot.className = 'custom-marker-dot info-toggle';
      dot.style.backgroundColor = this.loc.markerColor;
      dot.setAttribute('data-category', this.loc.type);
      this.div.appendChild(dot);
    } else {
      // Fallback to default icon
      const img = document.createElement('img');
      img.src = this.iconUrl;
      img.className = 'custom-marker-icon info-toggle';
      img.setAttribute('data-category', this.loc.type);
      this.div.appendChild(img);
    }

    // Info window content
    this.infoDiv = document.createElement('div');
    this.infoDiv.className = 'custom-info-window';
    this.infoDiv.innerHTML = buildInfoWindowHtml(this.loc.title, this.loc.payload);

    // Append info window to wrapper
    this.div.appendChild(this.infoDiv);
    this.getPanes().overlayMouseTarget.appendChild(this.div);

    // ✅ Only toggle when clicking .info-toggle element
    this.div.addEventListener('click', (e) => {
      const target = e.target;
      if (target.classList.contains('info-toggle')) {
        e.stopPropagation();

        // Close any other open info window
        if (activeInfoWindow && activeInfoWindow !== this.infoDiv) {
          activeInfoWindow.classList.remove('show');
        }

        // Toggle current one
        this.infoDiv.classList.toggle('show');
        activeInfoWindow = this.infoDiv.classList.contains('show') ? this.infoDiv : null;
      }
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
    // Get icon URL or color from location types
    let iconUrl = DEFAULT_ICON;
    let markerColor = '';
    
    if (locationTypes[loc.type]) {
      // Use custom icon if available
      if (locationTypes[loc.type].icon) {
        iconUrl = locationTypes[loc.type].icon;
      }
      // Store color for custom marker
      markerColor = locationTypes[loc.type].color || '';
    }
    
    const marker = new CustomMarker(
      new google.maps.LatLng(loc.lat, loc.lng), 
      map, 
      iconUrl, 
      {...loc, markerColor}
    );
    bounds.extend(marker.position);
  });

  if (!bounds.isEmpty()) map.fitBounds(bounds);

  map.addListener('click', () => {
    if (activeInfoWindow) {
      activeInfoWindow.classList.remove('show');
      activeInfoWindow = null;
    }
  });
}

function buildInfoWindowHtml(title, payload) {
  const urlLink = payload.url ? `<p class="meta"><a href="${escapeHtml(payload.url)}" target="_blank" rel="noopener">View Location</a></p>` : '';
  return `
    <div>
      <h3>${escapeHtml(title)}</h3>
      <div>${escapeHtml(payload.address || '')}</div>
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
</script>

  <?php endif; ?>
    <?php
      wp_reset_postdata();
      return ob_get_clean();
    }
    
} // MLM Shortcode ends

?>