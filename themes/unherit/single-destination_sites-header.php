<?php if (isset($guide_term)) { ?>
    <h2 class="pull-left page-title travel-dir-category-title"><?php esc_html_e($guide_term->name); ?></h2>
<?php } ?>

<?php
if(is_object($post)):
    // Ratings Base URL
    $rating_sort_url = get_destination_taxonomy_term_links( $term_id, $dest->post_name, 'travel-dir-category' );
    $rating = get_guide_lists_rating( $post->ID );

    $rate = array();
    foreach($rating['settings'] as $key => $val) {
        if(isset($rating['enabled']['rating_types_'.$key]) && $rating['enabled']['rating_types_'.$key] == 'true') {
            $style = isset($val['style'])? 'style="'.esc_attr($val['style']).'"' : '';

            $rate[$key]['desc'] = '<span class="'. esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'. esc_attr($val['class-menu']) .'"></span>';

            $rate[$key]['asc'] = '<span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span>';
        }
    }


    // Current sorting
    $sort_title_type  = ( isset($_GET['cat']) ) ? esc_attr($_GET['cat']) : '';
    $sort_title_order = ( isset($_GET['order']) ) ? esc_attr($_GET['order']) : 'desc';
    if(count($list) && !isset($rate[$sort_title_type][$sort_title_order])) {
        reset($rate);
        $sort_title_type = key($rate);
        // echo "<script>location.href = '". add_query_arg( array( 'cat' => $cat, 'order' => 'desc' ), $rating_sort_url )."';</script>";
    }
    $sort_title = (isset($rate[$sort_title_type][$sort_title_order])) ? $rate[$sort_title_type][$sort_title_order] : '<div style="width:90px">&nbsp;</div>';

    if (!empty($rate)) :
        // we have ratings applied to these items.
        ?>
        <div class="pull-right navbar-right filter-listing">
            <span><?php _e('Sort by', 'framework') ?> </span>
            <div class="btn-group">
                <button type="button" class="btn btn-default btn-sm"><?php echo $sort_title; // escaped above ?></button>
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu nav-condensed" role="menu">
                    <?php
                    foreach($rate as $key => $val): ?>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg( array( 'cat' => $key, 'order' => 'desc' ), $rating_sort_url )); ?>"><?php echo $val['desc']; // escaped above ?></a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg( array( 'cat' => $key, 'order' => 'asc' ), $rating_sort_url )); ?>"><?php echo $val['asc']; // escaped above ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    endif;
    ?>
<?php endif; ?>