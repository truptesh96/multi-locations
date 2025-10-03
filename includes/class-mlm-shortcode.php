 
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

        print_r($options);

        $show_phone = isset( $options['show_phone'] ) && $options['show_phone'];
        $default_map_zoom = isset( $options['default_map_zoom'] ) ? $options['default_map_zoom'] : 12;

        ob_start();
        echo '<div class="mlm-location-list">';
    ?>

<style>

#map { height: 600px; width: 100%; }

</style>
 

<div id="map" style="width:100%; height:500px;"></div>

<style>
/* Marker Styles */
.custom-marker-wrapper {
  cursor: pointer;
  position: absolute;
  transform: translate(-50%, -100%);
  transition: transform 0.3s ease;
  z-index: 2;
}
.custom-marker-wrapper:hover {
  transform: translate(-50%, -100%) scale(1.2);
}
.custom-marker-wrapper img.custom-marker-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

/* Info Window Styles */
.custom-info-window {
  position: absolute;
  pointer-events: none;
  white-space: nowrap;
  background: #fff;
  padding: 10px;
  border-radius: 6px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  opacity: 0;
  transform: translateY(-100px);
  transition: all 0.3s ease;
  z-index: 1;
}
.custom-info-window.show {
  opacity: 1;
  transform: translateY(-10px);
  pointer-events: auto;
}
.custom-info-window h3 {
  margin: 0 0 5px 0;
  font-size: 16px;
}
.custom-info-window .meta {
  font-size: 14px;
  color: #555;
}
.custom-info-window a {
  color: #3498db;
  text-decoration: none;
}
.custom-info-window a:hover {
  text-decoration: underline;
}

[data-category="monument"] { background: red; }
[data-category="historic"] { background: blue; }

</style>

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
  const mapCenter = { lat: 41.9028, lng: 12.4964 };
  map = new google.maps.Map(document.getElementById('map'), {
    center: mapCenter,
    zoom: 13,
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
      // Marker HTML
      this.div = document.createElement('div');
      this.div.className = 'custom-marker-wrapper';
      this.div.innerHTML = `<img src="${this.iconUrl}"  data-category="${this.loc.type}" class="custom-marker-icon"/>`;
      this.getPanes().overlayMouseTarget.appendChild(this.div);

      // Info window HTML
      this.infoDiv = document.createElement('div');
      this.infoDiv.className = 'custom-info-window';
      this.infoDiv.innerHTML = buildInfoWindowHtml(this.loc.title, this.loc.payload);
      this.getPanes().floatPane.appendChild(this.infoDiv);

      // Toggle info window
      this.div.addEventListener('click', (e) => {
        e.stopPropagation(); // prevent map click
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

      this.infoDiv.style.left = pos.x - this.infoDiv.offsetWidth / 2 + 'px';
      this.infoDiv.style.top = pos.y - this.infoDiv.offsetHeight - 40 + 'px';
    }

    onRemove() {
      if (this.div) this.div.remove();
      if (this.infoDiv) this.infoDiv.remove();
    }
  }

  // Add all markers
  locations.forEach(loc => {
    const iconUrl = ICONS[loc.type] || ICONS.default;
    const marker = new CustomMarker(new google.maps.LatLng(loc.lat, loc.lng), map, iconUrl, loc);
    bounds.extend(marker.position);
  });

  if (!bounds.isEmpty()) map.fitBounds(bounds);

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
  const urlLink = payload.url ? `<div class="meta"><a href="${escapeHtml(payload.url)}" target="_blank" rel="noopener">More info</a></div>` : '';
  return `
    <div>
      <h3>${escapeHtml(title)}</h3>
      <div>${escapeHtml(payload.address || '')}</div>
      <div class="meta">${escapeHtml(payload.description || '')}</div>
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