<?php
// Places (child destinations)
// -------------------------------------------------

// The Query
$args = array(
    'post_type' => 'destination',
    'post_parent' => $dest->ID, // $dest_ID,
    'posts_per_page' => isset($settings['number_posts_child'])? $settings['number_posts_child'] : 2,
    'meta_key' => 'destination_order',
    'orderby' => array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ),
);
$places_query = new WP_Query( $args );

if (isset($_GET['category'])) {
    $guide_term = get_term_by( 'slug', $_GET['category'], 'travel-dir-category' );
    $term_id = $guide_term->term_id;
} else {
    $term_id = 0;
}
$list = get_guide_lists_by_category($dest->ID, $term_id, 'Sorted IDs'); // we're only returning a sorted list of IDs
get_template_part( 'templates/parts/destinations-sub-nav.php' );
?>

<!-- Main Section
================================================== -->
<section class="main">
    <div class="container">
        <div class="row">

            <div class="col-md-9 col-sm-12">

                <div class="row">

                    <div class="col-md-9 col-md-push-3 col-sm-8 col-sm-push-4">
                        <div class="clearfix">
                            <?php require_once('single-destination_sites-header.php'); ?>
                        </div>

                        <!-- Destination Guide List -->
                        <section class="guide-list">
                            <?php require_once('single-destination_sites-list.php'); ?>
                        </section> <!-- /.guide-list -->

                    </div><!-- /.page-content -->

                    <div class="col-md-3 col-md-pull-9 col-sm-4 col-sm-pull-8 page-navigation">
                        <?php require_once('single-destination_sites-menu.php'); ?>
                    </div><!-- /.page-navigation -->

                </div>

            </div>

            <div class="col-md-3 col-sm-12">
                <?php get_sidebar(); ?>
            </div><!-- /sidebar -->

        </div><!-- /.row -->
    </div>
</section>
