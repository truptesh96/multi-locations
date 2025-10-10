<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MLM_Settings {
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media_scripts' ] );
    }
    
    public function enqueue_media_scripts($hook) {
        // Only enqueue on our settings page
        if (strpos($hook, 'page_mlm-settings') === false) {
            return;
        }
        
        // Enqueue WordPress media scripts
        wp_enqueue_media();
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
        
        add_settings_section(
            'mlm_location_types_section',
            'Location Types',
            [ $this, 'render_location_types_section_description' ],
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
            'center_lat',
            'Center Latitude',
            [ $this, 'render_center_lat_field' ],
            'mlm-settings',
            'mlm_main_section'
        );

        add_settings_field(
            'center_lng',
            'Center Longitude',
            [ $this, 'render_center_lng_field' ],
            'mlm-settings',
            'mlm_main_section'
        );

        add_settings_field(
            'show_phone',
            'Adjust zoom so it shows maximum locations',
            [ $this, 'render_adjust_zoom_field' ],
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
        $output['adjust_zoom'] = isset( $input['adjust_zoom'] ) ? (bool) $input['adjust_zoom'] : false;
        $output['center_lat'] = isset( $input['center_lat'] ) ? sanitize_text_field( $input['center_lat'] ) : '';
        $output['center_lng'] = isset( $input['center_lng'] ) ? sanitize_text_field( $input['center_lng'] ) : '';
        
        // Location types
        if (isset($input['location_types']) && is_array($input['location_types'])) {
            $output['location_types'] = [];
            foreach ($input['location_types'] as $type_key => $type_data) {
                $sanitized_type = [];
                $sanitized_type['name'] = isset($type_data['name']) ? sanitize_text_field($type_data['name']) : '';
                $sanitized_type['icon'] = isset($type_data['icon']) ? esc_url_raw($type_data['icon']) : '';
                $sanitized_type['color'] = isset($type_data['color']) ? sanitize_hex_color($type_data['color']) : '#000000';
                
                if (!empty($sanitized_type['name'])) {
                    $output['location_types'][sanitize_key($type_key)] = $sanitized_type;
                }
            }
        }
        
        return $output;
    }

    public function render_center_lat_field() {
        $options = get_option( 'location_settings' );
        $center_lat = isset( $options['center_lat'] ) ? $options['center_lat'] : '';
        echo '<input type="text" name="location_settings[center_lat]" value="' . esc_attr( $center_lat ) . '" class="regular-text">';
    }

    public function render_center_lng_field() {
        $options = get_option( 'location_settings' );
        $center_lng = isset( $options['center_lng'] ) ? $options['center_lng'] : '';
        echo '<input type="text" name="location_settings[center_lng]" value="' . esc_attr( $center_lng ) . '" class="regular-text">';
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

    public function render_adjust_zoom_field() {
        $options = get_option( 'location_settings' );
        $checked = isset( $options['adjust_zoom'] ) && $options['adjust_zoom'] ? 'checked' : '';
        echo '<input type="checkbox" name="location_settings[adjust_zoom]" value="1" ' . $checked . '> Yes';
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Location Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'mlm_settings_group' );
                do_settings_sections( 'mlm-settings' );
                $this->render_location_types_ui();
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function render_location_types_section_description() {
        echo '';
    }
    
    public function render_location_types_ui() {
        $options = get_option('location_settings');
        $location_types = isset($options['location_types']) ? $options['location_types'] : [];
        
        // Default types if none exist
        if (empty($location_types)) {
            $location_types = [
                'monument' => ['name' => 'Monument', 'icon' => '', 'color' => '#e74c3c'],
                'historic' => ['name' => 'Historic', 'icon' => '', 'color' => '#3498db'],
                'park' => ['name' => 'Park', 'icon' => '', 'color' => '#2ecc71'],
                'business' => ['name' => 'Business', 'icon' => '', 'color' => '#f39c12'],
                'restaurant' => ['name' => 'Restaurant', 'icon' => '', 'color' => '#9b59b6']
            ];
        }
        
        ?>
        <div id="location-types-table">
        <h2>Location Types/Categories</h2>
        <p>Configure location types with custom icons and colors. These will be used to style locations on the map.</p>
        <table class="form-table">
            <thead>
                <tr>
                    <th>Display Name</th>
                    <th>Type Key</th>
                    <th>Icon URL</th>
                    <th>Color</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($location_types as $type_key => $type_data) : ?>
                <tr>
                    <td>
                        <input type="text" name="location_settings[location_types][<?php echo esc_attr($type_key); ?>][name]" 
                        value="<?php echo esc_attr($type_data['name']); ?>" class="regular-text">
                    </td>
                    <td>
                        <input type="text" value="<?php echo esc_attr($type_key); ?>" readonly class="regular-text">
                    </td>
                    <td>
                        <div class="media-upload-container">
                            <input type="hidden" name="location_settings[location_types][<?php echo esc_attr($type_key); ?>][icon]" 
                                   value="<?php echo esc_url($type_data['icon']); ?>" class="icon-url-input">
                            <div class="icon-preview-container" style="margin-bottom: 5px;">
                                <?php if (!empty($type_data['icon'])) : ?>
                                    <img src="<?php echo esc_url($type_data['icon']); ?>" class="icon-preview" style="max-width: 40px; max-height: 40px;">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload-icon-button">Upload Icon</button>
                            <button type="button" class="button remove-icon-button" <?php echo empty($type_data['icon']) ? 'style="display:none;"' : ''; ?>>Remove</button>
                        </div>
                    </td>
                    <td>
                        <input type="color" name="location_settings[location_types][<?php echo esc_attr($type_key); ?>][color]" 
                               value="<?php echo esc_attr($type_data['color']); ?>">
                    </td>
                    <td>
                        <button type="button" class="button remove-type">Remove</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr id="new-type-row">
                    <td>
                        <input type="text" id="new-type-key" placeholder="Type key (e.g. school)" class="regular-text">
                    </td>
                    <td>
                        <input type="text" id="new-type-name" placeholder="Display name" class="regular-text">
                    </td>
                    <td>
                        <div class="media-upload-container">
                            <input type="hidden" id="new-type-icon" class="icon-url-input">
                            <div class="icon-preview-container" style="margin-bottom: 5px;"></div>
                            <button type="button" class="button upload-icon-button">Upload Icon</button>
                            <button type="button" class="button remove-icon-button" style="display:none;">Remove</button>
                        </div>
                    </td>
                    <td>
                        <input type="color" id="new-type-color" value="#3498db">
                    </td>
                    <td>
                        <button type="button" class="button button-primary" id="add-new-type">Add Type</button>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize media uploader
            var mediaUploader;
            
            // Handle upload icon button click
            $(document).on('click', '.upload-icon-button', function(e) {
                e.preventDefault();
                
                // Store the button that was clicked
                var button = $(this);
                var container = button.closest('.media-upload-container');
                var iconInput = container.find('.icon-url-input');
                var previewContainer = container.find('.icon-preview-container');
                var removeButton = container.find('.remove-icon-button');
                
                // If the media uploader already exists, open it
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                // Create the media uploader
                mediaUploader = wp.media({
                    title: 'Select Icon Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                
                // When an image is selected, run a callback
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    iconInput.val(attachment.url);
                    
                    // Update preview
                    previewContainer.html('<img src="' + attachment.url + '" class="icon-preview" style="max-width: 40px; max-height: 40px;">');
                    
                    // Show remove button
                    removeButton.show();
                });
                
                // Open the uploader dialog
                mediaUploader.open();
            });
            
            // Handle remove icon button click
            $(document).on('click', '.remove-icon-button', function() {
                var container = $(this).closest('.media-upload-container');
                var iconInput = container.find('.icon-url-input');
                var previewContainer = container.find('.icon-preview-container');
                
                // Clear the input and preview
                iconInput.val('');
                previewContainer.empty();
                
                // Hide remove button
                $(this).hide();
            });
            
            // Add new type
            $('#add-new-type').on('click', function() {
                var typeKey = $('#new-type-key').val().trim();
                var typeName = $('#new-type-name').val().trim();
                var typeIcon = $('#new-type-icon').val().trim();
                var typeColor = $('#new-type-color').val();
                
                if (typeKey === '' || typeName === '') {
                    alert('Type key and name are required');
                    return;
                }
                
                // Create icon preview HTML
                var iconPreviewHtml = '';
                if (typeIcon) {
                    iconPreviewHtml = '<img src="' + typeIcon + '" class="icon-preview" style="max-width: 40px; max-height: 40px;">';
                }
                
                // Create new row
                var newRow = `
                <tr>
                    <td>
                        <input type="text" value="${typeKey}" readonly class="regular-text">
                    </td>
                    <td>
                        <input type="text" name="location_settings[location_types][${typeKey}][name]" 
                               value="${typeName}" class="regular-text">
                    </td>
                    <td>
                        <div class="media-upload-container">
                            <input type="hidden" name="location_settings[location_types][${typeKey}][icon]" 
                                   value="${typeIcon}" class="icon-url-input">
                            <div class="icon-preview-container" style="margin-bottom: 5px;">
                                ${iconPreviewHtml}
                            </div>
                            <button type="button" class="button upload-icon-button">Upload Icon</button>
                            <button type="button" class="button remove-icon-button" ${!typeIcon ? 'style="display:none;"' : ''}>Remove</button>
                        </div>
                    </td>
                    <td>
                        <input type="color" name="location_settings[location_types][${typeKey}][color]" 
                               value="${typeColor}">
                    </td>
                    <td>
                        <button type="button" class="button remove-type">Remove</button>
                    </td>
                </tr>
                `;
                
                $(newRow).insertBefore('#new-type-row');
                
                // Clear inputs
                $('#new-type-key').val('');
                $('#new-type-name').val('');
                $('#new-type-icon').val('');
                $('#new-type-row .icon-preview-container').empty();
                $('#new-type-row .remove-icon-button').hide();
            });
            
            // Remove type
            $(document).on('click', '.remove-type', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }
}

?>