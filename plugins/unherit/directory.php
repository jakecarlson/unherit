<?php
if( ! class_exists( 'Travel_Directory_CPT' ) ) {
    class Travel_Directory_CPT {

        public function __construct( $settings ) {

            $this->init();
            $this->settings = $settings;
        }

        private function init() {

            // Actions
            add_action( 'init', array( $this, 'register_post_type' ), 100 );
            add_action( 'init', array( $this, 'register_taxonomies' ), 101 );
            add_action( 'travel-dir-category_add_form_fields', array( $this, 'edit_guide_list_category_form' ) );
            add_action( 'travel-dir-category_edit_form_fields', array( $this, 'edit_extra_fields_for_category'), 10, 2 );
            add_action( 'edited_travel-dir-category', array( $this, 'save_taxonomy_custom_meta'), 10, 2 );
            add_action( 'create_travel-dir-category', array( $this, 'save_taxonomy_custom_meta'), 10, 2 );
            add_action( 'admin_head', array( $this, 'remove_default_categories_fields') );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_function' ) );
            add_action( 'edit_form_after_title', array( $this, 'add_meta_box_after_title' ) );
            add_action( 'save_post', array( $this, 'save_meta_box_data' ) );
            add_action( 'admin_menu', array( $this, 'remove_default_taxonomy_metabox') );
            add_action( 'manage_travel-directory_posts_custom_column', array( $this, 'manage_guide_custom_columns'), 10, 2 );
            add_action( 'restrict_manage_posts', array( $this, 'add_guide_lists_filtering' ) );

            // Filters
            add_filter( 'manage_edit-travel-dir-category_columns', array( $this, 'taxonomy_columns') );
            add_filter( 'manage_travel-dir-category_custom_column', array( $this, 'manage_taxonomy_columns'), 10, 3 );
            add_filter( 'manage_travel-directory_posts_columns', array( $this, 'manage_guide_columns') );
            add_filter( 'parse_query', array( $this, 'change_query_after_filtering' ) );
            add_filter( 'get_terms', array( $this, 'sort_guide_taxonomies' ), 10, 3 );

            // Load resources
            add_action( 'init', array( $this, 'load_scripts_css' ) );
        }

        function init_settings( $settings ) {
            $this->settings = $settings;
        }

        function load_scripts_css() {
            // JS
            if( TRAVEL_PLUGIN_DEBUG )
                wp_enqueue_script( 'details-script', TRAVEL_PLUGIN_URL . 'assets/js/destinations.js', array('jquery'), '', true );
            else
                wp_enqueue_script( 'details-script', TRAVEL_PLUGIN_URL . 'assets/js/destinations.min.js', array('jquery'), '', true );
            // CSS
            wp_enqueue_style( 'details-css', TRAVEL_PLUGIN_URL . 'assets/css/destinations.css' );
            wp_enqueue_style( 'font-awesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', '', '4.7.0' ); // Source: http://cdnjs.com/libraries/font-awesome
        }

        function sort_guide_taxonomies ( $terms, $taxonomies, $args ) {
            if( !is_admin() )
                return $terms;

            global $current_screen;
            if ( ! empty( $current_screen ) && $current_screen->base == 'edit-tags' && $current_screen->parent_base == 'edit' && $current_screen->id == 'edit-travel-dir-category' ) {
                $terms_new = sort_directory_terms( $terms );
                return $terms_new;
            }
            return $terms;
        }

        function change_query_after_filtering( $query ) {
            if ( isset( $query->query['post_type'] ) && $query->query['post_type'] == 'travel-directory' ) {

                if( isset( $_GET['category_id'] ) && ! empty( $_GET['category_id'] ) && $_GET['category_id'] != 0 ) {
                    $query->query_vars['tax_query'] = array(
                        array(
                            'taxonomy' => 'travel-dir-category',
                            'field'    => 'id',
                            'terms' => (int) $_GET['category_id'] // casting (int) will sanatize this $_GET value.
                        )
                    );
                }
                if( isset( $_GET['destination_id'] ) && !empty( $_GET['destination_id'] ) && $_GET['destination_id'] != 0 ) {
                    $query->query_vars['meta_query'] = array(
                        array(
                            'key' 	=> 'destination_parent_id',
                            'value' => (int) $_GET['destination_id']
                        )
                    );
                }
            }
        }

        function add_guide_lists_filtering() {
            global $current_screen;

            if ( $current_screen->post_type == 'travel-directory' ) {

                $args = array(
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                    'hide_empty' => false,
                );
                $terms = get_terms( 'travel-dir-category', $args );
                $categories = array();
                foreach( $terms as $term ) {
                    $categories[] = $term;
                }

                $destinations_all = get_posts(
                    array(
                        'post_type'   => 'destination',
                        'orderby'     => 'title',
                        'order'       => 'ASC',
                        'posts_per_page' => -1
                    )
                );
                $destinations = get_page_hierarchy( $destinations_all );

                $category_selected = isset( $_GET['category_id'] ) ? $_GET['category_id'] : 0;
                echo '<select name="category_id">';
                printf( '<option value="0">' . __( 'All categories', 'destinations' ) . '</option>' );
                if ( !empty( $categories ) ) {
                    foreach ( $categories as $category ) {
                        printf( '<option value="%s"%s>%s</option>', esc_attr( $category->term_id ), selected( $category->term_id, $category_selected, false ), esc_html( $category->name ) );
                    }
                }
                echo '</select>';

                $destination_selected = isset( $_GET['destination_id'] ) ? $_GET['destination_id'] : 0;
                echo '<select name="destination_id">';
                printf( '<option value="0">' . __( 'All destinations', 'destinations' ) . '</option>' );
                if ( ! empty( $destinations ) ) {
                    foreach ( $destinations as $key => $destination ) {
                        $level = count( get_post_ancestors( $key ) );
                        printf( '<option value="%s"%s>%s</option>', esc_attr( $key), selected( $key, $destination_selected, false ), esc_html( str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level ) . get_the_title($key) ) );
                    }
                }
                echo '</select>';
            }
        }

        function manage_guide_columns( $columns ) {
            return array( 'cb' => '<input type="checkbox" />',
                'title' => __( 'Title', 'destinations' ),
                'destination' => __( 'Destination', 'destinations' ),
                'category' => __( 'Category', 'destinations' ),
                'author' => __( 'Author', 'destinations' ),
                'date' => __( 'Date', 'destinations' )
            );
        }

        function manage_guide_custom_columns( $column, $post_id ) {

            switch ( $column ) {

                case 'destination' :
                    $destination_id = get_guide_page_parent( $post_id );
                    echo get_the_title( $destination_id );
                    break;

                case 'category' :
                    $category = get_the_term_list( $post_id, 'travel-dir-category', '',  '', '' );
                    echo $category;
                    break;
            }
        }

        function get_count( $m, $published = false ) {
            $query = array(
                'post_type' => 'master-pages',
                'posts_per_page' => -1,
            );
            if( $published )
                $query['post_status'] = array( 'publish' );

            if( $m != 0 ) {
                $query['m'] = $_GET['m'];
            }
            $pages = new WP_Query( $query ); // all pages

            return $pages->post_count;
        }

        function edit_extra_fields_for_category( $term ) {
            global $current_screen;

            $term_slug              = $term->slug;
            $term_category_text     = get_option( 'term_category_text_' . $term_slug );
            $term_category_textarea = get_option( 'term_category_textarea_' . $term_slug );
            $term_category_select   = get_option( 'term_category_select_' . $term_slug );
            $term_category_radio    = get_option( 'term_category_radio_' . $term_slug );
            $term_data              = get_option( 'taxonomy_'.$term->term_id );
            $term_ratings_data      = $term_data;

            unset( $term_ratings_data['menu_order'] );
            unset( $term_ratings_data['rating_types_0'] );
            unset( $term_ratings_data['rating_types_1'] );

            $rating  = array();
            $ratings = apply_filters( 'rating_settings', $rating );

            unset( $ratings['settings'][0] );
            unset( $ratings['settings'][1] );

            wp_nonce_field( basename( __FILE__ ) , 'travel-dir-category-nonce' );
            ?>

            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="category-text"> <?php _e( 'Ratings Types', 'destinations' ); ?> </label>
                </th>
                <td>
                    <?php
                    if ( isset( $term_ratings_data ) && ! empty( $term_ratings_data ) ) {
                        foreach( $term_ratings_data as $key => $val ) {
                            $idx = str_replace( 'rating_types_', '', $key );
                            $rating[$idx] = ( isset( $term_data[$key] ) && $term_data[$key] == 'true' )? ' checked' : '';
                        }
                    }
                    $this->display_ratings( $rating, true ); ?>
                    <p class="description"><?php _e( 'Select the rating types for items added to this category. When displaying a list of items for this category, rating types can be used to filter results', 'destinations' ); ?></p>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="category-textarea"> <?php _e( 'Menu Order', 'destinations' ); ?> </label>
                </th>
                <td>
                    <input type="text" name="term_meta[menu_order]" id="term_meta[menu_order]" value="<?php echo ( isset( $term_data['menu_order'] ) ? $term_data['menu_order'] : '' ) ?>">
                    <p class="description"><?php _e( 'Sets the display order in menus', 'destinations' ); ?></p>
                </td>
            </tr>

            <?php

            if ( $current_screen->id == 'edit-travel-dir-category' ) {
                ?>
                <script type="text/javascript">
                    jQuery(function($) {
                        $('textarea#description').closest('tr.form-field').remove();
                        $('select#parent').closest('tr.form-field').remove();
                    });
                </script>
                <?php
            }
        }

        function display_ratings( $rating = array(), $need = false ){
            $rating = apply_filters( 'rating_settings', $rating );
            unset( $rating['settings'][0] );
            unset( $rating['settings'][1] );
            ?>
            <div class="rating-block">
                <?php
                foreach( $rating['settings'] as $key => $settings ):
                    $rating_key = ( isset( $rating[$key] ) ) ? $rating[$key] : '';  // to fix Notice: Undefined variable: $rating[$key]
                    ?>
                    <div class="rating-block-next">
                        <div class="rating-block-cb">
                            <div>
                                <input type="checkbox" name="term_meta[rating_types_<?php echo $key; ?>]" id="term_meta[rating_types_star]" value="" <?php echo ( ( $need ) ? $rating_key : '' ) ?>>
                                <input type="hidden" name="term_meta_val[rating_types_<?php echo $key; ?>]" value="1">
                            </div>
                        </div>
                        <?php for( $i = 1; $i <= 5; $i++ ): ?>
                            <div class="rating-block-item"><i class="<?php echo $settings['class']; ?> fa-lg" style="color:<?php echo $settings['color']; ?>"></i></div>
                        <?php endfor; ?>
                    </div><br>
                    <?php
                endforeach; ?>
            </div><br> <?php
        }

        function display_ratings_form( $rating ) {
            global $post;

            if( isset( $rating['rating_types_0'] ) ) {
                $rating['rating_types_star'] = $rating['rating_types_0'];
                unset( $rating['rating_types_0'] );
            }
            if( isset( $rating['rating_types_1'] ) ) {
                $rating['rating_types_usd'] = $rating['rating_types_1'];
                unset( $rating['rating_types_1'] );
            }

            $rating = apply_filters( 'rating_settings', $rating );

            // Get a list of the categories for each rating
            $terms_all = get_terms( 'travel-dir-category', array( 'hide_empty' => false) );
            $term_cat_ratings = array();
            foreach ( $terms_all as $the_term ) {
                $terms_data = get_option( 'taxonomy_'.$the_term->term_id );
                unset( $terms_data['rating_types_0'] );
                unset( $terms_data['rating_types_1'] );
                unset( $terms_data['menu_order'] );

                if ( isset( $terms_data ) && is_array( $terms_data ) ) {
                    foreach( $terms_data as $key => $val ) {

                        if ( $val == 'true' ) {
                            $term_cat_ratings[$key][] = 'travel-dir-'.$the_term->term_id;
                        }
                    }
                }
            }
            ?>

            <h3 style="padding-left:0px;"><?php _e( 'Ratings', 'destinations' ) ?></h3>
            <table >
                <colgroup>
                    <col span="1" style="width: 50%;">
                    <col span="1" style="width: 50%;">
                </colgroup>
                <tbody>
                <?php
                foreach( $rating['settings'] as $key => $val ):
                    if( isset( $term_cat_ratings['rating_types_'.$key] ) ):
                        ?>
                        <tr class="travel-dir-rating <?php echo implode( ' ', $term_cat_ratings['rating_types_'.$key] ); ?>">
                            <td><div class="ratebox" data-id="<?php echo $key; ?>" data-rating=""></div></td>
                            <td>
                                <input name="<?php echo 'rating_types_'.$key; ?>" id="rating-<?php echo $key; ?>" class="rate-manual-input widefat" value="<?php echo isset( $rating['rating_types_'.$key] ) ? $rating['rating_types_'.$key] : ''; ?>" ></input>
                                <input type="hidden" class="rate-class"  value="<?php echo $val['class']; ?>"></input>
                                <input type="hidden" class="rate-color"  value="<?php echo $val['color']; ?>"></input>
                            </td>
                        </tr>
                        <?php
                    endif;
                endforeach; ?>
                <input type="hidden" class="rating-is-front" value="false"></input>
                </tbody>
            </table>
            <?php
        }

        function edit_guide_list_category_form() {

            wp_nonce_field( basename( __FILE__ ) , 'travel-dir-category-nonce' );
            ?>
            <div class="form-field">
                <label for="term_meta[rating_types]"><?php _e( 'Rating Types', 'destinations' ); ?></label> <?php
                $this->display_ratings(); ?>
                <p class="description"><?php _e( 'Select the rating types for items added to this category. When displaying a list of items for this category, rating types can be used to filter results', 'destinations' ); ?></p>
            </div>

            <div class="form-field">
                <label for="term_meta[menu_order]"><?php _e( 'Menu Order', 'destinations' ); ?></label>
                <input type="text" name="term_meta[menu_order]" id="term_meta[menu_order]" value="">
                <p class="description"><?php _e( 'Sets the display order in menus', 'destinations' ); ?></p>
            </div> <?php
        }

        function remove_default_categories_fields() {
            global $current_screen;

            if ( $current_screen->id == 'edit-travel-dir-category' ) {
                ?>
                <script type="text/javascript">
                    jQuery(function($) {
                        $('textarea#tag-description').closest('div.form-field').remove();
                        $('select#parent').closest('div.form-field').remove();
                    });
                </script>
                <?php
            }
        }

        function taxonomy_columns( $columns ) {
            $new_columns = array(
                'cb' => '<input type="checkbox" />',
                'name' => __( 'Name', 'destinations' ),
                'rating_types' => __( 'Rating Types', 'destinations' ),
                'slug' => __( 'Slug', 'destinations' ),
                'order' => __( 'Order', 'destinations' )
            );
            return $new_columns;
        }

        function manage_taxonomy_columns( $out, $column_name, $term_id ) {

            $term_data = get_option( 'taxonomy_'.$term_id );
            $term_rating_data = $term_data;
            unset( $term_rating_data['menu_order'] );

            switch ( $column_name ) {

                case 'rating_types':
                    $rating = array();
                    $setts = apply_filters( 'rating_settings', $rating );
                    unset( $term_rating_data['rating_types_price'] );
                    unset( $setts['settings'][0] );
                    unset( $setts['settings'][1]) ;

                    foreach( $setts['settings'] as $key => $val ) {
                        if( isset( $term_data['rating_types_'.$key] ) )
                            $rating[] = ( $term_data['rating_types_'.$key] == 'true' ) ? '<i class="'.$val['class'].' fa-lg" style="color:'.$val['color'].'"></i>' : '';
                    }
                    $out .= implode( '&nbsp;', $rating );
                    break;

                case 'order':
                    $out .= $term_data['menu_order'];
                    break;
            }
            return $out;
        }

        function save_taxonomy_custom_meta( $term_id ) {
            if (
                isset( $_POST['term_meta'] )
                && isset( $_POST['term_meta_val'] )
                && isset( $_POST['travel-dir-category-nonce'] )
                && wp_verify_nonce( $_POST['travel-dir-category-nonce'], basename( __FILE__ ) )
            ) {
                $t_id = $term_id;
                $term_meta = get_option( "taxonomy_$t_id" );
                $cat_keys = array_keys( $_POST['term_meta'] );

                $term_meta['menu_order'] = $_POST['term_meta']['menu_order'];

                $ratings = $_POST['term_meta'];
                $ratings_val = $_POST['term_meta_val'];
                unset( $ratings['menu_order'] );
                foreach( $ratings_val as $key => $val ) {
                    $term_meta[$key] = isset( $ratings[$key] ) ? 'true' : 'false';
                }

                update_option( "taxonomy_$t_id", $term_meta );
            }
        }

        public function register_post_type() {
            global $wp_rewrite;

            // $slug1 = ( isset($this->settings['destinations_base']) && !empty($this->settings['destinations_base']) )? $this->settings['destinations_base'] . '/' : '';
            $slug2 = ( isset( $this->settings['guide_list_base'] ) && ! empty( $this->settings['directory_item_base'] ) )? $this->settings['directory_item_base'] : 'travel-directory';
            // $rewrite_slug = $slug1 . $slug2;
            $rewrite_slug = $slug2;

            $labels = array(
                'name' 				=> _x( 'Directory', 'post type general name', 'destinations' ),
                'singular_name' 	=> _x( 'Directory', 'post type singular name', 'destinations' ),
                'add_new' 			=> __( 'Add Directory Item', 'destinations' ),
                'add_new_item' 		=> __( 'Add New Directory Item', 'destinations' ),
                'edit_item' 		=> __( 'Edit Directory Item', 'destinations' ),
                'new_item' 			=> __( 'New Directory Item', 'destinations' ),
                'all_items' 		=> __( 'Directory Items', 'destinations' ), // __( 'All Directory Items' ),
                'view_item' 		=> __( 'View Directory Item', 'destinations' ),
                'search_items' 		=> __( 'Search Directory', 'destinations' ),
                'not_found' 		=> __( 'No Directory Items found', 'destinations' ),
                'not_found_in_trash'=> __( 'No Directory Items found in Trash', 'destinations' ),
                'parent_item_colon' => '',
                'name_admin_bar'    => __( 'Directory Item', 'destinations' ),
                'menu_name' 		=> __( 'Directory', 'destinations' )
            );

            $args = array(
                'labels'              => $labels,
                'public'              => true,
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'query_var'           => true,
                'capability_type'     => 'post',
                'hierarchical'        => true,
                'menu_icon'           => 'dashicons-location',
                // 'menu_position'       => null,
                'show_in_menu'        => 'edit.php?post_type=destination', // Include in the Destinations menu
                'rewrite'             => array( 'slug' => $rewrite_slug, 'hierarchical' => false, 'with_front' => true ),
                'supports'            => array( 'title', 'editor', 'thumbnail' )
            );

            register_post_type( 'travel-directory', $args );
            if( defined( 'ICL_LANGUAGE_CODE' ) )
                $wp_rewrite->flush_rules();
            if( isset($this->settings['rewrite_flush_rules']) && $this->settings['rewrite_flush_rules'] ) {
                $wp_rewrite->flush_rules();
                $this->settings['rewrite_flush_rules'] = 0;
                update_option( 'travel_guide_options', json_encode( $this->settings ) );
            }
        }

        public function register_taxonomies() {
            global $wp_rewrite;

            $slug = ( isset( $this->settings['guide_list_base'] ) && ! empty( $this->settings['guide_list_base'] ) )? $this->settings['guide_list_base'] : 'listings';

            // Add new taxonomy, make it hierarchical (like categories)
            $labels = array(
                'name' => __( 'Directory Types', 'destinations' ),
                'singular_name' => __( 'Directory Type', 'destinations' ),
                'search_items' =>  __( 'Search Directory Types', 'destinations' ),
                'popular_items' => __( 'Popular Directory Types', 'destinations' ),
                'all_items' => __( 'All Directory Types', 'destinations' ),
                'edit_item' => __( 'Edit Directory Type', 'destinations' ),
                'update_item' => __( 'Update Directory Type', 'destinations' ),
                'add_new_item' => __( 'Add New Directory Type', 'destinations' ),
                'new_item_name' => __( 'New Name', 'destinations' ),
                'separate_items_with_commas' => __( 'Separate with commas', 'destinations' ),
                'add_or_remove_items' => __( 'Add or remove lists', 'destinations' ),
                'choose_from_most_used' => __( 'Choose from the most frequent Directory Types', 'destinations' ),
            );

            // Lame trick, has to add to 'destination' CPT also, so it will appear in the menu (ugh)
            register_taxonomy( 'travel-dir-category', array( 'travel-directory', 'destination' ),
                array(
                    'hierarchical' => true,
                    'labels' => $labels,
                    'show_ui' => true,
                    'query_var' => true,
                    //'rewrite' => array( 'slug' => 'listings', 'hierarchical' => true, 'with_front'=> true )
                    'rewrite' => array( 'slug' => $slug, 'hierarchical' => true, 'with_front'=> true )
                )
            );

            add_filter( 'get_travel-dir-category', array( $this, 'get_travel_dir_category_term' ), 9999, 2 );
        }

        public function get_travel_dir_category_term( $_term, $taxonomy = null ) {
            if ( ( $_term instanceof WP_Term ) && isset( $_term->description ) ) {
                $_term->description = '';
            }

            return $_term;
        }

        function remove_default_taxonomy_metabox() {
            remove_meta_box( 'travel-dir-categorydiv', 'travel-directory', 'side' ); // replace with custom version!
            remove_meta_box( 'travel-dir-categorydiv', 'destination', 'side' ); // directory categories, not intended for destinations (so removed)
        }

        function add_meta_box_after_title( $post_type ) {
            global $post, $wp_meta_boxes;
            do_meta_boxes( get_current_screen(), 'advanced', $post );
            unset( $wp_meta_boxes[get_post_type($post)]['advanced'] );
        }

        public function add_meta_boxes_function() {

            add_meta_box(
                'guide_lists_intro',
                __( 'Introduction', 'destinations' ),
                array( $this, 'render_meta_box_intro' ),
                'travel-directory',
                'advanced',
                'high'
            );

            add_meta_box(
                'guide_lists_attributes',
                __( 'List Item Attributes', 'destinations' ),
                array( $this, 'render_meta_box_attributes' ),
                'travel-directory',
                'side',
                'low'
            );

            add_meta_box(
                'guide_lists_details',
                __( 'Details', 'destinations' ),
                array( $this, 'render_meta_box_details' ),
                'travel-directory',
                'normal',
                'high'
            );

            add_meta_box(
                'directory_item_map',
                __( 'Map Options', 'destinations' ),
                array( $this, 'render_meta_box_map_options' ),
                'travel-directory',
                'normal',
                'high'
            );

        }

        public function render_meta_box_intro() {
            global $post;

            wp_nonce_field( basename( __FILE__ ) , 'travel-directory-nonce' );

            $intro = get_destination_intro( $post->ID );
            echo '<h3 style="padding-left:0px;">' . __( 'Introduction Text', 'destinations' ) . '</h3>';
            echo '<textarea name="intro" class="settings-textarea widefat" rows=5>' . esc_textarea( $intro ) . '</textarea>';

        }

        public function render_meta_box_attributes() {
            global $post;
            $parent_id = 0;

            if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
                $trid = isset( $_GET['trid'] ) ? $_GET['trid'] : 0;
                if ( $trid ) {                                            // if new translated post
                    $post_default = get_post( $trid );
                    if ( ! empty( $post_default ) ) {
                        $parent_id         = get_guide_page_parent( $post_default->ID );
                        $parent_id         = (int) apply_filters( 'wpml_object_id', $parent_id, 'destination', false, ICL_LANGUAGE_CODE );
                        $category_selected = get_the_terms( $post_default->ID, 'travel-dir-category' );
                        if ( ! empty( $category_selected ) ) {
                            $category_selected_id = $this->get_selected_category_ids($category_selected);
                        }
                        $rating = get_meta_rating( $post_default->ID );
                    }
                } else {
                    $parent_id         = get_guide_page_parent( $post->ID );
                    $category_selected = get_the_terms( $post->ID, 'travel-dir-category' );
                    if ( ! empty( $category_selected ) ) {
                        $category_selected_id = $this->get_selected_category_ids($category_selected);
                    }
                    $rating = get_meta_rating( $post->ID );
                }
            } else {
                $parent_id         = get_guide_page_parent( $post->ID );
                $category_selected = get_the_terms( $post->ID, 'travel-dir-category' );
                if ( ! empty( $category_selected ) ) {
                    $category_selected_id = $this->get_selected_category_ids($category_selected);
                }
                $rating = get_meta_rating( $post->ID );
            }

            // check destination
            if ( isset( $parent_id ) && get_post_status( $parent_id ) == false ) {
                $parent_id = 0;
                if ( isset( $post_default ) && ! empty( $post_default ) ) {
                    set_guide_page_parent( $post_default->ID, 0 );
                } else {
                    set_guide_page_parent( $post->ID, 0 );
                }
            }

            // check directory type
            if ( isset( $category_selected_id ) && ! term_exists( $category_selected_id[0], 'travel-dir-category' ) ) {
                if ( isset( $post_default ) && ! empty( $post_default ) ) {
                    wp_remove_object_terms( $post_default->ID, $category_selected_id, 'travel-dir-category' );
                } else {
                    wp_remove_object_terms( $post->ID, $category_selected_id, 'travel-dir-category' );
                }
            }

            $parents = get_posts(
                array(
                    'post_type'        => 'destination',
                    'orderby'          => 'title',
                    'order'            => 'ASC',
                    'numberposts'      => - 1,
                    'suppress_filters' => defined( 'ICL_LANGUAGE_CODE' ) ? 0 : 1,
                )
            );
            $parents = get_page_hierarchy( $parents );

            $args       = array(
                'orderby'           => 'name',
                'order'             => 'ASC',
                'hide_empty'        => false,
                'exclude'           => array(),
                'exclude_tree'      => array(),
                'include'           => array(),
                'number'            => '',
                'fields'            => 'all',
                'slug'              => '',
                'parent'            => '',
                'hierarchical'      => true,
                'child_of'          => 0,
                'get'               => '',
                'name__like'        => '',
                'description__like' => '',
                'pad_counts'        => false,
                'offset'            => '',
                'search'            => '',
                'cache_domain'      => 'core'
            );
            $terms      = get_terms( 'travel-dir-category', $args );
            $categories = array();
            foreach ( $terms as $term ) {
                $categories[] = $term;
            }

            echo '<h3 style="padding-left:0px;">' . __( 'Parent Destination', 'destinations' ) . '</h3>';
            echo '<select name="destination_parent_id" class="widefat">';
            if ( ! empty( $parents ) ) {
                printf( '<option value="%s">%s</option>', '0', __( '(no parent)' ) );
                foreach ( $parents as $id => $parent ) {
                    $level = count( get_post_ancestors( $id ) );
                    printf( '<option value="%s"%s>%s</option>', esc_attr( $id ), selected( $id, $parent_id, false ), esc_html( str_repeat( '&nbsp;&nbsp;&nbsp;&nbsp;', $level ) . get_the_title( $id ) ) );
                }
            }
            echo '</select>';

            echo '<h3 style="padding-left:0px;">' . __( 'Directory Type', 'destinations' ) . '</h3>';
            echo '<select name="category_id[]" class="widefat" multiple>';
            if ( ! empty( $categories ) ) {
                printf( '<option value="%s">%s</option>', '0', __( '(no type)' ) );
                foreach ( $categories as $category ) {
                    $selected = in_array($category->term_id, $category_selected_id) ? 'selected' : false;
                    printf( '<option value="%s"%s>%s</option>', esc_attr( $category->term_id ), $selected, esc_html( $category->name ) );
                }
            }
            echo '</select>';

            $rating['star']  = ( isset( $rating['rating_types_star'] ) && ! empty( $rating['rating_types_star'] ) ) ? $rating['rating_types_star'] : '';
            $rating['price'] = ( isset( $rating['rating_types_price'] ) && ! empty( $rating['rating_types_price'] ) ) ? $rating['rating_types_price'] : '';
            $this->display_ratings_form( $rating );
        }

        public function render_meta_box_details() {
            global $post;

            if( defined( 'ICL_LANGUAGE_CODE' ) ) {
                $trid = isset( $_GET['trid'] ) ? $_GET['trid'] : 0;
                if( $trid ) { 											// if new translated post
                    $post_default = get_post( $trid );
                    if( ! empty( $post_default ) )
                        $details = get_meta_guide_lists_details( $post_default->ID );
                } else {
                    $details = get_meta_guide_lists_details( $post->ID );
                }
            } else {
                $details = get_meta_guide_lists_details( $post->ID );
            }

            echo '<h3 style="padding-left:0px;">' . __( 'Address', 'destinations' ) . '</h3>';
            echo '<textarea name="address" class="settings-textarea widefat" rows=5>' . ( isset($details['address'] ) ? esc_attr( str_replace( "&lt;br /&gt;", "\r\n", $details['address'] ) ) : '') . '</textarea>';

            $contact_last_number = ( isset( $details['contact_last_number'] ) && ! empty( $details['contact_last_number'] ) ) ? $details['contact_last_number'] : 1;
            echo '<p>';
            echo '<h3 style="padding-left:0px;">' . __( 'Contact Information', 'destinations' ) . '</h3>'; ?>
            <input class="details-contacts-last-number" type="hidden" name="contact_last_number"  value="<?php echo $contact_last_number + 1; ?>"></input>
            <table class="form-table" >
                <colgroup>
                    <col span="1" style="width: 20%;">
                    <col span="1" style="width: 77%;">
                    <col span="1" style="width: 3%;">
                </colgroup>
                <tbody>
                <tr class="details-contacts">
                    <td>
                        <input class="details-contacts-name" type="text" name="contact_name_main" value="<?php echo ( isset( $details['contact_name_main'] ) ? $details['contact_name_main'] : '' ); ?>" style="width:100%"></input>
                    </td>
                    <td>
                        <input class="details-contacts-value" type="text" name="contact_value_main" value="<?php echo ( isset($details['contact_value_main'] ) ? $details['contact_value_main'] : '' ); ?>" style="width:100%"></input>
                    </td>
                    <td>
                        <a href="#" class="travel_remove_custom_contact" style="display:none; background: url( <?php echo admin_url( '/images/xit.gif' ); ?> ) no-repeat;">&times;</a>
                    </td>
                </tr>
                <?php if( isset( $details['contacts'] ) && is_array( $details['contacts'] ) )
                    foreach( $details['contacts'] as $key => $val ): ?>
                        <tr class="details-contacts">
                            <td>
                                <input class="details-contacts-name" type="text" name="contact_name[<?php echo $key; ?>]" value="<?php echo key( $val ); ?>" style="width:100%"></input>
                            </td>
                            <td>
                                <input class="details-contacts-value" type="text" name="contact_value[<?php echo $key; ?>]" value="<?php echo $val[key( $val )]; ?>" style="width:100%"></input>
                            </td>
                            <td>
                                <a href="#" class="remove_custom_contact" style="background: url(<?php echo admin_url( '/images/xit.gif' ); ?>) no-repeat;">&times;</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <tr class="contact-extra-template" style="display:none;">
                    <td>
                        <input class="details-contacts-name" type="text" value="" style="width:100%"></input>
                    </td>
                    <td>
                        <input class="details-contacts-value" type="text" value="" style="width:100%"></input>
                    </td>
                    <td>
                        <a href="#" class="remove_custom_contact" style="background: url(<?php echo admin_url( '/images/xit.gif' ); ?>) no-repeat;">&times;</a>
                    </td>
                </tr>
                <tr class="contact-extra-description">
                    <td>
                        <p class="description"><?php _e( 'Title (optional)', 'destinations' ) ?></p>
                    </td>
                    <td>
                        <p class="description"><?php _e( 'Contact details', 'destinations' ) ?></p>
                    </td>
                    <td>
                    </td>
                </tr>
                <tr class="contact-extra-add">
                    <td>
                        <a class="button-secondary add_custom_contact" style="margin: 6px 0;"><?php _e( 'Add Contact', 'destinations' ); ?></a>
                    </td>
                </tr>
                </tbody>
            </table> <?php

            $other_last_number = ( isset( $details['other_last_number'] ) && ! empty( $details['other_last_number'] ) ) ? $details['other_last_number'] : 1;
            echo '<p>';
            echo '<h3 style="padding-left:0px;">' . __( 'Other Information', 'destinations' ) . '</h3>'; ?>
            <input class="details-other-last-number" type="hidden" name="other_last_number"  value="<?php echo $other_last_number + 1; ?>"></input>
            <table class="form-table" >
                <colgroup>
                    <col span="1" style="width: 20%;">
                    <col span="1" style="width: 77%;">
                    <col span="1" style="width: 3%;">
                </colgroup>
                <tbody>
                <tr class="details-other">
                    <td>
                        <input class="details-other-name" type="text" name="other_name_main" value="<?php echo ( isset( $details['other_name_main'] ) ? $details['other_name_main'] : '' ); ?>" style="width:100%"></input>
                    </td>
                    <td>
                        <input class="details-other-value" type="text" name="other_value_main" value="<?php echo ( isset( $details['other_value_main'] ) ? $details['other_value_main'] : '' ); ?>" style="width:100%"></input>
                    </td>
                    <td>
                        <a href="#" class="travel_remove_custom_contact" style="display:none; background: url(<?php echo admin_url( '/images/xit.gif' ); ?>) no-repeat;">&times;</a>
                    </td>
                </tr>
                <?php if( isset( $details['other'] ) && is_array( $details['other'] ) )
                    foreach( $details['other'] as $key => $val ): ?>
                        <tr class="details-other">
                            <td>
                                <input class="details-other-name" type="text" name="other_name[<?php echo $key; ?>]" value="<?php echo key( $val ); ?>" style="width:100%"></input>
                            </td>
                            <td>
                                <input class="details-other-value" type="text" name="other_value[<?php echo $key; ?>]" value="<?php echo $val[key( $val )]; ?>" style="width:100%"></input>
                            </td>
                            <td>
                                <a href="#" class="remove_custom_other" style="background: url(<?php echo admin_url( '/images/xit.gif' ); ?>) no-repeat;">&times;</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <tr class="other-extra-template" style="display:none;">
                    <td>
                        <input class="details-other-name" type="text" value="" style="width:100%"></input>
                    </td>
                    <td>
                        <input class="details-other-value" type="text" value="" style="width:100%"></input>
                    </td>
                    <td>
                        <a href="#" class="remove_custom_other" style="background: url( <?php echo admin_url('/images/xit.gif' ); ?>) no-repeat;">&times;</a>
                    </td>
                </tr>
                <tr class="other-extra-description">
                    <td>
                        <p class="description"><?php _e( 'Title (optional)', 'destinations' ) ?></p>
                    </td>
                    <td>
                        <p class="description"><?php _e( 'Other details', 'destinations' ) ?></p>
                    </td>
                    <td>
                    </td>
                </tr>
                <tr class="other-extra-add">
                    <td>
                        <a class="button-secondary add_custom_other" style="margin: 6px 0;"><?php _e( 'Add Detail', 'destinations' ); ?></a>
                    </td>
                </tr>
                </tbody>
            </table> <?php
        }

        public function render_meta_box_map_options() {
            global $post;

            if( defined( 'ICL_LANGUAGE_CODE' ) ) {
                $trid = isset( $_GET['trid'] ) ? $_GET['trid'] : 0;
                if( $trid ) { 											// if new translated post
                    $post_default = get_post( $trid );
                    if( ! empty( $post_default ) )
                        $meta = get_post_meta( $post_default->ID, 'guide_lists_details' );
                } else {
                    $meta = get_post_meta( $post->ID, 'guide_lists_details' );
                }
            } else {
                $meta = get_post_meta( $post->ID, 'guide_lists_details' );
            }

            $details    = empty( $meta[0] ) ? '' : json_decode(stripslashes( $meta[0] ), true);
            $google_map = ( isset( $details['google_map'] ) && ! empty( $details['google_map'] ) ) ? $details['google_map'] : array();

            ?>
            <div class="wrap">
                <h3 style="padding-left:0px;"><?php _e( 'Location', 'destinations' ) ?></h3>

                <p style="margin-top:0;">
                    <?php render_geocoding_map_options( $google_map ); ?>
                </p>

                <p>
                    <span style="display:inline-block; width:80px;"><label for="google_map_latitude"><?php _e(' Latitude', 'destinations' ); ?></label></span>
                    <input type="text" name="google_map_latitude" value="<?php echo ( isset( $google_map['latitude'] ) ? $google_map['latitude'] : '' ); ?>" size="30" />
                </p>

                <p>
                    <span style="display:inline-block; width:80px;"><label for="google_map_longitude"><?php _e( 'Longitude', 'destinations' ); ?></label></span>
                    <input type="text" name="google_map_longitude" value="<?php echo ( isset( $google_map['longitude'] ) ? $google_map['longitude'] : '' ); ?>" size="30" />
                </p>

                <p>
                    <span style="display:inline-block; width:80px;"><label for="google_map_zoom"><?php _e( 'Zoom', 'destinations' ); ?></label></span>
                    <select name="google_map_zoom" style="width: 6em;">
                        <?php
                        $map_zoom = ( isset( $google_map['zoom'] ) ) ? (int) $google_map['zoom'] : 11; // default 11
                        for ( $i = 1; $i <= 21; $i++ ) {
                            echo '<option value="' . $i . '" ' . ( $map_zoom == $i ? 'selected = "selected"' : '') . '>' . $i . '</option>';
                        } ?>
                    </select>
                </p>
            </div>
            <?php
        }

        public function save_meta_box_data( $post_id ) {//var_dump($post_id);
            $details = array();

            if( get_post_type( $post_id ) != 'travel-directory' )
                return 0;

            if ( ! isset( $_POST['travel-directory-nonce'] ) || ! wp_verify_nonce( $_POST['travel-directory-nonce'], basename( __FILE__ ) ) ) {
                return $post_id;
            }

            $intro = isset( $_POST['intro'] ) ? wp_kses_post( $_POST['intro'] ) : '';
            update_post_meta( $post_id, 'guide_lists_intro', $intro );

            $details['address'] = isset($_POST['address'])?  esc_textarea( str_replace( "<br /><br />", "<br />", preg_replace( "/\r|\n/", "<br />", $_POST['address'] ) ) ) : '';
            $details['google_map']['address'] = isset( $_POST['google_map_address'] ) ? esc_attr( $_POST['google_map_address'] ) : '';
            $details['google_map']['longitude'] = isset( $_POST['google_map_longitude'] ) ? esc_attr( $_POST['google_map_longitude'] ) : '';
            $details['google_map']['latitude'] = isset( $_POST['google_map_latitude'] ) ? esc_attr( $_POST['google_map_latitude'] ) : '';
            $details['google_map']['zoom'] = isset( $_POST['google_map_zoom'] ) ? esc_attr( $_POST['google_map_zoom'] ) : '';
            $details['contact_name_main'] = isset( $_POST['contact_name_main'] ) ? esc_attr( $_POST['contact_name_main'] ) : '';
            $details['contact_value_main'] = isset( $_POST['contact_value_main'] ) ? esc_attr( $_POST['contact_value_main'] ) : '';

            $contacts_custom_name = isset( $_POST['contact_name'] ) ? $_POST['contact_name'] : array();
            $contacts_custom_value = isset( $_POST['contact_value'] ) ? $_POST['contact_value'] : array();

            $contacts = array();
            foreach( $contacts_custom_name as $key => $val ) {
                $contacts[$key] = array( esc_attr( $val ) => esc_attr( $contacts_custom_value[$key] ) );
            }

            $details['contact_last_number'] = isset( $_POST['contact_last_number'] ) ? $_POST['contact_last_number'] : 1;
            $details['contacts'] = $contacts;
            $details['other_name_main'] = isset( $_POST['other_name_main'] ) ? esc_attr($_POST['other_name_main'] ) : '';
            $details['other_value_main'] = isset( $_POST['other_value_main'] ) ? esc_attr($_POST['other_value_main'] ) : '';

            $other_custom_name = isset( $_POST['other_name'] ) ? $_POST['other_name'] : array();
            $other_custom_value = isset( $_POST['other_value'] ) ? $_POST['other_value'] : array();

            $other = array();
            foreach( $other_custom_name as $key => $val ) {
                $other[$key] = array( esc_attr( $val ) => esc_attr( $other_custom_value[$key] ) );
            }

            $details['other_last_number'] = isset( $_POST['other_last_number'] ) ? $_POST['other_last_number'] : 1;
            $details['other'] = $other;

            if ( defined( 'JSON_UNESCAPED_UNICODE' ) ) {
                update_post_meta( $post_id, 'guide_lists_details', json_encode( $details, JSON_UNESCAPED_UNICODE ) );
            } else {
                update_post_meta( $post_id, 'guide_lists_details', unescaped_json( $details ) );
            }

            $category_id = ( isset( $_POST['category_id'] ) ) ? $_POST['category_id'] : '';
            if ( ! empty( $category_id ) ) {
                wp_set_post_terms( $post_id, $category_id, 'travel-dir-category', false );
            } else {
                wp_delete_object_term_relationships( $post_id, 'travel-dir-category' );
            }

            $parent_id = ( isset( $_POST['destination_parent_id'] ) && ! empty( $_POST['destination_parent_id'] ) ) ? $_POST['destination_parent_id'] : '';
            update_post_meta( $post_id, 'destination_parent_id', $parent_id );

            $rating = array();
            foreach( $_POST as $key => $val ) {
                if( strstr( $key, 'rating_types_' ) !== false ) {
                    $rating[$key] = $val;
                }
            }
            unset( $rating['rating_types_price'] );
            update_post_meta( $post_id, 'guide_list_rating', json_encode( $rating ) );
        }

        private function get_selected_category_ids($categories) {
            $ids = [];
            foreach ($categories as $category) {
                $ids[] = (int) $category->term_id;
            }
            return $ids;
        }

    }

}
