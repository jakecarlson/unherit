<?php
// Directory (index)
// -------------------------------------------------

if( count($sub_nav_items['directory']) && $places_query->have_posts()): ?>
<section class="narrow directory">

    <!-- Section Title -->
    <div class="title-row">
        <h3 class="title-entry"><?php _e('Categories', 'framework') ?></h3>
    </div>

    <div class="row">
        <?php
        $limit = isset($settings['number_posts_directory'])? $settings['number_posts_directory'] : 6;
        $placeholder = "<img width='960' height='540' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAJCAMAAAAM9FwAAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAAZQTFRF////AAAAVcLTfgAAAAF0Uk5TAEDm2GYAAAAOSURBVHjaYmAYpAAgwAAAmQABh704YAAAAABJRU5ErkJggg=='>";
        foreach($sub_nav_items['directory'] as $directory): ?>
            <div class="col-sm-6 col-lg-4">
                <article class="place-box card">
                    <a href="<?php echo esc_url($directory['link']); ?>" class="place-link">
                        <header>
                            <h3 class="entry-title"><i class="fa fa-folder"></i><?php echo esc_attr($directory['name']); ?></h3>
                        </header>
                        <?php if(isset($directory['image'])): ?>
                            <div class="entry-thumbnail">
                                <?php echo get_the_post_thumbnail($directory['post_ID'], 'place'); ?>
                            </div>
                        <?php else:
                            echo $placeholder;
                        endif;
                        ?>
                    </a>
                </article>
            </div>
            <?php

            $limit--;
            if (!$limit)
                break;
        endforeach; ?>
    </div> <!-- /.row -->
</section>
<?php endif;