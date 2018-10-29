<h2 class="pull-left unherit-sites-list-title">
    <?= __('Sites in ', 'framework') . get_the_title($dest); ?>
    <span><?= unherit_get_pagination_str($posts_query); ?></span>        
</h2>

<?php
if (is_object($post)) {

    // Ratings Base URL
    // $sort_url = get_destination_taxonomy_term_links($term_id, $dest->post_name, 'travel-dir-category');
    $sort_url = $_SERVER['REQUEST_URI'];
    $rating = get_guide_lists_rating($post->ID);

    $sort_types = [
        'title' => [
            'asc' => __('&darr; Title (A-Z)', 'framework'),
            'desc' => __('&uarr; Title (Z-A)', 'framework'),
        ],
    ];

    foreach($rating['settings'] as $key => $val) {
        if (isset($rating['enabled']['rating_types_'.$key]) && $rating['enabled']['rating_types_'.$key] == 'true') {
            $style = isset($val['style'])? 'style="'.esc_attr($val['style']).'"' : '';
            $sort_types[$key]['desc'] = '<span class="'. esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'. esc_attr($val['class-menu']) .'"></span>';
            $sort_types[$key]['asc'] = '<span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span>';
        }
    }

    // Current sorting
    $sort_title_type  = ( isset($_GET['sort']) ) ? esc_attr($_GET['sort']) : 'title';
    $sort_title_order = ( isset($_GET['order']) ) ? esc_attr($_GET['order']) : 'asc';
    if(count($list) && !isset($sort_types[$sort_title_type][$sort_title_order])) {
        reset($sort_types);
        $sort_title_type = key($sort_types);
    }
    $sort_title = (isset($sort_types[$sort_title_type][$sort_title_order])) ? $sort_types[$sort_title_type][$sort_title_order] : '<div style="width:90px">&nbsp;</div>';
    ?>

    <div class="pull-right navbar-right filter-listing unherit-filter-listing">
        <span><?php _e('Sort by', 'framework') ?> </span>
        <div class="btn-group">
            <button type="button" class="btn btn-default btn-sm"><?php echo $sort_title; // escaped above ?></button>
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu nav-condensed" role="menu">
                <?php foreach($sort_types as $type => $sorts) { ?>
                    <?php foreach ($sorts as $order => $display) { ?>
                        <li>
                            <a href="<?= esc_url(remove_query_arg('pagenum', add_query_arg(['sort'=>$type, 'order'=>$order], $sort_url))); ?>"><?= $display; ?></a>
                        </li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </div>
    </div>
<?php } ?>