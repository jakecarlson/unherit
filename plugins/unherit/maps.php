<?php
if( ! class_exists( 'Destination_Maps' ) ) {
    class Destination_Maps {

        public function __construct( $settings, $dest_id = false ) {

            $this->settings = $settings;
            $this->dest_id = $dest_id;
            $this->init();
        }

        private function init() {

            // Start the map output after WP is fully loaded
            add_action( 'wp', array( $this, 'initialize_map_output' ) );

            // filters
            add_filter( 'destination_id_for_maps', array( $this, 'filter_destination_id_for_maps') );
        }

        // Map content to output
        function initialize_map_output() {

            if ( ! is_admin() ) {

                // Check for a $post_ID
                $this->dest_id = ( ! $this->dest_id ) ? apply_filters( 'destination_id_for_maps', get_queried_object_id() ) : false;

                // Only process content output if there is a map for this ID
                if ( ! empty( $this->dest_id )) {
                    add_action( 'wp_enqueue_scripts', array( $this, 'load_map_scripts' ) ); // process CSS and JS needed for maps
                    add_action( 'wp_footer', array( $this, 'output_infobox' ) ); // Write InforBoxes for map pins to the footer

                    // Initialize output for add-ons
                    do_action( 'initialize_map_output' );
                }
            }

            if ( is_admin() ) {
                // Initialize output for add-ons, admin only
                do_action( 'initialize_map_output_admin' );
            }
        }

        /**
         * Check the current ID to see if it should display a map
         *
         * We're outputting maps to any destination with a map configured, it's sub-sections and individual directory
         * items with map values specified.
         */
        function filter_destination_id_for_maps( $post_id = 0 ) {
            global $post, $paged;

            $mapSourceID = 0;
            $this->pin_directory_item = 0;

            // Check if there is a map
            $mapSourceID = show_destination_map( $post_id );

            // Directory items have extra values
            if ( get_post_type() == 'travel-directory' && is_single() ) {
                $this->pin_directory_item = apply_filters( 'destination_map/pin_directory_item', $post_id ); // the current 'travel-directory' item
                $mapSourceID = get_guide_page_parent( $post_id ); // parent destination, used to source all directory pins
            }

            // Check for a result
            $this->dest_id = ( ! empty( $mapSourceID ) ) ? $mapSourceID : 0;

            return $this->dest_id;
        }

        function output_infobox() {
            if( isset($this->content) ) {
                echo $this->content;
            }
        }

        function create_infobox( $key, $attr ) {
            ob_start(); ?>
            <div class="infobox-wrapper" style="display:none;">
                <div class="infobox-destination">
                    <div  id="infobox-destination[<?php echo $key; ?>]">
                        <?php if( ! empty($attr['image'] ) ): ?>
                            <div class="infobox-destination-image" style="background-image: url( <?php echo esc_url( $attr['image_src'] ); ?> )"><a href="<?php echo esc_url( $attr['link'] ); ?>"><?php echo  $attr['image']; // escaped already ?></a></div>
                        <?php endif; ?>
                        <div class="infobox-destination-title">
                            <a href="<?php echo esc_url($attr['link']); ?>"><?php echo wp_kses_post( $attr['title'] ); ?></a>
                        </div>
                        <?php if( ! empty( $attr['ratings'] ) ): ?>
                            <div class="infobox-destination-ratings">
                                <?php echo $attr['ratings']; ?>
                            </div>
                        <?php endif; ?>
                        <?php if( ! empty( $attr['intro'] ) ): ?>
                            <div class="infobox-destination-text">
                                <p><?php echo wp_kses_post( dest_get_characters( $attr['intro'], 92 ) ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div> <?php

            $output = ob_get_clean();
            $this->content .= $output;
        }


        function load_map_scripts() {

            // JS for Maps
            $api_key = get_options_data( 'options-page', 'google-api-key' );
            $maps_lang = apply_filters( 'goexplore_google_maps_lang', get_option( 'WPLANG' ) );
            if( ! isset( $api_key ) || ( isset( $api_key ) && empty( $api_key ) ) )
                wp_enqueue_script( "google-maps", "https://maps.googleapis.com/maps/api/js?v=3&language=".$maps_lang, array( 'jquery' ) );
            else
                wp_enqueue_script( "google-maps", "https://maps.googleapis.com/maps/api/js?v=3&key=".$api_key."&language=".$maps_lang, array( 'jquery' ) );
            //wp_enqueue_script( "info-box", '//google-maps-utility-library-v3.googlecode.com/svn/trunk/infobox/src/infobox.js', array( 'jquery' ) );
            wp_enqueue_script( "info-box", TRAVEL_PLUGIN_URL . 'assets/js/infobox.js', array( 'jquery' ) );

            // Defaults
            $map_default_style = '[{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"gamma":"2.0"},{"saturation":"0"},{"hue":"#ff0076"},{"lightness":"18"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"visibility":"simplified"}]},{"featureType":"landscape","elementType":"all","stylers":[{"saturation":"-10"},{"lightness":"42"},{"gamma":1},{"hue":"#ffcc00"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"lightness":"50"},{"gamma":"1.50"}]},{"featureType":"landscape.natural.terrain","elementType":"all","stylers":[{"hue":"#14ff00"},{"lightness":"-25"},{"gamma":"1"},{"saturation":"-80"}]},{"featureType":"poi","elementType":"all","stylers":[{"hue":"#9bff00"},{"saturation":"-55"},{"lightness":"60"},{"gamma":"1.90"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"saturation":"-70"},{"lightness":"30"},{"gamma":"2.20"},{"visibility":"simplified"},{"hue":"#2d00ff"}]},{"featureType":"road.highway","elementType":"labels.icon","stylers":[{"lightness":"40"},{"saturation":"-30"},{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"saturation":"-40"},{"lightness":51.19999999999999},{"gamma":1},{"hue":"#ff0300"}]},{"featureType":"road.local","elementType":"all","stylers":[{"hue":"#FF0300"},{"saturation":-100},{"lightness":52},{"gamma":1}]},{"featureType":"water","elementType":"all","stylers":[{"saturation":"-15"},{"lightness":"0"},{"gamma":1},{"visibility":"simplified"},{"hue":"#0095ff"}]}]';
            $map_style            = apply_filters( 'destination_map/map_style', $map_default_style );
            $map_type             = apply_filters( 'destination_map/map_type', 'ROADMAP' );
            $map_path             = apply_filters( 'destination_map/map_path', TRAVEL_PLUGIN_URL.'assets/images' );
            $pin_images           = apply_filters( 'destination_map/pin_images', 'map-pin.png' );
            $pin_current_dest_img = apply_filters( 'destination_map/pin_current_dest_img', $pin_images );
            $info_on_click        = apply_filters( 'destination_map/info_on_click', true );

            // Saved values
            $attrs = ( $this->pin_directory_item ) ? get_directory_gmaps_options( $this->pin_directory_item ) : get_destination_gmaps_options( $this->dest_id );
            $attrs['show_directory_pins'] = isset( $attrs['show_directory_pins'] ) ? $attrs['show_directory_pins'] : '';
            $attrs['show_child_pins'] = isset( $attrs['show_child_pins'] ) ? $attrs['show_child_pins'] : '';
            $attrs['show_current_pin'] = isset( $attrs['show_current_pin']) ? $attrs['show_current_pin'] : '';
            $page_lat              = apply_filters( 'destination_map/page_lat', (float) esc_html( $attrs['latitude'] ) );
            $page_long             = apply_filters( 'destination_map/page_long', (float) esc_html( $attrs['longitude'] ) );
            $page_custom_zoom      = apply_filters( 'destination_map/page_custom_zoom', (int) esc_html( $attrs['zoom'] ) );
            $page_custom_zoom_prop = apply_filters( 'destination_map/page_custom_zoom_prop', 15 );
            $zoom_control 		   = ( isset( $this->settings['zoom_control'] ) && $this->settings['zoom_control'] == 'true' ) ? true : false;
            $zoom_scrollwheel	   = ( isset( $this->settings['zoom_scrollwheel'] ) && $this->settings['zoom_scrollwheel'] == 'true' ) ? true : false;

            if (unherit_post_is_itinerary()) {
                $options = get_destination_options(get_the_ID());
                if (isset($options['google_map'])) {
                    if (isset($options['google_map']['latitude']) && !empty($options['google_map']['latitude'])) {
                        $page_lat = $options['google_map']['latitude'];
                    }
                    if (isset($options['google_map']['longitude']) && !empty($options['google_map']['longitude'])) {
                        $page_long = $options['google_map']['longitude'];
                    }
                    if (isset($options['google_map']['zoom']) && !empty($options['google_map']['zoom'])) {
                        $page_custom_zoom = $options['google_map']['zoom'];
                    }
                }
            }

            $all = array();

            $this->pin_current_destination = 0;
            if( $this->pin_directory_item ) {
                $all[$this->dest_id] = $attrs;
                $this->pin_current_destination = apply_filters( 'destination_map/pin_current_destination', $this->pin_directory_item );
            } else {
                if( isset( $attrs['show_current_pin'] ) && $attrs['show_current_pin'] ) {
                    $all[$this->dest_id] = $attrs;
                    $this->pin_current_destination = apply_filters( 'destination_map/pin_current_destination', $this->dest_id );
                }
            }

            $use_generated_pins = array();
            if( $attrs['show_child_pins'] ) {
                $children = get_children($this->dest_id);
                foreach( $children as $child ) {
                    $all[$child->ID] = get_destination_gmaps_options($child->ID);
                    $all = get_children_destination_gmaps_options($child->ID, $all);
                }
            }

            if( $attrs['show_directory_pins'] ) {
                $all = array_merge($all, unherit_get_map_pins(get_the_ID()));
            }

            $this->content = '';
            foreach( $all as $key => $item ) {
                if( ! empty( $item['latitude'] ) && ! empty( $item['longitude'] ) ) {
                    $this->create_infobox( $key, $item );
                    unset( $item['image'] );
                    $use_generated_pins[$key] = $item;
                }
            }

            $generated_pins =  apply_filters( 'destination_map/generated_pins', $use_generated_pins );

            wp_enqueue_script( 'destination-maps', TRAVEL_PLUGIN_URL.'assets/js/maps.js', array( 'jquery' ), '1.0', true );
            $destination_map_options =
                apply_filters(
                    'destination_map/destination_map_options',
                    array(
                        'general_latitude'     => $page_lat,
                        'general_longitude'    => $page_long,
                        'path'                 => $map_path,
                        'pin_images'           => $pin_images,
                        'pin_directory_item'   => $this->pin_directory_item,
                        'pin_current_dest_img' => $pin_current_dest_img,
                        'pin_current_dest'     => $this->pin_current_destination,
                        'markers'              => json_encode( $generated_pins ),
                        'info_on_click'        => $info_on_click,
                        'page_custom_zoom'     => $page_custom_zoom,
                        'zoom_control'		   => $zoom_control,
                        'zoom_scrollwheel'	   => $zoom_scrollwheel,
                        'type'                 => $map_type,
                        'close_map'            => __( 'close map','destinations' ),
                        'map_style'            => stripslashes( $map_style ),
                    )
                );
            wp_localize_script( 'destination-maps', 'destination_map_options', $destination_map_options );

        }
    }

}